# 🚀 Workflow Completo: Descoberta → Sincronização → Deploy

## Visão Geral do Processo

Este guia cobre o fluxo completo desde descobrir a estrutura de produção até fazer deploy de alterações.

```
┌─────────────────────────────────────────────────────────────┐
│                    FLUXO COMPLETO                           │
└─────────────────────────────────────────────────────────────┘

1. DESCOBERTA          →  Analisa produção e encontra containers
2. SINCRONIZAÇÃO       →  Exporta dados e config de produção
3. DESENVOLVIMENTO     →  Trabalha localmente (espelho de prod)
4. VALIDAÇÃO           →  Testa tudo localmente
5. DEPLOY              →  Sobe alterações para produção
```

---

## 📋 Fase 1: Descoberta da Estrutura de Produção

### Objetivo
Entender completamente como está configurada a produção.

### Passo 1.1: Conectar ao Servidor de Produção

```bash
ssh jonaspacheco@SomaxiAutomacoes
```

### Passo 1.2: Transferir Script de Descoberta

**Opção A: Copiar manualmente**
```bash
# No servidor de produção, crie o arquivo
nano discover_production.sh

# Cole o conteúdo do script
# Ctrl+X para salvar

# Tornar executável
chmod +x discover_production.sh
```

**Opção B: Via SCP**
```bash
# Do seu computador local
scp scripts/discover_production.sh jonaspacheco@SomaxiAutomacoes:~/
```

### Passo 1.3: Executar Análise

```bash
# No servidor de produção
./discover_production.sh
```

**Saída esperada:**
```
╔══════════════════════════════════════════════════════════╗
║  DESCOBERTA DE ESTRUTURA DE PRODUÇÃO                     ║
╚══════════════════════════════════════════════════════════╝

[1/10] Verificando tipo de ambiente Docker...
✓ Ambiente: Docker Swarm (Produção)

[2/10] Listando containers em execução...
✓ Containers analisados

...

✓ Relatório salvo em: production_analysis/production_structure_XXXXXXXX.txt
```

### Passo 1.4: Analisar Relatório

```bash
# Visualizar relatório completo
cat production_analysis/production_structure_*.txt

# Ou ver apenas containers MySQL
cat production_analysis/production_structure_*.txt | grep -A 20 "MYSQL"
```

**O que procurar no relatório:**
- Nome do container MySQL (ex: `stack_db_1`, `tsi-mysql`, etc.)
- Portas expostas
- Variáveis de ambiente
- Volumes montados
- Network configurada

### Passo 1.5: Identificar Container MySQL Correto

Procure por:
```
CONTAINERS MYSQL ENCONTRADOS:
  stack_db_1        ← Este é o nome que você precisa!
```

---

## 📥 Fase 2: Sincronização (Produção → Local)

### Objetivo
Exportar dados e configurações de produção para ter um espelho local.

### Passo 2.1: Executar Script de Sincronização

**No servidor de produção:**

```bash
# Copiar script de sincronização
nano sync_with_production.sh
# (Cole o conteúdo e salve)

chmod +x sync_with_production.sh

# Executar com o nome do container correto
./sync_with_production.sh <NOME_DO_CONTAINER_MYSQL>

# Exemplo:
./sync_with_production.sh stack_db_1
```

**O script irá:**
1. Testar conexão com MySQL
2. Listar bancos de dados
3. Exportar schema (estrutura)
4. Exportar dados completos
5. Exportar configuração do container

**Saída esperada:**
```
[1/6] Testando conexão com banco de produção...
✓ Conexão OK!

[2/6] Listando bancos de dados em produção...
...

[6/6] Analisando configuração do container...
✓ Configuração salva

╔══════════════════════════════════════════════════════════╗
║  SINCRONIZAÇÃO CONCLUÍDA                                 ║
╚══════════════════════════════════════════════════════════╝

✓ Arquivos exportados:
  - Schema: backups/production_schema_XXXXXXXX.sql
  - Dados:  backups/production_data_XXXXXXXX.sql (5.2M)
  - Config: backups/production_container_config_XXXXXXXX.json
```

### Passo 2.2: Transferir Arquivos para Local

