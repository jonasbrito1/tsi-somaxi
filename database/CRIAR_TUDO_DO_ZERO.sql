-- ============================================
-- SCRIPT COMPLETO - CRIAR DO ZERO
-- Execute linha por linha no phpMyAdmin
-- ============================================

-- PASSO 1: Selecionar banco
USE dados_tripulantes_tsi;

-- PASSO 2: Remover tabela antiga se existir (CUIDADO!)
DROP TABLE IF EXISTS users;

-- PASSO 3: Criar tabela nova
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'visualizacao') NOT NULL DEFAULT 'visualizacao',
    primeiro_acesso BOOLEAN DEFAULT TRUE,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_perfil (perfil),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PASSO 4: Inserir usuário admin
INSERT INTO users (nome, email, password, perfil, primeiro_acesso, ativo)
VALUES (
    'Jonas Pacheco',
    'jonas.pacheco@somaxi.com.br',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    FALSE,
    TRUE
);

-- PASSO 5: Verificar resultado
SELECT 'Setup completo!' as status;
SELECT * FROM users;
