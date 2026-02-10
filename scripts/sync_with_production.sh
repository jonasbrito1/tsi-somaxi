#!/bin/bash
# ============================================
# Script de Sincronização com Produção
# Garante que ambiente local seja espelho de produção
# ============================================

set -e

echo "╔══════════════════════════════════════════════════════════╗"
echo "║  SINCRONIZAÇÃO LOCAL ↔ PRODUÇÃO                          ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Parâmetro: container MySQL de produção
PROD_MYSQL_CONTAINER="${1:-}"

if [ -z "$PROD_MYSQL_CONTAINER" ]; then
    echo -e "${RED}[ERRO]${NC} Nome do container MySQL não fornecido!"
    echo ""
    echo "Uso: $0 <nome_container_mysql>"
    echo ""
    echo "Exemplo:"
    echo "  $0 tsi_mysql_prod"
    echo "  $0 stack_db_1"
    echo ""
    echo "Para descobrir o nome correto, execute primeiro:"
    echo "  ${YELLOW}./discover_production.sh${NC}"
    echo ""
    exit 1
fi

echo -e "${BLUE}[INFO]${NC} Container MySQL de produção: ${GREEN}$PROD_MYSQL_CONTAINER${NC}"
echo ""

# Verificar se container existe
if ! docker ps --format "{{.Names}}" | grep -q "^${PROD_MYSQL_CONTAINER}$"; then
    echo -e "${RED}[ERRO]${NC} Container '$PROD_MYSQL_CONTAINER' não encontrado!"
    echo ""
    echo "Containers disponíveis:"
    docker ps --format "  - {{.Names}}"
    echo ""
    exit 1
fi

echo -e "${GREEN}✓${NC} Container encontrado!"
echo ""

# Credenciais
MYSQL_USER="${MYSQL_USER:-somaxi}"
MYSQL_PASS="${MYSQL_PASS:-S0m4x1@193}"
MYSQL_DB="${MYSQL_DB:-dados_tripulantes_tsi}"

echo -e "${BLUE}[1/6]${NC} Testando conexão com banco de produção..."

if docker exec "$PROD_MYSQL_CONTAINER" mysqladmin ping -u "$MYSQL_USER" -p"$MYSQL_PASS" 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Conexão OK!"
else
    echo -e "${RED}✗${NC} Falha na conexão."
    echo ""
    echo "Teste manualmente:"
    echo "  docker exec $PROD_MYSQL_CONTAINER mysql -u $MYSQL_USER -p"
    exit 1
fi

echo ""
echo -e "${BLUE}[2/6]${NC} Listando bancos de dados em produção..."

docker exec "$PROD_MYSQL_CONTAINER" mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" -e "SHOW DATABASES;" 2>/dev/null

echo ""
echo -e "${BLUE}[3/6]${NC} Analisando estrutura do banco '$MYSQL_DB'..."

# Verificar se banco existe
if ! docker exec "$PROD_MYSQL_CONTAINER" mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" -e "USE $MYSQL_DB;" 2>/dev/null; then
    echo -e "${RED}[ERRO]${NC} Banco de dados '$MYSQL_DB' não encontrado!"
    echo ""
    echo "Bancos disponíveis:"
    docker exec "$PROD_MYSQL_CONTAINER" mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" -e "SHOW DATABASES;" 2>/dev/null
    exit 1
fi

echo -e "${GREEN}✓${NC} Banco encontrado!"
echo ""

echo "Tabelas:"
docker exec "$PROD_MYSQL_CONTAINER" mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" -D "$MYSQL_DB" -e "SHOW TABLES;" 2>/dev/null

echo ""
echo "Contagem de registros:"
docker exec "$PROD_MYSQL_CONTAINER" mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" -D "$MYSQL_DB" -e "
SELECT 'users' as tabela, COUNT(*) as registros FROM users
UNION ALL
SELECT 'tabela_dados_tsi', COUNT(*) FROM tabela_dados_tsi
UNION ALL
SELECT 'rmm_relatorios', COUNT(*) FROM rmm_relatorios;
" 2>/dev/null || echo "Erro ao contar registros (tabelas podem não existir)"

echo ""
echo -e "${BLUE}[4/6]${NC} Exportando estrutura do banco (apenas schema)..."

BACKUP_DIR="$HOME/backups"
mkdir -p "$BACKUP_DIR"

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
SCHEMA_FILE="$BACKUP_DIR/production_schema_$TIMESTAMP.sql"

docker exec "$PROD_MYSQL_CONTAINER" mysqldump \
    -u "$MYSQL_USER" \
    -p"$MYSQL_PASS" \
    --no-data \
    --skip-tablespaces \
    --skip-routines \
    --triggers \
    --events \
    --databases "$MYSQL_DB" \
    > "$SCHEMA_FILE" 2>&1 | grep -v "Using a password" || true

echo -e "${GREEN}✓${NC} Schema exportado: $SCHEMA_FILE"

echo ""
echo -e "${BLUE}[5/6]${NC} Exportando dados completos..."

DATA_FILE="$BACKUP_DIR/production_data_$TIMESTAMP.sql"

docker exec "$PROD_MYSQL_CONTAINER" mysqldump \
    -u "$MYSQL_USER" \
    -p"$MYSQL_PASS" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --skip-lock-tables \
    --skip-tablespaces \
    --skip-routines \
    --databases "$MYSQL_DB" \
    --add-drop-database \
    --add-drop-table \
    --triggers \
    --events \
    --set-gtid-purged=OFF \
    > "$DATA_FILE" 2>&1 | grep -v "Using a password" || true

DATA_SIZE=$(du -h "$DATA_FILE" | cut -f1)
echo -e "${GREEN}✓${NC} Dados exportados: $DATA_FILE ($DATA_SIZE)"

echo ""
echo -e "${BLUE}[6/6]${NC} Analisando configuração do container..."

CONFIG_FILE="$BACKUP_DIR/production_container_config_$TIMESTAMP.json"

docker inspect "$PROD_MYSQL_CONTAINER" > "$CONFIG_FILE"

echo -e "${GREEN}✓${NC} Configuração salva: $CONFIG_FILE"

# Extrair informações importantes
echo ""
echo "═══════════════════════════════════════════════════════════"
echo "CONFIGURAÇÃO DO CONTAINER DE PRODUÇÃO"
echo "═══════════════════════════════════════════════════════════"
echo ""

docker inspect "$PROD_MYSQL_CONTAINER" --format '
Image: {{.Config.Image}}
Network: {{.HostConfig.NetworkMode}}
Restart: {{.HostConfig.RestartPolicy.Name}}

Environment Variables:
{{range .Config.Env}}  {{.}}
{{end}}

Volumes:
{{range .Mounts}}  {{.Source}} → {{.Destination}}
{{end}}

Ports:
{{range $key, $value := .NetworkSettings.Ports}}  {{$key}} → {{$value}}
{{end}}
'

echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║  SINCRONIZAÇÃO CONCLUÍDA                                 ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}✓${NC} Arquivos exportados:"
echo "  - Schema: $SCHEMA_FILE"
echo "  - Dados:  $DATA_FILE ($DATA_SIZE)"
echo "  - Config: $CONFIG_FILE"
echo ""
echo -e "${BLUE}[PRÓXIMO PASSO]${NC}"
echo "  Para importar no ambiente local, execute:"
echo "  ${YELLOW}./import_to_local.sh $DATA_FILE${NC}"
echo ""
