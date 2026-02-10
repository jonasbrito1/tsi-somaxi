<?php
// Habilite erros no ambiente de DEV:
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1) CONFIG - ajuste conforme seu ambiente
$host = 'localhost';
$user = 'somaxi';
$pass = 'S0m4x1@193';

// Dica: no seu log apareceu "Conexão OK com o banco: rmm_relatorios".
// Isso parece ser o NOME DO BANCO. Ajuste abaixo se necessário.
$defaultDb   = 'rmm_relatorios';
$defaultTable = 'rmm_relatorios'; // se a tabela tiver outro nome, ajuste aqui

// 2) Entrada por GET com default
$dbName   = isset($_GET['db'])    ? $_GET['db']    : $defaultDb;
$table    = isset($_GET['table']) ? $_GET['table'] : $defaultTable;

// 3) Validação de identificadores (evita injeção em nomes de objetos)
$identPattern = '/^[A-Za-z0-9_]+$/';
if (!preg_match($identPattern, $dbName)) {
  die("Erro: nome de banco inválido.");
}
if (!preg_match($identPattern, $table)) {
  die("Erro: nome de tabela inválido.");
}

// 4) Conexão
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
  $conn = new mysqli($host, $user, $pass, $dbName);
  $conn->set_charset('utf8mb4');
  echo "Conexão OK com o banco: " . htmlspecialchars($dbName) . "<br><br>";
} catch (mysqli_sql_exception $e) {
  die("Falha na conexão: " . $e->getMessage());
}

// 5) Verifica se a tabela existe (INFORMATION_SCHEMA)
try {
  $stmt = $conn->prepare(
    "SELECT 1 
     FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? 
     LIMIT 1"
  );
  $stmt->bind_param('ss', $dbName, $table);
  $stmt->execute();
  $exists = $stmt->get_result()->num_rows > 0;
  $stmt->close();

  if (!$exists) {
    die("Tabela '" . htmlspecialchars($table) . "' não encontrada no banco '" . htmlspecialchars($dbName) . "'.");
  }

  echo "Tabela encontrada: " . htmlspecialchars($table) . "<br><br>";

  // 6) Conta registros
  // Observação: não dá para bindar nomes de banco/tabela em prepared statements.
  // Como validamos via regex, concatenar com backticks é seguro.
  $sqlCount = "SELECT COUNT(*) AS total FROM `{$dbName}`.`{$table}`";
  $resCount = $conn->query($sqlCount);
  $row = $resCount->fetch_assoc();
  $total = (int)$row['total'];
  echo "Total de registros: {$total}<br><br>";

  // 7) Lista colunas
  $stmt = $conn->prepare(
    "SELECT COLUMN_NAME, DATA_TYPE 
     FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? 
     ORDER BY ORDINAL_POSITION"
  );
  $stmt->bind_param('ss', $dbName, $table);
  $stmt->execute();
  $cols = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  echo "Colunas (" . count($cols) . "): ";
  echo implode(', ', array_map(function($c){ return $c['COLUMN_NAME']; }, $cols));
  echo "<br><br>";

  // 8) Amostra de 5 linhas (sem ORDER; depois podemos melhorar)
  $sqlSample = "SELECT * FROM `{$dbName}`.`{$table}` LIMIT 5";
  $resSample = $conn->query($sqlSample);
  if ($resSample->num_rows === 0) {
    echo "Amostra: tabela sem registros.<br>";
  } else {
    echo "<strong>Amostra (até 5 linhas):</strong><br>";
    echo "<pre>";
    while ($r = $resSample->fetch_assoc()) {
      print_r($r);
    }
    echo "</pre>";
  }

} catch (mysqli_sql_exception $e) {
  die("Erro na verificação/consulta: " . $e->getMessage());
} finally {
  if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
  }
}
