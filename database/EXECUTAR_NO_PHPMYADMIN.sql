-- ============================================
-- SCRIPT COMPLETO PARA CRIAR SISTEMA DE LOGIN
-- Execute este script no phpMyAdmin
-- ============================================

-- Passo 1: Selecionar o banco de dados
USE dados_tripulantes_tsi;

-- Passo 2: Criar tabela de usuários
CREATE TABLE IF NOT EXISTS users (
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

-- Passo 3: Inserir primeiro usuário ADMIN
-- Email: jonas.pacheco@somaxi.com.br
-- Senha: Mudar@123
INSERT INTO users (nome, email, password, perfil, primeiro_acesso, ativo)
VALUES (
    'Jonas Pacheco',
    'jonas.pacheco@somaxi.com.br',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    FALSE,
    TRUE
);

-- Passo 4: Verificar se foi criado com sucesso
SELECT 'Tabela users criada com sucesso!' as status;
SELECT * FROM users WHERE email = 'jonas.pacheco@somaxi.com.br';
