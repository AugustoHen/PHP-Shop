<?php
include 'db.php';

// Verificando se todos os campos obrigatórios foram preenchidos
if (empty($_POST['nome']) || empty($_POST['dataNascimento']) || empty($_POST['morada'])) {
    echo '<div class="alert alert-error">Por favor, preencha todos os campos.</div>';
    header("Refresh:3; url=validarCompra.php"); // Redireciona para a página de validarCompra após 3 segundos
    exit;
}

$nome = trim($_POST['nome']);
$apelido = trim($_POST['apelido']);
$username = trim($_POST['username']);
$passe = password_hash(trim($_POST['passe']), PASSWORD_DEFAULT);
$dataNascimento = $_POST['dataNascimento'];
$morada = trim($_POST['morada']);
$quantidade1 = intval($_POST['quantidade1']);
$quantidade2 = intval($_POST['quantidade2']);
$quantidade3 = intval($_POST['quantidade3']);
$totalGeral = floatval($_POST['totalGeral']);

$produtosComprados = [];
if ($quantidade1 > 0) {
    $produtosComprados[] = "Produto A (Quantidade: $quantidade1)";
}
if ($quantidade2 > 0) {
    $produtosComprados[] = "Produto B (Quantidade: $quantidade2)";
}
if ($quantidade3 > 0) {
    $produtosComprados[] = "Produto C (Quantidade: $quantidade3)";
}

$produtosString = implode(", ", $produtosComprados);

// Verificando se o username já existe no banco de dados
$sqlUsernameCheck = "SELECT * FROM utilizadores WHERE username = ?";
$stmtUsernameCheck = $conn->prepare($sqlUsernameCheck);
$stmtUsernameCheck->bind_param("s", $username);
$stmtUsernameCheck->execute();
$result = $stmtUsernameCheck->get_result();

if ($result->num_rows > 0) {
    echo '<div class="alert alert-error">Erro: O nome de usuário já está em uso. Por favor, escolha outro.</div>';
    header("Refresh:3; url=validarCompra.php"); // Redireciona para a página de validarCompra após 3 segundos
    exit;
}

// Inserção do usuário
$sqlUtilizador = "INSERT INTO utilizadores (nome, apelido, username, passe) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sqlUtilizador);
$stmt->bind_param("ssss", $nome, $apelido, $username, $passe);
$stmt->execute();

// Verificando a idade do usuário
$hoje = new DateTime();
$dataNascimentoObj = new DateTime($dataNascimento);
$idade = $hoje->diff($dataNascimentoObj)->y;

if ($idade < 18) {
    echo '<div class="alert alert-warning">Você deve ter pelo menos 18 anos para realizar uma compra.</div>';
    header("Refresh:3; url=validarCompra.php"); // Redireciona para a página de validarCompra após 3 segundos
    exit;
}

// Inserção da encomenda
$sqlEncomenda = "INSERT INTO encomendas (nome, username, dataNascimento, morada, produtos, quantidade, total) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmtEncomenda = $conn->prepare($sqlEncomenda);
$stmtEncomenda->bind_param("sssssid", $nome, $username, $dataNascimento, $morada, $produtosString, $quantidadeTotal, $totalGeral);
$stmtEncomenda->execute();

// Atualizando a quantidade de produtos no estoque
if ($quantidade1 > 0) {
    $conn->query("UPDATE products SET quant = quant - $quantidade1 WHERE names = 'T-Shirt'");
}
if ($quantidade2 > 0) {
    $conn->query("UPDATE products SET quant = quant - $quantidade2 WHERE names = 'CD'");
}
if ($quantidade3 > 0) {
    $conn->query("UPDATE products SET quant = quant - $quantidade3 WHERE names = 'Souvenirs'");
}

$produtos = [];
if ($quantidade1 > 0) $produtos[] = "A x $quantidade1";
if ($quantidade2 > 0) $produtos[] = "B x $quantidade2";
if ($quantidade3 > 0) $produtos[] = "C x $quantidade3";
$produtosStr = implode(", ", $produtos);

// Redireciona o usuário após a finalização
header("Refresh:1; url=index.php");
exit;
?>
