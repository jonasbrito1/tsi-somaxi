#!/bin/bash
# ============================================
# Script de Descoberta de Estrutura de Produção
# Analisa containers, volumes, redes e configurações
# ============================================

set -e

echo "╔══════════════════════════════════════════════════════════╗"
echo "║  DESCOBERTA DE ESTRUTURA DE PRODUÇÃO                     ║"
echo "║  Sistema TSI - SOMAXI GROUP                              ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Diretório de output
OUTPUT_DIR="production_analysis"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REPORT_FILE="$OUTPUT_DIR/production_structure_$TIMESTAMP.txt"

mkdir -p "$OUTPUT_DIR"

echo -e "${BLUE}[INFO]${NC} Análise será salva em: ${GREEN}$REPORT_FILE${NC}"
echo ""

# Iniciar relatório
cat > "$REPORT_FILE" << 'EOF'
╔══════════════════════════════════════════════════════════╗
║     ANÁLISE DE ESTRUTURA DE PRODUÇÃO - TSI SOMAXI        ║
╚══════════════════════════════════════════════════════════╝

Data da Análise: $(date)
Servidor: $(hostname)
Usuário: $(whoami)

================================================================================
EOF

echo -e "${BLUE}[1/10]${NC} Verificando tipo de ambiente Docker..."

# Detectar se é Docker Swarm ou Docker Compose
IS_SWARM=false
if docker info 2>/dev/null | grep -q "Swarm: active"; then
    IS_SWARM=true
    echo -e "${GREEN}✓${NC} Ambiente: Docker Swarm (Produção)"
    echo "AMBIENTE: Docker Swarm" >> "$REPORT_FILE"
else
    echo -e "${YELLOW}⚠${NC} Ambiente: Docker standalone/Compose"
    echo "AMBIENTE: Docker Standalone/Compose" >> "$REPORT_FILE"
fi
echo "" >> "$REPORT_FILE"

# ============================================
# CONTAINERS E SERVIÇOS
# ============================================
echo ""
echo -e "${BLUE}[2/10]${NC} Listando containers em execução..."

{
    echo "================================================================================
1. CONTAINERS EM EXECUÇÃO
================================================================================
"
    if [ "$IS_SWARM" = true ]; then
        echo "--- SERVIÇOS DOCKER SWARM ---"
        docker service ls
        echo ""

        echo "--- TASKS DOS SERVIÇOS ---"
        docker service ls --format "{{.Name}}" | while read service; do
            echo ""
            echo "Serviço: $service"
            docker service ps "$service" --no-trunc
        done
        echo ""
    fi

    echo "--- CONTAINERS DOCKER ---"
    docker ps --all --format "table {{.ID}}\t{{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}"

    echo ""
    echo "--- DETALHES DOS CONTAINERS ---"
    docker ps --format "{{.Names}}" | while read container; do
        echo ""
        echo "═══════════════════════════════════════════"
        echo "Container: $container"
        echo "═══════════════════════════════════════════"
        docker inspect "$container" --format '
Image: {{.Config.Image}}
Network Mode: {{.HostConfig.NetworkMode}}
Restart Policy: {{.HostConfig.RestartPolicy.Name}}

Environment Variables:
{{range .Config.Env}}  - {{.}}
{{end}}

Mounts/Volumes:
{{range .Mounts}}  - {{.Source}} → {{.Destination}} ({{.Type}})
{{end}}

Ports:
{{range $key, $value := .NetworkSettings.Ports}}  - {{$key}} → {{$value}}
{{end}}
'
    done

} >> "$REPORT_FILE"

echo -e "${GREEN}✓${NC} Containers analisados"

# ============================================
# BUSCAR MYSQL/BANCO DE DADOS
# ============================================
echo ""
echo -e "${BLUE}[3/10]${NC} Procurando container do banco de dados MySQL..."

