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
        const { results } = await env.DB.prepare("SELECT * FROM drivers ORDER BY full_name ASC").all();
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
