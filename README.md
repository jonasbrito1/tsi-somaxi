# Sistema TSI - SOMAXI GROUP
## Dashboard de Monitoramento e Gestão de Segurança

![Status](https://img.shields.io/badge/status-prod ready-success)
![PHP](https://img.shields.io/badge/PHP-8.1-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)
![Docker](https://img.shields.io/badge/Docker-Enabled-blue)

---

## Índice
- [Sobre o Sistema](#sobre-o-sistema)
- [Requisitos](#requisitos)
- [Instalação e Configuração Local](#instalação-e-configuração-local)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Uso](#uso)
- [Ambientes](#ambientes)
- [Banco de Dados](#banco-de-dados)
- [Troubleshooting](#troubleshooting)

---

## Sobre o Sistema

Sistema de monitoramento e gestão de segurança desenvolvido para SOMAXI Group. Oferece:

- Dashboard interativo com KPIs em tempo real
- Monitoramento de dispositivos (desktops, notebooks, servidores)
- Gestão de ameaças e alertas de segurança
- Análise de coberturas (antivírus, patches, web protection)
- Relatórios executivos e gráficos
- Sistema de backup e chamados
- Extração inteligente de dados de PDFs

---

## Requisitos

### Software Necessário
- **Docker Desktop** >= 20.10
- **Docker Compose** >= 2.0
- **Git** (para controle de versão)

### Portas Utilizadas
- `8076` - Aplicação PHP (Apache)
- `3357` - MySQL
- `8098` - phpMyAdmin

---

## Instalação e Configuração Local

### 1. Clonar o Repositório
```bash
git clone <url-do-repositorio>
cd tsi_sistema
```

### 2. Configurar Variáveis de Ambiente

O arquivo `.env` já está configurado para ambiente local. Revise se necessário:

```bash
cat .env
```

**Configurações padrão:**
```env
ENVIRONMENT=local
DB_HOST=db
DB_NAME=dados_tripulantes_tsi
DB_USER=somaxi
DB_PASS=S0m4x1@193
DB_PORT=3306
```

### 3. Iniciar os Containers

```bash
# Parar containers existentes (se houver)
docker-compose down -v

# Construir e iniciar os containers
docker-compose build --no-cache
docker-compose up -d
```

### 4. Verificar Status

```bash
# Verificar se os containers estão rodando
docker ps

# Verificar logs
docker logs tsi_app
docker logs tsi_mysql
```

### 5. Acessar o Sistema

- **Sistema Principal**: http://localhost:8076
- **phpMyAdmin**: http://localhost:8098
- **Login Padrão**:
  - Usuário: `admin`
  - Senha: `admin123`

---

## Estrutura do Projeto

```
tsi_sistema/
├── .env                          # Configuração local
├── .env.production               # Configuração produção
├── docker-compose.yml            # Orquestração Docker
├── Dockerfile                    # Imagem PHP customizada
├── php.ini                       # Configurações PHP
│
├── index.php                     # Dashboard principal
├── form.php                      # Formulário de registro
├── consulta.php                  # Pesquisa de dados
├── editar.php                    # Edição de registros
├── relatorios.php                # Relatórios executivos
├── login.php                     # Autenticação
│
├── includes/
│   ├── db.php                    # Conexão BD (multi-ambiente)
│   └── header.php                # Header compartilhado
│
├── dados_dash/
│   ├── tsi/                      # Dashboard RMM
│   │   ├── api/                  # Endpoints REST
│   │   ├── config/
│   │   │   └── db.php            # Conexão RMM (multi-ambiente)
│   │   └── js/
│   ├── faturamento_nable/        # Sistema de faturamento
│   └── srm_tsiv2/                # Sistema SRM v2
│
├── database/
│   └── init.sql                  # Script de inicialização do BD
│
└── vendor/                       # Dependências PHP (Composer)
```

---

## Uso

### Páginas Principais

1. **Dashboard** (`/index.php`)
   - Visualização de KPIs
   - Gráficos interativos (Chart.js)
   - Filtros por tripulante, mês e ano

2. **Novo Registro** (`/form.php`)
   - Formulário com 25+ campos
   - Upload de PDF com extração automática
   - Validação de duplicatas

3. **Consulta** (`/consulta.php`)
   - Busca avançada
   - Sistema de arquivamento
   - Exportação de dados

4. **Relatórios** (`/relatorios.php`)
   - Top 10 performance
   - Análise de riscos
   - Evolução mensal

### Comandos Úteis

```bash
# Reiniciar containers
docker-compose restart

# Ver logs em tempo real
docker logs -f tsi_app

# Acessar shell do container PHP
docker exec -it tsi_app bash

# Acessar MySQL via CLI
docker exec -it tsi_mysql mysql -u somaxi -p

# Parar tudo
docker-compose down

# Limpar volumes (CUIDADO: apaga dados)
docker-compose down -v
```

---

## Ambientes

### Ambiente Local (Desenvolvimento)

O sistema detecta automaticamente o ambiente local através de:
- Leitura do arquivo `.env`
- Variável `ENVIRONMENT=local`
- Host do banco: `db` (Docker Compose)

**Características:**
- Debug habilitado
- Logs detalhados
- Recarregamento automático de código

### Ambiente de Produção (Docker Swarm)

Para produção, use `.env.production`:

```env
ENVIRONMENT=production
DB_HOST=mysql_mysql
RMM_DB_HOST=mysql_mysql
APP_DEBUG=false
```

**Características:**
- Debug desabilitado
- Logs otimizados
- Host do banco: `mysql_mysql` (Swarm service)

---

## Banco de Dados

### Tabelas Principais

1. **`users`** - Usuários do sistema
   - Autenticação com bcrypt
   - Campos: id, username, email, password

2. **`tabela_dados_tsi`** - Dados principais de monitoramento
   - 25+ campos de métricas
   - Índices otimizados
   - Constraint única: (tripulante, mes, ano)

3. **`rmm_relatorios`** - Relatórios RMM
   - Estrutura similar à tabela principal
   - Específico para dashboard RMM

### Acesso Direto ao Banco

```bash
# Via Docker
docker exec -it tsi_mysql mysql -u somaxi -pS0m4x1@193 dados_tripulantes_tsi

# Via phpMyAdmin
http://localhost:8098
```

### Backup

```bash
# Criar backup
docker exec tsi_mysql mysqldump -u somaxi -pS0m4x1@193 dados_tripulantes_tsi > backup_$(date +%Y%m%d).sql

# Restaurar backup
docker exec -i tsi_mysql mysql -u somaxi -pS0m4x1@193 dados_tripulantes_tsi < backup.sql
```

---

## Troubleshooting

### Container não inicia

```bash
# Ver logs detalhados
docker-compose logs -f

# Remover containers e volumes
docker-compose down -v

# Reconstruir tudo
docker-compose build --no-cache
docker-compose up -d
```

### Erro de conexão com banco

1. Verifique se o MySQL está pronto:
   ```bash
   docker logs tsi_mysql
   ```

2. Teste a conexão:
   ```bash
   docker exec tsi_mysql mysqladmin ping -u somaxi -pS0m4x1@193
   ```

3. Verifique as variáveis de ambiente:
   ```bash
   docker exec tsi_app env | grep DB_
   ```

### Porta já em uso

Se a porta 8076, 3357 ou 8098 já estiver em uso:

1. Edite o arquivo `.env`:
   ```env
   APP_PORT=8077
   MYSQL_PORT=3358
   PHPMYADMIN_PORT=8099
   ```

2. Reinicie os containers:
   ```bash
   docker-compose down
   docker-compose up -d
   ```

### Permissões de arquivo

Se encontrar erros de permissão:

```bash
# Windows (PowerShell como Admin)
icacls "c:\Users\Home\Desktop\Projects\tsi_sistema" /grant Everyone:F /T

# Linux/Mac
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
```

### Erro "Table doesn't exist"

Se as tabelas não foram criadas:

```bash
# Verificar se o script foi executado
docker exec tsi_mysql mysql -u somaxi -pS0m4x1@193 dados_tripulantes_tsi -e "SHOW TABLES;"

# Executar manualmente se necessário
docker exec -i tsi_mysql mysql -u somaxi -pS0m4x1@193 dados_tripulantes_tsi < database/init.sql
```

### Logs de erro PHP

```bash
# Ver últimas 50 linhas
docker exec tsi_app tail -n 50 /var/www/html/php_errors.log

# Acompanhar em tempo real
docker exec -it tsi_app tail -f /var/www/html/php_errors.log
```

---

## Contato e Suporte

- **Empresa**: SOMAXI Group
- **Sistema**: TSI - Dashboard de Monitoramento
- **Versão**: 2.0 (2025)

---

## Changelog

### v2.0 (Dezembro 2025)
- Implementado sistema de detecção automática de ambiente
- Adicionado suporte a variáveis de ambiente (.env)
- Configuração multi-ambiente (local/produção)
- Melhorias de segurança e performance
- Documentação completa

### v1.0 (2024)
- Versão inicial em produção
- Dashboard principal
- Sistema de relatórios
- Integração com RMM
