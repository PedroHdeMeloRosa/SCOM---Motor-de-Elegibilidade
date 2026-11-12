# Organização e fluxo de desenvolvimento

Regras básicas para manter o SCOM organizado, seguro e fácil de evoluir.

## 1. O que cada branch desenvolve

| Branch | Escopo |
|---|---|
| `chore/laravel-bootstrap` | Laravel, dependências, ambiente, banco e testes iniciais. |
| `feat/role-authorization` | Login, perfis (estudante, coordenador e diretor) e permissões. |
| `feat/edital-management` | Cadastro, edição, critérios e publicação de editais. |
| `feat/eligibility-engine` | Cálculo de elegibilidade e explicação dos resultados. |
| `feat/student-area` | Perfil acadêmico, painel, consulta de editais e resultados. |
| `feat/ai-edital-import` | Extração de dados dos editais com IA, sempre sujeita à revisão humana. |

## 2. Ordem de integração

1. **Bootstrap:** deixar Laravel, banco e testes funcionando.
2. **Autenticação e permissões:** criar a base de usuários e controlar o acesso.
3. **Editais:** definir editais, critérios e publicação.
4. **Motor:** calcular a elegibilidade usando critérios aprovados.
5. **Área do estudante:** exibir editais e resultados. O perfil pode avançar após a autenticação; os resultados dependem do motor.
6. **Importação por IA:** auxiliar o cadastro de editais já definido, sem substituir a revisão humana.

As etapas podem avançar em paralelo quando as interfaces entre os módulos estiverem combinadas. A integração deve respeitar essa ordem de dependência.

## 3. Fluxo obrigatório de cada funcionalidade

**Requisito → Testes → Implementação → Testes novamente → Verificação → Revisão → Pull Request → Merge.**

1. **Requisito:** entender regras, dados, permissões e resultado esperado.
2. **Testes:** escrever casos que comprovem o comportamento. Quando possível, confirmar que falham antes da implementação.
3. **Implementação:** fazer a menor alteração que atende ao requisito.
4. **Testes novamente:** executar os testes da funcionalidade e depois a suíte completa.
5. **Verificação:** conferir segurança, banco, integração e, quando houver interface, testar o fluxo no navegador.
6. **Revisão:** conferir o diff e remover arquivos indevidos, código morto e segredos.
7. **Pull Request:** explicar a mudança, os testes executados e eventuais limitações. Integrar à `main` somente após revisão e validação.

Testes unitários verificam regras isoladas, como o cálculo de elegibilidade. Testes de funcionalidade verificam fluxos completos, como login, permissões e gravação no banco.

Comandos principais:

```powershell
php artisan test
git status -sb
git diff --check
git diff
```

## 4. Regras importantes

- Não desenvolver diretamente na `main`. Criar branches a partir de uma `main` atualizada.
- Cada branch deve tratar de um escopo claro.
- Controllers coordenam requisições; validação fica no servidor; Policies e middleware protegem o acesso.
- Usar Eloquent e migrations para persistência e evolução do banco.
- Testar tanto acessos permitidos quanto negados. Esconder um botão na tela não é controle de segurança.
- Não enviar `.env`, senhas, tokens ou dados pessoais ao Git.
- Não adicionar bibliotecas, camadas ou testes sem necessidade demonstrável.
- A IA apenas sugere informações de editais. Critérios oficiais precisam de revisão e aprovação humana.
- O sistema deve continuar permitindo cadastro manual se a IA estiver indisponível.

## 5. Padrão de commits

Usar o tipo em inglês e a descrição em português:

```text
tipo(escopo): descrição em português
```

Exemplos:

```text
chore(laravel): configura a estrutura inicial
feat(autenticacao): implementa permissões por perfil
feat(editais): cadastra critérios de elegibilidade
test(elegibilidade): verifica os critérios obrigatórios
fix(estudante): corrige acesso ao resultado
docs(ambiente): atualiza instruções de instalação
```

## Quando uma branch está pronta?

A funcionalidade atende ao requisito, os testes passam, as permissões e os dados foram verificados, o diff está limpo e o Pull Request explica o que foi feito e como foi testado.

## Referências

- [Laravel: testes](https://laravel.com/docs/13.x/testing)
- [Laravel: autorização](https://laravel.com/docs/13.x/authorization)
- [Laravel: banco de dados e migrations](https://laravel.com/docs/13.x/database)
- [Conventional Commits](https://www.conventionalcommits.org/)
