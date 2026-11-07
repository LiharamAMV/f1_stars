import { onRequestGet as standingsGet } from './functions/api/standings.js';
import { onRequestGet as chartDataGet } from './functions/api/chart-data.js';
import { onRequestGet as sessionsGet } from './functions/api/sessions.js';
import { onRequestGet as driversGet } from './functions/api/drivers.js';
import { onRequestPost as submitBetPost } from './functions/api/submit-bet.js';
import { onRequestPost as deleteBetPost } from './functions/api/delete-bet.js';
import { onRequestPost as syncResultsPost } from './functions/api/sync-results.js';
import { onRequest as initHandler } from './functions/api/init.js';

export default {
    async fetch(request, env, ctx) {
        const url = new URL(request.url);
        const path = url.pathname;
        const method = request.method;

        // Route API endpoints
        if (path.startsWith('/api/')) {
            const context = { request, env, params: {}, waitUntil: (p) => ctx.waitUntil(p) };
            try {
                if (path === '/api/standings' && method === 'GET') {
                    return await standingsGet(context);
                }
                if (path === '/api/chart-data' && method === 'GET') {
                    return await chartDataGet(context);
                }
                if (path === '/api/sessions' && method === 'GET') {
                    return await sessionsGet(context);
                }
                if (path === '/api/drivers' && method === 'GET') {
                    return await driversGet(context);
                }
                if (path === '/api/submit-bet' && method === 'POST') {
                    return await submitBetPost(context);
                }
                if (path === '/api/delete-bet' && method === 'POST') {
                    return await deleteBetPost(context);
                }
                if (path === '/api/sync-results' && method === 'POST') {
                    return await syncResultsPost(context);
                }
                if (path === '/api/init') {
                    return await initHandler(context);
                }

                return new Response(JSON.stringify({ error: 'Endpoint not found' }), {
                    status: 404,
                    headers: { 'Content-Type': 'application/json' }
                });
            } catch (err) {
                return new Response(JSON.stringify({ error: err.message }), {
                    status: 500,
                    headers: { 'Content-Type': 'application/json' }
                });
            }
        }

        // Serve Static Assets via env.ASSETS
        let response = await env.ASSETS.fetch(request);

        // Fallback for clean URLs without trailing slash: e.g. /apostar -> /apostar/index.html
        if (response.status === 404 && !path.includes('.')) {
            const cleanPath = path.endsWith('/') ? `${path}index.html` : `${path}/index.html`;
            const altUrl = new URL(cleanPath, request.url);
            const altResp = await env.ASSETS.fetch(altUrl);
            if (altResp.status === 200) {
                return altResp;
            }
        }

        return response;
    }
};
