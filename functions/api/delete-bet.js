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
        const { bet_id } = body;

        if (!bet_id) {
            return new Response(JSON.stringify({ success: false, message: "ID de apuesta no especificado." }), {
                status: 400,
                headers: { "Content-Type": "application/json" }
            });
        }

        await env.DB.prepare("DELETE FROM bets WHERE id = ?").bind(bet_id).run();

        return new Response(JSON.stringify({ success: true, message: "Apuesta eliminada correctamente." }), {
            headers: { "Content-Type": "application/json" }
        });
    } catch (err) {
        return new Response(JSON.stringify({ success: false, error: err.message }), {
            status: 500,
            headers: { "Content-Type": "application/json" }
        });
    }
}
