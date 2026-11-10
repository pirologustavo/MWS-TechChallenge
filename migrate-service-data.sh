#!/usr/bin/env bash
set -euo pipefail

if [[ -f .env ]]; then
  set -a
  source <(sed 's/\r$//' .env)
  set +a
fi

# Copies the legacy schema into isolated schemas on mws-db. Run after migrations,
# with all targets empty and with application traffic stopped.
ROOT_PASSWORD="${LEGACY_DB_ROOT_PASSWORD:-root}"
CLIENTE_PASSWORD="${CLIENTE_DB_PASSWORD:-local-cliente-change-me}"
VEICULO_PASSWORD="${VEICULO_DB_PASSWORD:-local-veiculo-change-me}"
ESTOQUE_PASSWORD="${ESTOQUE_DB_PASSWORD:-local-estoque-change-me}"
FUNCIONARIO_PASSWORD="${FUNCIONARIO_DB_PASSWORD:-local-funcionario-change-me}"
OS_PASSWORD="${OS_DB_PASSWORD:-local-os-change-me}"

copy_table() {
  local table="$1" database="$2" user="$3" password="$4"
  echo "Copying legacy table $table to $database..."
  docker exec -e MYSQL_PWD="$ROOT_PASSWORD" mws-db \
    mysqldump -uroot --no-create-info --complete-insert --skip-triggers --single-transaction mws_db "$table" |
    docker exec -i -e MYSQL_PWD="$password" mws-db mysql -u"$user" "$database"
}

verify_count() {
  local table="$1" database="$2" user="$3" password="$4" source_rows target_rows
  source_rows=$(docker exec -e MYSQL_PWD="$ROOT_PASSWORD" mws-db mysql -N -uroot mws_db \
    -e "SELECT COUNT(*) FROM \`$table\`;" | tr -d '\r')
  target_rows=$(docker exec -e MYSQL_PWD="$password" mws-db mysql -N -u"$user" "$database" \
    -e "SELECT COUNT(*) FROM \`$table\`;" | tr -d '\r')
  if [[ "$source_rows" != "$target_rows" ]]; then
    echo "Row count mismatch for $database.$table: source=$source_rows target=$target_rows" >&2
    exit 1
  fi
}

copy_if_needed() {
  local table="$1" database="$2" user="$3" password="$4" source_rows target_rows
  source_rows=$(docker exec -e MYSQL_PWD="$ROOT_PASSWORD" mws-db mysql -N -uroot mws_db \
    -e "SELECT COUNT(*) FROM \`$table\`;" | tr -d '\r')
  target_rows=$(docker exec -e MYSQL_PWD="$password" mws-db mysql -N -u"$user" "$database" \
    -e "SELECT COUNT(*) FROM \`$table\`;" | tr -d '\r')

  if [[ "$source_rows" == "$target_rows" ]]; then
    echo "$database.$table already has the source row count; skipping copy."
    return
  fi
  if [[ "$target_rows" != "0" ]]; then
    echo "$database.$table contains $target_rows rows, while the legacy table has $source_rows." >&2
    echo "Stop here and inspect the target table before retrying; it may be a partial copy." >&2
    exit 1
  fi

  copy_table "$table" "$database" "$user" "$password"
  verify_count "$table" "$database" "$user" "$password"
}

legacy_status=$(docker exec -e MYSQL_PWD="$ROOT_PASSWORD" mws-db mysql -N -uroot mws_db \
  -e "SELECT GROUP_CONCAT(CONCAT(statid, ':', statusdesc) ORDER BY statid SEPARATOR '|') FROM status;" | tr -d '\r')
target_status=$(docker exec -e MYSQL_PWD="$OS_PASSWORD" mws-db mysql -N -uos_app os_db \
  -e "SELECT GROUP_CONCAT(CONCAT(statid, ':', statusdesc) ORDER BY statid SEPARATOR '|') FROM status;" | tr -d '\r')
if [[ "$legacy_status" != "$target_status" ]]; then
  echo "OS status IDs differ between legacy and target schemas; refusing to copy." >&2
  exit 1
