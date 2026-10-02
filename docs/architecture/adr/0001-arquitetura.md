---
adr: 1
title: Definição da Arquitetura de Software, Stack Tecnológico Core e Estratégia de Resiliência Multi-Ambiente
status: Approved
date: 2026-09-13
authors:
  - Josias Wattrelos
  - Antigravity AI
impacted_components:
  - directory: backend/
  - directory: public_html/
  - file: composer.json
  - file: .env.example
  - directory: docs/architecture/
rules:
  architectural_pattern: "Clean Architecture + DDD Modular + Action-Domain-Responder (ADR)"
  primary_web_server: "NGINX + PHP-FPM"
  secondary_web_server: "Apache 2.4+ (com mod_rewrite)"
  runtime_language: "PHP 8.4 (Mínimo suportado: PHP 8.1+)"
  http_microframework: "Slim Framework 4 (PSR-7 / PSR-11 / PSR-15)"
  database_rdbms: "MySQL 8.0 / MariaDB 10.6+ via PDO Nativo (InnoDB)"
  cache_primary: "Redis 7.x (com fallback obrigatório para No-Cache / Arquivo)"
  messaging_primary: "RabbitMQ 3.x (com fallback obrigatório para Fila via MySQL)"
  compliance_standards:
    - PSR-4 (Autoloader)
    - PSR-7 (HTTP Message)
    - PSR-11 (Dependency Injection Container)
    - PSR-12 / PER-CS (Coding Style)
    - PSR-15 (HTTP Server Handlers & Middlewares)
---

# ADR 0001: Definição da Arquitetura de Software para criar um ambiente de testes exaustivo de login (apenas login).

## 1. Status
**Aprovado** (2026-09-13)

---

## 2. Contexto e Declaração do Problema

O **Beta Engine SaaS** foi concebido como uma plataforma de e-commerce e gestão comercial (B2B/B2C/POS) modular e de alta performance. 

Ao projetar o sistema a partir do zero, a engenharia deparou-se com o dilema clássico de plataformas SaaS modernas:
1. **Heterogeneidade de Ambientes de Produção:** O sistema precisa ser implantado com excelência tanto em **infraestruturas dedicadas de nuvem de alta performance** (VPS, instâncias Docker/Kubernetes na AWS/DigitalOcean) quanto em **hospedagens comerciais compartilhadas ou gerenciadas** (como Hostinger, cPanel), onde tecnologias como RabbitMQ e Redis frequentemente não estão disponíveis ou exigem custos adicionais.
2. **Evitar o Aprisionamento a Monólitos Pesados:** Frameworks "tudo-em-um" (como Magento ou Laravel padrão) impõem centenas de bibliotecas desnecessárias, aumentando o *Time to First Byte (TTFB)* e exigindo alto consumo de memória RAM por processo PHP.
3. **Escalabilidade e Longevidade do Código:** A lógica central de negócio (como cálculo de frete, regras fiscais, emissão de tickets de pré-venda e liquidação financeira) deve ser totalmente independente da infraestrutura, permitindo trocas de banco, mensageria e interface gráfica sem reescrever as regras empresariais.

---

## 3. Fatores de Decisão (Drivers Arquiteturais)

* **Performance Extrema & Baixo Footprint:** Inicialização do ciclo de vida HTTP na ordem de microssegundos ($< 15\text{ms}$).
* **Desacoplamento e Testabilidade (Clean Architecture):** 100% das regras de negócio testáveis sem necessidade de conexões ativas com banco de dados ou internet.
* **Resiliência e Portabilidade (Design com Fallback):** O sistema deve "degradar suavemente" (*graceful degradation*), operando com Redis e RabbitMQ quando disponíveis, mas continuando 100% funcional caso o cliente utilize uma hospedagem básica sem esses serviços.
* **Aderência aos Padrões PHP (FIG / PSRs):** Interoperabilidade total entre bibliotecas de mercado.

---

## 4. Decisão

Decidimos estabelecer a seguinte arquitetura de software, stack tecnológico e matriz de componentes para o **Beta Engine SaaS**:

