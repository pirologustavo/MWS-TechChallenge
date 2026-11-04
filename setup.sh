#!/usr/bin/env bash
set -euo pipefail

if [[ -f .env ]]; then
  set -a
  # Strip CR from Windows-edited .env files before Bash evaluates assignments.
  source <(sed 's/\r$//' .env)
  set +a
fi

services=(funcionario-lv-api cliente-lv-api veiculo-lv-api estoque-lv-api os-lv-api)
for container in "${services[@]}"; do
  docker exec -u root "$container" chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
  docker exec -u root "$container" chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
done

echo "Applying service-owned migrations..."
for container in "${services[@]}"; do
  docker exec -u www-data "$container" php /var/www/html/artisan migrate --force
done

root_password="${LEGACY_DB_ROOT_PASSWORD:-root}"
legacy_has_data=$(docker exec -e MYSQL_PWD="$root_password" mws-db mysql -N -uroot -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='mws_db' AND table_name='clientes';" 2>/dev/null || echo 0)
if [[ "$legacy_has_data" == "1" ]]; then
  echo "Migrating legacy mws_db data into isolated service schemas..."
  ./migrate-service-data.sh
else
  echo "No legacy tables found; seeding the employee service."
  docker exec -u www-data funcionario-lv-api php /var/www/html/artisan db:seed --force
fi

echo "Service schemas are migrated."
