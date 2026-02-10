# Guia Manual de Exportação de Dados de Produção

## Quando usar este guia

Use este guia se:
- Não conseguir conectividade direta com o servidor de produção
- Os scripts automáticos falharem
- Preferir fazer a exportação manualmente

---

## ⚠️ IMPORTANTE: Operação READ-ONLY

Este processo **NÃO modifica** nada em produção. Apenas **lê** os dados.

---

## Método 1: Via Servidor de Produção (Recomendado)

### Passo 1: Conectar ao Servidor de Produção

Conecte-se ao servidor onde está rodando o Docker Swarm de produção.

### Passo 2: Exportar Dados

Execute este comando **DENTRO do servidor de produção**:

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

**Explicação dos parâmetros:**
- `--single-transaction`: Garante consistência sem travar tabelas
- `--quick`: Não carrega toda a tabela na memória
- `--lock-tables=false`: Não trava tabelas (produção continua funcionando)
- `--skip-lock-tables`: Evita locks desnecessários

### Passo 3: Verificar Arquivo Criado

```bash
# Ver tamanho do arquivo
ls -lh production_backup_*.sql

# Ver primeiras linhas (verificar integridade)
head -20 production_backup_*.sql

# Contar linhas
wc -l production_backup_*.sql
```

### Passo 4: Transferir para Ambiente Local

**Opção A: Via SCP (Linux/Mac)**
```bash
scp usuario@servidor:/caminho/production_backup_*.sql ./backups/
```

**Opção B: Via WinSCP (Windows)**
1. Abra WinSCP
2. Conecte-se ao servidor
3. Navegue até o arquivo
4. Arraste para a pasta `backups/` local

**Opção C: Via FTP/SFTP**
Use seu cliente FTP preferido para baixar o arquivo.

### Passo 5: Importar no Ambiente Local

**Windows (PowerShell):**
```powershell
cd scripts
.\import_to_local.ps1 ..\backups\production_backup_*.sql
```

**Linux/Mac:**
```bash
cd scripts
./import_to_local.sh ../backups/production_backup_*.sql
```

---

## Método 2: Via phpMyAdmin de Produção

### Passo 1: Acessar phpMyAdmin de Produção

Acesse o phpMyAdmin da produção via navegador.

### Passo 2: Selecionar Banco de Dados

1. Clique em `dados_tripulantes_tsi` no menu lateral

### Passo 3: Exportar

1. Clique na aba **"Exportar"**
2. Selecione método: **"Rápido"**
3. Formato: **"SQL"**
4. Clique em **"Executar"**

### Passo 4: Salvar Arquivo

Salve o arquivo baixado em: `backups/production_backup_manual.sql`

### Passo 5: Importar no Ambiente Local

**Windows (PowerShell):**
```powershell
cd scripts
.\import_to_local.ps1 ..\backups\production_backup_manual.sql
```

---

## Método 3: Via MySQL Workbench

### Passo 1: Conectar ao Servidor

1. Abra MySQL Workbench
2. Crie nova conexão:
   - Host: `mysql_mysql` (ou IP do servidor)
   - Port: `3306`
   - Username: `somaxi`
   - Password: `S0m4x1@193`

### Passo 2: Exportar Banco

1. Vá em **Server → Data Export**
2. Selecione o banco: `dados_tripulantes_tsi`
3. Selecione todas as tabelas:
   - `users`
   - `tabela_dados_tsi`
   - `rmm_relatorios`
4. **Export Options:**
   - Marque: "Include Create Schema"
   - Marque: "Export to Self-Contained File"
   - Escolha local: `backups/production_backup_workbench.sql`
5. Clique em **"Start Export"**

### Passo 3: Importar no Ambiente Local

Use o script de importação conforme descrito nos métodos anteriores.

---

## Verificação de Integridade

Após exportar, verifique se o arquivo está correto:

### 1. Tamanho do Arquivo

```bash
# Windows (PowerShell)
(Get-Item backups\production_backup_*.sql).Length / 1MB

# Linux/Mac
du -h backups/production_backup_*.sql
```

Deve ter pelo menos alguns MB (dependendo da quantidade de dados).

### 2. Conteúdo do Arquivo

```bash
# Windows (PowerShell)
Get-Content backups\production_backup_*.sql -TotalCount 20

# Linux/Mac
head -20 backups/production_backup_*.sql
```

Deve começar com algo como:
```sql
-- MySQL dump 10.13  Distrib 8.0.43
--
-- Host: mysql_mysql    Database: dados_tripulantes_tsi
```

### 3. Tabelas Incluídas

```bash
# Windows (PowerShell)
Select-String "CREATE TABLE" backups\production_backup_*.sql

# Linux/Mac
grep "CREATE TABLE" backups/production_backup_*.sql
```

Deve listar as 3 tabelas principais.

---

## Troubleshooting

### Erro: "Access Denied"

**Solução:** Verifique as credenciais:
- Usuário: `somaxi`
- Senha: `S0m4x1@193`

### Erro: "Table doesn't exist"

**Solução:** O banco de produção pode ter tabelas diferentes. Liste as tabelas existentes:

```bash
docker exec mysql_mysql mysql -u somaxi -pS0m4x1@193 dados_tripulantes_tsi -e "SHOW TABLES;"
```

### Arquivo muito grande

**Solução:** Compactar antes de transferir:

```bash
# Comprimir
gzip production_backup_*.sql

# Transferir
scp production_backup_*.sql.gz local:/backups/

# Descomprimir localmente
gunzip backups/production_backup_*.sql.gz
```

### Importação muito lenta

**Solução:** Desabilitar verificações temporariamente:

Edite o arquivo SQL e adicione no início:
```sql
SET FOREIGN_KEY_CHECKS=0;
SET UNIQUE_CHECKS=0;
SET AUTOCOMMIT=0;
```

E no final:
```sql
COMMIT;
SET FOREIGN_KEY_CHECKS=1;
SET UNIQUE_CHECKS=1;
SET AUTOCOMMIT=1;
```

---

## Segurança

### ✅ O QUE É SEGURO:
- Fazer dump/exportação (apenas leitura)
- Transferir arquivo de backup
- Importar no ambiente local
- Repetir o processo quantas vezes necessário

### ❌ O QUE NÃO FAZER:
- **NÃO** execute comandos `INSERT`, `UPDATE`, `DELETE` em produção
- **NÃO** importe dados locais de volta para produção sem autorização
- **NÃO** altere dados em produção durante o processo
- **NÃO** compartilhe o arquivo de backup (contém dados sensíveis)

---

## Contato para Suporte

Se encontrar problemas:
1. Verifique os logs: `docker logs mysql_mysql`
2. Teste a conexão: `docker exec mysql_mysql mysqladmin ping -u somaxi -pS0m4x1@193`
3. Consulte este guia novamente
4. Entre em contato com o administrador do sistema

---

**Última Atualização:** 26/12/2025
**Versão do Guia:** 1.0
