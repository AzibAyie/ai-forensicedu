export default {
  async fetch(request, env) {
    if (request.method !== 'POST') {
      return new Response(JSON.stringify({ error: { message: 'Method not allowed' } }), {
        status: 405,
        headers: { 'content-type': 'application/json' },
      });
    }

    const proxySecret = request.headers.get('X-Proxy-Secret');
    if (!proxySecret || proxySecret !== env.PROXY_SECRET) {
      return new Response(JSON.stringify({ error: { message: 'Unauthorized' } }), {
        status: 401,
        headers: { 'content-type': 'application/json' },
      });
    }

    const body = await request.text();

    const groqResponse = await fetch('https://api.groq.com/openai/v1/chat/completions', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${env.GROQ_API_KEY}`,
        'content-type': 'application/json',
      },
      body,
    });

    const responseBody = await groqResponse.text();

    return new Response(responseBody, {
      status: groqResponse.status,
      headers: { 'content-type': 'application/json' },
    });
  },
};
