#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

echo "Aștept ca baza de date să fie gata…"
until docker compose run --rm wpcli db check >/dev/null 2>&1; do
  sleep 2
done

if ! docker compose run --rm wpcli core is-installed >/dev/null 2>&1; then
  echo "Instalez WordPress…"
  docker compose run --rm wpcli core install \
    --url="http://localhost:8080" \
    --title="TPM Psych Platform (dev)" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@example.test
else
  echo "WordPress e deja instalat."
fi

echo "Activez pluginul…"
docker compose run --rm wpcli plugin activate tpm-psych-platform

cat <<'EOF'

Gata.

  URL:            http://localhost:8080/wp-admin
  Utilizator:      admin
  Parolă:          admin

Meniul "Testare psihologică" din stânga (wp-admin) deschide interfața de
administrare a instrumentelor. Dacă pluginul nu s-a activat pentru că
vendor/ lipsește, rulează întâi:

  docker compose run --rm composer install

apoi repornește acest script.
EOF
