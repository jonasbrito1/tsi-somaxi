<?php
/**
 * PROCESSO AVANÇADO DE EXTRAÇÃO DE PDF
 * Sistema Inteligente com múltiplas camadas de extração
 *
 * Métodos de extração (em ordem de preferência):
 * 1. Smalot/PdfParser - Extração de texto nativo do PDF
 * 2. Poppler pdftotext - Ferramenta de linha de comando
 * 3. Tesseract OCR - Para PDFs escaneados (imagens)
 * 4. Pattern matching inteligente - Regex avançado
 */

// Configuração otimizada
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
ini_set('max_execution_time', 120);
ini_set('memory_limit', '512M');

// Limpar output buffer
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Verificar arquivo
if (!isset($_FILES['pdf_upload'])) {
    echo json_encode(['success' => false, 'error' => 'Nenhum arquivo foi enviado']);
    exit;
}

$uploadError = $_FILES['pdf_upload']['error'];
if ($uploadError !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'Arquivo muito grande (limite do servidor)',
        UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande (limite do formulário)',
        UPLOAD_ERR_PARTIAL => 'Upload incompleto',
        UPLOAD_ERR_NO_FILE => 'Nenhum arquivo selecionado',
        UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado',
        UPLOAD_ERR_CANT_WRITE => 'Erro ao escrever arquivo',
        UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
    ];

    echo json_encode(['success' => false, 'error' => $errorMessages[$uploadError] ?? "Erro desconhecido"]);
    exit;
}

$uploadedFile = $_FILES['pdf_upload'];

// Validar tipo
$allowedMimeTypes = ['application/pdf', 'application/x-pdf', 'application/acrobat', 'applications/vnd.pdf', 'text/pdf', 'text/x-pdf'];
$fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

if (!in_array($uploadedFile['type'], $allowedMimeTypes) && $fileExtension !== 'pdf') {
    echo json_encode([
        'success' => false,
        'error' => "Tipo de arquivo não suportado. Apenas PDF é aceito."
    ]);
    exit;
}

// Validar tamanho (10MB máximo)
if ($uploadedFile['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Arquivo muito grande (máximo 10MB)']);
    exit;
}

try {
    // Criar diretório temporário
    $tempDir = __DIR__ . '/temp_uploads';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    // Salvar arquivo
    $tempFileName = uniqid('pdf_', true) . '.pdf';
    $tempFilePath = $tempDir . '/' . $tempFileName;

    if (!move_uploaded_file($uploadedFile['tmp_name'], $tempFilePath)) {
        throw new Exception('Erro ao salvar arquivo temporário');
    }

    // Log inicial
    error_log("==========================================================");
    error_log("📄 NOVO PDF RECEBIDO: " . $uploadedFile['name']);
    error_log("📦 Tamanho: " . round($uploadedFile['size'] / 1024, 2) . " KB");
    error_log("==========================================================");

    // PROCESSAMENTO INTELIGENTE
    $extractedData = processAdvancedPDF($tempFilePath, $uploadedFile['name']);

    // Limpar arquivo temporário
    unlink($tempFilePath);

    // Retornar resultado
    echo json_encode([
        'success' => true,
        'data' => $extractedData,
        'message' => 'PDF processado com sucesso usando sistema inteligente',
        'extraction_method' => $extractedData['_metadata']['extraction_method'] ?? 'unknown'
    ]);

} catch (Exception $e) {
    // Limpar arquivo temporário
    if (isset($tempFilePath) && file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }

    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar PDF: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

/**
 * PROCESSAMENTO AVANÇADO DE PDF
 * Usa múltiplos métodos de extração
 */
function processAdvancedPDF($pdfPath, $originalName) {
    $extractedText = '';
    $extractionMethod = 'none';

    // MÉTODO 1: Smalot/PdfParser (PREFERENCIAL)
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfPath);
            $extractedText = $pdf->getText();
            $extractionMethod = 'smalot_pdfparser';

            error_log("✅ Método 1: Smalot/PdfParser - " . strlen($extractedText) . " caracteres extraídos");

        } catch (Exception $e) {
            error_log("⚠️ Método 1 falhou: " . $e->getMessage());
        }
    }

    // MÉTODO 2: Poppler pdftotext (FALLBACK 1)
    if (empty($extractedText) || strlen($extractedText) < 100) {
        $pdftotext = shell_exec('which pdftotext 2>/dev/null');
        if ($pdftotext) {
            $extractedText = shell_exec("pdftotext \"$pdfPath\" - 2>/dev/null");
            if (!empty($extractedText)) {
                $extractionMethod = 'poppler_pdftotext';
                error_log("✅ Método 2: Poppler pdftotext - " . strlen($extractedText) . " caracteres extraídos");
            }
        }
    }

    // MÉTODO 3: Tesseract OCR (FALLBACK 2 - PDFs escaneados)
    if (empty($extractedText) || strlen($extractedText) < 50) {
        $tesseract = shell_exec('which tesseract 2>/dev/null');
        if ($tesseract) {
            // Converter PDF para imagem e aplicar OCR
            $imagePath = $pdfPath . '.png';
            shell_exec("pdftoppm \"$pdfPath\" " . $pdfPath . " -png -f 1 -singlefile 2>/dev/null");

            if (file_exists($imagePath)) {
                $ocrText = shell_exec("tesseract \"$imagePath\" stdout -l por 2>/dev/null");
                if (!empty($ocrText)) {
                    $extractedText = $ocrText;
                    $extractionMethod = 'tesseract_ocr';
                    error_log("✅ Método 3: Tesseract OCR - " . strlen($extractedText) . " caracteres extraídos");
                }
                unlink($imagePath);
            }
        }
    }

    // MÉTODO 4: Extração PHP nativa (ÚLTIMO RECURSO)
    if (empty($extractedText)) {
        $extractedText = extractTextPHPNative($pdfPath);
        $extractionMethod = 'php_native';
        error_log("✅ Método 4: PHP Native - " . strlen($extractedText) . " caracteres extraídos");
    }

    // Processar texto extraído
    error_log("📊 Total de texto extraído: " . strlen($extractedText) . " caracteres");
    error_log("🔧 Método usado: $extractionMethod");

    // Extrair dados estruturados
    $data = extractStructuredData($extractedText, $originalName);
    $data['_metadata'] = [
        'extraction_method' => $extractionMethod,
        'text_length' => strlen($extractedText),
        'filename' => $originalName
    ];

    return $data;
}