```bash
# Do servidor de produção para seu computador
# (Use WinSCP, FileZilla ou SCP)

# Via SCP (Linux/Mac):
scp ~/backups/production_*.sql local:~/Desktop/Projects/tsi_sistema/backups/
scp ~/backups/production_*.json local:~/Desktop/Projects/tsi_sistema/backups/
```

### Passo 2.3: Importar no Ambiente Local

**No seu computador local:**

```powershell
cd c:\Users\Home\Desktop\Projects\tsi_sistema\scripts

# Importar dados de produção
.\import_to_local.ps1 ..\backups\production_data_XXXXXXXX.sql
```

---

## 💻 Fase 3: Desenvolvimento Local

### Objetivo
Trabalhar localmente com dados reais de produção.

### Ambiente Local Ativo

Após a importação, você tem:

- ✅ Estrutura idêntica à produção
- ✅ Dados reais de produção
- ✅ Configuração espelhada
- ✅ Isolamento total (alterações não afetam produção)

### Fazer Alterações

```powershell
# 1. Containers rodando
docker ps

# 2. Fazer alterações nos arquivos
# Edite PHP, CSS, JS, etc.

# 3. Testar localmente
http://localhost:8076

# 4. Ver logs
docker logs -f tsi_app
docker logs -f tsi_mysql
```

### Commits Git

```bash
# Versionar alterações
git add .
git commit -m "Descrição da alteração"
git push
```

---

## ✅ Fase 4: Validação Pré-Deploy

### Objetivo
Garantir que está tudo OK antes de subir para produção.

### Passo 4.1: Executar Validação

```bash
cd scripts
./prepare_deploy.sh
```

**O script verifica:**
- ✅ Ambiente é local (não produção)
- ✅ Arquivos essenciais existem
- ✅ Docker Compose está válido
- ✅ Containers locais rodando
- ✅ Banco de dados acessível
- ✅ Sistema responde HTTP 200
- ✅ Sem erros críticos

**Saída esperada:**
```
╔══════════════════════════════════════════════════════════╗
║  RESULTADO DA VALIDAÇÃO                                  ║
╚══════════════════════════════════════════════════════════╝

✓ TUDO OK! Ambiente está pronto para deploy.

═══════════════════════════════════════════════════════════
CHECKLIST PARA DEPLOY
═══════════════════════════════════════════════════════════

Antes de fazer deploy em produção:
...
```

### Passo 4.2: Checklist Manual

Antes de fazer deploy, confirme:

- [ ] ✅ Sistema testado localmente e funcionando
- [ ] ✅ Login funciona
- [ ] ✅ Dashboard carrega
- [ ] ✅ Formulários funcionam
- [ ] ✅ Relatórios são gerados
- [ ] ✅ Sem erros no console do navegador
- [ ] ✅ Sem erros nos logs do Docker
- [ ] ✅ Backup de produção foi feito

---

## 🚀 Fase 5: Deploy para Produção

### ⚠️ IMPORTANTE: Backup Primeiro!

Antes de qualquer deploy, SEMPRE faça backup:

```bash
# No servidor de produção
./sync_with_production.sh <container_mysql>

# Isso cria backup automático
# Guarde este backup em local seguro!
```

### Método 1: Deploy Manual (Recomendado para primeira vez)

#### Passo 5.1: Backup de Produção

```bash
# No servidor de produção
docker exec <container_mysql> mysqldump -u somaxi -pS0m4x1@193 dados_tripulantes_tsi > backup_before_deploy_$(date +%Y%m%d_%H%M%S).sql
```

#### Passo 5.2: Copiar Arquivos Modificados

```bash
# Do local para produção
scp index.php jonaspacheco@SomaxiAutomacoes:~/tsi_sistema/
scp includes/db.php jonaspacheco@SomaxiAutomacoes:~/tsi_sistema/includes/
# ... copie todos os arquivos modificados
```

#### Passo 5.3: Atualizar Container em Produção

**Se usando Docker Swarm:**
```bash
# No servidor de produção
docker stack deploy -c docker-compose.yml tsi

# Ou atualizar serviço específico
docker service update --force tsi_app
```

**Se usando Docker Compose:**
```bash
# No servidor de produção
cd ~/tsi_sistema
docker-compose up -d --build
```

#### Passo 5.4: Verificar Deploy

