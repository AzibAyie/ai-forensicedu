# Groq proxy Worker

Why this exists: see "AI setup" in [../INSTALLATION.md](../INSTALLATION.md).

## Deploy

```bash
cd cloudflare-worker
npx wrangler login          # one-time browser auth, or set CLOUDFLARE_API_TOKEN
npx wrangler deploy
npx wrangler secret put GROQ_API_KEY    # paste your real Groq key when prompted
npx wrangler secret put PROXY_SECRET    # paste a random shared secret when prompted
```

The deploy output prints the Worker's URL — put that in `GROQ_PROXY_URL`,
and the same value you set for `PROXY_SECRET` in `GROQ_PROXY_SECRET`, in
this app's `.env` (and Render's environment variables for production).

## Rotate the shared secret

```bash
openssl rand -hex 32
npx wrangler secret put PROXY_SECRET
```

Then update `GROQ_PROXY_SECRET` everywhere the app runs.
