#!/bin/bash
# ============================================
# Script de Exportação de Dados de Produção
# MODO: READ-ONLY (Não altera produção)
# ============================================

set -e  # Parar em caso de erro

echo "╔══════════════════════════════════════════════════════════╗"
echo "║  EXPORTAÇÃO DE DADOS DE PRODUÇÃO - SOMAXI TSI            ║"
echo "║  MODO: READ-ONLY (Somente Leitura)                       ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurações de Produção
PROD_HOST="mysql_mysql"
PROD_PORT="3306"
PROD_USER="somaxi"
PROD_PASS="S0m4x1@193"
PROD_DB="dados_tripulantes_tsi"

# Diretório de backups
BACKUP_DIR="../backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/production_backup_$TIMESTAMP.sql"

# Criar diretório de backups se não existir
mkdir -p "$BACKUP_DIR"

echo -e "${BLUE}[INFO]${NC} Configurações:"
echo "  - Host: $PROD_HOST"
echo "  - Banco: $PROD_DB"
echo "  - Arquivo: $BACKUP_FILE"
echo ""

# Verificar se estamos em produção (proteção adicional)
HOSTNAME=$(hostname)
if [[ "$HOSTNAME" == *"swarm"* ]]; then
    echo -e "${RED}[ERRO]${NC} Este script está rodando em PRODUÇÃO!"
    echo "  Execute este script apenas em ambiente LOCAL."
    exit 1
fi

echo -e "${YELLOW}[AVISO]${NC} Este script fará apenas LEITURA dos dados de produção."
echo "  Nenhuma modificação será feita no banco de produção."
echo ""

# Perguntar confirmação
read -p "Deseja continuar? (s/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}[CANCELADO]${NC} Operação cancelada pelo usuário."
    exit 0
fi

echo ""
echo -e "${BLUE}[1/4]${NC} Testando conectividade com produção..."

# Testar conexão (READ-ONLY)
if docker run --rm mysql:8.0 mysqladmin ping \
    -h "$PROD_HOST" \
    -P "$PROD_PORT" \
    -u "$PROD_USER" \
    -p"$PROD_PASS" \
    --connect-timeout=5 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Conexão estabelecida com sucesso!"
else
    echo -e "${RED}✗${NC} Não foi possível conectar ao servidor de produção."
    echo ""
    echo -e "${YELLOW}[SOLUÇÃO ALTERNATIVA]${NC}"
    echo "  1. Acesse o servidor de produção"
    echo "  2. Execute o comando:"
    echo "     docker exec mysql_mysql mysqldump -u $PROD_USER -p$PROD_PASS $PROD_DB > backup.sql"
    echo "  3. Copie o arquivo backup.sql para: $BACKUP_DIR/"
    echo "  4. Execute o script de importação: ./import_to_local.sh"
    exit 1
fi

echo ""
echo -e "${BLUE}[2/4]${NC} Verificando tamanho do banco de dados..."

# Verificar tamanho do banco (apenas leitura)
DB_SIZE=$(docker run --rm mysql:8.0 mysql \
    -h "$PROD_HOST" \
    -P "$PROD_PORT" \
    -u "$PROD_USER" \
    -p"$PROD_PASS" \
    -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.TABLES WHERE table_schema = '$PROD_DB';" \
    -sN 2>/dev/null || echo "0")

echo -e "${GREEN}✓${NC} Tamanho do banco: ${DB_SIZE} MB"

echo ""
echo -e "${BLUE}[3/4]${NC} Contando registros..."

# Contar registros nas tabelas principais
TABLES=("users" "tabela_dados_tsi" "rmm_relatorios")

for table in "${TABLES[@]}"; do
    COUNT=$(docker run --rm mysql:8.0 mysql \
        -h "$PROD_HOST" \
        -P "$PROD_PORT" \
        -u "$PROD_USER" \
        -p"$PROD_PASS" \
        -D "$PROD_DB" \
        -e "SELECT COUNT(*) FROM $table;" \
        -sN 2>/dev/null || echo "0")

    echo -e "  - $table: ${GREEN}$COUNT${NC} registros"
done

echo ""
echo -e "${BLUE}[4/4]${NC} Exportando dados de produção..."
echo "  (Operação READ-ONLY - Produção não será afetada)"

# Fazer dump com mysqldump (READ-ONLY, com lock mínimo)
docker run --rm mysql:8.0 mysqldump \
    -h "$PROD_HOST" \
    -P "$PROD_PORT" \
    -u "$PROD_USER" \
    -p"$PROD_PASS" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --skip-lock-tables \
    --databases "$PROD_DB" \
    --add-drop-database \
    --add-drop-table \
    --routines \
    --triggers \
    --events \
    --set-gtid-purged=OFF \
    > "$BACKUP_FILE" 2>/dev/null

if [ $? -eq 0 ]; then
    BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    echo -e "${GREEN}✓${NC} Exportação concluída com sucesso!"
    echo ""
    echo "╔══════════════════════════════════════════════════════════╗"
    echo "║  EXPORTAÇÃO CONCLUÍDA                                    ║"
    echo "╚══════════════════════════════════════════════════════════╝"
    echo ""
    echo -e "  Arquivo: ${GREEN}$BACKUP_FILE${NC}"
    echo -e "  Tamanho: ${GREEN}$BACKUP_SIZE${NC}"
    echo ""
    echo -e "${BLUE}[PRÓXIMO PASSO]${NC}"
    echo "  Execute o script de importação:"
    echo "  ${YELLOW}./import_to_local.sh $BACKUP_FILE${NC}"
    echo ""
else
    echo -e "${RED}✗${NC} Erro ao exportar dados."
    exit 1
fi

echo -e "${GREEN}[SUCESSO]${NC} Nenhuma alteração foi feita em produção."
echo ""
