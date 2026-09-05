<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Rotas da aplicação
|--------------------------------------------------------------------------
|
| Controllers são referenciados pela string "Controller@metodo". O grupo
| com 'namespace' => 'App\Http\Controllers' já é aplicado em
| bootstrap/app.php (ao redor deste require), então aqui usamos só o nome
| curto da classe.
|
| Rotas com prefixo estático (ex.: /produtos/nome/{nome}) são registradas
| antes das rotas com parâmetro solto (/produtos/{id}) para não haver
| ambiguidade na hora do FastRoute casar a URL.
|
*/

$router->get('/', function () use ($router) {
	return response()->json([
		'aplicacao' => 'APO - Webservice RESTful (Desenvolvimento de Serviços para Internet)',
		'versao_lumen' => $router->app->version(),
		'documentacao' => url('/docs'),
	]);
});

// Produtos.
$router->group(['prefix' => 'produtos'], function () use ($router) {
	$router->get('/', 'ProdutoController@index');
	$router->get('/nome/{nome}', 'ProdutoController@buscarPorNome');
	$router->get('/fornecedor/{fornecedorId}', 'ProdutoController@buscarPorFornecedor');
	$router->get('/{id}', 'ProdutoController@show');
	$router->post('/', 'ProdutoController@store');
	$router->put('/{id}', 'ProdutoController@update');
	$router->delete('/{id}', 'ProdutoController@destroy');
	$router->post('/{id}/fornecedores/{fornecedorId}', 'ProdutoController@vincularFornecedor');
	$router->delete('/{id}/fornecedores/{fornecedorId}', 'ProdutoController@desvincularFornecedor');
});

// Fornecedores.
$router->group(['prefix' => 'fornecedores'], function () use ($router) {
	$router->get('/', 'FornecedorController@index');
	$router->get('/{id}', 'FornecedorController@show');
	$router->post('/', 'FornecedorController@store');
	$router->put('/{id}', 'FornecedorController@update');
	$router->delete('/{id}', 'FornecedorController@destroy');
});
