<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model do fornecedor.
 *
 * Um fornecedor pode fornecer vários produtos (relacionamento N:N), através
 * da mesma tabela pivô "produto_fornecedor" usada pelo model Produto.
 */
class Fornecedor extends Model
{
	use HasFactory;

	/**
	 * Nome da tabela, definido explicitamente pelo mesmo motivo do model Produto.
	 */
	protected $table = 'fornecedores';

	/**
	 * Campos preenchíveis em massa vindos do corpo das requisições da API.
	 */
	protected $fillable = [
		'nome',
		'cnpj',
		'email',
		'telefone',
		'endereco',
	];

	/**
	 * Relacionamento N:N inverso: produtos fornecidos por este fornecedor.
	 */
	public function produtos(): BelongsToMany
	{
		return $this->belongsToMany(
			Produto::class,
			'produto_fornecedor',
			'fornecedor_id',
			'produto_id'
		)->withTimestamps();
	}
}
