# Deploying BookbyHost (any hosting)

BookbyHost is two independent parts. Neither depends on a particular hosting company.

| Part | What it is | Runs on |
|---|---|---|
| **Back office + API** (`RentalProperty/`) | PHP 8 + MySQL (PHPJabbers script), `api.php`, `cron.php`, `payments_webhook.php` | Any PHP host: shared hosting, cPanel, a VPS with Apache/nginx + PHP-FPM, a PHP PaaS |
| **Website** (`stayowned/`) | Next.js 16 (marketing pages, sign-up, hosts' booking pages, hosts' own domains) | Any Node.js 20+ server (VPS, Docker, Render, Railway, Fly.io, DigitalOcean App Platform …) or a managed Next.js host (Vercel, Netlify …) |

The website talks to the back office only through `api.php` over HTTPS, so the two can live on different servers.

---

## 1. Back office (PHP)

1. Upload `RentalProperty/` and create the MySQL database (installer, or import + `app/config/updates/*.sql` in date order).
2. `app/config/config.inc.php`:
   - `PJ_INSTALL_URL` — address of the back office, e.g. `https://admin.bookbyhost.com/`
   - `PJ_WEBSITE_URL` — address of the website, e.g. `https://www.bookbyhost.com` (used for hosts' links and CORS)
   - `PJ_DOMAIN_CNAME_TARGET`, `PJ_DOMAIN_A_TARGET` — optional, see "Hosts' own domains" (empty = automatic)
   - `PJ_API_PROXY_SECRET` — optional: add `define("PJ_API_PROXY_SECRET", "long-random-text");` so the website server can pass the visitor's IP
3. `api.php` → `$allowed_origins`: add the website's address if it differs from `PJ_WEBSITE_URL`.
4. `app/web/upload/` must be writable by PHP.
5. Cron (every 5–15 minutes): `php /path/to/RentalProperty/cron.php` (subscriptions, daily exchange rate, iCal sync).
6. Payment gateways call `payments_webhook.php` / `index.php?controller=pjFront&action=pjActionConfirm` — the back office must be reachable over HTTPS from the internet.

## 2. Website (Next.js)

Environment variables (`.env.local` or the hosting's settings — see `.env.example`):

| Variable | Example | Notes |
|---|---|---|
| `NEXT_PUBLIC_SITE_URL` | `https://www.bookbyhost.com` | canonical URLs, sitemap; also a "main host" |
| `NEXT_PUBLIC_API_URL` | `https://admin.bookbyhost.com/api.php` | build-time value: rebuild after changing it |
| `SITE_HOSTS` | `bookbyhost.com,staging.bookbyhost.com,*.onrender.com` | other addresses of the website itself; `*.` = all subdomains (preview deployments) |
| `GEO_COUNTRY_HEADER` | `x-country` | optional; Cloudflare / CloudFront / Vercel / Netlify country headers are read automatically |
| `NEXT_OUTPUT` | `standalone` | optional; self-contained server for Docker / small servers |
| `NEXT_PUBLIC_GOOGLE_CLIENT_ID` | | optional ("Continue with Google") |

Any host name that is not the website itself is treated as a **host's own domain**.

### Option A — Node server (VPS, PaaS)
```bash
npm ci
npm run build
npm start            # listens on :3000 (PORT=xxxx to change)
```
Keep it running with your platform, `pm2 start npm --name bookbyhost -- start`, or a systemd service, and put a
reverse proxy with HTTPS in front (Caddy, nginx, Traefik, a load balancer).

### Option B — Docker (`NEXT_OUTPUT=standalone`)
```dockerfile
FROM node:20-alpine AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
ARG NEXT_PUBLIC_API_URL
ARG NEXT_PUBLIC_SITE_URL
ENV NEXT_OUTPUT=standalone NEXT_PUBLIC_API_URL=$NEXT_PUBLIC_API_URL NEXT_PUBLIC_SITE_URL=$NEXT_PUBLIC_SITE_URL
RUN npm run build

FROM node:20-alpine
WORKDIR /app
ENV NODE_ENV=production PORT=3000 HOSTNAME=0.0.0.0
COPY --from=build /app/.next/standalone ./
COPY --from=build /app/.next/static ./.next/static
COPY --from=build /app/public ./public
EXPOSE 3000
CMD ["node", "server.js"]
```

### Option C — Managed Next.js host
Connect the repository, set the variables above, deploy. Add the platform's preview addresses to `SITE_HOSTS`
(e.g. `*.vercel.app`, `*.netlify.app`).

---

## 3. Hosts' own domains ("Your own domain", Pro / Business)

How it works, on any hosting:

1. The host enters `www.goavilla.com` in **My company → Your own domain**.
2. The box shows one DNS record to add at their domain provider:
   - subdomain (`www.goavilla.com`): **CNAME** `www` → `PJ_DOMAIN_CNAME_TARGET` (default: the host of `PJ_WEBSITE_URL`)
   - bare domain (`goavilla.com`): **A** `@` → `PJ_DOMAIN_A_TARGET` (default: the IP of that host; **ALIAS/ANAME** when none is known)
3. **Check** marks it *Active* when the CNAME matches, the domain resolves to the website's IP, or the website
   answers on the domain (`https://www.goavilla.com/api/site-check` → `{"app":"bookbyhost"}`) — the last one
   covers proxies / CDNs that hide the server's IP.
4. The website's proxy (`src/proxy.ts`) asks `api.php?action=pjActionResolveDomain` which host the domain belongs to
   and shows only that host's pages. A plan without "Custom domain" stops the domain.

The web server in front of the website must accept these domains and give them HTTPS certificates. Pick one:

### Caddy (automatic HTTPS for every host domain — recommended for a VPS)
```caddyfile
{
	on_demand_tls {
		# 200 only for domains of hosts whose plan includes "Custom domain"
		ask https://admin.bookbyhost.com/api.php?action=pjActionDomainAllowed
	}
}

www.bookbyhost.com, bookbyhost.com {
	reverse_proxy 127.0.0.1:3000
}

# hosts' own domains
https:// {
	tls {
		on_demand
	}
	reverse_proxy 127.0.0.1:3000
}
```
Caddy calls `…pjActionDomainAllowed&domain=www.goavilla.com` before issuing a certificate, so nobody can make the
server request certificates for random domains. Set `PJ_DOMAIN_A_TARGET` to the server's IP.

### Cloudflare for SaaS (custom hostnames)
Point `PJ_DOMAIN_CNAME_TARGET` at your fallback origin hostname (e.g. `sites.bookbyhost.com`), add each host's
domain as a custom hostname (dashboard or API); Cloudflare issues the certificates. The site-check makes the
domain *Active* even though Cloudflare hides the server's IP.

### nginx / Traefik / a load balancer
Any setup works as long as requests for the host's domain reach the Next.js server with the original `Host`
header. Certificates: Traefik's on-demand ACME, certbot per domain, or the load balancer's certificate manager
(it can use `pjActionDomainAllowed` as its allow-list).

### Managed Next.js host (Vercel, Netlify …)
Set `PJ_DOMAIN_CNAME_TARGET` / `PJ_DOMAIN_A_TARGET` to the platform's documented DNS targets and add each host's
domain to the project in the platform's dashboard or API.

### Local test
`http://www.goavilla.com.localhost:3000` opens the site as the domain `www.goavilla.com` (`*.localhost` always
points to your own computer).

---

## 4. Checklist after deploying
- [ ] `https://<website>/api/site-check` answers `{"app":"bookbyhost", …}`
- [ ] Sign-up, log in, a property page and a booking work (booking e-mails arrive)
- [ ] Back office → My plan payment opens the gateway (test mode first)
- [ ] `php cron.php` runs from cron without errors
- [ ] A Pro test company's own domain goes from *Pending* to *Active* and opens its page over HTTPS
