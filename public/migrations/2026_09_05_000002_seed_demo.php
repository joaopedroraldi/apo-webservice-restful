<?php

/**
 * Seed de demonstração: popula fornecedores, produtos e os vínculos N:N
 * entre eles com dados fictícios, para facilitar a gravação do vídeo da
 * Etapa 3 (o enunciado pede para "deixar o banco de dados populado").
 *
 * Por que é um script à parte da migration de schema:
 *   Popular dados é uma preocupação diferente de criar tabelas; manter os
 *   dois separados permite rodar de novo a criação de schema (se um dia
 *   precisar) sem duplicar os dados de exemplo sem querer.
 *
 * Efeitos colaterais:
 *   - Insere registros em fornecedores, produtos e produto_fornecedor.
 *   - Ao final, apaga a si mesmo do disco (unlink(__FILE__)), seguindo a
 *     mesma convenção de autodestruição das migrations deste projeto.
 *
 * Comportamento não óbvio:
 *   - Roda dentro de uma transação. Diferente da migration de schema (onde
 *     CREATE TABLE dá commit implícito no MySQL), aqui só temos INSERTs,
 *     então a transação funciona normalmente: se algo falhar no meio,
 *     nada fica inserido pela metade.
 *   - Se rodado mais de uma vez (antes de se autodestruir por algum erro),
 *     os fornecedores não duplicam graças ao UNIQUE em cnpj (INSERT IGNORE);
 *     os produtos, porém, não têm campo único, então rodar duas vezes com
 *     sucesso geraria produtos duplicados - por isso a autodestruição é
 *     importante aqui também.
 */

require __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
$database = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE');
$username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME');
$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');

$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

try {
	$pdo = new PDO($dsn, $username, $password, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	]);

	$pdo->beginTransaction();

	$agora = date('Y-m-d H:i:s');

	// Fornecedores de exemplo. INSERT IGNORE evita erro caso o CNPJ já
	// exista (por exemplo, se este script for executado mais de uma vez
	// manualmente durante os testes).
	$fornecedores = [
		['nome' => 'Tech Distribuidora Ltda', 'cnpj' => '12.345.678/0001-90', 'email' => 'contato@techdist.com.br', 'telefone' => '(44) 3333-1000', 'endereco' => 'Av. Brasil, 1500 - Umuarama/PR'],
		['nome' => 'InfoParts Componentes', 'cnpj' => '98.765.432/0001-10', 'email' => 'vendas@infoparts.com.br', 'telefone' => '(11) 4444-2000', 'endereco' => 'Rua das Indústrias, 200 - São Paulo/SP'],
		['nome' => 'Mega Eletrônicos S.A.', 'cnpj' => '11.222.333/0001-44', 'email' => 'comercial@megaeletronicos.com.br', 'telefone' => '(41) 5555-3000', 'endereco' => 'Rod. BR-277, km 10 - Curitiba/PR'],
	];

	$stmtFornecedor = $pdo->prepare("
		INSERT IGNORE INTO fornecedores (nome, cnpj, email, telefone, endereco, created_at, updated_at)
		VALUES (:nome, :cnpj, :email, :telefone, :endereco, :agora, :agora)
	");

	$idsFornecedores = [];
	foreach ($fornecedores as $fornecedor) {
		$stmtFornecedor->execute([
			'nome' => $fornecedor['nome'],
			'cnpj' => $fornecedor['cnpj'],
			'email' => $fornecedor['email'],
			'telefone' => $fornecedor['telefone'],
			'endereco' => $fornecedor['endereco'],
			'agora' => $agora,
		]);

		// Recupera o id mesmo quando o INSERT IGNORE não insere nada (CNPJ
		// já existente), buscando pelo CNPJ, que é único.
		$busca = $pdo->prepare('SELECT id FROM fornecedores WHERE cnpj = :cnpj');
		$busca->execute(['cnpj' => $fornecedor['cnpj']]);
		$idsFornecedores[] = (int) $busca->fetchColumn();
	}

	// Produtos de exemplo, com preço e estoque variados para a demo ter
	// dados de aparência realista.
	$produtos = [
		['nome' => 'Teclado Mecânico RGB', 'descricao' => 'Teclado mecânico com switches azuis e iluminação RGB.', 'preco' => 259.90, 'quantidade_estoque' => 40],
		['nome' => 'Mouse Óptico Gamer', 'descricao' => 'Mouse óptico 7200 DPI com 6 botões programáveis.', 'preco' => 89.90, 'quantidade_estoque' => 75],
		['nome' => 'Monitor 24" Full HD', 'descricao' => 'Monitor LED 24 polegadas, 75Hz, Full HD.', 'preco' => 799.00, 'quantidade_estoque' => 20],
		['nome' => 'SSD NVMe 512GB', 'descricao' => 'SSD NVMe M.2 512GB, leitura de até 3500MB/s.', 'preco' => 349.50, 'quantidade_estoque' => 60],
		['nome' => 'Headset Gamer 7.1', 'descricao' => 'Headset com áudio surround 7.1 e microfone destacável.', 'preco' => 199.90, 'quantidade_estoque' => 35],
	];

	$stmtProduto = $pdo->prepare("
		INSERT INTO produtos (nome, descricao, preco, quantidade_estoque, created_at, updated_at)
		VALUES (:nome, :descricao, :preco, :quantidade_estoque, :agora, :agora)
	");

	$idsProdutos = [];
	foreach ($produtos as $produto) {
		$stmtProduto->execute([
			'nome' => $produto['nome'],
			'descricao' => $produto['descricao'],
			'preco' => $produto['preco'],
			'quantidade_estoque' => $produto['quantidade_estoque'],
			'agora' => $agora,
		]);

		$idsProdutos[] = (int) $pdo->lastInsertId();
	}

	// Vínculos N:N: cada produto é fornecido por um ou dois fornecedores,
	// simulando um catálogo real onde múltiplos fornecedores atendem o
	// mesmo produto.
	$vinculos = [
		[$idsProdutos[0], $idsFornecedores[0]],
		[$idsProdutos[0], $idsFornecedores[1]],
		[$idsProdutos[1], $idsFornecedores[1]],
		[$idsProdutos[2], $idsFornecedores[0]],
		[$idsProdutos[2], $idsFornecedores[2]],
		[$idsProdutos[3], $idsFornecedores[1]],
		[$idsProdutos[3], $idsFornecedores[2]],
		[$idsProdutos[4], $idsFornecedores[0]],
	];

	$stmtVinculo = $pdo->prepare("
		INSERT IGNORE INTO produto_fornecedor (produto_id, fornecedor_id, created_at, updated_at)
		VALUES (:produto_id, :fornecedor_id, :agora, :agora)
	");

	foreach ($vinculos as [$produtoId, $fornecedorId]) {
		$stmtVinculo->execute([
			'produto_id' => $produtoId,
			'fornecedor_id' => $fornecedorId,
			'agora' => $agora,
		]);
	}

	$pdo->commit();

	echo "OK: " . count($fornecedores) . " fornecedores, " . count($produtos) . " produtos e " . count($vinculos) . " vínculos inseridos.\n";

	unlink(__FILE__);
} catch (Throwable $e) {
	if (isset($pdo) && $pdo->inTransaction()) {
		$pdo->rollBack();
	}

	http_response_code(500);
	echo "ERRO ao popular o banco: " . $e->getMessage() . "\n";
}
