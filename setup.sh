#!/bin/bash
echo "Aguardando o banco de dados inicializar completamente..."
sleep 10

echo "Ajustando permissões de storage nos containers..."
docker exec -u root funcionario-lv-api chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec -u root funcionario-lv-api chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

docker exec -u root cliente-lv-api chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec -u root cliente-lv-api chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

docker exec -u root veiculo-lv-api chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec -u root veiculo-lv-api chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

docker exec -u root estoque-lv-api chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec -u root estoque-lv-api chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

docker exec -u root os-lv-api chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec -u root os-lv-api chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Iniciando Migrations e Seeds dos Microserviços..."
# Funcionário roda primeiro para garantir a estrutura de login e disparar o Seed
docker exec -u www-data funcionario-lv-api php /var/www/html/artisan migrate --seed --force

# Os demais rodam suas estruturas específicas sem colidir na tabela do Sanctum
docker exec -u www-data cliente-lv-api php /var/www/html/artisan migrate --force
docker exec -u www-data veiculo-lv-api php /var/www/html/artisan migrate --force
docker exec -u www-data estoque-lv-api php /var/www/html/artisan migrate --force
docker exec -u www-data os-lv-api php /var/www/html/artisan migrate --force

echo "Ambiente configurado com sucesso!"