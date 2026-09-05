<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use App\Models\Produto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller REST de Produtos.
 *
 * Cobre o CRUD completo (GET/POST/PUT/DELETE) exigido no trabalho, mais os
 * endpoints extras pedidos no enunciado: busca por nome, busca por fornecedor
 * e vínculo/desvínculo entre produto e fornecedor (relacionamento N:N).
 *
 * Todas as respostas são JSON, incluindo as de erro, para manter a API
 * consistente para quem for consumi-la (ex.: Postman, front-end).
 */
class ProdutoController extends Controller
{
	/**
	 * GET /produtos
	 *
	 * Lista todos os produtos. Aceita opcionalmente "?fornecedor_id=" na
	 * querystring para já filtrar pelos produtos de um fornecedor específico
	 * sem precisar chamar a rota /produtos/fornecedor/{id} separada.
	 * Cada produto já vem com seus fornecedores vinculados (eager loading),
	 * evitando o problema de N+1 queries.
	 */
	public function index(Request $request): JsonResponse
	{
		$query = Produto::query()->with('fornecedores');

		if ($request->filled('fornecedor_id')) {
			$fornecedorId = $request->query('fornecedor_id');

			$query->whereHas('fornecedores', function ($q) use ($fornecedorId) {
				$q->where('fornecedores.id', $fornecedorId);
			});
		}

		return response()->json($query->get());
	}

	/**
	 * GET /produtos/{id}
	 */
	public function show(int $id): JsonResponse
	{
		$produto = Produto::with('fornecedores')->find($id);

		if (!$produto) {
			return $this->respostaNaoEncontrado('Produto');
		}

		return response()->json($produto);
	}

	/**
	 * GET /produtos/nome/{nome}
	 *
	 * Busca produtos cujo nome contenha o termo informado (case-insensitive
	 * via LIKE), não uma igualdade exata: é mais útil para quem está
	 * pesquisando pelo nome do produto sem saber a grafia exata cadastrada.
	 */
	public function buscarPorNome(string $nome): JsonResponse
	{
		$produtos = Produto::with('fornecedores')
			->where('nome', 'LIKE', '%' . $nome . '%')
			->get();

		return response()->json($produtos);
	}

	/**
	 * GET /produtos/fornecedor/{fornecedorId}
	 *
	 * Lista os produtos vinculados a um fornecedor específico. Retorna 404
	 * se o fornecedor não existir, para diferenciar de "fornecedor existe
	 * mas não tem produtos" (que retornaria uma lista vazia).
	 */
	public function buscarPorFornecedor(int $fornecedorId): JsonResponse
	{
		$fornecedor = Fornecedor::find($fornecedorId);

		if (!$fornecedor) {
			return $this->respostaNaoEncontrado('Fornecedor');
		}

		return response()->json($fornecedor->produtos()->get());
	}

	/**
	 * POST /produtos
	 *
	 * Cadastra um novo produto. Aceita opcionalmente um array
	 * "fornecedores": [id, id, ...] no corpo da requisição para já criar o
	 * produto vinculado a um ou mais fornecedores num único request.
	 */
	public function store(Request $request): JsonResponse
	{
		$validador = Validator::make($request->all(), $this->regrasValidacao());

		if ($validador->fails()) {
			return $this->respostaValidacaoFalhou($validador);
		}

		$produto = Produto::create($request->only([
			'nome',
			'descricao',
			'preco',
			'quantidade_estoque',
		]));

		if ($request->filled('fornecedores')) {
			// sync() já valida que os ids existem na tabela fornecedores por
			// causa da foreign key; ids inválidos derrubariam a query com
			// um erro de integridade referencial, então filtramos antes.
			$idsValidos = Fornecedor::whereIn('id', $request->input('fornecedores'))
				->pluck('id');

			$produto->fornecedores()->sync($idsValidos);
		}

		return response()->json($produto->load('fornecedores'), 201);
	}

