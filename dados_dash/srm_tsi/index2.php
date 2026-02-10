<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="utils/logo_s.png" type="image/png">
    <title>Formulário de Coleta de Dados - TSI</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e4f1fe;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: linear-gradient(to right, #4facfe 0%, #00f2fe 100%);
        }

        .header {
            background-color: #0056b3;
            color: #ffffff;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
            font-size: 24px;
            font-weight: 600;
        }

        .form-container {
            width: 90%;
            max-width: 600px;
            margin: auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            border-radius: 5px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #007bff;
            outline: none;
        }

        /* Oculte as opções inicialmente */
        .hidden-options {
            display: none;
        }
    </style>
    <script>
        function mostrarTripulantes() {
            var tripulanteSelect = document.getElementById('tripulante');
            var tripulantesDiv = document.getElementById('tripulantes-div');

            // Exibe ou oculta o div de tripulantes com base na seleção
            if (tripulanteSelect.value !== "") {
                tripulantesDiv.style.display = "block";
            } else {
                tripulantesDiv.style.display = "none";
            }
        }
    </script>
</head>

<body>
    <div class="form-container">
        <div class="header">
            <h2>COLETA DE DADOS SRM - TSI</h2>
        </div>

        <h3>COBERTURA DOS SERVIÇOS ATIVOS</h3>

        <form action="" method="post">
            <label for="tripulante">Tripulante:</label>
            <select id="tripulante" name="tripulante" required onchange="mostrarTripulantes()">
                <option value="">Selecione o Tripulante</option>
                <option value="A Formula">A Formula</option>
                <option value="Aguia Transportes">Aguia Transportes</option>
                <option value="Amaral & CIA">Amaral & CIA</option>
                <option value="AgroBI">AgroBI</option>
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
                <option value="Horizonti Digital">Horizonti Digital</option>
                <option value="Imoveis para Expatriados">Imoveis para Expatriados</option>
                <option value="JLP Contabilidade">JLP Contabilidade</option>
                <option value="Lumma Despachante">Lumma Despachante</option>
                <option value="Lucas Soares & CIA">Lucas Soares & CIA</option>
                <option value="Marcuzzo & CIA">Marcuzzo & CIA</option>
                <option value="Medeiros & CIA">Medeiros & CIA</option>
                <option value="Mourão e Vasconcelos & CIA">Mourão e Vasconcelos & CIA</option>
                <option value="Munhoz & CIA">Munhoz & CIA</option>
                <option value="MGM Farma">MGM Farma</option>
                <option value="Onboarding Tripulantes">Onboarding Tripulantes</option>
                <option value="Ortec Contabilidade">Ortec Contabilidade</option>
                <option value="Paiva & CIA">Paiva & CIA</option>
                <option value="Paiva, Pokrovsky & CIA">Paiva, Pokrovsky & CIA</option>
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
            </select><br>

            <div id="tripulantes-div" style="display:none;">
                <!-- Aqui ficam os campos que aparecem após a seleção -->
                <label for="ano">Ano:</label>
                <input type="number" id="ano" name="ano" required><br>

                <label for="mes">Mês:</label>
                <select id="mes" name="mes" required>
                    <option value="Janeiro">Janeiro</option>
                    <option value="Fevereiro">Fevereiro</option>
                    <option value="Março">Março</option>
                    <option value="Abril">Abril</option>
                    <option value="Maio">Maio</option>
                    <option value="Junho">Junho</option>
                    <option value="Julho">Julho</option>
                    <option value="Agosto">Agosto</option>
                    <option value="Setembro">Setembro</option>
                    <option value="Outubro">Outubro</option>
                    <option value="Novembro">Novembro</option>
                    <option value="Dezembro">Dezembro</option>
                </select><br>

                <label for="pontuacao_integridade">Pontuação de Integridade:</label>
                <input type="number" id="pontuacao_integridade" name="pontuacao_integridade" required><br>

                <label for="monitoramento_proativo">Monitoramento Proativo:</label>
                <input type="number" id="monitoramento_proativo" name="monitoramento_proativo" required><br>

                <label for="disponibilidade_servidor">Disponibilidade do servidor:</label>
                <input type="number" id="disponibilidade_servidor" name="disponibilidade_servidor" required><br>

                <label for="falha_logon">Falhas de Logon:</label>
                <input type="number" id="falha_logon" name="falha_logon" required><br>

                <label for="cobertura_antivirus">Cobertura de Antivírus:</label>
                <input type="number" id="cobertura_antivirus" name="cobertura_antivirus" required><br>

                <label for="cobertura_atualizacao_patches">Cobertura de Atualização de Patches:</label>
                <input type="number" id="cobertura_atualizacao_patches" name="cobertura_atualizacao_patches" required><br>

                <label for="cobertura_web_protection">Cobertura de Web Protection:</label>
                <input type="number" id="cobertura_web_protection" name="cobertura_web_protection" required><br>

                <label for="total_dispositivos">Total de Dispositivos:</label>
                <input type="number" id="total_dispositivos" name="total_dispositivos" required><br>

                <label for="tipo_desktop">Desktop:</label>
                <input type="number" id="tipo_desktop" name="tipo_desktop" required><br>

                <label for="tipo_notebook">Notebook:</label>
                <input type="number" id="tipo_notebook" name="tipo_notebook" required><br>

                <label for="tipo_servidor">Servidor:</label>
                <input type="number" id="tipo_servidor" name="tipo_servidor" required><br>

                <label for="alertas_resolvidos">Alertas Resolvidos:</label>
                <input type="number" id="alertas_resolvidos" name="alertas_resolvidos" required><br>

                <label for="ameacas_detectadas">Ameaças Detectadas:</label>
                <input type="number" id="ameacas_detectadas" name="ameacas_detectadas" required><br>

                <label for="patches_instalados">Patches Instalados:</label>
                <input type="number" id="patches_instalados" name="patches_instalados" required><br>

                <label for="acessos_remotos">Acessos Remotos:</label>
                <input type="number" id="acessos_remotos" name="acessos_remotos" required><br>

                <label for="web_protection_filtradas_bloqueadas">Web Protection - Filtradas/Bloqueadas:</label>
                <input type="number" id="web_protection_filtradas_bloqueadas" name="web_protection_filtradas_bloqueadas" required><br>

                <label for="web_protection_mal_intencionadas_bloqueadas">Web Protection - Mal Intencionadas/Bloqueadas:</label>
                <input type="number" id="web_protection_mal_intencionadas_bloqueadas" name="web_protection_mal_intencionadas_bloqueadas" required><br>

                <label for="bkp_completo">Backup Completo:</label>
                <input type="number" id="bkp_completo" name="bkp_completo" required><br>

                <label for="bkp_com_erro">Backup com Erro:</label>
                <input type="number" id="bkp_com_erro" name="bkp_com_erro" required><br>

                <label for="bkp_com_falha">Backup com Falha:</label>
                <input type="number" id="bkp_com_falha" name="bkp_com_falha" required><br>

                <label for="chamados_abertos">Número de Chamados Abertos:</label>
                <input type="number" id="chamados_abertos" name="chamados_abertos" required><br>

                <label for="chamados_fechados">Número de Chamados Fechados:</label>
                <input type="number" id="chamados_fechados" name="chamados_fechados" required><br>
            </div>

            <input type="submit" value="Enviar">
        </form>
        <?php
    // Se o formulário foi submetido
    if(isset($_POST['submit'])) {
        // Obter os dados do formulário
        $tripulante = $_POST['tripulante'];
        $mes = $_POST['mes'];
        $total_dispositivos = $_POST['total_dispositivos'];
        $tipo_desktop = $_POST['tipo_desktop'];
        $tipo_notebook = $_POST['tipo_notebook'];
        $tipo_servidor = $_POST['tipo_servidor'];
        $alertas_resolvidos = $_POST['alertas_resolvidos'];
        $ameacas_detectadas = $_POST['ameacas_detectadas'];
        $patches_instalados = $_POST['patches_instalados'];
        $acessos_remotos = $_POST['acessos_remotos'];
        $pontuacao_integridade = $_POST['pontuacao_integridade'];
        $disponibilidade_servidor  = $_POST['disponibilidade_servidor'];
        $num_chamados_abertos = $_POST['num_chamados_abertos'];
        $num_chamados_fechados = $_POST['num_chamados_fechados'];
        $monitoramento_proativo = $_POST['monitoramento_proativo'];
        $falha_logon = $_POST['falha_logon'];
        $cobertura_atualizacao_patches = $_POST['cobertura_atualizacao_patches'];
        $cobertura_antivirus = $_POST['cobertura_antivirus'];
        $cobertura_web_protection = $_POST['cobertura_web_protection'];
        $web_protection_filtradas_bloqueadas = $_POST['web_protection_filtradas_bloqueadas'];
        $web_protection_mal_intencionadas_bloqueadas = $_POST['web_protection_mal_intencionadas_bloqueadas'];
        $bkp_completo = $_POST['bkp_completo'];
		$bkp_com_erro = $_POST['bkp_com_erro'];
		$bkp_com_falha = $_POST['bkp_com_falha'];


        // Configurações de conexão com o banco de dados
        $servidor = "localhost"; // Endereço do servidor MySQL (normalmente "localhost")
        $usuario = "somaxi"; // Nome de usuário do MySQL
        $senha = "S0m4x1@193"; // Senha do MySQL
        $banco_de_dados = "dados_tripulantes_tsi"; // Nome do banco de dados MySQL

        // Estabelecer conexão com o banco de dados
        $conn = new mysqli($servidor, $usuario, $senha, $banco_de_dados);

      // Verificar conexão
        if ($conn->connect_error) {
            die("Falha na conexão: " . $conn->connect_error);
        } else {
            echo "";
        }

        // Preparar e executar a consulta SQL para inserir os dados no banco de dados
        $sql = "INSERT INTO tabela_dados_tsi (tripulante, mes, total_dispositivos, tipo_desktop, tipo_notebook, tipo_servidor, disponibilidade_servidor, alertas_resolvidos, ameacas_detectadas, patches_instalados, acessos_remotos, pontuacao_integridade, num_chamados_abertos, num_chamados_fechados, monitoramento_proativo, falha_logon, cobertura_antivirus, cobertura_atualizacao_patches, cobertura_web_protection, web_protection_filtradas_bloqueadas, web_protection_mal_intencionadas_bloqueadas, bkp_com_falha, bkp_com_erro, bkp_completo) 
        VALUES ('$tripulante', '$mes', '$total_dispositivos', '$tipo_desktop', '$tipo_notebook', '$tipo_servidor', '$disponibilidade_servidor', '$alertas_resolvidos', '$ameacas_detectadas', '$patches_instalados', '$acessos_remotos', '$pontuacao_integridade', '$num_chamados_abertos', '$num_chamados_fechados', '$monitoramento_proativo', '$falha_logon', '$cobertura_antivirus', '$cobertura_atualizacao_patches', '$cobertura_web_protection', '$web_protection_filtradas_bloqueadas', '$web_protection_mal_intencionadas_bloqueadas', '$bkp_com_falha', '$bkp_com_erro', '$bkp_completo')";
            if ($conn->query($sql) === TRUE) {
                echo "<script>alert('Dados inseridos com sucesso no banco de dados.');</script>";
            } else {
                echo "<script>alert('Erro ao inserir dados: " . $conn->error . "');</script>";
            }

        // Fechar conexão
        $conn->close();
    }
    ?>

    </div>
</body>

</html>