fi

copy_if_needed funcionarios funcionario_db funcionario_app "$FUNCIONARIO_PASSWORD"
copy_if_needed personal_access_tokens funcionario_db funcionario_app "$FUNCIONARIO_PASSWORD"
copy_if_needed clientes cliente_db cliente_app "$CLIENTE_PASSWORD"
copy_if_needed veiculos veiculo_db veiculo_app "$VEICULO_PASSWORD"
copy_if_needed estoque estoque_db estoque_app "$ESTOQUE_PASSWORD"
copy_if_needed os os_db os_app "$OS_PASSWORD"
copy_if_needed os_itens os_db os_app "$OS_PASSWORD"
copy_if_needed os_status os_db os_app "$OS_PASSWORD"
docker exec -e MYSQL_PWD="$ROOT_PASSWORD" mws-db mysql -uroot -e "
UPDATE os_db.os AS target
JOIN mws_db.os AS legacy ON legacy.osid = target.osid
LEFT JOIN mws_db.clientes AS cliente ON cliente.clientid = legacy.clientid
LEFT JOIN mws_db.veiculos AS veiculo ON veiculo.carid = legacy.carid
LEFT JOIN mws_db.funcionarios AS funcionario ON funcionario.funcid = legacy.funcid
SET target.cliente_nome_snapshot = cliente.nome,
    target.cliente_email_snapshot = cliente.email,
    target.veiculo_modelo_snapshot = veiculo.modelo,
    target.veiculo_placa_snapshot = veiculo.placa,
    target.funcionario_nome_snapshot = funcionario.nome;
UPDATE os_db.os_itens AS target
JOIN mws_db.os_itens AS legacy
  ON legacy.osid = target.osid AND legacy.estoqid = target.estoqid
LEFT JOIN mws_db.estoque AS estoque ON estoque.estoqid = legacy.estoqid
SET target.descricao_snapshot = estoque.descricao;
"

for spec in \
  'cliente_db cliente_app CLIENTE_PASSWORD clientes' \
  'veiculo_db veiculo_app VEICULO_PASSWORD veiculos' \
  'estoque_db estoque_app ESTOQUE_PASSWORD estoque' \
  'os_db os_app OS_PASSWORD os' \
  'os_db os_app OS_PASSWORD os_itens' \
  'os_db os_app OS_PASSWORD os_status'; do
  read -r database user password_name table <<< "$spec"
  verify_count "$table" "$database" "$user" "${!password_name}"
done

orphan_counts=$(docker exec -e MYSQL_PWD="$ROOT_PASSWORD" mws-db mysql -N -uroot -e "
SELECT (SELECT COUNT(*) FROM veiculo_db.veiculos v LEFT JOIN cliente_db.clientes c ON c.clientid=v.clientid WHERE c.clientid IS NULL)
 + (SELECT COUNT(*) FROM os_db.os o LEFT JOIN cliente_db.clientes c ON c.clientid=o.clientid WHERE c.clientid IS NULL)
 + (SELECT COUNT(*) FROM os_db.os o LEFT JOIN veiculo_db.veiculos v ON v.carid=o.carid WHERE v.carid IS NULL)
 + (SELECT COUNT(*) FROM os_db.os o LEFT JOIN funcionario_db.funcionarios f ON f.funcid=o.funcid WHERE f.funcid IS NULL)
 + (SELECT COUNT(*) FROM os_db.os o LEFT JOIN os_db.status s ON s.statid=o.statid_atual WHERE s.statid IS NULL)
 + (SELECT COUNT(*) FROM os_db.os_itens i LEFT JOIN estoque_db.estoque e ON e.estoqid=i.estoqid WHERE e.estoqid IS NULL);
" | tr -d '\r')
if [[ "$orphan_counts" != "0" ]]; then
  echo "Backfill contains $orphan_counts orphan references; reconcile them before switching traffic." >&2
  exit 1
fi

echo "Copy complete. User IDs and Sanctum tokens were preserved. Stop legacy writes before switching traffic."
