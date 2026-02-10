#!/bin/bash
# ============================================
# Script de Preparação para Deploy em Produção
# Valida ambiente local antes de fazer upload
# ============================================

set -e

echo "╔══════════════════════════════════════════════════════════╗"
echo "║  PREPARAÇÃO PARA DEPLOY EM PRODUÇÃO                      ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

ERRORS=0
WARNINGS=0

echo -e "${YELLOW}[AVISO]${NC} Este script valida o ambiente local antes de deploy."
echo "  Nenhuma alteração será feita em produção."
echo ""

# ============================================
# VERIFICAÇÕES PRÉ-DEPLOY
# ============================================

echo -e "${BLUE}[1/12]${NC} Verificando se estamos em ambiente LOCAL..."

HOSTNAME=$(hostname)
if [[ "$HOSTNAME" == *"swarm"* ]] || [[ "$HOSTNAME" == *"prod"* ]]; then
    echo -e "${RED}✗ ERRO CRÍTICO${NC}"
    echo "  Este script está rodando em PRODUÇÃO!"
    echo "  Hostname: $HOSTNAME"
    echo ""
    echo "  Execute este script apenas em ambiente LOCAL."
    exit 1
fi

echo -e "${GREEN}✓${NC} Ambiente local confirmado (hostname: $HOSTNAME)"

echo ""
echo -e "${BLUE}[2/12]${NC} Verificando arquivos essenciais..."

REQUIRED_FILES=(
    "docker-compose.yml"
    "Dockerfile"
    ".env"
    "includes/db.php"
    "index.php"
    "login.php"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "../$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗${NC} $file ${RED}(FALTANDO)${NC}"
        ((ERRORS++))
    fi
done

echo ""
echo -e "${BLUE}[3/12]${NC} Verificando Docker Compose..."

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}✗${NC} docker-compose não encontrado!"
    ((ERRORS++))
else
    echo -e "${GREEN}✓${NC} docker-compose instalado"

    # Validar docker-compose.yml
    cd ..
    if docker-compose config > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} docker-compose.yml é válido"
    else
        echo -e "${RED}✗${NC} docker-compose.yml tem erros!"
        ((ERRORS++))
    fi
    cd scripts
fi

echo ""
echo -e "${BLUE}[4/12]${NC} Verificando containers locais..."

LOCAL_CONTAINERS=$(docker ps --filter "name=tsi" --format "{{.Names}}")

if [ -z "$LOCAL_CONTAINERS" ]; then
    echo -e "${YELLOW}⚠${NC} Nenhum container TSI rodando localmente"
    echo "  Execute: docker-compose up -d"
    ((WARNINGS++))
else
    echo -e "${GREEN}✓${NC} Containers locais rodando:"
    echo "$LOCAL_CONTAINERS" | sed 's/^/  - /'
fi

echo ""
echo -e "${BLUE}[5/12]${NC} Testando conexão com banco local..."

if docker exec tsi_mysql mysqladmin ping 2>/dev/null; then
    echo -e "${GREEN}✓${NC} MySQL local está respondendo"
else
    echo -e "${RED}✗${NC} MySQL local não está acessível"
    ((ERRORS++))
fi

echo ""
echo -e "${BLUE}[6/12]${NC} Verificando dados no banco local..."

if docker exec tsi_mysql mysql -u somaxi -pS0m4x1@193 dados_tripulantes_tsi -e "SHOW TABLES;" 2>/dev/null | grep -q "users"; then
    echo -e "${GREEN}✓${NC} Tabelas existem no banco local"

    # Contar registros
    echo ""
    echo "Registros no banco local:"
    docker exec tsi_mysql mysql -u somaxi -pS0m4x1@193 dados_tripulantes_tsi -e "
    SELECT 'users' as tabela, COUNT(*) as registros FROM users
    UNION ALL
    SELECT 'tabela_dados_tsi', COUNT(*) FROM tabela_dados_tsi
    UNION ALL
    SELECT 'rmm_relatorios', COUNT(*) FROM rmm_relatorios;
    " 2>/dev/null | sed 's/^/  /'
else
    echo -e "${YELLOW}⚠${NC} Banco local parece estar vazio"
    ((WARNINGS++))
fi

echo ""
echo -e "${BLUE}[7/12]${NC} Verificando variáveis de ambiente..."

if [ -f "../.env" ]; then
    echo -e "${GREEN}✓${NC} Arquivo .env existe"

    # Verificar variáveis críticas
    CRITICAL_VARS=("DB_HOST" "DB_NAME" "DB_USER" "DB_PASS")

    for var in "${CRITICAL_VARS[@]}"; do
        if grep -q "^$var=" ../.env; then
            VALUE=$(grep "^$var=" ../.env | cut -d'=' -f2)
            echo -e "${GREEN}✓${NC} $var=${CYAN}$VALUE${NC}"
        else
            echo -e "${RED}✗${NC} $var não definido"
            ((ERRORS++))
        fi
    done
