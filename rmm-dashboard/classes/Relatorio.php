<?php
class Relatorio {
public static function getKPIData() {
    $conn = getDBConnection();
    
    $query = "SELECT 
        AVG(pontuacao_integridade) AS pontuacao_media,
        AVG(disponibilidade_servidor) AS disponibilidade_media,
        AVG(antivirus_cobertura) AS cobertura_av,
        SUM(alertas_resolvidos) AS total_alertas
    FROM rmm_relatorios_new  // Tabela especificada corretamente
    WHERE ano_relatorio = YEAR(CURRENT_DATE)";
    
    $result = $conn->query($query);
    return $result->fetch_assoc();
}
	

    }
    
    public static function getMonthlyTrends() {
        $conn = getDBConnection();
        
        $query = "SELECT 
            CONCAT(mes_relatorio, '/', ano_relatorio) AS periodo,
            AVG(pontuacao_integridade) AS pontuacao,
            AVG(disponibilidade_servidor) AS disponibilidade,
            SUM(alertas_resolvidos) AS alertas
        FROM rmm_relatorios_new
        GROUP BY ano_relatorio, mes_relatorio
        ORDER BY ano_relatorio, mes_relatorio";
        
        $result = $conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public static function getClientsData() {
        $conn = getDBConnection();
        
        $query = "SELECT 
            cliente,
            AVG(pontuacao_integridade) AS pontuacao,
            AVG(antivirus_cobertura) AS cobertura_av,
            COUNT(*) AS total_relatorios
        FROM rmm_relatorios_new
        GROUP BY cliente
        ORDER BY pontuacao DESC";
        
        $result = $conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
