#!/usr/bin/env bash
set -e
MODE="${1:-status}"
case "$MODE" in
  off|detection)
    docker compose exec web sh -c "sed -i 's/^SecRuleEngine .*/SecRuleEngine DetectionOnly/' /etc/modsecurity/modsecurity.conf && apachectl -k graceful"
    echo 'ModSecurity: DetectionOnly (for BEFORE-hardening lab testing)'
    ;;
  on|blocking)
    docker compose exec web sh -c "sed -i 's/^SecRuleEngine .*/SecRuleEngine On/' /etc/modsecurity/modsecurity.conf && apachectl -k graceful"
    echo 'ModSecurity: On / blocking (for AFTER-hardening verification)'
    ;;
  status)
    docker compose exec web sh -c "grep -E '^SecRuleEngine' /etc/modsecurity/modsecurity.conf"
    ;;
  *) echo 'Usage: ./tools/waf-mode.sh {off|on|status}'; exit 1;;
esac
