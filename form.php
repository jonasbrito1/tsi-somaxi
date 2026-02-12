<?php
// Habilitar exibição de erros para debug (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once 'includes/db.php';
require_once 'includes/auth.php';

// Proteger página - requer login
require_login();

$user = get_logged_user();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coletar os dados do formulário
    $tripulante = $_POST['tripulante'];
    $mes = $_POST['mes'];
    $ano = $_POST['ano'];
    $pontuacao_integridade = $_POST['pontuacao_integridade'];
    $monitoramento_proativo = $_POST['monitoramento_proativo'];
    $disponibilidade_servidor = $_POST['disponibilidade_servidor'];
    $falha_logon = $_POST['falha_logon'];
    $cobertura_antivirus = $_POST['cobertura_antivirus'];
    $cobertura_atualizacao_patches = $_POST['cobertura_atualizacao_patches'];
    $cobertura_web_protection = $_POST['cobertura_web_protection'];
    $total_dispositivos = $_POST['total_dispositivos'];
    $tipo_desktop = $_POST['tipo_desktop'];
    $tipo_notebook = $_POST['tipo_notebook'];
    $tipo_servidor = $_POST['tipo_servidor'];
    $alertas_resolvidos = $_POST['alertas_resolvidos'];
    $ameacas_detectadas = $_POST['ameacas_detectadas'];
    $patches_instalados = $_POST['patches_instalados'];
    $acessos_remotos = $_POST['acessos_remotos'];
    $web_protection_filtradas_bloqueadas = $_POST['web_protection_filtradas_bloqueadas'];
    $web_protection_mal_intencionadas_bloqueadas = $_POST['web_protection_mal_intencionadas_bloqueadas'];
    $bkp_completo = $_POST['bkp_completo'];
    $bkp_com_erro = $_POST['bkp_com_erro'];
    $bkp_com_falha = $_POST['bkp_com_falha'];
    $num_chamados_abertos = isset($_POST['num_chamados_abertos']) ? $_POST['num_chamados_abertos'] : 0;
    $num_chamados_fechados = isset($_POST['num_chamados_fechados']) ? $_POST['num_chamados_fechados'] : 0;

    try {
        // Verificar se já existe registro para este tripulante, mês e ano
        $sql_check = "SELECT COUNT(*) as count FROM tabela_dados_tsi WHERE tripulante = :tripulante AND mes = :mes AND ano = :ano";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([':tripulante' => $tripulante, ':mes' => $mes, ':ano' => $ano]);
        $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($existing['count'] > 0) {
            $error_message = "Ja existe um registro para {$tripulante} no periodo {$mes}/{$ano}. Verifique a consulta ou edite o registro existente.";
        } else {
            // Preparar a consulta SQL para inserir os dados no banco de dados
            $sql = "INSERT INTO tabela_dados_tsi
                    (tripulante, mes, ano, pontuacao_integridade, monitoramento_proativo, disponibilidade_servidor, falha_logon,
                     cobertura_antivirus, cobertura_atualizacao_patches, cobertura_web_protection, total_dispositivos, tipo_desktop,
                     tipo_notebook, tipo_servidor, alertas_resolvidos, ameacas_detectadas, patches_instalados, acessos_remotos,
                     web_protection_filtradas_bloqueadas, web_protection_mal_intencionadas_bloqueadas, bkp_completo, bkp_com_erro,
                     bkp_com_falha, num_chamados_abertos, num_chamados_fechados)
                    VALUES
                    (:tripulante, :mes, :ano, :pontuacao_integridade, :monitoramento_proativo, :disponibilidade_servidor,
                     :falha_logon, :cobertura_antivirus, :cobertura_atualizacao_patches, :cobertura_web_protection,
                     :total_dispositivos, :tipo_desktop, :tipo_notebook, :tipo_servidor, :alertas_resolvidos, :ameacas_detectadas,
                     :patches_instalados, :acessos_remotos, :web_protection_filtradas_bloqueadas, :web_protection_mal_intencionadas_bloqueadas,
                     :bkp_completo, :bkp_com_erro, :bkp_com_falha, :num_chamados_abertos, :num_chamados_fechados)";

            $stmt = $pdo->prepare($sql);

            // Bind dos parâmetros
            $stmt->bindParam(':tripulante', $tripulante);
            $stmt->bindParam(':mes', $mes);
            $stmt->bindParam(':ano', $ano);
            $stmt->bindParam(':pontuacao_integridade', $pontuacao_integridade);
            $stmt->bindParam(':monitoramento_proativo', $monitoramento_proativo);
            $stmt->bindParam(':disponibilidade_servidor', $disponibilidade_servidor);
            $stmt->bindParam(':falha_logon', $falha_logon);
            $stmt->bindParam(':cobertura_antivirus', $cobertura_antivirus);
            $stmt->bindParam(':cobertura_atualizacao_patches', $cobertura_atualizacao_patches);
            $stmt->bindParam(':cobertura_web_protection', $cobertura_web_protection);
            $stmt->bindParam(':total_dispositivos', $total_dispositivos);
            $stmt->bindParam(':tipo_desktop', $tipo_desktop);
            $stmt->bindParam(':tipo_notebook', $tipo_notebook);
            $stmt->bindParam(':tipo_servidor', $tipo_servidor);
            $stmt->bindParam(':alertas_resolvidos', $alertas_resolvidos);
            $stmt->bindParam(':ameacas_detectadas', $ameacas_detectadas);
            $stmt->bindParam(':patches_instalados', $patches_instalados);
            $stmt->bindParam(':acessos_remotos', $acessos_remotos);
            $stmt->bindParam(':web_protection_filtradas_bloqueadas', $web_protection_filtradas_bloqueadas);
            $stmt->bindParam(':web_protection_mal_intencionadas_bloqueadas', $web_protection_mal_intencionadas_bloqueadas);
            $stmt->bindParam(':bkp_completo', $bkp_completo);
            $stmt->bindParam(':bkp_com_erro', $bkp_com_erro);
            $stmt->bindParam(':bkp_com_falha', $bkp_com_falha);
            $stmt->bindParam(':num_chamados_abertos', $num_chamados_abertos);
            $stmt->bindParam(':num_chamados_fechados', $num_chamados_fechados);

            // Executar a consulta
            if ($stmt->execute()) {
                $success_message = "Dados inseridos com sucesso! Registro criado para {$tripulante} em {$mes}/{$ano}.";
            } else {
                $error_message = "Erro ao inserir dados no banco de dados.";
            }
        }
    } catch (PDOException $e) {
        $error_message = "Erro ao inserir dados: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Registro - SOMAXI Group</title>
    <link rel="icon" href="utils/logo_s.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
    <script src="js/theme.js"></script>
    <script src="js/user-dropdown.js"></script>
</head>

<body>
    <!-- HEADER -->
    <header class="app-header">
        <div class="header-content">
            <div class="header-brand">
                <div class="brand-content">
                    <h1 class="header-title">
                        <svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        Novo Registro
                    </h1>
                    <p class="header-subtitle">Cadastro de dados de seguranca</p>
                </div>
            </div>
            <div class="header-logo">
                <img src="utils/logo_SOMAXI_GROUP_azul.png" alt="SOMAXI GROUP" class="company-logo">
            </div>
        </div>
    </header>

    <!-- NAVEGACAO -->
    <nav class="app-nav">
        <div class="nav-container">
            <div class="nav-links">
                <a href="index.php">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/></svg>
                    Dashboard
                </a>
                <a href="form.php" class="active">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                    Novo Registro
                </a>
                <a href="consulta.php">
                    <svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    Consultar Dados
                </a>
                <a href="relatorios.php">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                    Relatorios
                </a>
                <?php if (is_admin()): ?>
                <a href="auth/cadastro_usuario.php">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                    Usuários
                </a>
                <?php endif; ?>
            </div>
            <div class="user-section">
                <button class="theme-toggle-btn" onclick="toggleTheme()" title="Alternar tema">
                    <svg id="theme-icon-light" class="icon" viewBox="0 0 24 24" style="display: none;">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    <svg id="theme-icon-dark" class="icon" viewBox="0 0 24 24">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </button>
                <div class="user-dropdown">
                    <button class="user-dropdown-toggle">
                        <div class="user-avatar">
                            <svg class="icon icon-sm" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($user['nome']); ?></span>
                        <svg class="icon icon-sm chevron" viewBox="0 0 24 24">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="user-dropdown-menu">
                        <div class="dropdown-header">
                            <div class="dropdown-user-info">
                                <div class="dropdown-user-name"><?php echo htmlspecialchars($user['nome']); ?></div>
                                <div class="dropdown-user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="auth/editar_perfil.php" class="dropdown-item">
                            <svg class="icon icon-sm" viewBox="0 0 24 24">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <span>Editar Perfil</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="auth/logout.php" class="dropdown-item danger">
                            <svg class="icon icon-sm" viewBox="0 0 24 24">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            <span>Sair</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- CONTEUDO PRINCIPAL -->
    <main class="main-content">

        <!-- MENSAGENS -->
        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <svg class="icon" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- PROGRESS INDICATOR -->
        <div class="progress-container" style="background: var(--gray-200); height: 4px; border-radius: 2px; margin-bottom: 1.5rem; overflow: hidden;">
            <div class="progress-bar" id="progressBar" style="background: var(--success); height: 100%; border-radius: 2px; transition: width 0.3s ease; width: 0%;"></div>
        </div>

        <!-- FORMULARIO -->
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%); color: white; border: none;">
                <div class="card-title" style="color: white;">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Formulario de Coleta de Dados
                </div>
                <p style="font-size: var(--font-size-sm); opacity: 0.9; margin: 0;">Preencha todas as informacoes necessarias para o monitoramento</p>
            </div>

            <form action="form.php" method="post" id="mainForm" enctype="multipart/form-data">
                <div class="card-body">

                    <!-- UPLOAD AUTOMATICO DE RELATORIO PDF -->
                    <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid var(--info);">
                        <div class="card-header">
                            <div class="card-title">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                                Extracao Inteligente de Dados - SRM TSI
                            </div>
                        </div>
                        <div class="card-body">
                            <p style="font-size: var(--font-size-sm); color: var(--gray-500); margin-bottom: 1rem;">Sistema avancado com OCR e multiplos metodos de extracao</p>

                            <div class="upload-container-modern" id="uploadContainer">
                                <div class="upload-area-modern" id="uploadArea" style="border: 2px dashed var(--gray-300); border-radius: var(--radius-lg); padding: 2rem; text-align: center; cursor: pointer; transition: all 0.3s; background: var(--gray-50);">
                                    <div style="margin-bottom: 1rem;">
                                        <svg class="icon icon-xl" style="width: 48px; height: 48px; color: var(--gray-400);" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    </div>
                                    <div style="font-size: var(--font-size-lg); font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">
                                        Arraste seu relatorio PDF aqui
                                    </div>
                                    <div style="font-size: var(--font-size-sm); color: var(--gray-500); margin-bottom: 1rem;">ou</div>
                                    <button type="button" class="btn btn-primary" onclick="document.getElementById('pdfUpload').click()">
                                        <svg class="icon" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                        Procurar Arquivo
                                    </button>
                                    <div style="font-size: var(--font-size-xs); color: var(--gray-400); margin-top: 1rem;">
                                        Formatos suportados: PDF | Tamanho maximo: 10MB
                                    </div>
                                    <input type="file" id="pdfUpload" name="pdf_upload" accept=".pdf" style="display: none;">
                                </div>

                                <!-- Preview do arquivo selecionado -->
                                <div class="file-preview" id="filePreview" style="display: none; background: white; border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-md);">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; padding: 1rem; background: var(--gray-50); border-radius: var(--radius-md);">
                                        <svg class="icon icon-lg" style="color: var(--danger);" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        <div style="flex: 1;">
                                            <div id="fileName" style="font-weight: 600; color: var(--gray-900);">documento.pdf</div>
                                            <div id="fileSize" style="font-size: var(--font-size-xs); color: var(--gray-500);">0 MB</div>
                                        </div>
                                        <button type="button" class="btn btn-ghost" onclick="removeFile()" style="color: var(--danger);">
                                            <svg class="icon" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </div>
                                    <button type="button" id="processBtn" class="btn btn-success btn-block btn-lg">
                                        <svg class="icon" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                        Processar
                                    </button>
                                </div>

                                <!-- Status de processamento -->
                                <div class="upload-status-modern" id="uploadStatus" style="display: none; background: white; padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md);">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div class="loader-spinner" style="width: 40px; height: 40px; border: 3px solid var(--gray-200); border-top-color: var(--info); border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--gray-900);">Processando documento...</div>
                                            <div id="statusSubtitle" style="font-size: var(--font-size-sm); color: var(--gray-500);">Analisando estrutura do PDF</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Resultado -->
                                <div class="upload-success" id="uploadSuccess" style="display: none; background: var(--success-light); padding: 2rem; border-radius: var(--radius-lg); text-align: center;">
                                    <svg class="icon icon-xl" style="width: 48px; height: 48px; color: var(--success); margin-bottom: 1rem;" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    <div style="font-size: var(--font-size-lg); font-weight: 700; color: var(--success); margin-bottom: 0.5rem;">Dados extraidos com sucesso!</div>
                                    <div id="successDetails" style="font-size: var(--font-size-sm); color: #15803d;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SELECAO DE TRIPULANTE -->
                    <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid var(--primary);">
                        <div class="card-header">
                            <div class="card-title">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M3 7l9-4 9 4M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
                                Selecao de Tripulante
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label" for="tripulante">Tripulante <span style="color: var(--danger);">*</span></label>
                                <select id="tripulante" name="tripulante" class="form-control" required>
                                    <option value="">Selecione o Tripulante</option>
                                    <option value="A Formula">A Formula</option>
                                    <option value="Aguia Transportes">Aguia Transportes</option>
                                    <option value="Amaral & CIA">Amaral & CIA</option>
                                    <option value="Atria Corp">Atria Corp</option>
                                    <option value="AgroBI">AgroBI</option>
                                    <option value="Battisti & CIA">Battisti & CIA</option>
                                    <option value="Bevandick & CIA">Bevandick & CIA</option>
                                    <option value="Bolda Contabilidade">Bolda Contabilidade</option>
                                    <option value="Borges & CIA">Borges & CIA</option>
                                    <option value="Bittencourt & CIA">Bittencourt & CIA</option>
                                    <option value="Castro Alves & CIA">Castro Alves & CIA</option>
                                    <option value="Cardoso & CIA">Cardoso & CIA</option>
                                    <option value="Consult Contabilidade">Consult Contabilidade</option>
                                    <option value="ContaSul Contabilidade">ContaSul Contabilidade</option>
                                    <option value="CTA">CTA</option>
                                    <option value="CTIC">CTIC</option>
                                    <option value="Dr_Rodrigo">Dr_Rodrigo</option>
                                    <option value="Expert">Expert</option>
                                    <option value="Fabrica Cultural">Fabrica Cultural</option>
                                    <option value="Farias & CIA">Farias & CIA</option>
                                    <option value="Fecontesc">Fecontesc</option>
                                    <option value="Franks(teste)">Franks(teste)</option>
                                    <option value="Guerardt Santin & CIA">Guerardt Santin & CIA</option>
                                    <option value="Grid Tripulantes">Grid Tripulantes</option>
                                    <option value="Grupo Magrass">Grupo Magrass</option>
                                    <option value="Gomes & CIA">Gomes & CIA</option>
                                    <option value="Hyperion">Hyperion</option>
                                    <option value="Horizonti Digital">Horizonti Digital</option>
                                    <option value="Imoveis para Expatriados">Imoveis para Expatriados</option>
                                    <option value="JLP Contabilidade">JLP Contabilidade</option>
                                    <option value="Lumma Despachante">Lumma Despachante</option>
                                    <option value="Lucas Soares & CIA">Lucas Soares & CIA</option>
                                    <option value="Marcuzzo & CIA">Marcuzzo & CIA</option>
                                    <option value="Medeiros & CIA">Medeiros & CIA</option>
                                    <option value="Mourao e Vasconcelos & CIA">Mourao e Vasconcelos & CIA</option>
                                    <option value="Munhoz & CIA">Munhoz & CIA</option>
                                    <option value="MGM Farma">MGM Farma</option>
                                    <option value="Onboarding Tripulantes">Onboarding Tripulantes</option>
                                    <option value="Ortec Contabilidade">Ortec Contabilidade</option>
                                    <option value="Paiva & CIA">Paiva & CIA</option>
                                    <option value="Paiva, Pokrovsky & CIA">Paiva, Pokrovsky & CIA</option>
                                    <option value="Pregao de Guerra">Pregao de Guerra</option>
                                    <option value="Prime Inteligencia Contabil">Prime Inteligencia Contabil</option>
                                    <option value="Prostaff">Prostaff</option>
                                    <option value="Pousada Aloha">Pousada Aloha</option>
                                    <option value="Quantum Contabilidade">Quantum Contabilidade</option>
                                    <option value="Renova Marcas">Renova Marcas</option>
                                    <option value="Rockfeller">Rockfeller</option>
                                    <option value="Rodrigues & CIA">Rodrigues & CIA</option>
                                    <option value="SafeCar">SafeCar</option>
                                    <option value="Service Contabilidade">Service Contabilidade</option>
                                    <option value="Sindicont Joinville">Sindicont Joinville</option>
                                    <option value="Solution Corretora">Solution Corretora</option>
                                    <option value="Somaxi Franqueados">Somaxi Franqueados</option>
                                    <option value="Somaxi Group">Somaxi Group</option>
                                    <option value="Tambasco & CIA">Tambasco & CIA</option>
                                    <option value="Teixeira & CIA">Teixeira & CIA</option>
                                    <option value="Weef">Weef</option>
                                </select>
                                <p class="form-text">Selecione o tripulante para habilitar o restante do formulario</p>
                            </div>
                        </div>
                    </div>

                    <!-- FORMULARIO PRINCIPAL (INICIALMENTE OCULTO) -->
                    <div class="tripulante-section" id="mainFormSection" style="display: none;">

                        <!-- INFORMACOES BASICAS -->
                        <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid var(--warning);">
                            <div class="card-header">
                                <div class="card-title">
                                    <svg class="icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    Periodo
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label" for="ano">Ano <span style="color: var(--danger);">*</span></label>
                                        <select id="ano" name="ano" class="form-control" required>
                                            <option value="">Selecionar ano</option>
                                            <?php for ($ano = 2024; $ano <= 2030; $ano++): ?>
                                                <option value="<?php echo $ano; ?>" <?php echo ($ano == 2025) ? 'selected' : ''; ?>>
                                                    <?php echo $ano; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="mes">Mes <span style="color: var(--danger);">*</span></label>
                                        <select id="mes" name="mes" class="form-control" required>
                                            <option value="">Selecionar mes</option>
                                            <?php
                                            $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                                                     'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                            foreach ($meses as $mes): ?>
                                                <option value="<?php echo $mes; ?>"><?php echo $mes; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DISPOSITIVOS -->
                        <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid var(--info);">
                            <div class="card-header">
                                <div class="card-title">
                                    <svg class="icon" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                    Dispositivos
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label" for="total_dispositivos">Total de Dispositivos <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="total_dispositivos" name="total_dispositivos" class="form-control" min="0" required readonly>
                                        <p class="form-text">Calculado automaticamente</p>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="tipo_desktop">Desktop <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="tipo_desktop" name="tipo_desktop" class="form-control" min="0" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="tipo_notebook">Notebook <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="tipo_notebook" name="tipo_notebook" class="form-control" min="0" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="tipo_servidor">Servidor <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="tipo_servidor" name="tipo_servidor" class="form-control" min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEGURANCA -->
                        <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid var(--success);">
                            <div class="card-header">
                                <div class="card-title">
                                    <svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    Seguranca
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label" for="alertas_resolvidos">Alertas Resolvidos <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="alertas_resolvidos" name="alertas_resolvidos" class="form-control" min="0" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="ameacas_detectadas">Ameacas Detectadas <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="ameacas_detectadas" name="ameacas_detectadas" class="form-control" min="0" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="patches_instalados">Patches Instalados <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="patches_instalados" name="patches_instalados" class="form-control" min="0" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="acessos_remotos">Acessos Remotos <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="acessos_remotos" name="acessos_remotos" class="form-control" min="0" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="pontuacao_integridade">Pontuacao de Integridade (%) <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="pontuacao_integridade" name="pontuacao_integridade" class="form-control" min="0" max="100" required>
                                        <p class="form-text">Valor entre 0 e 100</p>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="falha_logon">Falhas de Logon <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="falha_logon" name="falha_logon" class="form-control" min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CHAMADOS -->
                        <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid var(--secondary);">
                            <div class="card-header">
                                <div class="card-title">
                                    <svg class="icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    Chamados
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label" for="num_chamados_abertos">Chamados Abertos <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="num_chamados_abertos" name="num_chamados_abertos" class="form-control" min="0" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="num_chamados_fechados">Chamados Fechados <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="num_chamados_fechados" name="num_chamados_fechados" class="form-control" min="0" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="monitoramento_proativo">Monitoramento Proativo (%) <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="monitoramento_proativo" name="monitoramento_proativo" class="form-control" min="0" max="100" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- COBERTURA -->
                        <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid var(--accent);">
                            <div class="card-header">
                                <div class="card-title">
                                    <svg class="icon" viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                                    Cobertura e Monitoramento
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label" for="cobertura_antivirus">Cobertura Antivirus (%) <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="cobertura_antivirus" name="cobertura_antivirus" class="form-control" min="0" max="100" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="cobertura_web_protection">Cobertura Web Protection (%) <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="cobertura_web_protection" name="cobertura_web_protection" class="form-control" min="0" max="100" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="cobertura_atualizacao_patches">Cobertura Atualizacao Patches (%) <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="cobertura_atualizacao_patches" name="cobertura_atualizacao_patches" class="form-control" min="0" max="100" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="disponibilidade_servidor">Disponibilidade Servidor (%) <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="disponibilidade_servidor" name="disponibilidade_servidor" class="form-control" min="0" max="100" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="web_protection_filtradas_bloqueadas">Web Protection - Filtradas/Bloqueadas <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="web_protection_filtradas_bloqueadas" name="web_protection_filtradas_bloqueadas" class="form-control" min="0" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="web_protection_mal_intencionadas_bloqueadas">Web Protection - Mal Intencionadas/Bloqueadas <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="web_protection_mal_intencionadas_bloqueadas" name="web_protection_mal_intencionadas_bloqueadas" class="form-control" min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BACKUP -->
                        <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid var(--danger);">
                            <div class="card-header">
                                <div class="card-title">
                                    <svg class="icon" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                    Backup
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label" for="bkp_completo">Backup Completo <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="bkp_completo" name="bkp_completo" class="form-control" min="0" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="bkp_com_erro">Backup com Erro <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="bkp_com_erro" name="bkp_com_erro" class="form-control" min="0" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="bkp_com_falha">Backup com Falha <span style="color: var(--danger);">*</span></label>
                                        <input type="number" id="bkp_com_falha" name="bkp_com_falha" class="form-control" min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ACOES DO FORMULARIO -->
                <div id="formActions" style="display: none; padding: 1.5rem; background: var(--gray-50); border-top: 1px solid var(--gray-200); text-align: center;">
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <button type="submit" class="btn btn-success btn-lg">
                            <svg class="icon" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Salvar Registro
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="resetForm()">
                            <svg class="icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                            Limpar Formulario
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .upload-area-modern:hover {
            border-color: var(--primary-light);
            background: var(--gray-100);
        }

        .upload-area-modern.drag-over {
            border-color: var(--success);
            background: var(--success-light);
        }

        .tripulante-section.show {
            display: block;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group.valid .form-control {
            border-color: var(--success);
            background: var(--success-light);
        }

        .form-group.invalid .form-control {
            border-color: var(--danger);
            background: var(--danger-light);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tripulanteSelect = document.getElementById('tripulante');
            const mainFormSection = document.getElementById('mainFormSection');
            const formActions = document.getElementById('formActions');
            const progressBar = document.getElementById('progressBar');

            // Mostrar formulario quando tripulante for selecionado
            tripulanteSelect.addEventListener('change', function() {
                if (this.value !== '') {
                    mainFormSection.classList.add('show');
                    mainFormSection.style.display = 'block';
                    formActions.style.display = 'block';
                    updateProgress();
                } else {
                    mainFormSection.classList.remove('show');
                    mainFormSection.style.display = 'none';
                    formActions.style.display = 'none';
                    progressBar.style.width = '0%';
                }
            });

            // Auto-calcular total de dispositivos
            function calcularTotal() {
                const desktop = parseInt(document.getElementById('tipo_desktop').value) || 0;
                const notebook = parseInt(document.getElementById('tipo_notebook').value) || 0;
                const servidor = parseInt(document.getElementById('tipo_servidor').value) || 0;
                const total = desktop + notebook + servidor;
                document.getElementById('total_dispositivos').value = total;
                updateProgress();
            }

            // Adicionar eventos nos campos de dispositivos
            ['tipo_desktop', 'tipo_notebook', 'tipo_servidor'].forEach(function(id) {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('input', calcularTotal);
                }
            });

            // Validacao de percentuais
            function validarPercentual(input) {
                const valor = parseInt(input.value);
                if (valor < 0) input.value = 0;
                if (valor > 100) input.value = 100;
            }

            // Aplicar validacao aos campos de percentual
            ['pontuacao_integridade', 'monitoramento_proativo', 'disponibilidade_servidor',
             'cobertura_antivirus', 'cobertura_web_protection', 'cobertura_atualizacao_patches'].forEach(function(id) {
                const input = document.getElementById(id);
                if (input) {
                    input.addEventListener('blur', function() {
                        validarPercentual(this);
                        updateProgress();
                    });
                }
            });

            // Atualizar barra de progresso
            function updateProgress() {
                const inputs = document.querySelectorAll('#mainFormSection input[required], #mainFormSection select[required]');
                let filled = 0;

                inputs.forEach(input => {
                    if (input.value.trim() !== '') {
                        filled++;
                    }
                });

                const percentage = Math.round((filled / inputs.length) * 100);
                progressBar.style.width = percentage + '%';
            }

            // Validacao visual em tempo real
            const inputs = document.querySelectorAll('input[required], select[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    const field = this.closest('.form-group');
                    if (field) {
                        if (this.value.trim() === '') {
                            field.classList.add('invalid');
                            field.classList.remove('valid');
                        } else {
                            field.classList.add('valid');
                            field.classList.remove('invalid');
                        }
                    }
                    updateProgress();
                });

                input.addEventListener('input', updateProgress);
            });

            // Validacao do formulario antes do envio
            document.getElementById('mainForm').addEventListener('submit', function(e) {
                let valid = true;
                const requiredInputs = document.querySelectorAll('input[required], select[required]');

                requiredInputs.forEach(input => {
                    if (input.value.trim() === '') {
                        const field = input.closest('.form-group');
                        if (field) {
                            field.classList.add('invalid');
                            field.classList.remove('valid');
                        }
                        valid = false;
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    alert('Por favor, preencha todos os campos obrigatorios antes de continuar.');

                    // Rolar para o primeiro campo invalido
                    const firstInvalid = document.querySelector('.form-group.invalid input, .form-group.invalid select');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstInvalid.focus();
                    }
                }
            });

            // Atalhos de teclado
            document.addEventListener('keydown', function(e) {
                // Ctrl + S para salvar
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    document.getElementById('mainForm').submit();
                }
            });

            // Animacao de foco nos campos
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    if (this.parentElement) {
                        this.parentElement.style.transform = 'scale(1.02)';
                        this.parentElement.style.transition = 'transform 0.2s ease';
                    }
                });

                input.addEventListener('blur', function() {
                    if (this.parentElement) {
                        this.parentElement.style.transform = 'scale(1)';
                    }
                });
            });

            // Auto-hide messages
            setTimeout(function() {
                const messages = document.querySelectorAll('.alert');
                messages.forEach(function(message) {
                    message.style.opacity = '0';
                    message.style.transform = 'translateY(-20px)';
                    message.style.transition = 'all 0.3s ease';
                    setTimeout(() => message.style.display = 'none', 300);
                });
            }, 5000);
        });

        // Funcao para resetar formulario
        function resetForm() {
            if (confirm('Tem certeza de que deseja limpar todos os campos do formulario?')) {
                document.getElementById('mainForm').reset();
                document.getElementById('mainFormSection').classList.remove('show');
                document.getElementById('mainFormSection').style.display = 'none';
                document.getElementById('formActions').style.display = 'none';
                document.getElementById('progressBar').style.width = '0%';

                // Remover classes de validacao
                document.querySelectorAll('.form-group').forEach(field => {
                    field.classList.remove('valid', 'invalid');
                });
            }
        }

        // === FUNCIONALIDADE MODERNA DE UPLOAD DE PDF ===
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const pdfUpload = document.getElementById('pdfUpload');
            const filePreview = document.getElementById('filePreview');
            const processBtn = document.getElementById('processBtn');
            const uploadStatus = document.getElementById('uploadStatus');
            const uploadSuccess = document.getElementById('uploadSuccess');

            // Drag and Drop Moderno
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                uploadArea.classList.add('drag-over');
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                uploadArea.classList.remove('drag-over');
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                uploadArea.classList.remove('drag-over');

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelection(files[0]);
                }
            });

            // Click direto na area (excluindo o botao para evitar duplicacao)
            uploadArea.addEventListener('click', function(e) {
                // Nao acionar se o clique for no botao (para evitar duplo trigger)
                if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                    return;
                }
                if (e.target === uploadArea || e.target.closest('.upload-area-modern')) {
                    pdfUpload.click();
                }
            });

            // Input file change
            pdfUpload.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    handleFileSelection(e.target.files[0]);
                }
            });

            // Processar selecao de arquivo
            function handleFileSelection(file) {
                // Validacoes
                if (file.type !== 'application/pdf') {
                    alert('Por favor, selecione apenas arquivos PDF');
                    return;
                }

                if (file.size > 10 * 1024 * 1024) {
                    alert('Arquivo muito grande. Tamanho maximo: 10MB');
                    return;
                }

                // Atualizar preview
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = `${(file.size / 1024 / 1024).toFixed(2)} MB`;

                // Mostrar preview e esconder area de upload
                uploadArea.style.display = 'none';
                filePreview.style.display = 'block';
                uploadStatus.style.display = 'none';
                uploadSuccess.style.display = 'none';
            }

            // Processar PDF e auto-preencher - VERSAO MODERNA
            processBtn.addEventListener('click', function() {
                if (!pdfUpload.files || pdfUpload.files.length === 0) {
                    alert('Nenhum arquivo selecionado');
                    return;
                }

                const file = pdfUpload.files[0];
                const formData = new FormData();
                formData.append('pdf_upload', file);
                formData.append('action', 'process_pdf');

                // Esconder preview e mostrar status
                filePreview.style.display = 'none';
                uploadStatus.style.display = 'flex';

                // Simulacao de etapas para feedback
                const statusMessages = [
                    'Fazendo upload do documento...',
                    'Extraindo texto com OCR...',
                    'Processando com IA...',
                    'Identificando campos...',
                    'Finalizando extracao...'
                ];

                let currentStep = 0;
                const statusInterval = setInterval(() => {
                    if (currentStep < statusMessages.length - 1) {
                        document.getElementById('statusSubtitle').textContent = statusMessages[currentStep];
                        currentStep++;
                    }
                }, 1200);

                fetch('process_pdf.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(text => {
                    // Limpar intervalo
                    clearInterval(statusInterval);

                    // Verificar se e HTML
                    if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                        throw new Error('Servidor retornou HTML em vez de JSON');
                    }

                    // Parse JSON
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Erro ao fazer parse:', e);
                        console.error('Resposta:', text.substring(0, 500));
                        throw new Error('Resposta invalida do servidor');
                    }
                })
                .then(data => {
                    uploadStatus.style.display = 'none';

                    if (data.success) {
                        // Mostrar sucesso
                        uploadSuccess.style.display = 'block';
                        document.getElementById('successDetails').textContent =
                            `Metodo: ${data.extraction_method || 'N/A'} | ${Object.keys(data.data).length} campos detectados`;

                        // Auto-preencher formulario
                        setTimeout(() => {
                            autoFillForm(data.data);
                            uploadSuccess.style.display = 'none';
                            uploadArea.style.display = 'block';
                        }, 2500);

                    } else {
                        console.error('Erro:', data.error);
                        alert('Erro ao processar PDF: ' + (data.error || 'Erro desconhecido'));
                        filePreview.style.display = 'block';
                    }
                })
                .catch(error => {
                    clearInterval(statusInterval);
                    console.error('Erro:', error);
                    uploadStatus.style.display = 'none';
                    alert('Erro: ' + error.message);
                    filePreview.style.display = 'block';
                });
            });
        });

        // Funcao global para remover arquivo
        function removeFile() {
            const pdfUpload = document.getElementById('pdfUpload');
            const uploadArea = document.getElementById('uploadArea');
            const filePreview = document.getElementById('filePreview');

            pdfUpload.value = '';
            filePreview.style.display = 'none';
            uploadArea.style.display = 'block';
        }

            // Auto-preenchimento do formulario
            function autoFillForm(data) {
                console.log('Iniciando autopreenchimento com dados:', data);

                // 1. EXPANDIR AUTOMATICAMENTE TODOS OS CAMPOS
                const mainFormSection = document.getElementById('mainFormSection');
                const formActions = document.getElementById('formActions');

                mainFormSection.classList.add('show');
                mainFormSection.style.display = 'block';
                formActions.style.display = 'block';

                console.log('Formulario expandido automaticamente');

                // 2. PREENCHER CAMPOS BASICOS OBRIGATORIOS
                if (data.tripulante) {
                    const tripulanteField = document.getElementById('tripulante');
                    if (tripulanteField) {
                        tripulanteField.value = data.tripulante;
                        console.log('Tripulante preenchido:', data.tripulante);
                    }
                }

                if (data.mes) {
                    const mesField = document.getElementById('mes');
                    if (mesField) {
                        // Converter numero do mes (01-12) para nome do mes
                        const mesesNomes = {
                            '01': 'Janeiro', '1': 'Janeiro',
                            '02': 'Fevereiro', '2': 'Fevereiro',
                            '03': 'Março', '3': 'Março',
                            '04': 'Abril', '4': 'Abril',
                            '05': 'Maio', '5': 'Maio',
                            '06': 'Junho', '6': 'Junho',
                            '07': 'Julho', '7': 'Julho',
                            '08': 'Agosto', '8': 'Agosto',
                            '09': 'Setembro', '9': 'Setembro',
                            '10': 'Outubro',
                            '11': 'Novembro',
                            '12': 'Dezembro'
                        };

                        // Se data.mes for numero (01-12), converter para nome
                        const mesValue = mesesNomes[data.mes] || data.mes;
                        mesField.value = mesValue;
                        console.log('Mes preenchido:', data.mes, '->', mesValue);
                    }
                }

                if (data.ano) {
                    const anoField = document.getElementById('ano');
                    if (anoField) {
                        anoField.value = data.ano;
                        console.log('Ano preenchido:', data.ano);
                    }
                }

                // 3. PREENCHER TODOS OS CAMPOS NUMERICOS COM PRECISAO
                const numericFields = {
                    // Indicadores principais
                    'pontuacao_integridade': data.pontuacao_integridade || 0,
                    'monitoramento_proativo': data.monitoramento_proativo || 0,
                    'disponibilidade_servidor': data.disponibilidade_servidor || 0,
                    'falha_logon': data.falha_logon || 0,

                    // Coberturas
                    'cobertura_antivirus': data.cobertura_antivirus || 0,
                    'cobertura_atualizacao_patches': data.cobertura_atualizacao_patches || 0,
                    'cobertura_web_protection': data.cobertura_web_protection || 0,

                    // Dispositivos
                    'total_dispositivos': data.total_dispositivos || 0,
                    'tipo_desktop': data.tipo_desktop || 0,
                    'tipo_notebook': data.tipo_notebook || 0,
                    'tipo_servidor': data.tipo_servidor || 0,

                    // Atividades
                    'alertas_resolvidos': data.alertas_resolvidos || 0,
                    'ameacas_detectadas': data.ameacas_detectadas || 0,
                    'patches_instalados': data.patches_instalados || 0,
                    'acessos_remotos': data.acessos_remotos || 0,

                    // Web Protection
                    'web_protection_filtradas_bloqueadas': data.web_protection_filtradas_bloqueadas || 0,
                    'web_protection_mal_intencionadas_bloqueadas': data.web_protection_mal_intencionadas_bloqueadas || 0,

                    // Chamados
                    'num_chamados_abertos': data.num_chamados_abertos || 0,
                    'num_chamados_fechados': data.num_chamados_fechados || 0,

                    // Backup
                    'bkp_completo': data.bkp_completo || 0,
                    'bkp_com_erro': data.bkp_com_erro || 0,
                    'bkp_com_falha': data.bkp_com_falha || 0
                };

                let fieldsFound = 0;
                let fieldsFilled = 0;

                Object.entries(numericFields).forEach(([field, value]) => {
                    const element = document.getElementById(field);
                    if (element) {
                        fieldsFound++;
                        if (value !== undefined && value !== null) {
                            element.value = value;
                            element.dispatchEvent(new Event('input'));
                            fieldsFilled++;
                            console.log(`${field}: ${value}`);
                        } else {
                            console.log(`${field}: valor nao encontrado, usando 0`);
                            element.value = 0;
                            element.dispatchEvent(new Event('input'));
                            fieldsFilled++;
                        }
                    } else {
                        console.error(`Campo nao encontrado: ${field}`);
                    }
                });

                console.log(`Preenchimento concluido: ${fieldsFilled}/${fieldsFound} campos preenchidos`);

                // 4. ATUALIZAR BARRA DE PROGRESSO
                setTimeout(() => {
                    const progressEvent = new Event('input');
                    document.querySelectorAll('input, select').forEach(el => {
                        el.dispatchEvent(progressEvent);
                    });
                    updateProgress();
                    console.log('Progresso atualizado');
                }, 100);

                // 5. DESTACAR CAMPOS PREENCHIDOS AUTOMATICAMENTE
                setTimeout(() => {
                    document.querySelectorAll('input').forEach(el => {
                        if (el.value && el.value !== '' && el.value !== '0') {
                            el.style.backgroundColor = '#e8f5e8';
                            el.style.borderColor = '#4caf50';
                        }
                    });

                    // Scroll suave para o topo do formulario
                    mainFormSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

                    console.log('Campos destacados e formulario exibido');
                }, 200);
            }

            // Funcao updateProgress global
            function updateProgress() {
                const inputs = document.querySelectorAll('#mainFormSection input[required], #mainFormSection select[required]');
                const progressBar = document.getElementById('progressBar');
                let filled = 0;

                inputs.forEach(input => {
                    if (input.value.trim() !== '') {
                        filled++;
                    }
                });

                const percentage = Math.round((filled / inputs.length) * 100);
                if (progressBar) {
                    progressBar.style.width = percentage + '%';
                }
            }
    </script>
</body>
</html>
