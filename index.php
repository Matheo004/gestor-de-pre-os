<?php
// 1. CONEXÃO
// Configurações de conexão (Substitua pelos seus dados)
$servername = "seu_servidor_aqui"; 
$username   = "seu_usuario_aqui"; 
$password   = "sua_senha_aqui"; 
$dbname     = "seu_banco_de_dados";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Erro de conexão: " . $conn->connect_error); }
$conn->set_charset("utf8"); // Garante acentuação correta

$mensagem = ""; $relatorio_comparativo = ""; $modal_exclusao = "";
$dados_labels = []; $dados_valores = [];
$titulo_grafico = "Geral (Gastos por Dia)";

// --- MELHORIA 1: CADASTRO COM PREPARED STATEMENTS (SEGURANÇA) ---
if (isset($_POST['cadastrar'])) {
    $nome = $_POST['nome_produto'];
    $valor_limpo = str_replace(['.', ','], ['', '.'], $_POST['valor_pago']);
    $unid = $_POST['unidade_medida'];
    $cat = $_POST['categoria']; 
    $loc = $_POST['local_compra'];
    $data = $_POST['data_compra'];
    
    // O "stmt" protege contra SQL Injection
    $stmt = $conn->prepare("INSERT INTO historico_precos (nome_produto, valor_pago, unidade_medida, categoria, local_compra, data_compra) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdssss", $nome, $valor_limpo, $unid, $cat, $loc, $data);
    
    if($stmt->execute()) {
        $mensagem = "<div class='toast toast-success'>✔️ Salvo com sucesso!</div>";
    }
    $stmt->close();
}

// --- MELHORIA 2: LÓGICA DO GRÁFICO ---
if (isset($_POST['gerar_relatorio']) && isset($_POST['selecionados'])) {
    $id_sel = intval($_POST['selecionados'][0]);
    
    $stmt = $conn->prepare("SELECT nome_produto FROM historico_precos WHERE id_registro = ?");
    $stmt->bind_param("i", $id_sel);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    $nome_p = $p['nome_produto'];
    $titulo_grafico = "Histórico de Preço: $nome_p";
    $stmt->close();

    $stmt_g = $conn->prepare("SELECT data_compra, valor_pago FROM historico_precos WHERE nome_produto = ? ORDER BY data_compra ASC");
    $stmt_g->bind_param("s", $nome_p);
    $stmt_g->execute();
    $res_g = $stmt_g->get_result();
    while($rg = $res_g->fetch_assoc()){
        $dados_labels[] = date('d/m', strtotime($rg['data_compra']));
        $dados_valores[] = $rg['valor_pago'];
    }
    $stmt_g->close();
} else {
    $res_g = $conn->query("SELECT data_compra, SUM(valor_pago) as total FROM historico_precos GROUP BY data_compra ORDER BY data_compra ASC LIMIT 7");
    while($rg = $res_g->fetch_assoc()){
        $dados_labels[] = date('d/m', strtotime($rg['data_compra']));
        $dados_valores[] = $rg['total'];
    }
}

// 2. EXCLUSÃO (Segura)
if (isset($_GET['solicitar_exclusao'])) {
    $id_del = intval($_GET['solicitar_exclusao']);
    $stmt = $conn->prepare("SELECT nome_produto FROM historico_precos WHERE id_registro = ?");
    $stmt->bind_param("i", $id_del);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows > 0) {
        $item = $res->fetch_assoc(); $nome_item = $item['nome_produto'];
        $modal_exclusao = "<div class='modal-overlay'><div class='modal-card'><div style='font-size:50px;'>⚠️</div><h3 style='color:#ef4444; font-weight:800;'>Confirmar Exclusão</h3><p>Deseja apagar <strong>$nome_item</strong>?</p><div style='display:flex; gap:15px; margin-top:25px; justify-content:center;'><a href='?executar_exclusao=$id_del' class='btn-confirmar-final'>SIM</a><a href='index.php' class='btn-cancelar-final'>CANCELAR</a></div></div></div>";
    }
    $stmt->close();
}
if (isset($_GET['executar_exclusao'])) {
    $id_final = intval($_GET['executar_exclusao']);
    $stmt = $conn->prepare("DELETE FROM historico_precos WHERE id_registro = ?");
    $stmt->bind_param("i", $id_final);
    $stmt->execute();
    $mensagem = "<div class='toast toast-error'>🗑️ Registro removido!</div>";
    $stmt->close();
}