```
┌────────────────────────────────────────────────────────────────────────┐
│                   STACK TECNOLÓGICO CORE DO SAAS                       │
├────────────────────────────────────────────────────────────────────────┤
│ 1. Arquitetura: Clean Architecture + DDD Modular + ADR                 │
│ 2. Servidor Web: NGINX + PHP-FPM (Prioritário) | Apache (Suportado)    │
│ 3. Runtime: PHP 8.4 (Prioritário) | PHP 8.1+ (Compatibilidade Mínima) │
│ 4. Framework HTTP: Slim Framework 4 (PSR-7 / PSR-15)                   │
│ 5. Banco de Dados: MySQL 8.0 / MariaDB 10.6+ (InnoDB / PDO Nativo)    │
│ 6. Cache de Alta Velocidade: Redis (com Fallback Transparente)         │
│ 7. Mensageria Assíncrona: RabbitMQ (com Fallback via Banco de Dados)   │
└────────────────────────────────────────────────────────────────────────┘
```

### 4.1. Estilo Arquitetural: Clean Architecture + DDD + ADR
- **Clean Architecture (Robert C. Martin):** Divisão estrita em 4 camadas: `Domain`, `Application`, `Infrastructure` e `Http` (Apresentação). A regra de dependência é absoluta: o Domínio nunca importa classes de infraestrutura.
- **Domain-Driven Design (DDD):** Entidades com estado e comportamento, *Value Objects* imutáveis para garantir invariantes (ex: `CpfCnpj`, `Money`, `Email`), *Agregados* para consistência transacional e interfaces de repositórios no domínio.
- **Action-Domain-Responder (ADR):** Substituição do MVC clássico por *Skinny Actions* de responsabilidade única. A Action recebe o request HTTP, invoca o Caso de Uso (`Application/UseCases`) e delega a saída para um `Responder` dedicado (`TwigHtmlResponder` ou `JsonApiResponse`).

### 4.2. Servidor Web: NGINX (Prioritário) com Suporte a Apache
- **Prioritário (Recomendado): NGINX + PHP-FPM via Unix Socket.**
  - *Motivo:* Arquitetura assíncrona orientada a eventos (*event-driven* com `epoll`), suportando dezenas de milhares de conexões simultâneas com consumo mínimo de RAM. O NGINX serve arquivos estáticos (CSS, JS, imagens) diretamente com cache agressivo, sem acionar o runtime do PHP.
- **Suportado: Apache 2.4+ com `mod_rewrite`.**
  - *Motivo:* Compatibilidade para clientes hospedados em cPanel e servidores compartilhados que dependem de regras locais em `.htaccess`.

### 4.3. Linguagem e Runtime: PHP 8.4 (Suporte Mínimo: PHP 8.1+)
- **Prioritário: PHP 8.4.**
  - *Motivo:* Tipagem estrita de propriedades, *Constructor Property Promotion*, *Readonly Classes*, funções assíncronas do Fiber, suporte aprimorado a tipos de interseção/união e ganho de performance do compilador JIT (*Just-In-Time*).
- **Suporte Mínimo: PHP 8.1.**
  - *Motivo:* Garantir que provedores que ainda não atualizaram para a versão 8.4 consigam executar a aplicação sem incompatibilidades sintáticas fatais.

### 4.4. Framework HTTP: Slim Framework 4
- *Motivo:* O Slim 4 gerencia estritamente o pipeline de requisições HTTP (roteamento e middlewares) em conformidade com a PSR-7 e PSR-15. Ele não impõe ORMs pesados, não cria containers monolíticos e tem um tempo de inicialização (*boot time*) quase nulo.

### 4.5. Banco de Dados Relacional: MySQL 8.0 / MariaDB 10.6+
- **Motor de Armazenamento:** Exclusivamente `InnoDB` com conformidade transacional ACID total.
- **Driver de Conexão:** `PDO` nativo com `PDO::ATTR_EMULATE_PREPARES => false`, garantindo tipagem nativa de retorno de colunas e proteção contra SQL Injection.
- **Suporte Futuro:** Abstração via *Data Mapper* e *QueryBuilder* permite estender a persistência para PostgreSQL sem alterar as regras de domínio.

