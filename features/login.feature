# language: pt

@auth @login @exaustivo
Funcionalidade: Autenticação de Usuários e Gestão de Sessão de Login
  Como um usuário cadastrado ou visitante
  Quero autenticar minhas credenciais na interface de login
  Para acessar o sistema com segurança e gerenciar minha sessão

  Contexto:
    Dado que o usuário está na página de login

  @sucesso @login_valido
  Cenário: Login realizado com sucesso
    Quando o usuário preenche o e-mail com "usuario@teste.com"
    E o usuário preenche a senha com "Senha@123"
    E clica no botão de entrar
    Então o sistema deve exibir a mensagem "Login realizado com sucesso!"
    E deve apresentar o painel principal simulado

  @falha @seguranca @anti_enumeracao
  Cenário: Tentativa de login com senha incorreta
    Quando o usuário preenche o e-mail com "usuario@teste.com"
    E o usuário preenche a senha com "SenhaIncorreta"
    E clica no botão de entrar
    Então o sistema deve exibir a mensagem de erro "Credenciais inválidas."

  @falha @seguranca @anti_enumeracao
  Cenário: Tentativa de login com e-mail não cadastrado
    Quando o usuário preenche o e-mail com "inexistente@teste.com"
    E o usuário preenche a senha com "Senha@123"
    E clica no botão de entrar
    Então o sistema deve exibir a mensagem de erro "Credenciais inválidas."

  @falha @conta_inativa
  Cenário: Tentativa de login com conta desativada
    Quando o usuário preenche o e-mail com "inativo@teste.com"
    E o usuário preenche a senha com "Senha@123"
    E clica no botão de entrar
    Então o sistema deve exibir a mensagem de erro "Conta de cliente desativada."

  @falha @validacao_campos
  Cenário: Tentativa de envio com campos obrigatórios vazios
    Quando o usuário deixa os campos de e-mail e senha vazios
    E clica no botão de entrar
    Então o sistema deve exibir o alerta "Preencha todos os campos obrigatórios."

  @logout @sessao
  Cenário: Encerramento de sessão (Logout) com sucesso
    Quando o usuário preenche o e-mail com "usuario@teste.com"
    E o usuário preenche a senha com "Senha@123"
    E clica no botão de entrar
    Então deve apresentar o painel principal simulado
    Quando o usuário clica no botão de sair
    Então o formulário de login deve ser reexibido
    E o sistema deve exibir a mensagem "Sessão encerrada com sucesso."
