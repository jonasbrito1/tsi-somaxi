# ============================================
# Script de Exportação de Dados de Produção
# MODO: READ-ONLY (Não altera produção)
# PowerShell Version
# ============================================

$ErrorActionPreference = "Stop"

Write-Host "╔══════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  EXPORTAÇÃO DE DADOS DE PRODUÇÃO - SOMAXI TSI            ║" -ForegroundColor Cyan
Write-Host "║  MODO: READ-ONLY (Somente Leitura)                       ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Configurações de Produção
$PROD_HOST = "mysql_mysql"
$PROD_PORT = "3306"
$PROD_USER = "somaxi"
$PROD_PASS = "S0m4x1@193"
$PROD_DB = "dados_tripulantes_tsi"

# Diretório de backups
$BACKUP_DIR = "..\backups"
$TIMESTAMP = Get-Date -Format "yyyyMMdd_HHmmss"
$BACKUP_FILE = "$BACKUP_DIR\production_backup_$TIMESTAMP.sql"

# Criar diretório de backups se não existir
if (-not (Test-Path $BACKUP_DIR)) {
    New-Item -ItemType Directory -Path $BACKUP_DIR | Out-Null
}

Write-Host "[INFO] Configurações:" -ForegroundColor Blue
Write-Host "  - Host: $PROD_HOST"
Write-Host "  - Banco: $PROD_DB"
Write-Host "  - Arquivo: $BACKUP_FILE"
Write-Host ""

Write-Host "[AVISO] Este script fará apenas LEITURA dos dados de produção." -ForegroundColor Yellow
Write-Host "  Nenhuma modificação será feita no banco de produção."
Write-Host ""

# Perguntar confirmação
$confirmation = Read-Host "Deseja continuar? (s/N)"
if ($confirmation -ne "s" -and $confirmation -ne "S") {
    Write-Host "[CANCELADO] Operação cancelada pelo usuário." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "[1/4] Testando conectividade com produção..." -ForegroundColor Blue

# Testar conexão
try {
    $pingResult = docker run --rm mysql:8.0 mysqladmin ping `
        -h $PROD_HOST `
        -P $PROD_PORT `
        -u $PROD_USER `
        -p"$PROD_PASS" `
        --connect-timeout=5 2>&1

    Write-Host "✓ Conexão estabelecida com sucesso!" -ForegroundColor Green
} catch {
    Write-Host "✗ Não foi possível conectar ao servidor de produção." -ForegroundColor Red
    Write-Host ""
    Write-Host "[SOLUÇÃO ALTERNATIVA]" -ForegroundColor Yellow
    Write-Host "  1. Acesse o servidor de produção"
    Write-Host "  2. Execute o comando:"
    Write-Host "     docker exec mysql_mysql mysqldump -u $PROD_USER -p$PROD_PASS $PROD_DB > backup.sql" -ForegroundColor Cyan
    Write-Host "  3. Copie o arquivo backup.sql para: $BACKUP_DIR\"
    Write-Host "  4. Execute o script de importação: .\import_to_local.ps1"
    exit 1
}

Write-Host ""
Write-Host "[2/4] Verificando tamanho do banco de dados..." -ForegroundColor Blue

# Verificar tamanho do banco (apenas leitura)
try {
    $DB_SIZE = docker run --rm mysql:8.0 mysql `
        -h $PROD_HOST `
        -P $PROD_PORT `
        -u $PROD_USER `
        -p"$PROD_PASS" `
        -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.TABLES WHERE table_schema = '$PROD_DB';" `
        -sN 2>$null

    Write-Host "✓ Tamanho do banco: $DB_SIZE MB" -ForegroundColor Green
} catch {
    Write-Host "⚠ Não foi possível verificar o tamanho" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "[3/4] Contando registros..." -ForegroundColor Blue

# Contar registros nas tabelas principais
$TABLES = @("users", "tabela_dados_tsi", "rmm_relatorios")

foreach ($table in $TABLES) {
    try {
        $COUNT = docker run --rm mysql:8.0 mysql `
            -h $PROD_HOST `
            -P $PROD_PORT `
            -u $PROD_USER `
            -p"$PROD_PASS" `
            -D $PROD_DB `
            -e "SELECT COUNT(*) FROM $table;" `
            -sN 2>$null

        Write-Host "  - $table: $COUNT registros" -ForegroundColor Green
    } catch {
        Write-Host "  - $table: 0 registros" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "[4/4] Exportando dados de produção..." -ForegroundColor Blue
Write-Host "  (Operação READ-ONLY - Produção não será afetada)"

# Fazer dump com mysqldump (READ-ONLY, com lock mínimo)
try {
    docker run --rm mysql:8.0 mysqldump `
        -h $PROD_HOST `
        -P $PROD_PORT `
        -u $PROD_USER `
        -p"$PROD_PASS" `
        --single-transaction `
        --quick `
        --lock-tables=false `
        --skip-lock-tables `
        --databases $PROD_DB `
        --add-drop-database `
        --add-drop-table `
        --routines `
        --triggers `
        --events `
        --set-gtid-purged=OFF `
        | Out-File -FilePath $BACKUP_FILE -Encoding UTF8

    $BACKUP_SIZE = (Get-Item $BACKUP_FILE).Length / 1MB
    $BACKUP_SIZE = [math]::Round($BACKUP_SIZE, 2)

    Write-Host "✓ Exportação concluída com sucesso!" -ForegroundColor Green
    Write-Host ""
    Write-Host "╔══════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
    Write-Host "║  EXPORTAÇÃO CONCLUÍDA                                    ║" -ForegroundColor Cyan
    Write-Host "╚══════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  Arquivo: $BACKUP_FILE" -ForegroundColor Green
    Write-Host "  Tamanho: $BACKUP_SIZE MB" -ForegroundColor Green
    Write-Host ""
    Write-Host "[PRÓXIMO PASSO]" -ForegroundColor Blue
    Write-Host "  Execute o script de importação:" -ForegroundColor Blue
    Write-Host "  .\import_to_local.ps1 $BACKUP_FILE" -ForegroundColor Yellow
    Write-Host ""
} catch {
    Write-Host "✗ Erro ao exportar dados: $_" -ForegroundColor Red
    exit 1
}

Write-Host "[SUCESSO] Nenhuma alteração foi feita em produção." -ForegroundColor Green
Write-Host ""
