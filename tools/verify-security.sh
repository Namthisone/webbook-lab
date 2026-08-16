#!/usr/bin/env bash
set -u
BASE="${BASE:-https://localhost:8443}"
echo '=== HTTPS / SECURITY HEADERS ==='
curl -k -sS -D - -o /dev/null "$BASE/" | grep -Ei 'HTTP/|strict-transport|content-security-policy|x-frame-options|x-content-type-options|referrer-policy|permissions-policy|set-cookie' || true
echo
echo '=== MODSECURITY AUDIT LOG ==='
docker compose exec -T web sh -c 'tail -n 40 /var/log/apache2/modsec_audit.log 2>/dev/null || true'
echo
echo '=== APACHE ERROR / LOGIN FAILURES ==='
docker compose exec -T web sh -c 'grep -R "WEBBOOK-LOGIN-FAIL" /var/log/apache2 2>/dev/null | tail -n 20 || true'
echo
echo '=== FAIL2BAN ==='
docker compose logs --tail=80 fail2ban 2>/dev/null || true
