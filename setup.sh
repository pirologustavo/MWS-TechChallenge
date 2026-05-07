#!/bin/bash
echo "Iniciando Migrations dos Microserviços..."

docker exec -it cliente-lv-api php artisan migrate --force
docker exec -it veiculo-lv-api php artisan migrate --force
docker exec -it estoque-lv-api php artisan migrate --force
docker exec -it funcionario-lv-api php artisan migrate --seed --force
docker exec -it os-lv-api php artisan migrate --force

echo "Ambiente configurado com sucesso!"