else
    echo -e "${RED}✗${NC} Arquivo .env não encontrado"
    ((ERRORS++))
fi

echo ""
echo -e "${BLUE}[8/12]${NC} Testando acesso ao sistema..."

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8076/index.php 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✓${NC} Sistema acessível (HTTP 200)"
elif [ "$HTTP_CODE" = "302" ] || [ "$HTTP_CODE" = "301" ]; then
    echo -e "${GREEN}✓${NC} Sistema acessível (HTTP $HTTP_CODE - redirect)"
else
    echo -e "${YELLOW}⚠${NC} Sistema retornou HTTP $HTTP_CODE"
    ((WARNINGS++))
fi

echo ""
echo -e "${BLUE}[9/12]${NC} Verificando permissões de arquivos..."

if [ -w "../index.php" ]; then
    echo -e "${GREEN}✓${NC} Arquivos têm permissão de escrita"
else
    echo -e "${YELLOW}⚠${NC} Alguns arquivos podem não ter permissão adequada"
    ((WARNINGS++))
fi

echo ""
echo -e "${BLUE}[10/12]${NC} Verificando tamanho do projeto..."

PROJECT_SIZE=$(du -sh .. 2>/dev/null | cut -f1)
echo -e "${GREEN}✓${NC} Tamanho total do projeto: ${CYAN}$PROJECT_SIZE${NC}"

echo ""
echo -e "${BLUE}[11/12]${NC} Verificando dependências do Composer..."

if [ -d "../vendor" ]; then
    echo -e "${GREEN}✓${NC} Diretório vendor existe"

    VENDOR_SIZE=$(du -sh ../vendor 2>/dev/null | cut -f1)
    echo "  Tamanho: $VENDOR_SIZE"
else
    echo -e "${YELLOW}⚠${NC} Diretório vendor não encontrado"
    echo "  Algumas dependências PHP podem estar faltando"
    ((WARNINGS++))
fi

echo ""
echo -e "${BLUE}[12/12]${NC} Verificando logs de erro..."

if [ -f "../php_errors.log" ]; then
    ERROR_COUNT=$(wc -l < ../php_errors.log 2>/dev/null || echo "0")

    if [ "$ERROR_COUNT" -gt 0 ]; then
        echo -e "${YELLOW}⚠${NC} Existem $ERROR_COUNT linhas no log de erros"
        echo ""
        echo "Últimas 5 linhas:"
        tail -5 ../php_errors.log | sed 's/^/  /'
    else
        echo -e "${GREEN}✓${NC} Sem erros no log"
    fi
else
    echo -e "${GREEN}✓${NC} Nenhum arquivo de log de erros"
fi

# ============================================
# GERAR RELATÓRIO
# ============================================

echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║  RESULTADO DA VALIDAÇÃO                                  ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✓ TUDO OK!${NC} Ambiente está pronto para deploy."
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠ AVISOS: $WARNINGS${NC}"
    echo "  O deploy pode ser feito, mas revise os avisos acima."
else
    echo -e "${RED}✗ ERROS ENCONTRADOS: $ERRORS${NC}"
    echo "  Corrija os erros antes de fazer deploy."
    echo ""
    exit 1
fi

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "CHECKLIST PARA DEPLOY"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "Antes de fazer deploy em produção:"
echo ""
echo "  ${CYAN}1. Backup${NC}"
echo "     [ ] Backup completo de produção feito"
echo "     [ ] Backup testado e funcionando"
echo ""
echo "  ${CYAN}2. Testes${NC}"
echo "     [ ] Sistema testado localmente"
echo "     [ ] Login funcionando"
echo "     [ ] Dashboard carregando"
echo "     [ ] Formulários funcionando"
echo ""
echo "  ${CYAN}3. Configuração${NC}"
echo "     [ ] Arquivo .env.production revisado"
echo "     [ ] Credenciais de produção confirmadas"
echo "     [ ] Portas corretas configuradas"
echo ""
echo "  ${CYAN}4. Deploy${NC}"
echo "     [ ] Janela de manutenção agendada"
echo "     [ ] Usuários notificados"
echo "     [ ] Plano de rollback preparado"
echo ""
echo "═══════════════════════════════════════════════════════════"
echo ""
echo -e "${BLUE}[PRÓXIMO PASSO]${NC}"
echo "  Se tudo estiver OK, execute:"
echo "  ${YELLOW}./deploy_to_production.sh${NC}"
echo ""
