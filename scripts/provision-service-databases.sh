#!/usr/bin/env bash
set -euo pipefail

for variable in CLIENTE_DB_PASSWORD VEICULO_DB_PASSWORD ESTOQUE_DB_PASSWORD FUNCIONARIO_DB_PASSWORD OS_DB_PASSWORD; do
  value="${!variable}"
  if [[ ! "$value" =~ ^[A-Za-z0-9_-]{12,128}$ ]]; then
    echo "$variable must be 12-128 characters using letters, numbers, underscore, or hyphen." >&2
    exit 1
  fi
done

mysql --protocol=tcp -h 127.0.0.1 -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "
CREATE DATABASE IF NOT EXISTS cliente_db;
CREATE DATABASE IF NOT EXISTS veiculo_db;
CREATE DATABASE IF NOT EXISTS estoque_db;
CREATE DATABASE IF NOT EXISTS funcionario_db;
CREATE DATABASE IF NOT EXISTS os_db;
CREATE USER IF NOT EXISTS 'cliente_app'@'%' IDENTIFIED BY '${CLIENTE_DB_PASSWORD}';
CREATE USER IF NOT EXISTS 'veiculo_app'@'%' IDENTIFIED BY '${VEICULO_DB_PASSWORD}';
CREATE USER IF NOT EXISTS 'estoque_app'@'%' IDENTIFIED BY '${ESTOQUE_DB_PASSWORD}';
CREATE USER IF NOT EXISTS 'funcionario_app'@'%' IDENTIFIED BY '${FUNCIONARIO_DB_PASSWORD}';
CREATE USER IF NOT EXISTS 'os_app'@'%' IDENTIFIED BY '${OS_DB_PASSWORD}';
ALTER USER 'cliente_app'@'%' IDENTIFIED BY '${CLIENTE_DB_PASSWORD}';
ALTER USER 'veiculo_app'@'%' IDENTIFIED BY '${VEICULO_DB_PASSWORD}';
ALTER USER 'estoque_app'@'%' IDENTIFIED BY '${ESTOQUE_DB_PASSWORD}';
ALTER USER 'funcionario_app'@'%' IDENTIFIED BY '${FUNCIONARIO_DB_PASSWORD}';
ALTER USER 'os_app'@'%' IDENTIFIED BY '${OS_DB_PASSWORD}';
GRANT ALL PRIVILEGES ON cliente_db.* TO 'cliente_app'@'%';
GRANT ALL PRIVILEGES ON veiculo_db.* TO 'veiculo_app'@'%';
GRANT ALL PRIVILEGES ON estoque_db.* TO 'estoque_app'@'%';
GRANT ALL PRIVILEGES ON funcionario_db.* TO 'funcionario_app'@'%';
GRANT ALL PRIVILEGES ON os_db.* TO 'os_app'@'%';
FLUSH PRIVILEGES;
"
