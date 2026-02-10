# 📁 Scripts do Sistema TSI

Este diretório contém scripts para gerenciamento, manutenção e deploy do sistema.

---

## 🚀 Quick Start

### Primeira vez configurando o ambiente?

**Leia**: [PRODUCTION_WORKFLOW.md](PRODUCTION_WORKFLOW.md) - Guia completo do fluxo

**Passos rápidos:**

1. **No servidor de produção** - Descubra a estrutura:
   ```bash
   ./discover_production.sh
   ```

2. **No servidor de produção** - Exporte os dados:
   ```bash
   ./sync_with_production.sh <nome_do_container_mysql>
   ```

3. **No seu computador local** - Importe:
   ```powershell
   .\import_to_local.ps1 ..\backups\production_data_*.sql
   ```

---

## 📋 Scripts Disponíveis

### 🔍 Descoberta e Análise (Execute no SERVIDOR DE PRODUÇÃO)

| Script | Descrição | Uso |
|--------|-----------|-----|
| `discover_production.sh` | Analisa toda estrutura de produção (containers, redes, volumes) | `./discover_production.sh` |
| `sync_with_production.sh` | Exporta dados e configs de produção | `./sync_with_production.sh <container_mysql>` |

### 📥 Importação de Dados

#### Windows (PowerShell)

| Script | Onde Executar | Descrição | Uso |
|--------|---------------|-----------|-----|
| `export_production.ps1` | Produção | Exporta dados (READ-ONLY) | `.\export_production.ps1` |
| `import_to_local.ps1` | Local | Importa para ambiente local | `.\import_to_local.ps1 <arquivo.sql>` |

#### Linux/Mac (Bash)

| Script | Onde Executar | Descrição | Uso |
|--------|---------------|-----------|-----|
| `export_production.sh` | Produção | Exporta dados (READ-ONLY) | `./export_production.sh` |
| `import_to_local.sh` | Local | Importa para ambiente local | `./import_to_local.sh <arquivo.sql>` |

### ✅ Validação e Deploy

| Script | Onde Executar | Descrição | Uso |
|--------|---------------|-----------|-----|
| `prepare_deploy.sh` | Local | Valida ambiente local antes de deploy | `./prepare_deploy.sh` |

---

## 📚 Documentação Completa

| Arquivo | Descrição | Quando Ler |
|---------|-----------|------------|
| **[PRODUCTION_WORKFLOW.md](PRODUCTION_WORKFLOW.md)** | **Workflow completo**: Descoberta → Deploy | ⭐ **Leia primeiro!** |
| [README_IMPORT_PRODUCTION.md](README_IMPORT_PRODUCTION.md) | Guia completo de importação de dados | Para importar dados |
| [MANUAL_EXPORT_GUIDE.md](MANUAL_EXPORT_GUIDE.md) | Guia passo a passo para exportação manual | Se scripts automáticos falharem |

---

## 🎯 Cenários Comuns

### Cenário 1: Primeira vez (Configurar ambiente local)

**Você precisa:**
1. Descobrir estrutura de produção
2. Exportar dados
3. Importar localmente

**Siga**: [PRODUCTION_WORKFLOW.md](PRODUCTION_WORKFLOW.md) - Seção "Fase 1 e 2"

### Cenário 2: Atualizar dados locais com produção

**Você já tem ambiente local funcionando e quer atualizar dados:**

```bash
# 1. No servidor de produção
./sync_with_production.sh <container_mysql>

# 2. Transferir arquivo para local

# 3. No ambiente local
.\import_to_local.ps1 ..\backups\production_data_*.sql
```

### Cenário 3: Fazer deploy de alterações para produção

**Você fez alterações locais e quer subir para produção:**

```bash
# 1. No ambiente local - Validar
./prepare_deploy.sh

# 2. Seguir processo de deploy
# Leia: PRODUCTION_WORKFLOW.md - Seção "Fase 5"
```

---

## ⚠️ IMPORTANTE: Segurança

### ✅ Operações SEGURAS (não afetam produção):
- ✅ Executar `discover_production.sh` (apenas lê)
- ✅ Executar `sync_with_production.sh` (apenas lê)
- ✅ Executar `export_production.*` (apenas lê)
- ✅ Executar `import_to_local.*` (apenas no local)
- ✅ Fazer dump de produção (operação READ-ONLY)

### ❌ NÃO FAÇA:
- ❌ Importar dados locais de volta para produção sem backup
- ❌ Executar comandos DELETE/UPDATE em produção sem backup
- ❌ Modificar produção durante horário de pico
- ❌ Deploy sem testar localmente primeiro

---

## 📊 Estrutura de Backups

```
backups/
├── production_analysis/              # Relatórios de descoberta
│   └── production_structure_*.txt
├── production_schema_*.sql           # Schema de produção
├── production_data_*.sql             # Dados de produção
├── production_container_config_*.json # Config dos containers
├── local_backup_before_import_*.sql  # Backup local automático
└── .gitkeep
```

**Nota:** Arquivos `.sql` estão no `.gitignore` e não serão commitados.

---

## 🔧 Troubleshooting

### Container MySQL não encontrado

**Erro:**
```
Error response from daemon: No such container: mysql_mysql
```

**Solução:**
```bash
# Execute o script de descoberta
./discover_production.sh

# Procure o container MySQL correto
cat production_analysis/production_structure_*.txt | grep -i mysql
```

### Erro: "Comando não encontrado" (PowerShell)

**Solução:**
```powershell
# Verificar política de execução
Get-ExecutionPolicy

# Se necessário, permitir execução de scripts
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Erro: "Permission denied" (Linux/Mac)

**Solução:**
```bash
# Tornar scripts executáveis
chmod +x *.sh

# Executar
./discover_production.sh
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

---

## 🎓 Comandos Úteis de Referência

### No Servidor de Produção

```bash
# Descobrir estrutura completa
./discover_production.sh

# Exportar dados
./sync_with_production.sh <container_mysql>

# Listar containers
docker ps

# Ver logs
docker logs -f <container_name>

# Backup manual
docker exec <mysql_container> mysqldump -u somaxi -pS0m4x1@193 dados_tripulantes_tsi > backup.sql
```

### No Ambiente Local

```powershell
# Importar dados
.\import_to_local.ps1 ..\backups\production_data_*.sql

# Validar antes de deploy
.\prepare_deploy.sh

# Ver containers
docker ps

# Ver logs
docker logs -f tsi_app

# Testar sistema
curl http://localhost:8076
```

---

## 📞 Suporte

Para mais informações:

1. **Workflow Completo**: [PRODUCTION_WORKFLOW.md](PRODUCTION_WORKFLOW.md)
2. **Importação de Dados**: [README_IMPORT_PRODUCTION.md](README_IMPORT_PRODUCTION.md)
3. **Exportação Manual**: [MANUAL_EXPORT_GUIDE.md](MANUAL_EXPORT_GUIDE.md)
4. **Verificar logs**: `docker logs <container_name>`

---

## 📈 Fluxo Visual

```
PRODUÇÃO              →     LOCAL              →     PRODUÇÃO
(Servidor)                  (Seu PC)                 (Deploy)

1. discover_production.sh   4. import_to_local   7. prepare_deploy
2. sync_with_production     5. Desenvolver       8. Deploy
3. Transferir arquivos      6. Testar            9. Validar
```

---

**Última Atualização:** 26/12/2025
**Versão:** 2.0
**Status:** ✅ Pronto para uso com workflow completo
