<?php
// db.php - Configuração para Docker Swarm (SomaxiNet)
$banco_de_dados = "dados_tripulantes_tsi";
$host = "mysql_mysql";
$username = "somaxi";
$password = "S0m4x1@193";
$port = 3306;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$banco_de_dados;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    error_log("Erro de conexão com o banco: " . $e->getMessage());
    die("Erro de conexão com o banco de dados.");
}
?>