{
    echo "
================================================================================
2. BANCO DE DADOS (MySQL)
================================================================================
"

    # Procurar containers MySQL
    MYSQL_CONTAINERS=$(docker ps --filter "ancestor=mysql" --format "{{.Names}}" 2>/dev/null || echo "")

    if [ -z "$MYSQL_CONTAINERS" ]; then
        # Tentar por nome comum
        MYSQL_CONTAINERS=$(docker ps --format "{{.Names}}" | grep -iE "mysql|mariadb|db|database" || echo "")
    fi

    if [ -n "$MYSQL_CONTAINERS" ]; then
        echo "CONTAINERS MYSQL ENCONTRADOS:"
        echo "$MYSQL_CONTAINERS"
        echo ""

        echo "$MYSQL_CONTAINERS" | while read mysql_container; do
            echo "--- Container: $mysql_container ---"

            # Testar conexão
            echo "Testando conexão..."
            docker exec "$mysql_container" mysqladmin ping 2>/dev/null && echo "Status: ONLINE" || echo "Status: ERRO AO CONECTAR"

            echo ""
            echo "Variáveis de ambiente:"
            docker inspect "$mysql_container" --format '{{range .Config.Env}}{{println .}}{{end}}' | grep -iE "MYSQL|DB"

            echo ""
            echo "Portas:"
            docker port "$mysql_container" 2>/dev/null || echo "Nenhuma porta exposta"

            echo ""
            echo "Tentando listar bancos de dados..."

            # Tentar várias combinações de credenciais
            MYSQL_USER="somaxi"
            MYSQL_PASS="S0m4x1@193"

            docker exec "$mysql_container" mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" -e "SHOW DATABASES;" 2>/dev/null || \
            docker exec "$mysql_container" mysql -u root -p"$MYSQL_PASS" -e "SHOW DATABASES;" 2>/dev/null || \
            docker exec "$mysql_container" mysql -u root -e "SHOW DATABASES;" 2>/dev/null || \
            echo "Não foi possível listar bancos (credenciais desconhecidas)"

            echo ""
            echo "══════════════════════════════════════════"
            echo ""
        done
    else
        echo "NENHUM CONTAINER MYSQL ENCONTRADO!"
        echo ""
        echo "Todos os containers em execução:"
        docker ps --format "{{.Names}}"
    fi

} >> "$REPORT_FILE"

echo -e "${GREEN}✓${NC} Banco de dados analisado"

# ============================================
# REDES
# ============================================
echo ""
echo -e "${BLUE}[4/10]${NC} Analisando redes Docker..."

{
    echo "
================================================================================
3. REDES DOCKER
================================================================================
"
    docker network ls
    echo ""

    echo "--- DETALHES DAS REDES ---"
    docker network ls --format "{{.Name}}" | while read network; do
        echo ""
        echo "Rede: $network"
        docker network inspect "$network" --format '
Driver: {{.Driver}}
Scope: {{.Scope}}
Subnet: {{range .IPAM.Config}}{{.Subnet}}{{end}}

Containers conectados:
{{range $key, $value := .Containers}}  - {{$value.Name}} ({{$value.IPv4Address}})
{{end}}
'
    done

} >> "$REPORT_FILE"

echo -e "${GREEN}✓${NC} Redes analisadas"

# ============================================
# VOLUMES
# ============================================
echo ""
echo -e "${BLUE}[5/10]${NC} Analisando volumes Docker..."

{
    echo "
================================================================================
4. VOLUMES DOCKER
================================================================================
"
    docker volume ls
    echo ""

    echo "--- DETALHES DOS VOLUMES ---"
    docker volume ls --format "{{.Name}}" | while read volume; do
        echo ""
        echo "Volume: $volume"
        docker volume inspect "$volume" --format '
Driver: {{.Driver}}
Mountpoint: {{.Mountpoint}}
Created: {{.CreatedAt}}
'
    done

} >> "$REPORT_FILE"

echo -e "${GREEN}✓${NC} Volumes analisados"

# ============================================
# DOCKER COMPOSE FILES
# ============================================
echo ""
echo -e "${BLUE}[6/10]${NC} Procurando arquivos de configuração..."

{
    echo "
================================================================================
5. ARQUIVOS DE CONFIGURAÇÃO
================================================================================
"

    echo "--- Procurando docker-compose.yml ---"
    find /home -name "docker-compose.yml" 2>/dev/null | head -10 || echo "Nenhum encontrado em /home"
    find /opt -name "docker-compose.yml" 2>/dev/null | head -10 || echo "Nenhum encontrado em /opt"
    find /var -name "docker-compose.yml" 2>/dev/null | head -10 || echo "Nenhum encontrado em /var"

    echo ""
    echo "--- Procurando arquivos .env ---"
    find /home -name ".env" 2>/dev/null | head -10 || echo "Nenhum encontrado em /home"
    find /opt -name ".env" 2>/dev/null | head -10 || echo "Nenhum encontrado em /opt"

} >> "$REPORT_FILE"

echo -e "${GREEN}✓${NC} Arquivos de configuração mapeados"

# ============================================
# SERVIÇOS DO SISTEMA
# ============================================
echo ""
echo -e "${BLUE}[7/10]${NC} Verificando serviços do sistema..."

