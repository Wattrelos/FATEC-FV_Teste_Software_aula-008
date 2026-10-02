# Roteiro da Atividade — Segundo Encontro

## Resultado de aprendizagem

Ao final, o participante deverá conseguir:

- explicar por que as dependências Python são isoladas por projeto;
- criar e utilizar uma `.venv`;
- executar cenários Behave;
- relacionar uma frase Gherkin ao step Python correspondente;
- identificar ações e verificações realizadas pelo Playwright;
- interpretar resultados `Pass` e `Fail` sem esconder divergências.

## 1. Retomada

Relembre os quatro comportamentos já validados manualmente:

1. credenciais válidas;
2. senha incorreta;
3. usuário inexistente;
4. campos obrigatórios vazios.

Mensagem-chave: a automação começa depois que o comportamento foi compreendido.

## 2. Preparação do ambiente

Na raiz deste material, execute:

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install -r requirements.txt
python -m playwright install chromium
```

Verificações:

- a pasta `.venv` foi criada;
- o terminal apresenta `(.venv)`;
- Behave e Playwright foram instalados sem erro;
- o download do Chromium foi concluído.

## 3. Primeira execução automatizada

```powershell
python -m behave
```

Observe:

- quantidade de funcionalidades e cenários;
- passos aprovados ou com falha;
- tempo real informado pelo executor.

Não antecipe que a execução ficará `Green`. Primeiro observe o resultado real.

## 4. Ponte entre especificação e automação

Abra lado a lado:

- `features/login.feature`;
- `features/steps/login_steps.py`.

Localize a frase:

```gherkin
Quando o usuário preenche o e-mail com "usuario@teste.com"
```

Relacione-a ao decorator `@when`, ao parâmetro `{email}`, ao seletor `input-email` e à ação `fill`.

Depois repita a análise para:

- o clique em `button-submit`;
- a verificação de `feedback-message`;
- a exibição de `dashboard-panel`.

## 5. Demonstração visível

```powershell
$env:TESTE_HEADLESS="false"
python -m behave --name "Login realizado com sucesso"
Remove-Item Env:TESTE_HEADLESS
```

Peça que os participantes identifiquem cada ação descrita no Gherkin enquanto o navegador é controlado.

## 6. Manual e automatizado

Compare:

| Manual | Automatizado |
|---|---|
| Explora situações novas e usabilidade | Repete verificações conhecidas |
| Depende da interação humana | Executa ações programadas |
| Registro é produzido pelo executor | Behave apresenta Pass ou Fail |
| Mais custoso para repetir | Adequado para regressões frequentes |

Os dois tipos são complementares.

## 7. Falha controlada

Altere temporariamente, no cenário de sucesso, o texto esperado:

```gherkin
Então o sistema deve exibir a mensagem "Mensagem propositalmente incorreta"
```

Execute:

```powershell
python -m behave --name "Login realizado com sucesso"
```

Analise o cenário, o passo e a diferença indicada. Em seguida, desfaça a mudança, salve e execute novamente:

```powershell
python -m behave
```

O encerramento só ocorre depois de confirmar o retorno ao estado `Green`.

## 8. Perguntas de consolidação

1. O que pertence à regra de negócio e o que pertence à ferramenta?
2. Por que os seletores `data-testid` reduzem fragilidade?
3. O que um resultado `Fail` informa?
4. Quando o teste manual continua sendo necessário?
5. Qual novo comportamento poderia ser especificado e automatizado?

