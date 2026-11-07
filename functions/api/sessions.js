import { ensureDatabase } from './_init_sql.js';

export async function onRequestGet(context) {
    const { env } = context;

    if (!env.DB) {
        return new Response(JSON.stringify({ success: false, message: "Base de datos D1 no vinculada." }), {
            status: 500,
            headers: { "Content-Type": "application/json" }
        });
    }

    try {
        await ensureDatabase(env.DB);

        // Fetch participants
        const { results: participants } = await env.DB.prepare("SELECT * FROM participants ORDER BY nick ASC").all();

        // Fetch drivers
        const { results: drivers } = await env.DB.prepare("SELECT * FROM drivers ORDER BY full_name ASC").all();

        // Fetch sessions
        const { results: sessions } = await env.DB.prepare("SELECT * FROM sessions ORDER BY session_date ASC").all();

        // Fetch official results
        const { results: resultsRows } = await env.DB.prepare(`
            SELECT r.*, d.short_name, d.team_colour, d.headshot_url 
            FROM results r
            JOIN drivers d ON r.driver_number = d.driver_number
            ORDER BY r.session_id ASC, r.position ASC
        `).all();

        // Fetch bets
        const { results: bets } = await env.DB.prepare(`
            SELECT b.*, p.nick AS participant_nick 
            FROM bets b
            JOIN participants p ON b.participant_id = p.id
            ORDER BY b.session_id ASC, b.id ASC
        `).all();

        // Group results by session
        const resultsBySession = {};
        resultsRows.forEach(r => {
            if (!resultsBySession[r.session_id]) resultsBySession[r.session_id] = [];
            resultsBySession[r.session_id].push(r.short_name);
        });

        const mappedSessions = sessions.map(s => ({
            ...s,
            official_results: resultsBySession[s.id] || []
        }));

        return new Response(JSON.stringify({
            success: true,
            data: {
                participants: participants,
                drivers: drivers,
                sessions: mappedSessions,
                bets: bets
            }
        }), {
            headers: { "Content-Type": "application/json" }
        });
    } catch (err) {
        return new Response(JSON.stringify({ success: false, error: err.message }), {
            status: 500,
            headers: { "Content-Type": "application/json" }
        });
    }
}
