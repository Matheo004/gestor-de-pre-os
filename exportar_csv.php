<?php
// 1. CONEXÃO
// Configurações de conexão (Substitua pelos seus dados)
$servername = "seu_servidor_aqui"; 
$username   = "seu_usuario_aqui"; 
$password   = "sua_senha_aqui"; 
$dbname     = "seu_banco_de_dados";

$conn = new mysqli($servername, $username, $password, $dbname);

// FORÇAR O BANCO A MANDAR OS DADOS EM UTF8
$conn->set_charset("utf8");

$filename = "Relatorio_Serrone_" . date('d_m_Y') . ".xls";

// 2. HEADERS
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

// 3. INJETAR O "BOM" PARA O EXCEL RECONHECER UTF-8
echo "\xEF\xBB\xBF";

// 4. ESTRUTURA DA TABELA
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
echo '<table border="1">
    <tr>
        <th colspan="5" style="background-color: #1e293b; color: #ffffff; font-size: 18px; height: 40px;">RELATÓRIO DE PREÇOS - SERRONE BURGER</th>
    </tr>
    <tr style="background-color: #fbbf24; font-weight: bold; text-align: center;">
        <td style="width: 250px;">PRODUTO</td>
        <td style="width: 100px;">VALOR (R$)</td>
        <td style="width: 150px;">CATEGORIA</td>
        <td style="width: 150px;">LOCAL</td>
        <td style="width: 120px;">DATA</td>
    </tr>';

// 5. BUSCA OS DADOS (Sem funções de conversão que podem apagar o texto)
$query = $conn->query("SELECT nome_produto, valor_pago, categoria, local_compra, data_compra FROM historico_precos ORDER BY data_compra DESC");

while ($row = $query->fetch_assoc()) {
    echo '
    <tr>
        <td style="padding: 5px;">' . $row['nome_produto'] . '</td>
        <td style="text-align: right; padding: 5px;">' . number_format($row['valor_pago'], 2, ',', '.') . '</td>
        <td style="padding: 5px;">' . $row['categoria'] . '</td>
        <td style="padding: 5px;">' . $row['local_compra'] . '</td>
        <td style="text-align: center; padding: 5px;">' . date('d/m/Y', strtotime($row['data_compra'])) . '</td>
    </tr>';
}

echo '</table>';
exit;
?>
