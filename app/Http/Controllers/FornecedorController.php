<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller REST de Fornecedores.
 *
 * CRUD completo (GET/POST/PUT/DELETE). O vínculo entre fornecedor e produto
 * fica nas rotas de Produto (POST/DELETE /produtos/{id}/fornecedores/{id}),
 * para não duplicar a mesma responsabilidade nos dois controllers.
 */
class FornecedorController extends Controller
{
	/**
	 * GET /fornecedores
	 *
	 * Lista todos os fornecedores, já com os produtos que cada um fornece
	 * (eager loading), evitando N+1 queries se o consumidor da API precisar
	 * exibir essa informação junto.
	 */
	public function index(): JsonResponse
	{
		return response()->json(Fornecedor::with('produtos')->get());
	}

	/**
	 * GET /fornecedores/{id}
	 */
	public function show(int $id): JsonResponse
	{
		$fornecedor = Fornecedor::with('produtos')->find($id);

		if (!$fornecedor) {
			return $this->respostaNaoEncontrado();
		}

		return response()->json($fornecedor);
	}

	/**
	 * POST /fornecedores
	 */
	public function store(Request $request): JsonResponse
	{
		$validador = Validator::make($request->all(), $this->regrasValidacao());

		if ($validador->fails()) {
			return $this->respostaValidacaoFalhou($validador);
		}

		$fornecedor = Fornecedor::create($request->only([
			'nome',
			'cnpj',
			'email',
			'telefone',
			'endereco',
		]));

		return response()->json($fornecedor, 201);
	}

	/**
	 * PUT /fornecedores/{id}
	 */
	public function update(Request $request, int $id): JsonResponse
	{
		$fornecedor = Fornecedor::find($id);

		if (!$fornecedor) {
			return $this->respostaNaoEncontrado();
		}

		$validador = Validator::make($request->all(), $this->regrasValidacao(atualizacao: true, ignorarId: $id));

		if ($validador->fails()) {
			return $this->respostaValidacaoFalhou($validador);
		}

		$fornecedor->fill($request->only([
			'nome',
			'cnpj',
			'email',
			'telefone',
			'endereco',
		]));
		$fornecedor->save();

		return response()->json($fornecedor);
	}

	/**
	 * DELETE /fornecedores/{id}
	 *
	 * Remove o fornecedor. Os vínculos na tabela pivô são removidos
	 * automaticamente pelo ON DELETE CASCADE da migration; os produtos em
	 * si NÃO são excluídos, apenas deixam de estar ligados a este
	 * fornecedor.
	 */
	public function destroy(int $id): JsonResponse
	{
		$fornecedor = Fornecedor::find($id);

		if (!$fornecedor) {
			return $this->respostaNaoEncontrado();
		}

		$fornecedor->delete();

		return response()->json(['mensagem' => 'Fornecedor removido com sucesso.']);
	}

	/**
	 * Regras de validação do fornecedor. O CNPJ é único na tabela; ao
	 * atualizar, "ignorarId" exclui o próprio registro dessa checagem de
	 * unicidade (senão o fornecedor nunca conseguiria salvar mantendo o
	 * mesmo CNPJ que já tinha).
	 */
	private function regrasValidacao(bool $atualizacao = false, ?int $ignorarId = null): array
	{
		$obrigatorio = $atualizacao ? 'sometimes|required' : 'required';
		$regraCnpj = "{$obrigatorio}|string|max:18|unique:fornecedores,cnpj";

		if ($ignorarId !== null) {
			$regraCnpj .= ",{$ignorarId}";
		}

		return [
			'nome' => "{$obrigatorio}|string|max:150",
			'cnpj' => $regraCnpj,
			'email' => 'nullable|email|max:150',
			'telefone' => 'nullable|string|max:20',
			'endereco' => 'nullable|string|max:255',
		];
	}

	/**
	 * Resposta padronizada de "não encontrado" (404).
	 */
	private function respostaNaoEncontrado(): JsonResponse
	{
		return response()->json([
			'mensagem' => 'Fornecedor não encontrado.',
		], 404);
	}

	/**
	 * Resposta padronizada de falha de validação (422).
	 */
	private function respostaValidacaoFalhou($validador): JsonResponse
	{
		return response()->json([
			'mensagem' => 'Dados inválidos.',
			'erros' => $validador->errors(),
		], 422);
	}
}