// 4. RELATÓRIO COMPARATIVO
if (isset($_POST['gerar_relatorio']) && isset($_POST['selecionados'])) {
    $id_sel = intval($_POST['selecionados'][0]);
    $stmt = $conn->prepare("SELECT nome_produto FROM historico_precos WHERE id_registro = ?");
    $stmt->bind_param("i", $id_sel);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc(); 
    $nome_p = $p['nome_produto'];
    $stmt->close();

    $stmt_c = $conn->prepare("SELECT * FROM historico_precos WHERE nome_produto = ? ORDER BY data_compra DESC LIMIT 3");
    $stmt_c->bind_param("s", $nome_p);
    $stmt_c->execute();
    $res_3 = $stmt_c->get_result();
    $dados = []; $menor_preco = 999999;
    while($r = $res_3->fetch_assoc()) { $dados[] = $r; if($r['valor_pago'] < $menor_preco) { $menor_preco = $r['valor_pago']; } }
    
    $relatorio_comparativo = "<div class='premium-card-history'><div class='history-header'>📊 Comparativo: $nome_p</div>";
    foreach($dados as $d) {
        $is_cheapest = ($d['valor_pago'] == $menor_preco) ? " <span class='badge-cheap'>MAIS BARATO</span>" : "";
        $relatorio_comparativo .= "<div class='history-item'><div><span class='history-price'>R$ ".number_format($d['valor_pago'],2,',','.')."</span> $is_cheapest<div style='font-size:12px; color:#fbbf24; font-weight:bold;'>🛒 ".$d['local_compra']." | ".date('d/m/y', strtotime($d['data_compra']))."</div></div></div>";
    }
    $relatorio_comparativo .= "</div>";
    $stmt_c->close();
}

