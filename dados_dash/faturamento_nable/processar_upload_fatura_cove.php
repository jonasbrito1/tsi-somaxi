<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function logAndEcho($message) {
    echo $message . "<br>";
    error_log($message);
}

// Configurações do banco de dados
$host = 'localhost';
$db   = 'faturamento_nable';
$user = 'somaxi';
$pass = 'S0m4x1@193';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logAndEcho("Conexão com o banco de dados estabelecida com sucesso");
} catch (PDOException $e) {
    logAndEcho("Erro na conexão com o banco de dados: " . $e->getMessage());
    die();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo_xlsx'])) {
    $arquivo = $_FILES['arquivo_xlsx'];
    
    if ($arquivo['error'] === UPLOAD_ERR_OK) {
        logAndEcho("Arquivo recebido com sucesso");
        try {
            $spreadsheet = IOFactory::load($arquivo['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            logAndEcho("Arquivo XLSX carregado com sucesso");
            
            $rowCount = $worksheet->getHighestRow();
            logAndEcho("Número total de linhas no arquivo: " . $rowCount);
            
            $stmt = $pdo->prepare("INSERT INTO fatura_cove (Data_Fatura, Produto, Unidade, Quantidade, Valor_Dolar, Valor_Real, Cliente, Detalhes_Uso) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $pdo->beginTransaction();
            logAndEcho("Iniciando transação");
            
            $linhasProcessadas = 0;
            $linhasInseridas = 0;
            
            foreach ($worksheet->getRowIterator(2) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                $dados = [];
                foreach ($cellIterator as $cell) {
                    $dados[] = $cell->getValue();
                }
                
                $linhasProcessadas++;
                
                logAndEcho("Processando linha " . $linhasProcessadas . ": " . implode(", ", $dados));
                
                if (count($dados) !== 8) {
                    logAndEcho("Erro: Número incorreto de colunas na linha. Esperado: 8, Atual: " . count($dados));
                    continue;
                }
                
                // Convertendo a data
                $data_fatura = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dados[0]);
                $dados[0] = $data_fatura->format('Y-m-d');
                
                // Convertendo valores
                $dados[3] = intval($dados[3]); // Quantidade
                $dados[4] = floatval($dados[4]); // Valor_Dolar
                
                // Tratando o Valor_Real
                if (strpos($dados[5], '=') !== false) {
                    // Suponha-se que o Valor_Dolar está na mesma linha
                    $dados[5] = floatval($dados[4]) * 6.28; // Multiplicação pelo câmbio fornecido
                } else {
                    $dados[5] = floatval(str_replace(['R$', ' '], '', $dados[5])); 
                }
                
                try {
                    $stmt->execute($dados);
                    $linhasInseridas++;
                    logAndEcho("Linha inserida com sucesso");
                } catch (PDOException $e) {
                    logAndEcho("Erro ao inserir dados: " . $e->getMessage());
                    logAndEcho("SQL State: " . $e->getCode());
                }
            }
            
            $pdo->commit();
            logAndEcho("Transação confirmada");
            
            logAndEcho("Processamento concluído. Linhas processadas: $linhasProcessadas. Linhas inseridas: $linhasInseridas");
        } catch (Exception $e) {
            $pdo->rollBack();
            logAndEcho("Erro ao processar o arquivo: " . $e->getMessage());
        }
    } else {
        logAndEcho("Erro no upload do arquivo: " . $arquivo['error']);
    }
} else {
    logAndEcho("Nenhum arquivo enviado");
}
