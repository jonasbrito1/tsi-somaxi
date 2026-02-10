<?php
// Habilitar exibição de erros para debug (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar a sessão (mantido para compatibilidade)
session_start();

require_once 'includes/db.php';

// Obter o ID do registro a ser editado
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$registro = null;
$erro = '';

if ($id > 0) {
    // Buscar dados para edição
    try {
        $sql = "SELECT * FROM tabela_dados_tsi WHERE id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            $erro = 'Registro não encontrado.';
        }
    } catch (Exception $e) {
        $erro = 'Erro ao buscar registro: ' . $e->getMessage();
    }
} else {
    $erro = 'ID inválido.';
}

// Obter lista de tripulantes para o select
$tripulantes = [];
try {
    $stmt_tripulantes = $pdo->query("SELECT DISTINCT tripulante FROM tabela_dados_tsi WHERE tripulante IS NOT NULL ORDER BY tripulante ASC");
    $tripulantes = $stmt_tripulantes->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Em caso de erro, continuar com array vazio
    $tripulantes = [];
}

// Informações do usuário logado
$username = 'Acesso Público';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Dados - SOMAXI Group</title>
    <link rel="icon" href="utils/logo_s.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
</head>

<body>
    <!-- HEADER -->
    <header class="app-header">
        <div class="header-content">
            <div class="header-brand">
                <div class="brand-content">
                    <h1 class="header-title">
                        <svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                        Editar Registro
                    </h1>
                    <p class="header-subtitle">Atualizacao de dados</p>
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
                <a href="index.php" class="nav-link">
                    <svg class="icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
                <a href="form.php" class="nav-link">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Novo Registro
                </a>
                <a href="consulta.php" class="nav-link">
                    <svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    Consultar Dados
                </a>
                <a href="relatorios.php" class="nav-link">
                    <svg class="icon" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    Relatorios
                </a>
            </div>
            <div class="user-section">
                <div class="user-info">
                    <div class="user-avatar">
                        <svg class="icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <span><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- CONTEUDO PRINCIPAL -->
    <main class="main-content">

        <!-- BREADCRUMB -->
        <nav class="breadcrumb">
            <a href="index.php">
                <svg class="icon" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Dashboard
            </a>
            <span class="breadcrumb-separator">/</span>
            <a href="consulta.php">
                <svg class="icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                Consultar Dados
            </a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">
                <svg class="icon" viewBox="0 0 24 24"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                Editar Registro
            </span>
        </nav>

        <?php if ($erro): ?>
        <div class="alert alert-error">
            <svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?php echo htmlspecialchars($erro); ?>
        </div>
        <div style="text-align: center; padding: 2rem;">
            <a href="consulta.php" class="btn btn-secondary">
                <svg class="icon" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Voltar para Consulta
            </a>
        </div>
        <?php else: ?>

        <!-- CARTAO DE INFORMACOES -->
        <div class="info-card">
            <div class="info-icon">
                <svg class="icon icon-lg" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="info-content">
                <h2>Editando: <?php echo htmlspecialchars($registro['tripulante']); ?></h2>
                <p>Periodo: <?php echo htmlspecialchars($registro['mes']); ?>/<?php echo htmlspecialchars($registro['ano']); ?> | ID: #<?php echo $registro['id']; ?></p>
            </div>
        </div>

        <!-- FORMULARIO -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Formulario de Edicao
                </h2>
                <p class="card-subtitle">Atualize as informacoes do registro selecionado</p>
            </div>

            <form action="editar_submit.php?id=<?php echo $registro['id']; ?>" method="POST" id="editForm">
                <div class="card-body">
                    <div class="form-grid">

                        <!-- INFORMACOES BASICAS -->
                        <div class="form-section">
                            <div class="section-title">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                Informacoes Basicas
                            </div>
                            <div class="fields-grid">
                                <div class="form-group required">
                                    <label class="form-label" for="tripulante">Tripulante</label>
                                    <select id="tripulante" name="tripulante" class="form-control" required>
                                        <option value="" disabled>Selecionar tripulante</option>
                                        <?php foreach ($tripulantes as $tripulante): ?>
                                            <option value="<?php echo htmlspecialchars($tripulante['tripulante']); ?>"
                                                    <?php echo ($registro['tripulante'] === $tripulante['tripulante']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tripulante['tripulante']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="mes">Mes</label>
                                    <select id="mes" name="mes" class="form-control" required>
                                        <option value="" disabled>Selecionar mes</option>
                                        <?php
                                        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                                                 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                        foreach ($meses as $mes): ?>
                                            <option value="<?php echo $mes; ?>"
                                                    <?php echo ($registro['mes'] === $mes) ? 'selected' : ''; ?>>
                                                <?php echo $mes; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="ano">Ano</label>
                                    <select id="ano" name="ano" class="form-control" required>
                                        <option value="" disabled>Selecionar ano</option>
                                        <?php for ($ano = 2024; $ano <= 2030; $ano++): ?>
                                            <option value="<?php echo $ano; ?>"
                                                    <?php echo ($registro['ano'] == $ano) ? 'selected' : ''; ?>>
                                                <?php echo $ano; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- DISPOSITIVOS -->
                        <div class="form-section">
                            <div class="section-title">
                                <svg class="icon" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                Dispositivos
                            </div>
                            <div class="fields-grid">
                                <div class="form-group required">
                                    <label class="form-label" for="total_dispositivos">Total de Dispositivos</label>
                                    <input type="number" id="total_dispositivos" name="total_dispositivos" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['total_dispositivos']); ?>"
                                           min="0" required>
                                    <div class="form-help">Numero total de dispositivos gerenciados</div>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="tipo_desktop">Desktop</label>
                                    <input type="number" id="tipo_desktop" name="tipo_desktop" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['tipo_desktop']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="tipo_notebook">Notebook</label>
                                    <input type="number" id="tipo_notebook" name="tipo_notebook" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['tipo_notebook']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="tipo_servidor">Servidor</label>
                                    <input type="number" id="tipo_servidor" name="tipo_servidor" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['tipo_servidor']); ?>"
                                           min="0" required>
                                </div>
                            </div>
                        </div>

                        <!-- SEGURANCA -->
                        <div class="form-section">
                            <div class="section-title">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                Seguranca
                            </div>
                            <div class="fields-grid">
                                <div class="form-group required">
                                    <label class="form-label" for="alertas_resolvidos">Alertas Resolvidos</label>
                                    <input type="number" id="alertas_resolvidos" name="alertas_resolvidos" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['alertas_resolvidos']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="ameacas_detectadas">Ameacas Detectadas</label>
                                    <input type="number" id="ameacas_detectadas" name="ameacas_detectadas" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['ameacas_detectadas']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="patches_instalados">Patches Instalados</label>
                                    <input type="number" id="patches_instalados" name="patches_instalados" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['patches_instalados']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="acessos_remotos">Acessos Remotos</label>
                                    <input type="number" id="acessos_remotos" name="acessos_remotos" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['acessos_remotos']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="pontuacao_integridade">Pontuacao de Integridade (%)</label>
                                    <input type="number" id="pontuacao_integridade" name="pontuacao_integridade" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['pontuacao_integridade']); ?>"
                                           min="0" max="100" required>
                                    <div class="form-help">Valor entre 0 e 100</div>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="falha_logon">Falhas de Logon</label>
                                    <input type="number" id="falha_logon" name="falha_logon" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['falha_logon']); ?>"
                                           min="0" required>
                                </div>
                            </div>
                        </div>

                        <!-- CHAMADOS -->
                        <div class="form-section">
                            <div class="section-title">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                Chamados
                            </div>
                            <div class="fields-grid">
                                <div class="form-group required">
                                    <label class="form-label" for="num_chamados_abertos">Chamados Abertos</label>
                                    <input type="number" id="num_chamados_abertos" name="num_chamados_abertos" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['num_chamados_abertos']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="num_chamados_fechados">Chamados Fechados</label>
                                    <input type="number" id="num_chamados_fechados" name="num_chamados_fechados" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['num_chamados_fechados']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="monitoramento_proativo">Monitoramento Proativo</label>
                                    <input type="number" id="monitoramento_proativo" name="monitoramento_proativo" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['monitoramento_proativo']); ?>"
                                           min="0" required>
                                </div>
                            </div>
                        </div>

                        <!-- COBERTURA -->
                        <div class="form-section">
                            <div class="section-title">
                                <svg class="icon" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                                Cobertura e Monitoramento
                            </div>
                            <div class="fields-grid">
                                <div class="form-group required">
                                    <label class="form-label" for="cobertura_antivirus">Cobertura Antivirus (%)</label>
                                    <input type="number" id="cobertura_antivirus" name="cobertura_antivirus" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['cobertura_antivirus']); ?>"
                                           min="0" max="100" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="cobertura_web_protection">Cobertura Web Protection (%)</label>
                                    <input type="number" id="cobertura_web_protection" name="cobertura_web_protection" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['cobertura_web_protection']); ?>"
                                           min="0" max="100" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="cobertura_atualizacao_patches">Cobertura Atualizacao Patches (%)</label>
                                    <input type="number" id="cobertura_atualizacao_patches" name="cobertura_atualizacao_patches" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['cobertura_atualizacao_patches']); ?>"
                                           min="0" max="100" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="disponibilidade_servidor">Disponibilidade Servidor (%)</label>
                                    <input type="number" id="disponibilidade_servidor" name="disponibilidade_servidor" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['disponibilidade_servidor']); ?>"
                                           min="0" max="100" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="web_protection_filtradas_bloqueadas">Web Protection - Filtradas/Bloqueadas</label>
                                    <input type="number" id="web_protection_filtradas_bloqueadas" name="web_protection_filtradas_bloqueadas" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['web_protection_filtradas_bloqueadas']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="web_protection_mal_intencionadas_bloqueadas">Web Protection - Mal Intencionadas/Bloqueadas</label>
                                    <input type="number" id="web_protection_mal_intencionadas_bloqueadas" name="web_protection_mal_intencionadas_bloqueadas" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['web_protection_mal_intencionadas_bloqueadas']); ?>"
                                           min="0" required>
                                </div>
                            </div>
                        </div>

                        <!-- BACKUP -->
                        <div class="form-section">
                            <div class="section-title">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                Backup
                            </div>
                            <div class="fields-grid">
                                <div class="form-group required">
                                    <label class="form-label" for="bkp_completo">Backup Completo</label>
                                    <input type="number" id="bkp_completo" name="bkp_completo" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['bkp_completo']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="bkp_com_erro">Backup com Erro</label>
                                    <input type="number" id="bkp_com_erro" name="bkp_com_erro" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['bkp_com_erro']); ?>"
                                           min="0" required>
                                </div>

                                <div class="form-group required">
                                    <label class="form-label" for="bkp_com_falha">Backup com Falha</label>
                                    <input type="number" id="bkp_com_falha" name="bkp_com_falha" class="form-control"
                                           value="<?php echo htmlspecialchars($registro['bkp_com_falha']); ?>"
                                           min="0" required>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ACOES DO FORMULARIO -->
                <div class="card-footer">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success btn-lg">
                            <svg class="icon" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Salvar Alteracoes
                        </button>
                        <a href="consulta.php" class="btn btn-secondary btn-lg">
                            <svg class="icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <?php endif; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-calcular total de dispositivos
            function calcularTotal() {
                const desktop = parseInt(document.getElementById('tipo_desktop').value) || 0;
                const notebook = parseInt(document.getElementById('tipo_notebook').value) || 0;
                const servidor = parseInt(document.getElementById('tipo_servidor').value) || 0;
                const total = desktop + notebook + servidor;
                document.getElementById('total_dispositivos').value = total;
            }

            // Adicionar eventos nos campos de dispositivos
            ['tipo_desktop', 'tipo_notebook', 'tipo_servidor'].forEach(function(id) {
                document.getElementById(id).addEventListener('input', calcularTotal);
            });

            // Validacao de percentuais
            function validarPercentual(input) {
                const valor = parseInt(input.value);
                if (valor < 0) input.value = 0;
                if (valor > 100) input.value = 100;
            }

            // Aplicar validacao aos campos de percentual
            ['pontuacao_integridade', 'cobertura_antivirus', 'cobertura_web_protection',
             'cobertura_atualizacao_patches', 'disponibilidade_servidor'].forEach(function(id) {
                const input = document.getElementById(id);
                input.addEventListener('blur', function() {
                    validarPercentual(this);
                });
            });

            // Validacao visual em tempo real
            const inputs = document.querySelectorAll('input[required], select[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    const field = this.closest('.form-group');
                    if (this.value.trim() === '') {
                        field.classList.add('invalid');
                        field.classList.remove('valid');
                    } else {
                        field.classList.add('valid');
                        field.classList.remove('invalid');
                    }
                });
            });

            // Confirmacao antes de sair
            let formModificado = false;
            const form = document.getElementById('editForm');
            const inputs_form = form.querySelectorAll('input, select');

            inputs_form.forEach(input => {
                input.addEventListener('change', function() {
                    formModificado = true;
                });
            });

            window.addEventListener('beforeunload', function(e) {
                if (formModificado) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // Remover aviso ao submeter
            form.addEventListener('submit', function() {
                formModificado = false;
            });

            // Atalhos de teclado
            document.addEventListener('keydown', function(e) {
                // Ctrl + S para salvar
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    form.submit();
                }

                // Escape para cancelar
                if (e.key === 'Escape') {
                    if (confirm('Deseja cancelar a edicao? Alteracoes nao salvas serao perdidas.')) {
                        window.location.href = 'consulta.php';
                    }
                }
            });

            // Animacao de foco nos campos
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                    this.parentElement.style.transition = 'transform 0.2s ease';
                });

                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>