### 4.6. Estratégia de Cache com Resiliência (Redis + Fallback)
- **Recomendado:** **Redis 7.x** para armazenamento em memória de sessões distribuídas e cache de consultas frequentes do catálogo.
- **Mecanismo de Fallback Obrigatório:** Em ambientes como Hostinger sem servidor Redis dedicado, o sistema detecta a flag `REDIS_ENABLED=false` no `.env` e direciona as sessões para o manipulador nativo do PHP e o cache para o sistema de arquivos local (`var/cache/`), sem lançar exceções.

### 4.7. Estratégia de Mensageria com Resiliência (RabbitMQ + Fallback)
- **Recomendado:** **RabbitMQ 3.x** com protocolo AMQP para despacho assíncrono de tarefas pesadas (envio de e-mails, emissão de boletos, notificações de webhooks).
- **Mecanismo de Fallback Obrigatório:** Caso a infraestrutura não possua RabbitMQ, a interface `QueueServiceInterface` ativa automaticamente o driver `DatabaseQueueAdapter`, gravando as mensagens em uma tabela MySQL de fila (`queue_jobs`) processada por um script Cron CLI periódico.

---

## 5. Consequências e Trade-offs

### Consequências Positivas (Prós)
* **Alta Portabilidade Comercial:** O produto pode ser vendido tanto para clientes que possuem servidores dedicados na nuvem (usando NGINX + Redis + RabbitMQ) quanto para pequenos lojistas que rodam em hospedagens econômicas de R$ 15/mês.
* **Testabilidade Imbatível:** Com a Clean Architecture e injeção de dependência via interfaces, podemos rodar 500 testes unitários em menos de 2 segundos, pois nenhuma classe de negócio depende do MySQL ou do NGINX para ser executada.
* **Segurança Reforçada:** O diretório público isolado (`public_html/`) com Front Controller único impede que arquivos `.env`, scripts internos do backend ou código-fonte fiquem acessíveis externamente.
* **Zero Aprisionamento Tecnológico:** Se amanhã o Slim Framework for descontinuado, apenas a pasta `src/Http/` é impactada. Toda a pasta `src/Domain/` e `src/Application/` permanece intacta.

### Consequências Negativas / Desafios (Contras)
* **Maior Quantidade Inicial de Arquivos:** Comparado a um script procedural com SQL inline, a Clean Architecture exige mais classes (Interfaces, DTOs, Use Cases, Mappers).
  - *Mitigação:* Desenvolvemos o script de automação (`003_gerar_estrutura_pastas.sh`) e templates de scaffolding para acelerar a criação de novas features.
* **Necessidade de Manter Drivers Duplos para Fallback:** A equipe precisa testar os dois cenários (com Redis/RabbitMQ e com Fallback em arquivo/banco).
  - *Mitigação:* A suíte de testes automatizados do PHPUnit possui testes de integração específicos para ambos os adaptadores.

---

## 6. Matriz de Ambientes de Implantação

| Componente | Ambiente Corporativo / Nuvem (Alta Escala) | Ambiente de Hospedagem Econômica (Entrada) |
| :--- | :--- | :--- |
| **Servidor Web** | NGINX + PHP-FPM (Unix Socket) | NGINX ou Apache 2.4 com `.htaccess` |
| **PHP Runtime** | PHP 8.4 com OPcache e JIT ativo | PHP 8.1 ou 8.2 (Hostinger/cPanel) |
| **Banco de Dados** | MySQL 8.0 Enterprise / RDS | MySQL 8.0 / MariaDB compartilhado |
| **Sessão & Cache** | Redis 7.x (Em memória RAM) | Sessão Nativa PHP + Cache em Disco (`var/cache/`) |
| **Filas & Eventos** | RabbitMQ Server (Workers daemon) | Tabela MySQL `queue_jobs` via Cron Job (`bin/cron`) |
| **Roteamento** | `index.php` via NGINX `try_files` | `index.php` via Apache `mod_rewrite` |

---

> **Conclusão:**  
> A aprovação desta ADR estabelece o alicerce fundamental do **Beta Engine SaaS**. A combinação de **Clean Architecture com estratégias de Fallback transparente** posiciona a plataforma como uma solução técnica de classe mundial: ultraveloz onde houver recursos e resiliente onde houver limitações de infraestrutura.