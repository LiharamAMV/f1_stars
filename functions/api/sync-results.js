export async function onRequestPost(context) {
    const { env } = context;

    if (!env.DB) {
        return new Response(JSON.stringify({ success: false, message: "Base de datos D1 no vinculada." }), {
            status: 500,
            headers: { "Content-Type": "application/json" }
        });
    }

    try {
        // Find completed or past pending sessions
        const { results: sessions } = await env.DB.prepare(`
            SELECT * FROM sessions 
            WHERE is_completed = 0 AND session_date <= datetime('now')
            ORDER BY session_date ASC
            LIMIT 5
        `).all();

        if (!sessions || sessions.length === 0) {
            return new Response(JSON.stringify({
                success: true,
                loaded: 0,
                message: "No hay sesiones pendientes de resultados en este momento."
            }), {
                headers: { "Content-Type": "application/json" }
            });
        }

        const typeMap = {
            'race': 'Race',
            'qualy': 'Qualifying',
            'sprintRace': 'Sprint',
            'sprintQualy': 'Sprint Qualifying'
        };

        let loaded = 0;
        const messages = [];

        for (const session of sessions) {
            let sessionKey = session.openf1_session_key;

            if (!sessionKey) {
                const openF1Name = typeMap[session.session_type] || 'Race';
                const lookupUrl = `https://api.openf1.org/v1/sessions?year=${session.season}&session_name=${encodeURIComponent(openF1Name)}`;
                try {
                    const lookupResp = await fetch(lookupUrl, { headers: { "User-Agent": "F1StarsApp/2.0" } });
                    if (lookupResp.ok) {
                        const lookupData = await lookupResp.json();
                        if (Array.isArray(lookupData)) {
                            const targetTs = new Date(session.session_date).getTime();
                            let bestKey = null;
                            let bestDelta = Infinity;

                            for (const item of lookupData) {
                                if (!item.session_key || !item.date_start) continue;
                                const itemTs = new Date(item.date_start).getTime();
                                const delta = Math.abs(itemTs - targetTs);
                                if (delta < bestDelta) {
                                    bestDelta = delta;
                                    bestKey = item.session_key;
                                }
                            }

                            if (bestKey && bestDelta < 86400000) { // Within 24 hours
                                sessionKey = bestKey;
                                await env.DB.prepare("UPDATE sessions SET openf1_session_key = ? WHERE id = ?")
                                    .bind(sessionKey, session.id).run();
                            }
                        }
                    }
                } catch (e) {
                    console.error("Lookup error:", e);
                }
            }

            if (!sessionKey) {
                messages.push(`No se encontró session_key en OpenF1 para ${session.round_name} (${session.session_type_str})`);
                continue;
            }

            // Fetch results for sessionKey
            const resUrl = `https://api.openf1.org/v1/session_result?session_key=${sessionKey}`;
            const resResp = await fetch(resUrl, { headers: { "User-Agent": "F1StarsApp/2.0" } });
            if (!resResp.ok) {
                messages.push(`Error al consultar resultados para ${session.round_name}`);
                continue;
            }

            const data = await resResp.json();
            if (!Array.isArray(data) || data.length === 0) {
                messages.push(`${session.round_name} aún no tiene resultados oficiales en OpenF1.`);
                continue;
            }

            const validRows = data
                .filter(r => r.position > 0 && r.driver_number > 0)
                .sort((a, b) => a.position - b.position);

            if (validRows.length >= 3) {
                // Clear existing results for this session
                await env.DB.prepare("DELETE FROM results WHERE session_id = ?").bind(session.id).run();

                // Insert top positions
                for (const row of validRows.slice(0, 10)) {
                    await env.DB.prepare(
                        "INSERT INTO results (session_id, position, driver_number) VALUES (?, ?, ?)"
                    ).bind(session.id, row.position, row.driver_number).run();
                }

                // Mark session as completed
                await env.DB.prepare("UPDATE sessions SET is_completed = 1 WHERE id = ?").bind(session.id).run();

                // Recalculate points for all bets in this session
                const resultsMap = {};
                validRows.forEach(r => {
                    resultsMap[r.driver_number] = r.position;
                });

                const { results: bets } = await env.DB.prepare("SELECT * FROM bets WHERE session_id = ?").bind(session.id).all();

                for (const bet of bets) {
                    let points = 0;
                    points += getPoints(bet.driver1_number, 1, resultsMap);
                    points += getPoints(bet.driver2_number, 2, resultsMap);
                    points += getPoints(bet.driver3_number, 3, resultsMap);

                    await env.DB.prepare("UPDATE bets SET points = ? WHERE id = ?").bind(points, bet.id).run();
                }

                loaded++;
                messages.push(`✓ Resultados cargados para ${session.round_name} (${session.session_type_str})`);
            }
        }

        return new Response(JSON.stringify({
            success: true,
            loaded: loaded,
            message: loaded > 0 ? `Se cargaron resultados de ${loaded} sesión(es).` : "Resultados al día.",
            details: messages
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

function getPoints(driverNumber, expectedPos, resultsMap) {
    if (!resultsMap[driverNumber]) return 0;
    const actualPos = resultsMap[driverNumber];
    const diff = Math.abs(actualPos - expectedPos);
    if (diff >= 3) return 0;
    return 3 - diff;
}
