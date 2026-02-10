-- ============================================
-- Script de Inicialização do Banco de Dados
-- Sistema TSI - SOMAXI GROUP
-- ============================================

-- Seleciona o banco de dados
USE dados_tripulantes_tsi;

-- ============================================
-- Tabela de Usuários
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_username (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela Principal de Dados TSI
-- ============================================
CREATE TABLE IF NOT EXISTS `tabela_dados_tsi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tripulante` VARCHAR(255) NOT NULL COMMENT 'Nome do cliente',
  `mes` VARCHAR(20) NOT NULL COMMENT 'Mês do relatório',
  `ano` INT NOT NULL COMMENT 'Ano do relatório',

  -- Métricas de Integridade e Monitoramento
  `pontuacao_integridade` INT DEFAULT 0 COMMENT 'Pontuação de integridade (0-100)',
  `monitoramento_proativo` INT DEFAULT 0 COMMENT 'Score de monitoramento proativo',
  `disponibilidade_servidor` INT DEFAULT 0 COMMENT 'Disponibilidade do servidor (%)',
  `falha_logon` INT DEFAULT 0 COMMENT 'Número de falhas de logon',

  -- Cobertura de Segurança
  `cobertura_antivirus` INT DEFAULT 0 COMMENT 'Cobertura de antivírus (%)',
  `cobertura_atualizacao_patches` INT DEFAULT 0 COMMENT 'Cobertura de patches (%)',
  `cobertura_web_protection` INT DEFAULT 0 COMMENT 'Cobertura web protection (%)',

  -- Dispositivos
  `total_dispositivos` INT DEFAULT 0 COMMENT 'Total de dispositivos monitorados',
  `tipo_desktop` INT DEFAULT 0 COMMENT 'Número de desktops',
  `tipo_notebook` INT DEFAULT 0 COMMENT 'Número de notebooks',
  `tipo_servidor` INT DEFAULT 0 COMMENT 'Número de servidores',

  -- Atividades e Incidentes
  `alertas_resolvidos` INT DEFAULT 0 COMMENT 'Número de alertas resolvidos',
  `ameacas_detectadas` INT DEFAULT 0 COMMENT 'Número de ameaças detectadas',
  `patches_instalados` INT DEFAULT 0 COMMENT 'Número de patches instalados',
  `acessos_remotos` INT DEFAULT 0 COMMENT 'Número de acessos remotos',

  -- Web Protection
  `web_protection_filtradas_bloqueadas` INT DEFAULT 0 COMMENT 'Páginas filtradas/bloqueadas',
  `web_protection_mal_intencionadas_bloqueadas` INT DEFAULT 0 COMMENT 'Páginas mal-intencionadas bloqueadas',

  -- Backup
  `bkp_completo` INT DEFAULT 0 COMMENT 'Backups completos',
  `bkp_com_erro` INT DEFAULT 0 COMMENT 'Backups com erro',
  `bkp_com_falha` INT DEFAULT 0 COMMENT 'Backups com falha',

  -- Chamados
  `num_chamados_abertos` INT DEFAULT 0 COMMENT 'Número de chamados abertos',
  `num_chamados_fechados` INT DEFAULT 0 COMMENT 'Número de chamados fechados',

  -- Metadados
  `status` VARCHAR(20) DEFAULT 'ativo' COMMENT 'Status: ativo ou arquivado',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Índices para otimização
  INDEX idx_tripulante (`tripulante`),
  INDEX idx_ano_mes (`ano`, `mes`),
  INDEX idx_status (`status`),
  INDEX idx_created_at (`created_at`),
  UNIQUE KEY unique_registro (`tripulante`, `mes`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabela principal de dados de monitoramento TSI';

-- ============================================
-- Tabela de Relatórios RMM
-- ============================================
CREATE TABLE IF NOT EXISTS `rmm_relatorios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cliente` VARCHAR(255) NOT NULL COMMENT 'Nome do cliente',
  `mes` VARCHAR(20) NOT NULL,
  `ano` INT NOT NULL,

  -- Métricas RMM
  `pontuacao_integridade` INT DEFAULT 0,
  `monitoramento_proativo` INT DEFAULT 0,
  `disponibilidade_servidor` INT DEFAULT 0,
  `falha_logon` INT DEFAULT 0,
  `cobertura_antivirus` INT DEFAULT 0,
  `cobertura_atualizacao_patches` INT DEFAULT 0,
  `cobertura_web_protection` INT DEFAULT 0,
  `total_dispositivos` INT DEFAULT 0,
  `tipo_desktop` INT DEFAULT 0,
  `tipo_notebook` INT DEFAULT 0,
  `tipo_servidor` INT DEFAULT 0,
  `alertas_resolvidos` INT DEFAULT 0,
  `ameacas_detectadas` INT DEFAULT 0,
  `patches_instalados` INT DEFAULT 0,
  `acessos_remotos` INT DEFAULT 0,
  `web_protection_filtradas_bloqueadas` INT DEFAULT 0,
  `web_protection_mal_intencionadas_bloqueadas` INT DEFAULT 0,
  `bkp_completo` INT DEFAULT 0,
  `bkp_com_erro` INT DEFAULT 0,
  `bkp_com_falha` INT DEFAULT 0,
  `num_chamados_abertos` INT DEFAULT 0,
  `num_chamados_fechados` INT DEFAULT 0,

  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_cliente (`cliente`),
  INDEX idx_ano_mes (`ano`, `mes`),
  UNIQUE KEY unique_relatorio (`cliente`, `mes`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabela de relatórios RMM';

-- ============================================
-- Inserir Usuário Admin Padrão
-- ============================================
-- Senha: admin123
-- Hash gerado com password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `users` (`username`, `email`, `password`)
VALUES ('admin', 'admin@somaxigroup.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm')
ON DUPLICATE KEY UPDATE `email` = 'admin@somaxigroup.com';

-- ============================================
-- Inserir Dados de Exemplo (Opcional)
-- ============================================
INSERT INTO `tabela_dados_tsi`
(`tripulante`, `mes`, `ano`, `pontuacao_integridade`, `monitoramento_proativo`,
 `disponibilidade_servidor`, `cobertura_antivirus`, `cobertura_atualizacao_patches`,
 `total_dispositivos`, `tipo_desktop`, `tipo_notebook`, `tipo_servidor`,
 `alertas_resolvidos`, `ameacas_detectadas`, `patches_instalados`)
VALUES
('Cliente Exemplo', 'Janeiro', 2025, 95, 90, 99, 100, 98, 50, 30, 15, 5, 120, 5, 250)
ON DUPLICATE KEY UPDATE `pontuacao_integridade` = 95;

-- ============================================
-- Verificação de Integridade
-- ============================================
SELECT 'Tabelas criadas com sucesso!' as status;
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_registros FROM tabela_dados_tsi;

-- ============================================
-- FIM DO SCRIPT
-- ============================================