/**
 * EXTRAÇÃO PHP NATIVA
 */
function extractTextPHPNative($pdfPath) {
    $content = file_get_contents($pdfPath);
    $text = '';

    // Stream compression handling
    if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $matches)) {
        foreach ($matches[1] as $stream) {
            $decoded = @gzuncompress($stream);
            if ($decoded === false) {
                $decoded = @gzinflate($stream);
            }
            if ($decoded) {
                $text .= $decoded . ' ';
            }
        }
    }

    // Direct text extraction
    if (preg_match_all('/\((.*?)\)/s', $content, $matches)) {
        foreach ($matches[1] as $match) {
            if (strlen($match) > 2) {
                $text .= $match . ' ';
            }
        }
    }

    // Normalize
    $text = preg_replace('/[^\x20-\x7E\sáàâãéèêíìîóòôõúùûçÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]/u', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}

/**
 * EXTRAÇÃO ESTRUTURADA DE DADOS
 * Sistema inteligente com múltiplos padrões
 */
function extractStructuredData($text, $filename) {
    error_log("=== INICIANDO EXTRAÇÃO ESTRUTURADA ===");
    error_log("Primeiros 300 chars: " . substr($text, 0, 300));

    $data = [];

    // === EXTRAÇÃO DE CLIENTE ===
    $data['tripulante'] = extractClient($text, $filename);
    error_log("✅ Cliente: " . $data['tripulante']);

    // === EXTRAÇÃO DE PERÍODO ===
    list($data['mes'], $data['ano']) = extractPeriod($text, $filename);
    error_log("✅ Período: {$data['mes']}/{$data['ano']}");

    // === FUNÇÃO AUXILIAR PARA EXTRAIR NÚMEROS ===
    // IMPORTANTE: Converter vírgula decimal (88,9) para ponto decimal (88.9) e ARREDONDAR
    $extractNum = function($patterns) use ($text) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Converter vírgula para ponto ANTES de remover outros caracteres
                $num = str_replace(',', '.', $matches[1]);
                // Depois remover % e espaços
                $num = str_replace(['%', ' '], ['', ''], $num);
                $floatValue = floatval($num);
                // ARREDONDAR para número inteiro (88.9 → 89, 88.4 → 88)
                $result = round($floatValue);
                error_log("  ✓ Match: {$matches[1]} => $num => $floatValue => arredondado: $result");
                return $result;
            }
        }
        return 0;
    };

    // === INDICADORES PRINCIPAIS ===

    // Pontuação de Integridade - Valor dentro de círculo no início do relatório
    // O valor aparece APÓS o título, geralmente após várias linhas
    $data['pontuacao_integridade'] = $extractNum([
        // Padrão: "Pontuação de integridade" seguido por conteúdo e depois número%
        '/Pontuação de integridade.*?Relatório Executivo.*?(\d+[,.]?\d*)\s*%/is',
        // Padrão: busca perto do título do relatório
        '/Efetividade da proteção.*?(\d+[,.]?\d*)\s*%/is',
        // Padrão backup: número% próximo de "Pontuação de integridade"
        '/(\d+[,.]?\d*)\s*%\s*Pontuação de integridade/i',
    ]);

    // Monitoramento Proativo - Seção Breakdown, primeira coluna, valor ao lado do título
    $data['monitoramento_proativo'] = $extractNum([
        // Padrão específico: Breakdown > Monitoramento proativo > valor ao lado
        '/Breakdown.*?Monitoramento proativo\s*(\d+[,.]?\d*)\s*%/is',
        '/Monitoramento proativo\s*(\d+[,.]?\d*)\s*%/i',
        '/(\d+[,.]?\d*)\s*%\s*Monitoramento proativo/i',
        '/Monitoramento proativo\s*:?\s*(\d+[,.]?\d*)%?/i',
    ]);

    // Disponibilidade Servidor - Seção Breakdown, segunda coluna, valor ao lado
    // IMPORTANTE: O texto pode ter quebras de linha entre "Disponibilidade" e "servidor"
    $data['disponibilidade_servidor'] = $extractNum([
        // Padrão com quebra de linha: Disponibilidade\ndo\nservidor
        '/Disponibilidade\s+do\s+servidor\s*(\d+[,.]?\d*)\s*%/is',
        '/Breakdown.*?Disponibilidade\s+do\s+servidor\s*(\d+[,.]?\d*)\s*%/is',
        // Padrão sem quebra
        '/Breakdown.*?Disponibilidade do servidor\s*(\d+[,.]?\d*)\s*%/is',
        '/Disponibilidade.*?servidor\s*(\d+[,.]?\d*)\s*%/is',
        '/Server availability\s*:?\s*(\d+[,.]?\d*)%?/i',
    ]);

    // Falha de Logon - Seção Breakdown, terceira coluna, valor ao lado
    // IMPORTANTE: Pode ter quebras de linha "Falhas em tentativas de\nlogon"
    $data['falha_logon'] = $extractNum([
        // Padrão com quebras de linha e espaços flexíveis
        '/Falhas\s+em\s+tentativas\s+de\s+logon\s+(\d+[,.]?\d*)\s*%/is',
        '/Breakdown.*?Falhas\s+em\s+tentativas\s+de\s+logon\s+(\d+[,.]?\d*)\s*%/is',
        // Padrões alternativos
        '/Falhas em tentativas de logon\s*(\d+[,.]?\d*)\s*%/is',
        '/(\d+[,.]?\d*)\s*%\s*Falhas.*?logon/is',
        '/Falha.*?logon\s*:?\s*(\d+[,.]?\d*)%?/i',
        '/Login failure rate\s*:?\s*(\d+[,.]?\d*)%?/i',
    ]);

    // === COBERTURAS - Seção Breakdown ===

    // Antivírus - Breakdown > Antivirus > Cobertura (valor ao lado)
    $data['cobertura_antivirus'] = $extractNum([
        '/Breakdown.*?Antivírus.*?Cobertura\s*(\d+[,.]?\d*)\s*%/is',
        '/Antivírus.*?Cobertura\s*(\d+[,.]?\d*)\s*%/is',
        '/Cobertura\s*(\d+[,.]?\d*)\s*%.*?Antivírus/is',
        '/(\d+[,.]?\d*)\s*%.*?Cobertura.*?Antivírus/is',
    ]) ?: 0;

    // Gerenciamento de Patch - Cobertura (valor ao lado)
    $data['cobertura_atualizacao_patches'] = $extractNum([
        '/Gerenciamento de patch.*?Cobertura\s*(\d+[,.]?\d*)\s*%/is',
        '/Patch management.*?Cobertura\s*(\d+[,.]?\d*)\s*%/is',
        '/Cobertura\s*(\d+[,.]?\d*)\s*%.*?patch/is',
        '/(\d+[,.]?\d*)\s*%.*?Cobertura.*?patch/is',
    ]) ?: 0;

    // Web Protection - Se não houver informação, retorna 0
    $data['cobertura_web_protection'] = $extractNum([
        '/Web Protection.*?(\d+[,.]?\d*)\s*%/is',
        '/Proteção.*?Web\s*(\d+[,.]?\d*)\s*%/is',
    ]) ?: 0;

    // === DISPOSITIVOS - Distribuídos entre servidor, desktop, notebook, outros ===

    // Total de Dispositivos Gerenciados
    $data['total_dispositivos'] = $extractNum([
        '/Dispositivos gerenciados\s*(\d+)/i',
        '/(\d+)\s*Dispositivos gerenciados/i',
        '/Total.*?dispositivos\s*(\d+)/i',
        '/Managed devices\s*(\d+)/i',
    ]) ?: 0;

    // Servidores
    $data['tipo_servidor'] = $extractNum([
        '/(\d+)\s*Servidor(?:es)?/i',
        '/Servidor(?:es)?\s*(\d+)/i',
        '/(\d+)\s*Server(?:s)?/i',
    ]) ?: 0;

    // Desktops (pode aparecer como "Desktop" ou "Desktops")
    $data['tipo_desktop'] = $extractNum([
        '/(\d+)\s+Desktops?\b/i',
        '/(\d+)\s+Desktop\b/i',
        '/Desktop(?:s)?\s*:?\s*(\d+)/i',
        '/(\d+)\s*Workstation(?:s)?/i',
    ]) ?: 0;

    // Notebooks
    $data['tipo_notebook'] = $extractNum([
        '/(\d+)\s*Notebook(?:s)?/i',
        '/Notebook(?:s)?\s*(\d+)/i',
        '/(\d+)\s*Laptop(?:s)?/i',
    ]) ?: 0;

    // Outros Dispositivos (singular "Outro" ou plural "Outros")
    $outros = $extractNum([
        '/(\d+)\s+Outros?/i',  // Aceita tanto "Outro" quanto "Outros"
        '/Outros?\s*:?\s*(\d+)/i',
        '/(\d+)\s+Other(?:s)?/i',
        '/Other(?:s)?\s*(\d+)/i',
    ]) ?: 0;

    // IMPORTANTE: Quando houver "Outros", somar aos Desktops
    if ($outros > 0) {
        error_log("⚠️ Encontrados $outros dispositivos 'Outros' - somando aos Desktops");
        $data['tipo_desktop'] += $outros;
        error_log("✅ Desktops ajustado: {$data['tipo_desktop']} (incluindo $outros Outros)");
    }

    // === ATIVIDADES ===

    // Alertas Resolvidos - Quadro com título e valor abaixo
    $data['alertas_resolvidos'] = $extractNum([
        '/Alertas resolvidos\s*(\d+)/i',
        '/(\d+)\s*Alertas resolvidos/i',
        '/Resolved alerts\s*(\d+)/i',
        '/Alertas\s*(\d+).*?resolvidos/is',
    ]) ?: 0;

    // Ameaças Detectadas
    $data['ameacas_detectadas'] = $extractNum([
        '/Ameaças.*?quarentena.*?(\d+)/is',
        '/(\d+)\s*Ameaças/i',
        '/Threats.*?(\d+)/i',
    ]) ?: 0;

    // Patches - Trazer corretamente informações de patch

    // Patches Aprovados (Verificações aprovadas)
    // IMPORTANTE: Em tabelas Smalot, o número vem após "Efetividade da proteção" e antes de "X Servidor"
    $data['patches_aprovados'] = $extractNum([
        '/Efetividade da proteção.*?(\d+)\s+\d+\s+Servidor/is',
        '/Dispositivos gerenciados.*?(\d+)\s+\d+\s+Servidor/is',
        '/(\d+)\s+\d+\s+Servidor.*?\d+\s+Desktop/is',
        '/Verificações aprovadas.*?(\d+)/is',
        '/Aprovado[s]?\s*(\d+)/i',
        '/Approved.*?(\d+)/i',
    ]) ?: 0;

    // Patches Rejeitados
    $data['patches_rejeitados'] = $extractNum([
        '/Rejeitado[s]?\s*(\d+)/i',
        '/(\d+)\s*Rejeitado/i',
        '/Rejected patches\s*(\d+)/i',
    ]) ?: 0;

    // Patches Instalados
    $data['patches_instalados'] = $extractNum([
        '/Instalado[s]?\s*(\d+)/i',
        '/(\d+)\s*Instalado/i',
        '/Patches instalados\s*(\d+)/i',
        '/(\d+)\s*Patches instalados/i',
        '/Installed patches\s*(\d+)/i',
    ]) ?: 0;

    // Acesso Remoto - Quantidade de sessões Take Control
    $data['acessos_remotos'] = $extractNum([
        '/Sessões do Take Control\s*(\d+)/i',
        '/(\d+)\s*Sessões do Take Control/i',
        '/Take Control sessions\s*(\d+)/i',
        '/Take Control\s*(\d+)/i',
        '/sessões.*?Take Control.*?(\d+)/is',
    ]) ?: 0;

    // === INFORMAÇÕES DO CLIENTE (TRIPULANTE) ===

    // Extrair do padrão: "Gerado para [NOME] para [MÊS] [ANO]"
    if (preg_match('/Gerado para\s+(.+?)\s+para\s+([A-Za-zçãõ]+)\s+(\d{4})/is', $text, $match)) {
        $data['tripulante'] = trim($match[1]);
        $mesPortugues = trim($match[2]);
        $data['ano'] = trim($match[3]);

        // Converter mês de português/inglês para número
        $meses = [
            'janeiro' => '01', 'january' => '01',
            'fevereiro' => '02', 'february' => '02',
            'março' => '03', 'march' => '03',
            'abril' => '04', 'april' => '04',
            'maio' => '05', 'may' => '05',
            'junho' => '06', 'june' => '06',
            'julho' => '07', 'july' => '07',
            'agosto' => '08', 'august' => '08',
            'setembro' => '09', 'september' => '09',
            'outubro' => '10', 'october' => '10',
            'novembro' => '11', 'november' => '11',
            'dezembro' => '12', 'december' => '12'
        ];
        $data['mes'] = $meses[strtolower($mesPortugues)] ?? $mesPortugues;
    } else {
        // Fallback: extrair do nome do arquivo se não encontrar no texto
        if (preg_match('/^([^_]+)/', $originalName, $match)) {
            $data['tripulante'] = $match[1];
        }

        if (preg_match('/(January|February|March|April|May|June|July|August|September|October|November|December)_(\d{4})/i', $originalName, $match)) {
            $meses = [
                'January' => '01', 'February' => '02', 'March' => '03',
                'April' => '04', 'May' => '05', 'June' => '06',
                'July' => '07', 'August' => '08', 'September' => '09',
                'October' => '10', 'November' => '11', 'December' => '12'
            ];
            $data['mes'] = $meses[$match[1]] ?? '';
            $data['ano'] = $match[2];
        }
    }

    // === GARANTIR ZEROS PARA CAMPOS NÃO ENCONTRADOS ===
    $allFields = [
        'pontuacao_integridade', 'monitoramento_proativo', 'disponibilidade_servidor', 'falha_logon',
        'cobertura_antivirus', 'cobertura_atualizacao_patches', 'cobertura_web_protection',
        'total_dispositivos', 'tipo_servidor', 'tipo_desktop', 'tipo_notebook',
        'alertas_resolvidos', 'ameacas_detectadas', 'patches_instalados', 'acessos_remotos',
        'web_protection_filtradas_bloqueadas', 'web_protection_mal_intencionadas_bloqueadas',
        'num_chamados_abertos', 'num_chamados_fechados',
        'bkp_completo', 'bkp_com_erro', 'bkp_com_falha'
    ];

    foreach ($allFields as $field) {
        if (!isset($data[$field])) {
            $data[$field] = 0;
        }
    }

    error_log("=== EXTRAÇÃO CONCLUÍDA ===");
    error_log("Campos preenchidos: " . count(array_filter($data, function($v) { return $v > 0; })));

    return $data;
}

