#!/bin/bash
# ============================================
# Script de Importação de Dados para Local
# DESTINO: Ambiente Local (Docker Compose)
# ============================================

set -e  # Parar em caso de erro

echo "╔══════════════════════════════════════════════════════════╗"
echo "║  IMPORTAÇÃO DE DADOS PARA AMBIENTE LOCAL                 ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Verificar argumento (arquivo de backup)
if [ -z "$1" ]; then
    echo -e "${YELLOW}[AVISO]${NC} Nenhum arquivo especificado."
    echo ""
    echo "Procurando backups disponíveis..."
    BACKUP_DIR="../backups"

    if [ -d "$BACKUP_DIR" ]; then
        LATEST_BACKUP=$(ls -t "$BACKUP_DIR"/*.sql 2>/dev/null | head -1)

        if [ -n "$LATEST_BACKUP" ]; then
            echo -e "${GREEN}✓${NC} Backup mais recente encontrado:"
            echo "  $LATEST_BACKUP"
            echo ""
            read -p "Deseja usar este arquivo? (s/N): " -n 1 -r
            echo ""
            if [[ $REPLY =~ ^[Ss]$ ]]; then
                BACKUP_FILE="$LATEST_BACKUP"
            else
                echo -e "${RED}[ERRO]${NC} Nenhum arquivo selecionado."
                echo ""
                echo "Uso: $0 <arquivo_backup.sql>"
                exit 1
            fi
        else
            echo -e "${RED}[ERRO]${NC} Nenhum backup encontrado."
            echo ""
            echo "Execute primeiro: ./export_production.sh"
            exit 1
        fi
    else
        echo -e "${RED}[ERRO]${NC} Diretório de backups não encontrado."
        echo ""
        echo "Execute primeiro: ./export_production.sh"
        exit 1
    fi
else
    BACKUP_FILE="$1"
fi

# Verificar se arquivo existe
if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}[ERRO]${NC} Arquivo não encontrado: $BACKUP_FILE"
    exit 1
fi

# Verificar se estamos em produção (proteção)
HOSTNAME=$(hostname)
if [[ "$HOSTNAME" == *"swarm"* ]]; then
    echo -e "${RED}[ERRO CRÍTICO]${NC} Este script está rodando em PRODUÇÃO!"
    echo "  Execute este script apenas em ambiente LOCAL."
    exit 1
fi

# Configurações locais
LOCAL_CONTAINER="tsi_mysql"
LOCAL_USER="somaxi"
LOCAL_PASS="S0m4x1@193"
LOCAL_DB="dados_tripulantes_tsi"

BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)

echo -e "${BLUE}[INFO]${NC} Configurações:"
echo "  - Container: $LOCAL_CONTAINER"
echo "  - Banco: $LOCAL_DB"
echo "  - Arquivo: $BACKUP_FILE ($BACKUP_SIZE)"
echo ""

echo -e "${YELLOW}[AVISO]${NC} Esta operação irá:"
echo "  1. Fazer backup dos dados locais atuais"
echo "  2. Substituir todos os dados locais pelos dados de produção"
echo "  3. Preservar a estrutura e relacionamentos"
echo ""

read -p "Deseja continuar? (s/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}[CANCELADO]${NC} Operação cancelada pelo usuário."
    exit 0
fi

echo ""
echo -e "${BLUE}[1/5]${NC} Verificando container local..."

# Verificar se container está rodando
if ! docker ps | grep -q "$LOCAL_CONTAINER"; then
    echo -e "${RED}✗${NC} Container $LOCAL_CONTAINER não está rodando."
    echo ""
    echo "Inicie o container com:"
    echo "  docker-compose up -d"
    exit 1
fi

echo -e "${GREEN}✓${NC} Container está rodando!"

echo ""
echo -e "${BLUE}[2/5]${NC} Fazendo backup dos dados locais atuais..."

# Criar backup dos dados locais antes de importar
BACKUP_DIR_LOCAL="../backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOCAL_BACKUP="$BACKUP_DIR_LOCAL/local_backup_before_import_$TIMESTAMP.sql"

mkdir -p "$BACKUP_DIR_LOCAL"

docker exec "$LOCAL_CONTAINER" mysqldump \
    -u "$LOCAL_USER" \
    -p"$LOCAL_PASS" \
    --databases "$LOCAL_DB" \
    > "$LOCAL_BACKUP" 2>/dev/null

if [ $? -eq 0 ]; then
    LOCAL_BACKUP_SIZE=$(du -h "$LOCAL_BACKUP" | cut -f1)
    echo -e "${GREEN}✓${NC} Backup local salvo: $LOCAL_BACKUP ($LOCAL_BACKUP_SIZE)"
else
    echo -e "${YELLOW}⚠${NC} Não foi possível criar backup local (banco pode estar vazio)"
fi

echo ""
echo -e "${BLUE}[3/5]${NC} Verificando integridade do arquivo de backup..."

# Verificar se arquivo SQL é válido
if head -10 "$BACKUP_FILE" | grep -q "MySQL dump"; then
    echo -e "${GREEN}✓${NC} Arquivo de backup é válido (MySQL dump)"
else
    echo -e "${RED}✗${NC} Arquivo de backup pode estar corrompido."
    read -p "Deseja continuar mesmo assim? (s/N): " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        echo -e "${YELLOW}[CANCELADO]${NC} Operação cancelada."
        exit 0
    fi
fi

echo ""
echo -e "${BLUE}[4/5]${NC} Importando dados de produção para ambiente local..."
echo "  (Isso pode levar alguns minutos dependendo do tamanho)"

# Importar dados
docker exec -i "$LOCAL_CONTAINER" mysql \
    -u "$LOCAL_USER" \
    -p"$LOCAL_PASS" \
    < "$BACKUP_FILE" 2>&1 | grep -v "Using a password"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Importação concluída com sucesso!"
else
    echo -e "${RED}✗${NC} Erro durante importação."
    echo ""
    echo -e "${YELLOW}[RECUPERAÇÃO]${NC}"
    echo "  Para restaurar os dados locais anteriores:"
    echo "  docker exec -i $LOCAL_CONTAINER mysql -u $LOCAL_USER -p$LOCAL_PASS < $LOCAL_BACKUP"
    exit 1
fi

echo ""
echo -e "${BLUE}[5/5]${NC} Validando dados importados..."

# Contar registros importados
TABLES=("users" "tabela_dados_tsi" "rmm_relatorios")

echo ""
for table in "${TABLES[@]}"; do
    COUNT=$(docker exec "$LOCAL_CONTAINER" mysql \
        -u "$LOCAL_USER" \
        -p"$LOCAL_PASS" \
        -D "$LOCAL_DB" \
        -e "SELECT COUNT(*) FROM $table;" \
        -sN 2>/dev/null || echo "0")

    echo -e "  - $table: ${GREEN}$COUNT${NC} registros"
done

echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║  IMPORTAÇÃO CONCLUÍDA COM SUCESSO!                       ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}✓${NC} Dados de produção importados para ambiente local"
echo -e "${GREEN}✓${NC} Backup local salvo em: $LOCAL_BACKUP"
echo ""
echo -e "${BLUE}[PRÓXIMOS PASSOS]${NC}"
echo "  1. Acesse o sistema: ${YELLOW}http://localhost:8076${NC}"
echo "  2. Faça login com: ${YELLOW}admin / admin123${NC}"
echo "  3. Verifique se os dados estão corretos"
echo ""
echo -e "${YELLOW}[IMPORTANTE]${NC}"
echo "  - Este é um ambiente LOCAL de desenvolvimento"
echo "  - Alterações aqui NÃO afetam produção"
echo "  - Para restaurar dados locais anteriores:"
echo "    ${YELLOW}docker exec -i $LOCAL_CONTAINER mysql -u $LOCAL_USER -p$LOCAL_PASS < $LOCAL_BACKUP${NC}"
echo ""
