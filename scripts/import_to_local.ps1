# ============================================
# Script de Importação de Dados para Local
# DESTINO: Ambiente Local (Docker Compose)
# PowerShell Version
# ============================================

param(
    [string]$BackupFile
)

$ErrorActionPreference = "Stop"

Write-Host "╔══════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  IMPORTAÇÃO DE DADOS PARA AMBIENTE LOCAL                 ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Verificar argumento (arquivo de backup)
if (-not $BackupFile) {
    Write-Host "[AVISO] Nenhum arquivo especificado." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Procurando backups disponíveis..."

    $BACKUP_DIR = "..\backups"

    if (Test-Path $BACKUP_DIR) {
        $LATEST_BACKUP = Get-ChildItem -Path "$BACKUP_DIR\*.sql" | Sort-Object LastWriteTime -Descending | Select-Object -First 1

        if ($LATEST_BACKUP) {
            Write-Host "✓ Backup mais recente encontrado:" -ForegroundColor Green
            Write-Host "  $($LATEST_BACKUP.FullName)"
            Write-Host ""

            $confirmation = Read-Host "Deseja usar este arquivo? (s/N)"
            if ($confirmation -eq "s" -or $confirmation -eq "S") {
                $BackupFile = $LATEST_BACKUP.FullName
            } else {
                Write-Host "[ERRO] Nenhum arquivo selecionado." -ForegroundColor Red
                Write-Host ""
                Write-Host "Uso: .\import_to_local.ps1 <arquivo_backup.sql>"
                exit 1
            }
        } else {
            Write-Host "[ERRO] Nenhum backup encontrado." -ForegroundColor Red
            Write-Host ""
            Write-Host "Execute primeiro: .\export_production.ps1"
            exit 1
        }
    } else {
        Write-Host "[ERRO] Diretório de backups não encontrado." -ForegroundColor Red
        Write-Host ""
        Write-Host "Execute primeiro: .\export_production.ps1"
        exit 1
    }
}

# Verificar se arquivo existe
if (-not (Test-Path $BackupFile)) {
    Write-Host "[ERRO] Arquivo não encontrado: $BackupFile" -ForegroundColor Red
    exit 1
}

# Configurações locais
$LOCAL_CONTAINER = "tsi_mysql"
$LOCAL_USER = "somaxi"
$LOCAL_PASS = "S0m4x1@193"
$LOCAL_DB = "dados_tripulantes_tsi"

$BACKUP_SIZE = (Get-Item $BackupFile).Length / 1MB
$BACKUP_SIZE = [math]::Round($BACKUP_SIZE, 2)

Write-Host "[INFO] Configurações:" -ForegroundColor Blue
Write-Host "  - Container: $LOCAL_CONTAINER"
Write-Host "  - Banco: $LOCAL_DB"
Write-Host "  - Arquivo: $BackupFile ($BACKUP_SIZE MB)"
Write-Host ""

Write-Host "[AVISO] Esta operação irá:" -ForegroundColor Yellow
Write-Host "  1. Fazer backup dos dados locais atuais"
Write-Host "  2. Substituir todos os dados locais pelos dados de produção"
Write-Host "  3. Preservar a estrutura e relacionamentos"
Write-Host ""