/**
 * Extrai nome do cliente
 */
function extractClient($text, $filename) {
    // Padrões específicos de relatórios TSI
    $patterns = [
        '/Gerado para\s+([A-Za-z\s&]+)\s+para o período/i',
        '/Cliente:\s*([A-Za-z\s&]+)/i',
        '/Empresa:\s*([A-Za-z\s&]+)/i',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            $name = trim($matches[1]);
            if (strlen($name) >= 3) {
                return $name;
            }
        }
    }

    // Fallback: extrair do nome do arquivo
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = preg_replace('/[_-].*/', '', $name);
    return ucfirst(strtolower($name));
}

/**
 * Extrai período (mês e ano)
 */
function extractPeriod($text, $filename) {
    // PRIORIDADE 1: Extrair do padrão "Gerado para [NOME] para [MÊS] [ANO]"
    if (preg_match('/Gerado para\s+.+?\s+para\s+([A-Za-zçãõ]+)\s+(\d{4})/is', $text, $match)) {
        $mesPortugues = trim($match[1]);
        $ano = trim($match[2]);

        $meses = [
            'janeiro' => '01', 'january' => '01',
            'fevereiro' => '02', 'february' => '02',
            'março' => '03', 'march' => '03',
            'abril' => '04', 'april' => '04',
            'maio' => '05', 'may' => '05',
            'junho' => '06', 'june' => '06',
            'julho' => '07', 'july' => '07',
            'agosto' => '08', 'august' => '08',
            'setembro' => '09', 'september' => '09',
            'outubro' => '10', 'october' => '10',
            'novembro' => '11', 'november' => '11',
            'dezembro' => '12', 'december' => '12'
        ];

        $mes = $meses[strtolower($mesPortugues)] ?? normalizeMonth($mesPortugues);
        error_log("✅ Período extraído do padrão 'Gerado para': $mesPortugues ($mes) / $ano");
        return [$mes, intval($ano)];
    }

    // PRIORIDADE 2: Padrões genéricos de mês/ano no texto
    $patterns = [
        '/(\w+)\s+(\d{4})/i',
        '/(\d{4})\s+(\w+)/i',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            $mes = normalizeMonth($matches[1]);
            $ano = is_numeric($matches[1]) ? intval($matches[2]) : intval($matches[1]);
            if ($ano > 2000 && !empty($mes)) {
                error_log("✅ Período extraído de padrão genérico: $mes / $ano");
                return [$mes, $ano];
            }
        }
    }

    // PRIORIDADE 3 (Fallback): Extrair do filename
    if (preg_match('/(\w+)_(\d{4})/i', $filename, $matches)) {
        $mes = normalizeMonth($matches[1]);
        $ano = intval($matches[2]);
        error_log("✅ Período extraído do filename: $mes / $ano");
        return [$mes, $ano];
    }

    // ÚLTIMO RECURSO: mês anterior
    $mes = date('n') - 1;
    $ano = date('Y');
    if ($mes <= 0) {
        $mes = 12;
        $ano--;
    }

    $meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
              'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

    error_log("⚠️ Usando mês anterior como fallback: {$meses[$mes]} / $ano");
    return [$meses[$mes], $ano];
}

