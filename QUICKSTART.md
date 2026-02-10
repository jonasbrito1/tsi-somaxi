# ⚡ Quick Start - Sistema TSI Local

Guia rápido para começar a trabalhar com o sistema TSI localmente.

---

## 🎯 Objetivo

Ter o sistema TSI rodando localmente com dados de produção em **menos de 30 minutos**.

---

## 📋 Pré-requisitos

- ✅ Docker Desktop instalado e rodando
- ✅ Acesso ao servidor de produção (SSH)
- ✅ Este repositório clonado localmente

---

## 🚀 Passo a Passo (Primeira Vez)

### 1. Descobrir Estrutura de Produção

**No servidor de produção** (via SSH):

```bash
# Conectar
ssh jonaspacheco@SomaxiAutomacoes

# Copiar e executar script de descoberta
# (Cole o conteúdo de scripts/discover_production.sh)
nano discover_production.sh
chmod +x discover_production.sh
./discover_production.sh

# Encontre o nome do container MySQL
# Procure por algo como: stack_db_1, tsi-mysql, etc.
cat production_analysis/production_structure_*.txt | grep -i mysql
```

**Anote o nome do container MySQL!** Exemplo: `stack_db_1`

### 2. Exportar Dados de Produção

**Ainda no servidor de produção:**

```bash
# Copiar script de sincronização
nano sync_with_production.sh
# (Cole o conteúdo de scripts/sync_with_production.sh)

chmod +x sync_with_production.sh

# Executar com o nome do container MySQL
./sync_with_production.sh stack_db_1
# ↑ Use o nome que você encontrou no passo 1

# Isso cria:
# - backups/production_schema_*.sql
# - backups/production_data_*.sql
# - backups/production_container_config_*.json
```

### 3. Transferir Arquivos para Local

**Do servidor para seu computador:**

```bash
# Via WinSCP (Windows):
# 1. Abra WinSCP
# 2. Conecte ao servidor
# 3. Arraste backups/*.sql para c:\Users\Home\Desktop\Projects\tsi_sistema\backups\

# Ou via SCP (Linux/Mac):
scp jonaspacheco@SomaxiAutomacoes:~/backups/production_*.sql ./backups/
```

### 4. Iniciar Ambiente Local

**No seu computador local** (PowerShell):

```powershell
cd c:\Users\Home\Desktop\Projects\tsi_sistema

# Parar containers antigos (se houver)
docker-compose down -v

# Iniciar containers
docker-compose up -d

# Aguardar containers ficarem prontos (30-60 segundos)
Start-Sleep -Seconds 60

# Verificar status
docker ps
```

Deve mostrar:
- `tsi_app` (Running)
- `tsi_mysql` (Running - Healthy)
- `tsi_phpmyadmin` (Running)

### 5. Importar Dados de Produção

```powershell
cd scripts

# Importar dados
.\import_to_local.ps1 ..\backups\production_data_*.sql

# O script irá:
# - Fazer backup dos dados locais atuais
# - Importar dados de produção
# - Validar importação
```

### 6. Acessar o Sistema

Abra no navegador:

- **Sistema**: http://localhost:8076
- **Login**: Use credenciais de produção (ou admin/admin123)
- **phpMyAdmin**: http://localhost:8098

---

## 🔄 Workflow Diário

### Atualizar Dados Locais com Produção

```bash
# 1. No servidor de produção
./sync_with_production.sh stack_db_1

# 2. Transferir arquivo novo

# 3. No local
.\import_to_local.ps1 ..\backups\production_data_*.sql
```

### Trabalhar Localmente

```powershell
# Ver logs
docker logs -f tsi_app

# Reiniciar containers
docker-compose restart

# Parar tudo
docker-compose down

# Iniciar tudo
docker-compose up -d
```

### Fazer Alterações

1. Edite arquivos (PHP, CSS, JS)
2. Teste: http://localhost:8076
3. Commit:
   ```bash
   git add .
   git commit -m "Descrição"
   git push
   ```

---

## ✅ Validar Antes de Deploy

```bash
cd scripts
./prepare_deploy.sh
```

Se tudo OK:
```
✓ TUDO OK! Ambiente está pronto para deploy.
```

---

## 🆘 Problemas Comuns

### Containers não iniciam

```powershell
# Ver erros
docker-compose logs

# Reconstruir
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### Não consegue conectar ao banco

```powershell
# Verificar se MySQL está rodando
docker ps | findstr mysql

# Ver logs do MySQL
docker logs tsi_mysql

# Testar conexão
docker exec tsi_mysql mysqladmin ping -u somaxi -pS0m4x1@193
```

### Sistema retorna erro 500

```powershell
# Ver logs do PHP
docker logs tsi_app --tail 50

# Ver logs de erro do PHP
docker exec tsi_app cat /var/www/html/php_errors.log
```

### Porta já em uso

Edite `.env`:
```env
APP_PORT=8077     # Era 8076
MYSQL_PORT=3358   # Era 3357
```

Reinicie:
```powershell
docker-compose down
docker-compose up -d
```

---

## 📚 Documentação Completa

Para entender o processo completo:

- **Workflow Completo**: [scripts/PRODUCTION_WORKFLOW.md](scripts/PRODUCTION_WORKFLOW.md)
- **Importação de Dados**: [scripts/README_IMPORT_PRODUCTION.md](scripts/README_IMPORT_PRODUCTION.md)
- **Exportação Manual**: [scripts/MANUAL_EXPORT_GUIDE.md](scripts/MANUAL_EXPORT_GUIDE.md)

---

## 🎓 Comandos Úteis

```powershell
# Docker
docker ps                              # Listar containers
docker logs -f <nome>                  # Ver logs em tempo real
docker exec -it <nome> bash            # Entrar no container
docker-compose restart                 # Reiniciar tudo
docker system prune -a                 # Limpar tudo (CUIDADO)

# MySQL
docker exec tsi_mysql mysql -u somaxi -pS0m4x1@193 dados_tripulantes_tsi

# Ver bancos de dados
docker exec tsi_mysql mysql -u somaxi -pS0m4x1@193 -e "SHOW DATABASES;"

# Ver tabelas
docker exec tsi_mysql mysql -u somaxi -pS0m4x1@193 dados_tripulantes_tsi -e "SHOW TABLES;"

# Backup manual
docker exec tsi_mysql mysqldump -u somaxi -pS0m4x1@193 dados_tripulantes_tsi > backup.sql
```

---

## ✨ Próximos Passos

Agora que o sistema está rodando localmente:

1. ✅ Explore o dashboard
2. ✅ Teste todas as funcionalidades
3. ✅ Faça pequenas alterações e teste
4. ✅ Leia a documentação completa
5. ✅ Configure seu ambiente de desenvolvimento favorito

**Dúvidas?** Consulte [scripts/PRODUCTION_WORKFLOW.md](scripts/PRODUCTION_WORKFLOW.md)

---

**Tempo estimado total:** 20-30 minutos

**Última atualização:** 26/12/2025