$sugestoes = $conn->query("SELECT DISTINCT nome_produto FROM historico_precos ORDER BY nome_produto ASC");
$lista_geral = $conn->query("SELECT * FROM historico_precos ORDER BY data_compra DESC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serrone | Gestor</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --preto: #000000; --azul: #1e293b; --amarelo: #fbbf24; }
        * { box-sizing: border-box; margin:0; padding:0; font-family: 'Sora', sans-serif; }
        body { background: #f1f5f9; color: var(--preto); padding-bottom: 120px; }
        .container { max-width: 800px; margin: auto; padding: 15px; }
        header h1 { text-align: center; padding: 20px; color: var(--azul); font-weight: 800; }
        .toast { padding: 18px; border-radius: 12px; margin-bottom: 20px; text-align: center; font-weight: 800; border: 3px solid; }
        .toast-success { background: #dcfce7; color: #166534; border-color: #22c55e; }
        .toast-error { background: #fee2e2; color: #991b1b; border-color: #ef4444; }
        .card { background: #fff; padding: 25px; border-radius: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); border: 2px solid var(--azul); margin-bottom: 25px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full { grid-column: span 2; }
        label { font-size: 14px; font-weight: 800; color: #000; text-transform: uppercase; margin-bottom: 8px; display: block; }
        input, select { width: 100%; padding: 16px; border-radius: 12px; border: 2px solid #000; font-size: 16px; font-weight: 700; background: #fff; }
        .btn-add { background: var(--azul); color: #fff; padding: 20px; border-radius: 15px; font-weight: 800; font-size: 18px; width: 100%; border: none; margin-top: 15px; cursor: pointer; }
        .prod-card { background: #fff; padding: 20px; border-radius: 15px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; border: 2px solid #cbd5e1; }
        .price { font-weight: 800; color: #166534; font-size: 18px; }
        .premium-card-history { background: var(--azul); color: #fff; padding: 25px; border-radius: 20px; border-left: 8px solid var(--amarelo); margin-bottom: 25px; }
        .history-header { font-weight: 800; font-size: 20px; color: var(--amarelo); margin-bottom: 15px; }
        .history-item { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .history-price { font-size: 20px; font-weight: 800; color: #4ade80; }
        .badge-cheap { background: #22c55e; color: white; font-size: 10px; padding: 4px 8px; border-radius: 5px; font-weight: 800; margin-left: 10px; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .modal-card { background: #fff; padding: 35px; border-radius: 25px; text-align: center; width: 350px; border: 4px solid var(--azul); }
        .btn-confirmar-final { background: #ef4444; color: #fff; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 800; display: inline-block; }
        .btn-cancelar-final { background: #f1f5f9; color: #000; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 800; border: 2px solid #000; display: inline-block; }
        .btn-float { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: var(--amarelo); color: #000; padding: 20px 40px; border-radius: 50px; font-weight: 800; border: 3px solid #000; z-index: 100; cursor: pointer; }

        @media print {
            .btn-float, .btn-add, #formCadastro, #mainSearch, .fas, .cat-tag, input[type="checkbox"], a { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            body { background: white; padding: 0; }
        }
    </style>
</head>
<body>

<?= $modal_exclusao ?>

<div class="container">
    <header><h1>🛒 GESTOR DE PREÇOS</h1></header>

    <div class="card" style="padding: 15px; border-color: #16a34a;">
       <p style="text-align:center; font-weight:800; color:#16a34a; margin-bottom:10px;">📈 <?= $titulo_grafico ?></p>
       <canvas id="graficoPrecos" style="width: 100%; max-height: 200px;"></canvas>
       
       <div style="display: flex; justify-content: center; gap: 20px; margin-top: 15px;">
           <a href="exportar_csv.php" style="color:#16a34a; font-weight:800; text-decoration:none; font-size:14px;">
               <i class="fas fa-file-excel"></i> EXCEL PRO
           </a>
           <a href="javascript:void(0)" onclick="window.print()" style="color:#ef4444; font-weight:800; text-decoration:none; font-size:14px;">
               <i class="fas fa-file-pdf"></i> GERAR PDF
           </a>
       </div>
    </div>

    <?= $mensagem ?>
    <?= $relatorio_comparativo ?>

    <section class="card">
        <form method="POST" id="formCadastro">
            <div class="grid">
                <div class="full">
                    <label>1. Nome do Produto</label>
                    <input type="text" name="nome_produto" id="nome_produto" list="sugestoes" onblur="buscarInteligente(this.value)" required autocomplete="off">
                    <datalist id="sugestoes">
                        <?php while($s = $sugestoes->fetch_assoc()){ echo "<option value='".htmlspecialchars($s['nome_produto'])."'>"; } ?>
                    </datalist>
                </div>
                
                <div class="full">
                    <label>2. Categoria</label>
                    <select name="categoria" id="categoria" required>
                        <option value="Mercado">🛒 Mercado Geral</option>
                        <option value="Hortifruti">🍎 Hortifruti</option>
                        <option value="Açougue">🥩 Açougue</option>
                        <option value="Farmácia">💊 Farmácia</option>
                        <option value="Padaria">🍞 Padaria</option>
                        <option value="Limpeza">🧼 Limpeza</option>
                        <option value="Higiene">🪥 Higiene Pessoal</option>
                        <option value="Outros">❓ Outros</option>
                    </select>
                </div>

                <div>
                    <label>3. Valor (R$)</label>
                    <input type="text" name="valor_pago" id="moeda" oninput="formatarMoeda(this)" inputmode="numeric" placeholder="0,00" required>
                </div>
                <div>
                    <label>4. Medida</label>
                    <select name="unidade_medida">
                        <option value="Un">Unidade</option>
                        <option value="kg">Quilo (kg)</option>
                        <option value="Litro">Litro</option>
                        <option value="Pacote">Pacote</option>
                    </select>
                </div>
                <div class="full">
                    <label>5. Local da Compra</label>
                    <input type="text" name="local_compra" id="local_compra" placeholder="Onde comprou?" required>
                </div>
                <div class="full">
                    <label>6. Data da Compra</label>
                    <input type="date" name="data_compra" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <button type="submit" name="cadastrar" class="btn-add">SALVAR REGISTRO</button>
        </form>
    </section>

    <div style="position:relative; margin-bottom:20px;">
        <input type="text" id="mainSearch" oninput="smartSearch()" placeholder="🔍 Pesquise por Produto, Local ou Categoria..." style="width:100%; padding:15px; border-radius:15px; border:3px solid var(--azul); font-weight:700;">
    </div>

    <form method="POST">
        <div id="listaWrapper">
            <?php while($row = $lista_geral->fetch_assoc()): ?>
            <div class="prod-card" data-string="<?= strtolower($row['nome_produto'] . ' ' . $row['local_compra'] . ' ' . $row['categoria']) ?>">
                <div style="display:flex; align-items:center; gap:15px;">
                    <input type="checkbox" name="selecionados[]" value="<?= $row['id_registro'] ?>" style="width:28px; height:28px; accent-color:var(--azul)">
                    <div>
                        <span style="font-weight:800; font-size:16px; color:var(--azul);"><?= $row['nome_produto'] ?></span>
                        <span class="cat-tag" style="font-size: 11px; background: #eee; padding: 2px 6px; border-radius: 4px; margin-left: 5px;"><?= $row['categoria'] ?></span><br>
                        <span style="font-weight:700; color:#64748b; font-size:13px;">📍 <?= $row['local_compra'] ?> | <?= date('d/m', strtotime($row['data_compra'])) ?></span>
                    </div>
                </div>
                <div style="text-align:right;"><span class="price">R$ <?= number_format($row['valor_pago'], 2, ',', '.') ?></span><br><a href="?solicitar_exclusao=<?= $row['id_registro'] ?>" style="color: #ef4444; font-size: 20px;"><i class="fas fa-trash-alt"></i></a></div>
            </div>
            <?php endwhile; ?>
        </div>
        <button type="submit" name="gerar_relatorio" class="btn-float">COMPARAR PREÇOS</button>
    </form>
</div>

<script>
// --- AJUSTE: FAZ AS MENSAGENS SUMIREM SOZINHAS ---
window.addEventListener('load', function() {
    const toast = document.querySelector('.toast');
    if (toast) {
        setTimeout(() => {
            toast.style.transition = "opacity 0.5s ease";
            toast.style.opacity = "0";
            setTimeout(() => { 
                toast.style.display = 'none';
                // Limpa a URL para a mensagem não voltar no F5
                const url = new URL(window.location);
                url.searchParams.delete('executar_exclusao');
                url.searchParams.delete('msg'); // caso use o padrão de msg futura
                window.history.replaceState({}, '', url);
            }, 500);
        }, 3000); // 3 segundos aparecendo
    }
});

let sugestaoValor = "";
let sugestaoLocal = "";

function formatarMoeda(i) {
    var v = i.value.replace(/\D/g,'');
    v = (v/100).toFixed(2) + '';
    v = v.replace(".", ",");
    v = v.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    v = v.replace(/(\d)(\d{3}),/g, "$1.$2,");
    i.value = v;
}

function buscarInteligente(nome) {
    if (nome.length < 2) return;
    fetch('busca_automacao.php?nome=' + encodeURIComponent(nome))
        .then(res => res.json())
        .then(data => {
            if (data.sucesso) {
                sugestaoValor = data.valor.replace('.', ',');
                sugestaoLocal = data.local;
                document.getElementById('moeda').placeholder = "Enter p/: " + sugestaoValor;
                document.getElementById('local_compra').placeholder = "Enter p/: " + sugestaoLocal;
            }
        });
}

document.getElementById('moeda').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        if (this.value === "" && sugestaoValor !== "") {
            e.preventDefault();
            this.value = sugestaoValor;
            formatarMoeda(this);
            document.getElementById('local_compra').focus();
        } else if (this.value !== "") {
            e.preventDefault();
            document.getElementById('local_compra').focus();
        }
    }
});

document.getElementById('local_compra').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && this.value === "" && sugestaoLocal !== "") {
        e.preventDefault();
        this.value = sugestaoLocal;
    }
});

function smartSearch() {
    let termo = document.getElementById('mainSearch').value.toLowerCase().trim();
    let cards = document.querySelectorAll('.prod-card');
    cards.forEach(card => {
        let infoCard = card.getAttribute('data-string');
        if (infoCard.indexOf(termo) !== -1) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

const ctx = document.getElementById('graficoPrecos').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($dados_labels) ?>,
        datasets: [{
            label: 'Valor (R$)',
            data: <?= json_encode($dados_valores) ?>,
            borderColor: '#16a34a',
            backgroundColor: 'rgba(22, 163, 74, 0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: false } }
    }
});
</script>
</body>
</html>