{
    echo "
================================================================================
6. SERVIÇOS DO SISTEMA
================================================================================
"

    echo "--- Serviços systemd relacionados a Docker ---"
    systemctl list-units --type=service | grep -iE "docker|container" || echo "Nenhum encontrado"

} >> "$REPORT_FILE"

echo -e "${GREEN}✓${NC} Serviços verificados"

# ============================================
# PORTAS E EXPOSIÇÃO
# ============================================
echo ""
echo -e "${BLUE}[8/10]${NC} Verificando portas expostas..."

{
    echo "
================================================================================
7. PORTAS EXPOSTAS
================================================================================
"

    echo "--- Portas em uso no sistema ---"
    netstat -tlnp 2>/dev/null | grep -iE "docker|mysql|apache|nginx|php" || \
    ss -tlnp 2>/dev/null | grep -iE "docker|mysql|apache|nginx|php" || \
    echo "Não foi possível listar portas (permissão necessária)"

    echo ""
    echo "--- Mapeamento de portas dos containers ---"
    docker ps --format "{{.Names}}" | while read container; do
        echo ""
        echo "Container: $container"
        docker port "$container" 2>/dev/null || echo "  Nenhuma porta mapeada"
    done

} >> "$REPORT_FILE"

echo -e "${GREEN}✓${NC} Portas mapeadas"

# ============================================
# IMAGENS
# ============================================
echo ""
echo -e "${BLUE}[9/10]${NC} Listando imagens Docker..."

{
    echo "
================================================================================
8. IMAGENS DOCKER
================================================================================
"
    docker images --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}\t{{.CreatedAt}}"

} >> "$REPORT_FILE"

echo -e "${GREEN}✓${NC} Imagens listadas"

# ============================================
# ESTRUTURA DE ARQUIVOS
# ============================================
echo ""
echo -e "${BLUE}[10/10]${NC} Analisando estrutura de arquivos do projeto..."

{
    echo "
================================================================================
9. ESTRUTURA DE ARQUIVOS DO PROJETO
================================================================================
"

    echo "--- Diretório atual ---"
    pwd
    echo ""

    echo "--- Estrutura de diretórios (primeiros 3 níveis) ---"
    find . -maxdepth 3 -type d 2>/dev/null | head -50 || echo "Erro ao listar diretórios"

    echo ""
    echo "--- Arquivos PHP principais ---"
    find . -maxdepth 2 -name "*.php" -type f 2>/dev/null | head -20 || echo "Nenhum encontrado"

    echo ""
    echo "--- Arquivos de configuração ---"
    find . -maxdepth 2 -name "*.yml" -o -name "*.yaml" -o -name ".env*" -o -name "Dockerfile" 2>/dev/null || echo "Nenhum encontrado"

} >> "$REPORT_FILE"

echo -e "${GREEN}✓${NC} Estrutura de arquivos analisada"

# ============================================
# RESUMO E RECOMENDAÇÕES
# ============================================
{
    echo "
================================================================================
10. RESUMO E RECOMENDAÇÕES
================================================================================

PRÓXIMOS PASSOS:

1. Revise este relatório completamente
2. Identifique o container MySQL correto
3. Identifique as credenciais de acesso
4. Copie o arquivo docker-compose.yml de produção (se existir)
5. Execute o script de exportação com o container correto

COMANDOS ÚTEIS:

# Listar todos os containers
docker ps -a

# Inspecionar container específico
docker inspect <nome_container>

# Executar comando no container
docker exec <nome_container> <comando>

# Ver logs do container
docker logs <nome_container>

# Exportar configuração do Swarm (se aplicável)
docker stack ls
docker stack ps <stack_name>

================================================================================
FIM DO RELATÓRIO
================================================================================
"
} >> "$REPORT_FILE"

# ============================================
# FINALIZAR
# ============================================
echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║  ANÁLISE CONCLUÍDA                                       ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}✓${NC} Relatório salvo em: ${CYAN}$REPORT_FILE${NC}"
echo ""
echo -e "${BLUE}[PRÓXIMOS PASSOS]${NC}"
echo "  1. Leia o relatório: ${YELLOW}cat $REPORT_FILE${NC}"
echo "  2. Identifique o container MySQL correto"
echo "  3. Execute o script de exportação atualizado"
echo ""

# Mostrar preview do relatório
echo -e "${BLUE}[PREVIEW]${NC} Primeiras linhas do relatório:"
echo "─────────────────────────────────────────────────────────"
head -50 "$REPORT_FILE"
echo "─────────────────────────────────────────────────────────"
echo ""
echo -e "Para ver o relatório completo: ${YELLOW}cat $REPORT_FILE${NC}"
echo ""
