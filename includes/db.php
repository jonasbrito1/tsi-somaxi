<?php
// db.php - Configuração de Banco de Dados com Detecção de Ambiente

/**
 * Carrega variáveis de ambiente do arquivo .env
 */
function loadEnv($path = __DIR__ . '/../.env') {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignora comentários
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse da linha KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove aspas se existirem
            $value = trim($value, '"\'');

            // Define como variável de ambiente se ainda não existir
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

/**
 * Detecta o ambiente (local ou produção)
 */
function detectEnvironment() {
    // Primeiro tenta carregar .env
    loadEnv();

    // Verifica variável de ambiente
    if (isset($_ENV['ENVIRONMENT'])) {
        return $_ENV['ENVIRONMENT'];
    }

    // Detecta por hostname do Docker
    $hostname = gethostname();
    if (strpos($hostname, 'swarm') !== false || getenv('DOCKER_SWARM') === 'true') {
        return 'production';
    }

    // Detecta por host do banco
    if (getenv('DB_HOST') === 'mysql_mysql') {
        return 'production';
    }

    // Padrão: local
    return 'local';
}

/**
 * Obtém configuração do banco baseada no ambiente
 */
function getDatabaseConfig() {
    $environment = detectEnvironment();

    if ($environment === 'production') {
        // Configuração de Produção (Docker Swarm)
        return [
            'host' => getenv('DB_HOST') ?: 'mysql_mysql',
            'database' => getenv('DB_NAME') ?: 'dados_tripulantes_tsi',
            'username' => getenv('DB_USER') ?: 'somaxi',
            'password' => getenv('DB_PASS') ?: 'S0m4x1@193',
            'port' => getenv('DB_PORT') ?: 3306,
        ];
    } else {
        // Configuração Local (Docker Compose)
        return [
            'host' => getenv('DB_HOST') ?: 'db',
            'database' => getenv('DB_NAME') ?: 'dados_tripulantes_tsi',
            'username' => getenv('DB_USER') ?: 'somaxi',
            'password' => getenv('DB_PASS') ?: 'S0m4x1@193',
            'port' => getenv('DB_PORT') ?: 3306,
        ];
    }
}

// Carrega configuração
$config = getDatabaseConfig();
$banco_de_dados = $config['database'];
$host = $config['host'];
$username = $config['username'];
$password = $config['password'];
$port = $config['port'];

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$banco_de_dados;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    error_log("Erro de conexão com o banco [$host]: " . $e->getMessage());
    die("Erro de conexão com o banco de dados. Ambiente: " . detectEnvironment());
}
?>