```bash
# Ver logs
docker service logs -f tsi_app  # (Swarm)
# ou
docker-compose logs -f          # (Compose)

# Verificar status
docker service ls               # (Swarm)
# ou
docker-compose ps               # (Compose)
```

### Método 2: Deploy com Git (Recomendado para updates frequentes)

#### Configurar no Servidor de Produção:

```bash
# No servidor de produção
cd ~/tsi_sistema

# Inicializar Git (se não tiver)
git init
git remote add origin <URL_DO_REPOSITORIO>

# Pull das alterações
git pull origin main

# Rebuild containers
docker-compose up -d --build
```

---

## 🔄 Workflow Completo (Resumo Visual)

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  PRODUÇÃO         ──────────→      LOCAL                    │
│  (Servidor)       ←──────────   (Seu PC)                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘

1. DESCOBERTA (no servidor de produção):
   $ ./discover_production.sh
   → Identifica: container MySQL, portas, configs

2. SYNC (no servidor de produção):
   $ ./sync_with_production.sh <container_mysql>
   → Exporta: dados, schema, configs

3. TRANSFERIR (do servidor para local):
   $ scp backups/* local:/backups/
   → Copia: arquivos SQL e JSON

4. IMPORTAR (no local):
   $ ./import_to_local.ps1 backups/production_data_*.sql
   → Importa: dados de produção no local

5. DESENVOLVER (no local):
   - Fazer alterações
   - Testar localmente
   - Commitar no Git

6. VALIDAR (no local):
   $ ./prepare_deploy.sh
   → Verifica: tudo OK antes de deploy

7. DEPLOY (no servidor de produção):
   - Fazer backup
   - Copiar arquivos
   - Rebuild containers
   - Verificar funcionamento

8. MONITORAR (pós-deploy):
   - Ver logs
   - Testar sistema
   - Confirmar funcionamento
```

---

## 📝 Comandos Rápidos de Referência

### No Servidor de Produção

```bash
# Descobrir estrutura
./discover_production.sh

# Sincronizar/exportar
./sync_with_production.sh <container_mysql>

# Listar containers
docker ps

# Logs de container
docker logs -f <container_name>

# Entrar no container
docker exec -it <container_name> bash

# Backup manual de banco
docker exec <mysql_container> mysqldump -u somaxi -pS0m4x1@193 dados_tripulantes_tsi > backup.sql
```

### No Ambiente Local

```powershell
# Importar dados
.\import_to_local.ps1 ..\backups\production_data_*.sql

# Validar pré-deploy
.\prepare_deploy.sh

# Ver containers
docker ps

# Ver logs
docker logs -f tsi_app

# Testar sistema
curl http://localhost:8076
```

---

## 🆘 Troubleshooting

### Container MySQL não encontrado

**Solução:**
```bash
# Listar todos os containers
docker ps -a

# Procurar por MySQL
docker ps | grep -i mysql
```

### Erro de permissão ao executar scripts

**Solução:**
```bash
chmod +x *.sh
```

### Erro ao importar dados

**Solução:**
```bash
# Verificar se container está rodando
docker ps | grep tsi_mysql

# Ver logs do MySQL
docker logs tsi_mysql

# Testar conexão manual
docker exec -it tsi_mysql mysql -u somaxi -pS0m4x1@193
```

### Deploy não funcionou

**Rollback:**
```bash
# No servidor de produção

# Restaurar backup
docker exec -i <mysql_container> mysql -u somaxi -pS0m4x1@193 dados_tripulantes_tsi < backup_before_deploy_*.sql

# Reverter código (se usando Git)
git reset --hard HEAD~1

# Rebuild
docker-compose up -d --build
```

---

## 📞 Suporte

Se encontrar problemas:

1. **Verifique os logs:**
   ```bash
   docker logs <container_name> --tail 100
   ```

2. **Verifique a documentação completa:**
   - `README_IMPORT_PRODUCTION.md`
   - `MANUAL_EXPORT_GUIDE.md`

3. **Execute os scripts de diagnóstico:**
   ```bash
   ./discover_production.sh
   ./prepare_deploy.sh
   ```

---

**Última Atualização:** 26/12/2025
**Versão:** 1.0
**Status:** ✅ Pronto para uso
