export async function onRequestPost(context) {
    const { request, env } = context;

    if (!env.DB) {
        return new Response(JSON.stringify({ success: false, message: "Base de datos D1 no vinculada." }), {
            status: 500,
            headers: { "Content-Type": "application/json" }
        });
    }

    try {
        const body = await request.json();
        const { nick, session_id, driver1_number, driver2_number, driver3_number } = body;

        if (!nick || !session_id || !driver1_number || !driver2_number || !driver3_number) {
            return new Response(JSON.stringify({ success: false, message: "Faltan datos obligatorios." }), {
                status: 400,
                headers: { "Content-Type": "application/json" }
            });
        }

        // Validate duplicates
        if (driver1_number === driver2_number || driver1_number === driver3_number || driver2_number === driver3_number) {
            return new Response(JSON.stringify({ success: false, message: "No puedes seleccionar el mismo piloto dos veces." }), {
                status: 400,
                headers: { "Content-Type": "application/json" }
            });
        }

        // Check participant
        const participant = await env.DB.prepare("SELECT id FROM participants WHERE nick = ?").bind(nick).first();
        if (!participant) {
            return new Response(JSON.stringify({ success: false, message: "Participante no encontrado." }), {
                status: 404,
                headers: { "Content-Type": "application/json" }
            });
        }

        // Check session
        const session = await env.DB.prepare("SELECT * FROM sessions WHERE id = ?").bind(session_id).first();
        if (!session) {
            return new Response(JSON.stringify({ success: false, message: "Sesión no encontrada." }), {
                status: 404,
                headers: { "Content-Type": "application/json" }
            });
        }

        // Check existing bet
        const existingBet = await env.DB.prepare(
            "SELECT id FROM bets WHERE session_id = ? AND participant_id = ?"
        ).bind(session_id, participant.id).first();

        if (existingBet) {
            await env.DB.prepare(`
                UPDATE bets 
                SET driver1_number = ?, driver2_number = ?, driver3_number = ?, created_at = datetime('now')
                WHERE id = ?
            `).bind(driver1_number, driver2_number, driver3_number, existingBet.id).run();
        } else {
            await env.DB.prepare(`
                INSERT INTO bets (session_id, participant_id, driver1_number, driver2_number, driver3_number, created_at)
                VALUES (?, ?, ?, ?, ?, datetime('now'))
            `).bind(session_id, participant.id, driver1_number, driver2_number, driver3_number).run();
        }

        return new Response(JSON.stringify({
            success: true,
            message: existingBet ? "Apuesta actualizada correctamente." : "Apuesta guardada con éxito."
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