	/**
	 * PUT /produtos/{id}
	 *
	 * Atualiza os dados de um produto existente. O relacionamento com
	 * fornecedores só é alterado se "fornecedores" vier no corpo da
	 * requisição, para não desvincular tudo sem essa ser a intenção de
	 * quem está apenas atualizando o preço, por exemplo.
	 */
	public function update(Request $request, int $id): JsonResponse
	{
		$produto = Produto::find($id);

		if (!$produto) {
			return $this->respostaNaoEncontrado('Produto');
		}

		$validador = Validator::make($request->all(), $this->regrasValidacao(atualizacao: true));

		if ($validador->fails()) {
			return $this->respostaValidacaoFalhou($validador);
		}

		$produto->fill($request->only([
			'nome',
			'descricao',
			'preco',
			'quantidade_estoque',
		]));
		$produto->save();

		if ($request->has('fornecedores')) {
			$idsValidos = Fornecedor::whereIn('id', $request->input('fornecedores'))
				->pluck('id');

			$produto->fornecedores()->sync($idsValidos);
		}

		return response()->json($produto->load('fornecedores'));
	}

	/**
	 * DELETE /produtos/{id}
	 *
	 * Remove o produto. Os vínculos na tabela pivô são removidos
	 * automaticamente pelo ON DELETE CASCADE definido na migration.
	 */
	public function destroy(int $id): JsonResponse
	{
		$produto = Produto::find($id);

		if (!$produto) {
			return $this->respostaNaoEncontrado('Produto');
		}

		$produto->delete();

		return response()->json(['mensagem' => 'Produto removido com sucesso.']);
	}

	/**
	 * POST /produtos/{id}/fornecedores/{fornecedorId}
	 *
	 * Vincula um produto a um fornecedor (cria uma linha na tabela pivô).
	 * Usa attach() em vez de sync() para não desfazer outros vínculos já
	 * existentes desse produto com outros fornecedores.
	 */
	public function vincularFornecedor(int $id, int $fornecedorId): JsonResponse
	{
		$produto = Produto::find($id);

		if (!$produto) {
			return $this->respostaNaoEncontrado('Produto');
		}

		$fornecedor = Fornecedor::find($fornecedorId);

		if (!$fornecedor) {
			return $this->respostaNaoEncontrado('Fornecedor');
		}

		// syncWithoutDetaching evita erro de chave duplicada se o vínculo
		// já existir, simplesmente não faz nada nesse caso.
		$produto->fornecedores()->syncWithoutDetaching([$fornecedorId]);

		return response()->json($produto->load('fornecedores'));
	}

	/**
	 * DELETE /produtos/{id}/fornecedores/{fornecedorId}
	 *
	 * Remove o vínculo entre um produto e um fornecedor, sem excluir nem o
	 * produto nem o fornecedor em si.
	 */
	public function desvincularFornecedor(int $id, int $fornecedorId): JsonResponse
	{
		$produto = Produto::find($id);

		if (!$produto) {
			return $this->respostaNaoEncontrado('Produto');
		}

		$produto->fornecedores()->detach($fornecedorId);

		return response()->json($produto->load('fornecedores'));
	}

	/**
	 * Regras de validação do produto. Em atualização (PUT), os campos usam
	 * "sometimes" para permitir atualização parcial (não obriga reenviar
	 * todos os campos só para mudar um).
	 */
	private function regrasValidacao(bool $atualizacao = false): array
	{
		$obrigatorio = $atualizacao ? 'sometimes|required' : 'required';

		return [
			'nome' => "{$obrigatorio}|string|max:150",
			'descricao' => 'nullable|string',
			'preco' => "{$obrigatorio}|numeric|min:0",
			'quantidade_estoque' => 'sometimes|integer|min:0',
			'fornecedores' => 'sometimes|array',
			'fornecedores.*' => 'integer',
		];
	}

	/**
	 * Resposta padronizada de "não encontrado", reaproveitada por todos os
	 * métodos deste controller para manter o formato do erro consistente.
	 */
	private function respostaNaoEncontrado(string $entidade): JsonResponse
	{
		return response()->json([
			'mensagem' => "{$entidade} não encontrado(a).",
		], 404);
	}

	/**
	 * Resposta padronizada de falha de validação (422 Unprocessable Entity).
	 */
	private function respostaValidacaoFalhou($validador): JsonResponse
	{
		return response()->json([
			'mensagem' => 'Dados inválidos.',
			'erros' => $validador->errors(),
		], 422);
	}
}
