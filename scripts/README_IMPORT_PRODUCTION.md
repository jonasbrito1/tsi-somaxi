# 📥 Importação de Dados de Produção - Guia Completo

## Visão Geral

Este guia explica como importar dados de produção para o ambiente local de forma **100% segura**, sem qualquer risco de alterar ou interromper a produção.

---

## ⚠️ GARANTIAS DE SEGURANÇA

### ✅ O que este processo FAZ:
- ✅ Lê dados de produção (READ-ONLY)
- ✅ Cria backup dos dados locais antes de importar
- ✅ Importa dados apenas no ambiente LOCAL
- ✅ Permite reverter importação a qualquer momento
- ✅ Mantém produção totalmente inalterada

### ❌ O que este processo NÃO FAZ:
- ❌ NÃO modifica dados de produção
- ❌ NÃO trava tabelas de produção
- ❌ NÃO interrompe serviço de produção
- ❌ NÃO altera configurações de produção

---

## 📋 Pré-requisitos

Antes de começar, certifique-se de que:

1. ✅ Ambiente local está rodando (`docker ps` deve mostrar `tsi_app`, `tsi_mysql`, `tsi_phpmyadmin`)
2. ✅ Você tem acesso ao servidor de produção (se for usar exportação automática)
3. ✅ Há espaço em disco suficiente (pelo menos 2x o tamanho do banco)

Para verificar se o ambiente local está OK:
```powershell
docker ps --filter "name=tsi"
```

Se não estiver rodando:
```powershell
docker-compose up -d
```

---

## 🚀 Método 1: Exportação Manual (RECOMENDADO)

**Use este método se:**
- Não tem conectividade direta com produção
- Quer ter controle total do processo
- É sua primeira vez fazendo isso

### Passo 1: Acessar Servidor de Produção

Conecte-se ao servidor onde está rodando o Docker Swarm de produção (via SSH, RDP, etc.)

### Passo 2: Exportar Dados (NO SERVIDOR DE PRODUÇÃO)

Execute este comando **dentro do servidor de produção**:

```bash
docker exec mysql_mysql mysqldump \
  -u somaxi \
  -pS0m4x1@193 \
  --single-transaction \
  --quick \
  --lock-tables=false \
  --skip-lock-tables \
  --databases dados_tripulantes_tsi \
  --add-drop-database \
  --add-drop-table \
  --routines \
  --triggers \
  --events \
  --set-gtid-purged=OFF \
  > production_backup_$(date +%Y%m%d_%H%M%S).sql
```

**⏱️ Tempo estimado:** 1-5 minutos (dependendo do tamanho do banco)

### Passo 3: Verificar Arquivo Criado

```bash
# Ver tamanho e confirmar criação
ls -lh production_backup_*.sql
```

### Passo 4: Transferir para Ambiente Local

**Opção A - WinSCP (Windows):**
1. Abra WinSCP
2. Conecte ao servidor de produção
3. Navegue até `production_backup_*.sql`
4. Arraste para: `c:\Users\Home\Desktop\Projects\tsi_sistema\backups\`

**Opção B - SCP (Linux/Mac):**
```bash
scp usuario@servidor-producao:/caminho/production_backup_*.sql ./backups/
```

### Passo 5: Importar Localmente (NO SEU COMPUTADOR LOCAL)

Abra PowerShell e execute:

```powershell
cd c:\Users\Home\Desktop\Projects\tsi_sistema\scripts

# Executar importação
.\import_to_local.ps1 ..\backups\production_backup_*.sql
```

**⏱️ Tempo estimado:** 2-10 minutos

---

## 🔄 Método 2: Exportação Automática

**Use este método se:**
- Tem conectividade de rede com o servidor de produção
- O container `mysql_mysql` é acessível pela rede

### Executar Script Completo

```powershell
cd c:\Users\Home\Desktop\Projects\tsi_sistema\scripts

# Exportar de produção
.\export_production.ps1

# Se sucesso, importar
.\import_to_local.ps1
```

**⚠️ Nota:** Se houver erro de conexão, use o Método 1 (Manual).

---

## 🌐 Método 3: Via phpMyAdmin

**Use este método se:**
- Tem acesso ao phpMyAdmin de produção via navegador
- Prefere interface gráfica

### Passo 1: Acessar phpMyAdmin de Produção

Acesse via navegador (URL fornecida pelo administrador)

### Passo 2: Exportar Banco

1. Selecione `dados_tripulantes_tsi` no menu lateral
2. Clique na aba **"Exportar"**
3. Método: **Personalizado**
4. Configurações:
   - ✅ Marque todas as tabelas
   - ✅ Formato: SQL
   - ✅ Estrutura: `CREATE TABLE`
   - ✅ Dados: `INSERT`
5. Clique em **"Executar"**
6. Salve como: `backups\production_backup_phpmyadmin.sql`

### Passo 3: Importar Localmente

```powershell
cd scripts
.\import_to_local.ps1 ..\backups\production_backup_phpmyadmin.sql
```

---

## 📊 Verificação Pós-Importação

### 1. Verificar Dados Importados

Acesse o phpMyAdmin local: http://localhost:8098

**Credenciais:**
- Servidor: `db`
- Usuário: `somaxi`
- Senha: `S0m4x1@193`

### 2. Contar Registros

```powershell
docker exec tsi_mysql mysql -u somaxi -pS0m4x1@193 dados_tripulantes_tsi -e "
SELECT
  'users' as tabela, COUNT(*) as registros FROM users
