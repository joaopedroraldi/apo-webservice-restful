<?php

/**
 * Migration: cria as tabelas do domínio da Atividade Prática Orientada.
 *
 * Por que este arquivo existe:
 *   O ambiente de produção (DirectAdmin, sem acesso SSH/artisan) não permite rodar
 *   "php artisan migrate". Por isso, seguindo a convenção deste projeto, alterações
 *   de schema são feitas por um script PHP autoexecutável: basta acessá-lo pela URL
 *   uma vez, ele roda o SQL necessário e se autodestrói (unlink) para não poder ser
 *   executado de novo por engano nem deixar rastro publicamente acessível.
 *
 * O que este script cria:
 *   - fornecedores        : cadastro de fornecedores.
 *   - produtos            : cadastro de produtos.
 *   - produto_fornecedor  : tabela pivô do relacionamento N:N entre produtos e
 *                           fornecedores (um produto pode ter vários fornecedores
 *                           e um fornecedor pode fornecer vários produtos).
 *
 * Efeitos colaterais:
 *   - Cria três tabelas InnoDB no banco configurado no .env do projeto.
 *   - Ao final, apaga a si mesmo do disco (unlink(__FILE__)).
 *
 * Dependências externas:
 *   - Requer o autoload do Composer (vendor/vlucas/phpdotenv) só para ler o .env;
 *     a criação das tabelas em si usa PDO puro, sem depender do framework Lumen
 *     estar totalmente inicializado.
 *
 * Comportamento não óbvio:
 *   - Se as tabelas já existirem, os comandos usam "IF NOT EXISTS" e não falham;
 *     mesmo assim o arquivo se autodestrói ao final, então só existe uma janela
 *     de execução por deploy (se precisar rodar de novo, é preciso reenviar o
 *     arquivo por SFTP).
 */

// Carrega o autoload do Composer para reaproveitar o phpdotenv já usado pelo Lumen.
require __DIR__ . '/../../vendor/autoload.php';

// Carrega as variáveis do .env do projeto (dois níveis acima de public/migrations).
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

// Monta a conexão PDO diretamente a partir do .env, sem passar pelo container do Lumen
// (mantém o script independente, então ele funciona mesmo se algo no bootstrap quebrar).
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

	// Observação importante: NÃO envolvemos os CREATE TABLE em uma transação.
	// No MySQL/MariaDB, todo DDL (CREATE/ALTER/DROP TABLE) dá commit implícito
	// e encerra qualquer transação em andamento; tentar controlar isso com
	// beginTransaction()/commit() ao redor de DDL só gera o erro espúrio
	// "There is no active transaction". Cada CREATE TABLE abaixo já é atômico
	// por si só.

	// Tabela de fornecedores. CNPJ é único para evitar cadastro duplicado do
	// mesmo fornecedor.
	$pdo->exec("
		CREATE TABLE IF NOT EXISTS fornecedores (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			nome VARCHAR(150) NOT NULL,
			cnpj VARCHAR(18) NOT NULL,
			email VARCHAR(150) NULL,
			telefone VARCHAR(20) NULL,
			endereco VARCHAR(255) NULL,
			created_at TIMESTAMP NULL DEFAULT NULL,
			updated_at TIMESTAMP NULL DEFAULT NULL,
			UNIQUE KEY fornecedores_cnpj_unique (cnpj)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
	");

	// Tabela de produtos. preco fica em DECIMAL (nunca FLOAT) para não perder
	// precisão em valores monetários.
	$pdo->exec("
		CREATE TABLE IF NOT EXISTS produtos (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			nome VARCHAR(150) NOT NULL,
			descricao TEXT NULL,
			preco DECIMAL(10,2) NOT NULL,
			quantidade_estoque INT UNSIGNED NOT NULL DEFAULT 0,
			created_at TIMESTAMP NULL DEFAULT NULL,
			updated_at TIMESTAMP NULL DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
	");

	// Tabela pivô do relacionamento N:N produtos <-> fornecedores. A constraint
	// UNIQUE evita vincular o mesmo par produto/fornecedor duas vezes; os ON
	// DELETE CASCADE removem o vínculo automaticamente se o produto ou o
	// fornecedor forem excluídos, evitando registros órfãos.
	$pdo->exec("
		CREATE TABLE IF NOT EXISTS produto_fornecedor (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			produto_id BIGINT UNSIGNED NOT NULL,
			fornecedor_id BIGINT UNSIGNED NOT NULL,
			created_at TIMESTAMP NULL DEFAULT NULL,
			updated_at TIMESTAMP NULL DEFAULT NULL,
			UNIQUE KEY produto_fornecedor_unique (produto_id, fornecedor_id),
			CONSTRAINT produto_fornecedor_produto_fk
				FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE CASCADE,
			CONSTRAINT produto_fornecedor_fornecedor_fk
				FOREIGN KEY (fornecedor_id) REFERENCES fornecedores (id) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
	");

	echo "OK: tabelas fornecedores, produtos e produto_fornecedor criadas/verificadas com sucesso.\n";

	// Autodestruição: exigência do padrão deste projeto para migrations em PHP,
	// evitando que o script fique acessível publicamente depois de já ter sido usado.
	unlink(__FILE__);
} catch (Throwable $e) {
	// Em caso de erro o arquivo NÃO se autodestrói, para permitir corrigir e
	// tentar de novo sem precisar reenviar o arquivo por SFTP.
	http_response_code(500);
	echo "ERRO ao criar as tabelas: " . $e->getMessage() . "\n";
}
