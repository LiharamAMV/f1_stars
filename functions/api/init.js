import { ensureDatabase, SEED_SQL } from './_init_sql.js';

export async function onRequest(context) {
    const { env } = context;

    if (!env.DB) {
        return new Response(JSON.stringify({ success: false, message: "Base de datos D1 no vinculada." }), {
            status: 500,
            headers: { "Content-Type": "application/json" }
        });
    }

    try {
        await env.DB.exec(SEED_SQL);
        return new Response(JSON.stringify({
            success: true,
            message: "✓ Base de datos D1 inicializada con éxito con todos los datos y participantes de 2026."
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
