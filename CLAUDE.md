# APO João Pedro - Notas para o Claude

## Projeto
- **Website:** https://ralditech.com.br/apo-joao-pedro-raldi/
- **Cliente:** Unipar (faculdade)
- **Status:** Em desenvolvimento (acesso via https://ralditech.com.br/apo-joao-pedro-raldi/)

## Regras de comportamento

- NUNCA usar travessão "-" em nenhum arquivo, com exceção deste. Sempre substituir por hífen "-".
- Quando não tiver certeza em qual parte mexer, perguntar antes de agir: o site principal ou o gerenciador/alt CMS?
- Sempre que for necessário executar comandos SQL, deve criar uma migration para isso.
- PHP migrations: sempre que criar um arquivo PHP de migration, incluir `unlink(__FILE__)` após execução bem-sucedida - o arquivo deve se autodestruir.
- Após gerar qualquer migration, exibir no chat o link completo de acesso.
- Textos em português devem possuir a pontuação correta (acentos, cedilha, etc.).
- Arquivos temporários de diagnóstico também devem se autodestruir ao final da execução: adicionar `unlink(__FILE__);` como última instrução do script, antes do `?>`.
- Apenas um arquivo `.md` deve existir no projeto: este `CLAUDE.md`. Não criar outros arquivos `.md`.

## Comando de registro

- Quando o usuário digitar `Registra:` ou `Registrar:` seguido de um texto, transcrever o conteúdo da melhor forma possível e salvá-lo como nova regra neste `CLAUDE.md`, na seção mais adequada.

## Estilo de código

- Indentação sempre com tabulação (`\t`), nunca com espaços.
- Comentários em PHP de backend (lógica de negócio, APIs, migrations, scripts) devem existir e ser completos: explicar o que o bloco faz, por que existe, quais são os efeitos colaterais, dependências externas e comportamentos não óbvios. Quanto mais informação, melhor.
- Arquivos de frontend (HTML, CSS, JS) não devem ter comentários.

## Ambiente de desenvolvimento

- **Editor:** VS Code com extensão SFTP.
- **Deploy:** sempre em produção - não existe ambiente de teste.
- **Watcher SFTP:** monitora criação e alteração de arquivos; qualquer arquivo salvo é enviado automaticamente ao servidor.

## Infraestrutura

- **Servidor:** Linux com DirectAdmin.
- **Banco de dados:** MySQL (ralditechcombr_apo-websercice-restful).
-apo-joao-pedro-raldi/ **PHP:** 8.4.
- **Domínio:** ralditech.com.br/apo-joao-pedro-raldi/

## Credenciais (referência)
- **DB user/name:** ralditechcombr_apo-websercice-restful