# APO - Webservice RESTful (Produtos e Fornecedores)

Atividade Prática Orientada da disciplina **Desenvolvimento de Serviços para Internet** (UNIPAR EAD).

- **Aluno:** João Pedro Raldi
- **RA:** 09048486
- **Professor:** Carlos Eduardo Simões Pelegrin
- **Temática:** Webservice RESTful

## Sobre o projeto

Webservice RESTful desenvolvido em **Lumen 10** (micro-framework do Laravel) para o
cadastro de **Produtos** e **Fornecedores**, com relacionamento **N:N** entre eles:
um produto pode ter vários fornecedores e um fornecedor pode fornecer vários produtos.

A API cobre os quatro verbos HTTP pedidos no enunciado (GET, POST, PUT, DELETE), além
de endpoints extras para busca por nome, busca por fornecedor e vínculo/desvínculo
entre produto e fornecedor.

## Tecnologias

- PHP 8.4
- [Lumen 10](https://lumen.laravel.com/docs/10.x)
- MySQL
- Composer

## Modelagem (DER)

O Diagrama Entidade-Relacionamento com o relacionamento N:N entre Produtos e
Fornecedores está em [`der/der.pdf`](der/der.pdf).

## Endpoints

Documentação interativa (Swagger/OpenAPI) publicada em:
**https://ralditech.com.br/apo-joao-pedro-raldi/docs/**

Resumo dos endpoints:

| Método | Rota                                             | Descrição                                   |
|--------|---------------------------------------------------|----------------------------------------------|
| GET    | `/produtos`                                        | Lista todos os produtos (aceita `?fornecedor_id=`) |
| GET    | `/produtos/{id}`                                   | Busca um produto pelo id                     |
| GET    | `/produtos/nome/{nome}`                            | Busca produtos pelo nome (contém)            |
| GET    | `/produtos/fornecedor/{fornecedorId}`              | Lista os produtos de um fornecedor           |
| POST   | `/produtos`                                        | Cadastra um novo produto                     |
| PUT    | `/produtos/{id}`                                   | Atualiza um produto                          |
| DELETE | `/produtos/{id}`                                   | Remove um produto                            |
| POST   | `/produtos/{id}/fornecedores/{fornecedorId}`       | Vincula um produto a um fornecedor           |
| DELETE | `/produtos/{id}/fornecedores/{fornecedorId}`       | Remove o vínculo entre produto e fornecedor  |
| GET    | `/fornecedores`                                    | Lista todos os fornecedores                  |
| GET    | `/fornecedores/{id}`                               | Busca um fornecedor pelo id                  |
| POST   | `/fornecedores`                                    | Cadastra um novo fornecedor                  |
| PUT    | `/fornecedores/{id}`                               | Atualiza um fornecedor                       |
| DELETE | `/fornecedores/{id}`                               | Remove um fornecedor                         |

## Como rodar localmente

```bash
composer install
cp .env.example .env
# edite o .env com as credenciais do seu MySQL local
php -S localhost:8000 -t public
```

## Configuração do banco de dados (schema e dados de exemplo)

Este projeto roda em uma hospedagem sem acesso SSH/artisan, então a criação das
tabelas e a carga de dados de exemplo são feitas por scripts PHP que rodam uma
única vez, direto pelo navegador, e se autodestroem depois de executados:

1. Configure o `.env` com as credenciais do banco.
2. Acesse `SEU_DOMINIO/migrations/2026_09_05_000001_create_tables.php` para criar as tabelas.
3. Acesse `SEU_DOMINIO/migrations/2026_09_05_000002_seed_demo.php` para popular dados de exemplo.

## Testando com Postman

Uma collection pronta com todos os endpoints está em
[`public/docs/postman_collection.json`](public/docs/postman_collection.json) - basta
importar no Postman e ajustar a variável `base_url`.

## Vídeo de demonstração

Link do vídeo (YouTube, não listado): _a preencher após a gravação_.
