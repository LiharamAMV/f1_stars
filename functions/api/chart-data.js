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

        // Get all completed sessions ordered chronologically
        const sessionsQuery = `
            SELECT id, country, round_name, session_date 
            FROM sessions 
            WHERE is_completed = 1 
            ORDER BY session_date ASC
        `;
        const { results: sessions } = await env.DB.prepare(sessionsQuery).all();

        // Get unique GPs (country) in chronological order
        const gpsMap = new Map();
        sessions.forEach(s => {
            if (!gpsMap.has(s.country)) {
                gpsMap.set(s.country, {
                    country: s.country,
                    round_name: s.round_name,
                    date: s.session_date
                });
            }
        });

        const labels = Array.from(gpsMap.keys());
        const fullNames = labels.map(c => gpsMap.get(c).round_name || `GP ${c}`);

        // Get participants
        const { results: participants } = await env.DB.prepare("SELECT id, nick FROM participants ORDER BY nick ASC").all();

        // Get all bets with points for completed sessions
        const betsQuery = `
            SELECT b.participant_id, p.nick, s.country, SUM(b.points) as round_points
            FROM bets b
            JOIN participants p ON b.participant_id = p.id
            JOIN sessions s ON b.session_id = s.id
            WHERE s.is_completed = 1 AND b.points IS NOT NULL
            GROUP BY b.participant_id, p.nick, s.country
        `;
        const { results: betPoints } = await env.DB.prepare(betsQuery).all();

        const pointsMap = {};
        participants.forEach(p => {
            pointsMap[p.nick] = {};
        });

        betPoints.forEach(bp => {
            if (pointsMap[bp.nick]) {
                pointsMap[bp.nick][bp.country] = (pointsMap[bp.nick][bp.country] || 0) + bp.round_points;
            }
        });

        const series = participants.map(p => {
            let runningTotal = 0;
            const data = labels.map(gp => {
                runningTotal += (pointsMap[p.nick][gp] || 0);
                return runningTotal;
            });
            return {
                nick: p.nick,
                data: data
            };
        });

        series.sort((a, b) => {
            const lastA = a.data.length ? a.data[a.data.length - 1] : 0;
            const lastB = b.data.length ? b.data[b.data.length - 1] : 0;
            return lastB - lastA;
        });

        return new Response(JSON.stringify({
            success: true,
            data: {
                labels: labels,
                full_names: fullNames,
                series: series
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