$confirmation = Read-Host "Deseja continuar? (s/N)"
if ($confirmation -ne "s" -and $confirmation -ne "S") {
    Write-Host "[CANCELADO] Operação cancelada pelo usuário." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "[1/5] Verificando container local..." -ForegroundColor Blue

# Verificar se container está rodando
$containerRunning = docker ps --filter "name=$LOCAL_CONTAINER" --format "{{.Names}}"

if (-not $containerRunning) {
    Write-Host "✗ Container $LOCAL_CONTAINER não está rodando." -ForegroundColor Red
    Write-Host ""
    Write-Host "Inicie o container com:"
    Write-Host "  docker-compose up -d" -ForegroundColor Cyan
    exit 1
}

Write-Host "✓ Container está rodando!" -ForegroundColor Green

Write-Host ""
Write-Host "[2/5] Fazendo backup dos dados locais atuais..." -ForegroundColor Blue

# Criar backup dos dados locais antes de importar
$BACKUP_DIR_LOCAL = "..\backups"
$TIMESTAMP = Get-Date -Format "yyyyMMdd_HHmmss"
$LOCAL_BACKUP = "$BACKUP_DIR_LOCAL\local_backup_before_import_$TIMESTAMP.sql"

if (-not (Test-Path $BACKUP_DIR_LOCAL)) {
    New-Item -ItemType Directory -Path $BACKUP_DIR_LOCAL | Out-Null
}

try {
    docker exec $LOCAL_CONTAINER mysqldump `
        -u $LOCAL_USER `
        -p"$LOCAL_PASS" `
        --databases $LOCAL_DB `
        | Out-File -FilePath $LOCAL_BACKUP -Encoding UTF8

    $LOCAL_BACKUP_SIZE = (Get-Item $LOCAL_BACKUP).Length / 1MB
    $LOCAL_BACKUP_SIZE = [math]::Round($LOCAL_BACKUP_SIZE, 2)

    Write-Host "✓ Backup local salvo: $LOCAL_BACKUP ($LOCAL_BACKUP_SIZE MB)" -ForegroundColor Green
} catch {
    Write-Host "⚠ Não foi possível criar backup local (banco pode estar vazio)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "[3/5] Verificando integridade do arquivo de backup..." -ForegroundColor Blue

# Verificar se arquivo SQL é válido
$fileContent = Get-Content -Path $BackupFile -TotalCount 10 -ErrorAction SilentlyContinue

if ($fileContent -match "MySQL dump") {
    Write-Host "✓ Arquivo de backup é válido (MySQL dump)" -ForegroundColor Green
} else {
    Write-Host "✗ Arquivo de backup pode estar corrompido." -ForegroundColor Red
    $confirmation = Read-Host "Deseja continuar mesmo assim? (s/N)"
    if ($confirmation -ne "s" -and $confirmation -ne "S") {
        Write-Host "[CANCELADO] Operação cancelada." -ForegroundColor Yellow
        exit 0
    }
}

Write-Host ""
Write-Host "[4/5] Importando dados de produção para ambiente local..." -ForegroundColor Blue
Write-Host "  (Isso pode levar alguns minutos dependendo do tamanho)"

# Importar dados
try {
    Get-Content $BackupFile | docker exec -i $LOCAL_CONTAINER mysql `
        -u $LOCAL_USER `
        -p"$LOCAL_PASS" 2>&1 | Where-Object { $_ -notmatch "Using a password" }

    Write-Host "✓ Importação concluída com sucesso!" -ForegroundColor Green
} catch {
    Write-Host "✗ Erro durante importação: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "[RECUPERAÇÃO]" -ForegroundColor Yellow
    Write-Host "  Para restaurar os dados locais anteriores:"
    Write-Host "  Get-Content $LOCAL_BACKUP | docker exec -i $LOCAL_CONTAINER mysql -u $LOCAL_USER -p$LOCAL_PASS" -ForegroundColor Cyan
    exit 1
}

Write-Host ""
Write-Host "[5/5] Validando dados importados..." -ForegroundColor Blue

# Contar registros importados
$TABLES = @("users", "tabela_dados_tsi", "rmm_relatorios")

Write-Host ""
foreach ($table in $TABLES) {
    try {
        $COUNT = docker exec $LOCAL_CONTAINER mysql `
            -u $LOCAL_USER `
            -p"$LOCAL_PASS" `
            -D $LOCAL_DB `
            -e "SELECT COUNT(*) FROM $table;" `
            -sN 2>$null

        Write-Host "  - $table: $COUNT registros" -ForegroundColor Green
    } catch {
        Write-Host "  - $table: 0 registros" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "╔══════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  IMPORTAÇÃO CONCLUÍDA COM SUCESSO!                       ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""
Write-Host "✓ Dados de produção importados para ambiente local" -ForegroundColor Green
Write-Host "✓ Backup local salvo em: $LOCAL_BACKUP" -ForegroundColor Green
Write-Host ""
Write-Host "[PRÓXIMOS PASSOS]" -ForegroundColor Blue
Write-Host "  1. Acesse o sistema: http://localhost:8076" -ForegroundColor Yellow
Write-Host "  2. Faça login com: admin / admin123" -ForegroundColor Yellow
Write-Host "  3. Verifique se os dados estão corretos"
Write-Host ""
Write-Host "[IMPORTANTE]" -ForegroundColor Yellow
Write-Host "  - Este é um ambiente LOCAL de desenvolvimento"
Write-Host "  - Alterações aqui NÃO afetam produção"
Write-Host "  - Para restaurar dados locais anteriores:" -ForegroundColor Yellow
Write-Host "    Get-Content $LOCAL_BACKUP | docker exec -i $LOCAL_CONTAINER mysql -u $LOCAL_USER -p$LOCAL_PASS" -ForegroundColor Cyan
Write-Host ""
