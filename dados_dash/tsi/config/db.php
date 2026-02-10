<?php
declare(strict_types=1);

/**
 * Carrega variáveis de ambiente do arquivo .env
 */
function loadEnvForRMM($path = __DIR__ . '/../../../.env') {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');

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
function detectRMMEnvironment(): string {
    loadEnvForRMM();

    if (isset($_ENV['ENVIRONMENT'])) {
        return $_ENV['ENVIRONMENT'];
    }

    $hostname = gethostname();
    if (strpos($hostname, 'swarm') !== false || getenv('DOCKER_SWARM') === 'true') {
        return 'production';
    }

    if (getenv('RMM_DB_HOST') === 'mysql_mysql') {
        return 'production';
    }

    return 'local';
}

/**
 * Abre conexão PDO com MySQL - Multi-Ambiente
 */
function connectDB(): PDO {
    $environment = detectRMMEnvironment();

    if ($environment === 'production') {
        // Configuração de Produção (Docker Swarm)
        $host = getenv('RMM_DB_HOST') ?: 'mysql_mysql';
    } else {
        // Configuração Local (Docker Compose)
        $host = getenv('RMM_DB_HOST') ?: 'db';
    }

    $db      = getenv('RMM_DB_NAME') ?: 'rmm_relatorios';
    $user    = getenv('DB_USER') ?: 'somaxi';
    $pass    = getenv('DB_PASS') ?: 'S0m4x1@193';
    $charset = "utf8mb4";

    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        error_log("Erro de conexão com o banco RMM [$host/$db]: " . $e->getMessage());
        http_response_code(500);
        exit("Problemas para conectar ao banco de dados. Ambiente: $environment");
    }
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":t" => $table, ":c" => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function numericExpr(string $col, string $type = "int", string $agg = "SUM", ?string $alias = null): string {
    $alias = $alias ?: $col;
    switch ($type) {
        case "percent": return "$agg(CAST(REPLACE($col, \"%\", \"\") AS DECIMAL(10,2))) AS $alias";
        case "decimal": return "$agg(CAST($col AS DECIMAL(10,2))) AS $alias";
        default: return "$agg(CAST(REPLACE($col, \",\", \"\") AS SIGNED)) AS $alias";
    }
}
?>