UNION ALL
SELECT 'tabela_dados_tsi', COUNT(*) FROM tabela_dados_tsi
UNION ALL
SELECT 'rmm_relatorios', COUNT(*) FROM rmm_relatorios;
"
```

### 3. Testar Sistema

1. Acesse: http://localhost:8076/login.php
2. Faça login com credenciais de produção
3. Verifique se os dados aparecem corretamente no dashboard

---

## 🔙 Reverter Importação

Se algo der errado, você pode reverter facilmente:

### Opção 1: Usar Backup Automático

O script de importação cria automaticamente um backup antes de importar. Para reverter:

```powershell
# Listar backups disponíveis
Get-ChildItem ..\backups\local_backup_before_import_*.sql | Sort-Object LastWriteTime

# Restaurar (use o arquivo mais recente)
Get-Content ..\backups\local_backup_before_import_XXXXXXXX_XXXXXX.sql | `
  docker exec -i tsi_mysql mysql -u somaxi -pS0m4x1@193
```

### Opção 2: Reiniciar do Zero

```powershell
# Parar containers
docker-compose down -v

# Reiniciar (vai criar banco vazio novamente)
docker-compose up -d
```

---

## 🔧 Troubleshooting

### Erro: "Container não está rodando"

**Solução:**
```powershell
docker-compose up -d
docker ps
```

### Erro: "Arquivo não encontrado"

**Solução:** Verifique o caminho completo:
```powershell
Get-ChildItem ..\backups\*.sql
```

### Importação muito lenta

**Solução:** É normal para bancos grandes. Aguarde a conclusão.

Para acompanhar o progresso:
```powershell
# Em outro terminal
docker stats tsi_mysql
```

### Erro: "Access Denied"

**Solução:** Verifique se as credenciais estão corretas no arquivo `.env`

```powershell
cat ..\.env | Select-String "DB_"
```

### Erro: "Table already exists"

**Solução:** O script está configurado com `--add-drop-table`, mas se persistir:

```powershell
# Limpar banco antes
docker exec tsi_mysql mysql -u somaxi -pS0m4x1@193 -e "DROP DATABASE IF EXISTS dados_tripulantes_tsi; CREATE DATABASE dados_tripulantes_tsi;"

# Importar novamente
.\import_to_local.ps1 ..\backups\production_backup_*.sql
```

---

## 📝 Boas Práticas

### 1. Fazer Backups Regulares

Crie um agendamento para exportar dados de produção periodicamente:

```powershell
# No servidor de produção, adicione ao cron
# 0 2 * * * docker exec mysql_mysql mysqldump ... > backup_$(date +\%Y\%m\%d).sql
```

### 2. Manter Backups Organizados

```
backups/
├── production_backup_20251226_120000.sql  (Produção de hoje)
├── production_backup_20251225_120000.sql  (Produção de ontem)
├── local_backup_before_import_...sql      (Backups locais)
└── manual_backups/                        (Backups manuais)
```

### 3. Testar Antes de Usar

Sempre teste a importação em ambiente de desenvolvimento antes de aplicar em staging.

### 4. Documentar Mudanças

Mantenha um log de quando fez importações:

```powershell
# Adicionar ao arquivo backups/import_log.txt
Add-Content ..\backups\import_log.txt "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - Importação de produção realizada"
```

---

## 🔐 Segurança

### Proteção de Dados Sensíveis

Os arquivos de backup contêm **dados sensíveis**:

1. ✅ Nunca commite backups no Git
2. ✅ Não compartilhe backups publicamente
3. ✅ Delete backups antigos regularmente
4. ✅ Use criptografia se for transferir pela internet

### Limpeza de Backups Antigos

```powershell
# Manter apenas últimos 7 dias
Get-ChildItem ..\backups\*.sql |
  Where-Object {$_.LastWriteTime -lt (Get-Date).AddDays(-7)} |
  Remove-Item -Confirm
```

---

## 📞 Suporte

### Logs de Erro

Se encontrar problemas, verifique os logs:

```powershell
# Logs do MySQL
docker logs tsi_mysql --tail 50

# Logs da aplicação
docker logs tsi_app --tail 50
```

### Checklist de Verificação

Antes de reportar problema, verifique:

- [ ] Containers estão rodando?
- [ ] Arquivo de backup existe e não está vazio?
- [ ] Credenciais estão corretas no `.env`?
- [ ] Há espaço em disco suficiente?
- [ ] Você está executando no diretório correto?

---

## 🎯 Resumo Rápido

**Para importar dados de produção:**

1. **No servidor de produção:** Exportar dados
   ```bash
   docker exec mysql_mysql mysqldump -u somaxi -pS0m4x1@193 --single-transaction dados_tripulantes_tsi > backup.sql
   ```

2. **Transferir** arquivo para: `c:\Users\Home\Desktop\Projects\tsi_sistema\backups\`

3. **No ambiente local:** Importar
   ```powershell
   cd scripts
   .\import_to_local.ps1 ..\backups\backup.sql
   ```

4. **Verificar:** http://localhost:8076

**Tempo total estimado:** 10-20 minutos

---

**Última Atualização:** 26/12/2025
**Versão:** 1.0
**Status:** ✅ Pronto para uso
