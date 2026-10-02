# Implementações futuras de Segurança.

## Cenário em um e-commerce
> Para um e-commerce, os critérios exigidos na criação de senhas visam equilibrar a segurança dos dados financeiros/pessoais do cliente com uma boa experiência de usuário (UX).
> Atualmente, o mercado segue as diretrizes globais do NIST (Instituto Nacional de Padrões e Tecnologia), que mudaram o foco de "senhas complexas difíceis de lembrar" para "senhas longas e resistentes a ataques automatizados".
> Abaixo estão os critérios normalmente exigidos e as melhores práticas adotadas:

## 🔒 Critérios de Força (Complexidade Mínima)

* Comprimento Mínimo: Exigência de no mínimo 8 a 12 caracteres. O tamanho da senha é o fator mais crítico contra ataques de força bruta.
* Variedade de Caracteres: Combinação de pelo menos três destas quatro categorias:
* Letras maiúsculas (A-Z)
* Letras minúsculas (a-z)
* Números (0-9)
* Caracteres especiais (@, #, $, %, etc.)

------------------------------
## 🚫 Filtros de Bloqueio (O que Proibir)
Para evitar senhas óbvias que ferramentas de hackers adivinham em segundos, o sistema deve recusar:

* Sequências repetitivas ou óbvias: Como 123456, abcdef ou qwerty.
* Dados do próprio cadastro: O sistema deve cruzar dados e proibir o uso do nome, sobrenome, data de nascimento, CPF ou o próprio e-mail do usuário na senha.
* Palavras comuns de dicionário: Uso de listas negras de senhas vulneráveis (ex: proibir a palavra senha123 ou ecommerce2026).

------------------------------
## 🎨 Critérios de Usabilidade (UX e Boas Práticas)
Exigir uma senha segura não pode fazer o cliente desistir da compra. Por isso, os e-commerces modernos aplicam estas regras de desenvolvimento:

* Medidor de Força em Tempo Real: Uma barra visual (geralmente mudando de vermelho para verde) que indica se a senha está fraca, média ou forte à medida que o usuário digita.
* Exibição dos Requisitos: Mostrar claramente quais critérios foram atendidos (ex: um check verde ao lado de "Pelo menos 8 caracteres").
* Opção "Mostrar Senha": Um ícone de olho para o usuário visualizar o que digitou, reduzindo erros de digitação e frustrações.
* Suporte a Espaços: Permitir que o usuário use espaços, viabilizando o uso de passphrases (frases secretas como meu gato gosta de comer peixe), que são extremamente seguras e fáceis de lembrar.

------------------------------
## 🛠️ Como isso vira Requisito de Software (Exemplo Prático)
Para os alunos de ADS, vejam como esse mapeamento de segurança vira um Requisito Não-Funcional (RNF) e seu respectivo Caso de Teste:

* RNF-01 (Segurança): O sistema deve impor políticas restritas de senha no cadastro de usuários, exigindo o mínimo de 8 caracteres, contendo letras, números e caracteres especiais, além de validar a senha contra uma lista de termos comuns.
* Caso de Teste Relacionado:
* Ação: Tentar cadastrar uma conta com a senha 12345678.
* Resultado Esperado: O sistema deve recusar o cadastro, exibir uma mensagem de erro instrutiva e destacar visualmente qual critério de segurança não foi atingido.

Podemos avançar estruturando esses critérios no formato que os professores de engenharia de software adoram. O que prefere ver agora?


