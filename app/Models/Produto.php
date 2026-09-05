<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model do produto.
 *
 * Um produto pode estar vinculado a vários fornecedores (relacionamento N:N),
 * através da tabela pivô "produto_fornecedor". Por isso o model não tem uma
 * coluna "fornecedor_id": o vínculo mora na tabela pivô, não aqui.
 */
class Produto extends Model
{
	use HasFactory;

	/**
	 * Nome da tabela. Definido explicitamente porque o padrão do Eloquent
	 * (plural do nome da classe) já bateria com "produtos", mas deixamos
	 * explícito para não depender de convenção implícita.
	 */
	protected $table = 'produtos';

	/**
	 * Campos que podem ser preenchidos em massa (create()/fill()) vindos
	 * do corpo das requisições da API. Ficam de fora: id, created_at, updated_at.
	 */
	protected $fillable = [
		'nome',
		'descricao',
		'preco',
		'quantidade_estoque',
	];

	/**
	 * Conversões de tipo automáticas: preco sempre volta como string decimal
	 * com 2 casas (evita problemas de arredondamento de float no JSON) e
	 * quantidade_estoque sempre como inteiro.
	 */
	protected $casts = [
		'preco' => 'decimal:2',
		'quantidade_estoque' => 'integer',
	];

	/**
	 * Relacionamento N:N com Fornecedor através da tabela pivô
	 * "produto_fornecedor". withTimestamps() faz o Eloquent preencher
	 * created_at/updated_at da própria linha da tabela pivô ao vincular.
	 */
	public function fornecedores(): BelongsToMany
	{
		return $this->belongsToMany(
			Fornecedor::class,
			'produto_fornecedor',
			'produto_id',
			'fornecedor_id'
		)->withTimestamps();
	}
}
