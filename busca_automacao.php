<?php
$conn = new mysqli("localhost", "root", "", "db_gestor_precos");
$nome = $_GET['nome'] ?? '';
if ($nome) {
    $sql = "SELECT valor_pago, local_compra FROM historico_precos WHERE nome_produto = '$nome' ORDER BY id_registro DESC LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        echo json_encode(['sucesso' => true, 'valor' => number_format($row['valor_pago'], 2, '.', ''), 'local' => $row['local_compra']]);
        exit;
    }
}
echo json_encode(['sucesso' => false]);