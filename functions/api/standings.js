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
        const query = `
            SELECT 
                p.id, 
                p.nick, 
                COALESCE(SUM(b.points), 0) AS points, 
                COUNT(b.id) AS bets 
            FROM participants p 
            LEFT JOIN bets b ON p.id = b.participant_id 
            GROUP BY p.id, p.nick 
            ORDER BY points DESC, nick ASC
        `;
        const { results } = await env.DB.prepare(query).all();

        return new Response(JSON.stringify({ success: true, data: results }), {
            headers: { "Content-Type": "application/json" }
        });
    } catch (err) {
        return new Response(JSON.stringify({ success: false, error: err.message }), {
            status: 500,
            headers: { "Content-Type": "application/json" }
        });
    }
}
