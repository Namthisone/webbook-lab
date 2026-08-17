# Security Review Status — WebBook Lab

## Scope

This review covers the PHP application under `src/`, the admin area, the deliberately vulnerable `src/security-lab/` area, and the Attack/Defense container boundary.

## Architecture decision

- `:8081` / `WEBBOOK_SECURITY_MODE=attack`: isolated training target. SQL injection and security-lab demonstrations remain intentionally vulnerable.
- `:8080` / `:8443` / `WEBBOOK_SECURITY_MODE=defense`: hardened application. The Defense image loads `src/security-lab/defense_bootstrap.php` automatically.
- Defense never exposes the deliberately vulnerable `security-lab` routes.

## Changes applied

1. Shared PDO bootstrap uses exceptions, UTF-8, native prepares, and defense-only security headers.
2. Defense runtime now sets HttpOnly/SameSite session cookies.
3. Defense runtime adds security headers and a CSP.
4. Defense runtime protects every `/admin/` endpoint with a server-side `admin` role check.
5. Defense POST requests require a session-bound CSRF token. Existing POST forms receive the token automatically through the defense bootstrap.
6. Defense blocks cross-site requests to legacy state-changing GET endpoints as an additional Fetch Metadata defense.
7. Defense limits request bodies to 10 MB.
8. Defense PHP errors are hidden from clients and logged instead.
9. Defense blocks execution of PHP-family files in `src/uploads`.
10. The cart now uses POST + CSRF for delete/clear operations and keeps existing add-to-cart links functional.
11. The vulnerable `src/security-lab/` routes are restricted to the Attack instance.
12. Attack remains free of ModSecurity and the Defense bootstrap so the lab can demonstrate the before/after difference.

## Areas intentionally left vulnerable

The files in `src/security-lab/` are demonstration targets. They should not be converted into secure production code because the assignment requires an observable vulnerability/defense comparison. They must only be reachable from the isolated Attack instance.

## Remaining manual verification

After pulling the changes, verify:

- Attack login page is reachable on `http://<VM-IP>:8081/dang_nhap.php`.
- Defense HTTP redirects to HTTPS and Defense HTTPS is reachable on `https://<VM-IP>:8443/`.
- Defense `/security-lab/` returns 404.
- Non-admin users receive 403 from `/admin/` on Defense.
- Defense POST forms include `defense_csrf` and reject missing/invalid tokens.
- PHP files uploaded under `/uploads` cannot execute on Defense.
- ModSecurity/CRS logs appear under the Defense Apache log directory.
- Attack and Defense use separate MariaDB services/ports.

## Important

Do not expose the Attack service to the public Internet. It is intentionally vulnerable and exists only for the controlled university lab.