/**
 * Normaliza nome do mês
 */
function normalizeMonth($month) {
    $months = [
        'january' => 'Janeiro', 'jan' => 'Janeiro',
        'february' => 'Fevereiro', 'feb' => 'Fevereiro',
        'march' => 'Março', 'mar' => 'Março',
        'april' => 'Abril', 'apr' => 'Abril',
        'may' => 'Maio',
        'june' => 'Junho', 'jun' => 'Junho',
        'july' => 'Julho', 'jul' => 'Julho',
        'august' => 'Agosto', 'aug' => 'Agosto',
        'september' => 'Setembro', 'sep' => 'Setembro',
        'october' => 'Outubro', 'oct' => 'Outubro',
        'november' => 'Novembro', 'nov' => 'Novembro',
        'december' => 'Dezembro', 'dec' => 'Dezembro',
        'janeiro' => 'Janeiro', 'fevereiro' => 'Fevereiro', 'março' => 'Março',
        'abril' => 'Abril', 'maio' => 'Maio', 'junho' => 'Junho',
        'julho' => 'Julho', 'agosto' => 'Agosto', 'setembro' => 'Setembro',
        'outubro' => 'Outubro', 'novembro' => 'Novembro', 'dezembro' => 'Dezembro'
    ];

    $normalized = $months[strtolower($month)] ?? ucfirst(strtolower($month));
    return $normalized;
}
?>
