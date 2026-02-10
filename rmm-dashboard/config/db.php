<?php
define("DB_HOST", "mysql_mysql");
define("DB_USER", "somaxi");
define("DB_PASS", "S0m4x1@193");
define("DB_NAME", "rmm_relatorios");

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Conexão falhou: " . $conn->connect_error);
    }
    
    return $conn;
}
?>
