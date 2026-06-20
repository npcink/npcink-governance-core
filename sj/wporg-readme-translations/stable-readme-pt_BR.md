# pt_BR - Stable Readme Translation Draft

## Short Description

Governança, aprovação, verificação antes do commit e logs de auditoria para operações WordPress assistidas por IA.

## Description

Npcink Governance Core é a camada de governança do Npcink AI para operações WordPress. Ela oferece a administradores do site e plugins host confiáveis uma camada de aprovação revisável antes que ações assistidas por IA sejam confirmadas em um site WordPress.

Core descobre operações disponíveis da WordPress Abilities API, registra ações propostas por IA, oferece suporte a aprovação e rejeição, retorna contexto de commit preflight e armazena evidências de auditoria. Ele foi projetado para instalações em que ferramentas de IA, adapters ou plugins de produto podem solicitar alterações no WordPress, mas o site ainda precisa de um registro claro de governança.

### O que o Core faz

- Descobre WordPress abilities disponíveis para entrada na governança.
- Armazena proposals para operações WordPress assistidas por IA.
- Oferece suporte a decisões de approve e reject com evidência de auditoria.
- Fornece contexto de commit-preflight antes que um host ou adapter confiável execute gravações finais.
- Gerencia scoped app keys para clientes confiáveis de governança.
- Registra eventos de auditoria para criação de proposal, policy evaluation, approval, rejection, commit preflight e execution-result handoff.
- Mantém a governança separada da geração de conteúdo, roteamento de modelos, cobrança de serviços em nuvem e execução de gravações finais.

### Para quem é este plugin

Npcink Governance Core é para administradores WordPress, plugins host, adapters e desenvolvedores que precisam de um limite local de aprovação e auditoria para operações WordPress assistidas por IA. Ele é útil quando ferramentas de IA podem preparar ou solicitar alterações, mas o proprietário do site ou um cliente confiável de governança precisa revisar, aprovar e rastrear essas alterações.

Este plugin não é um redator de IA de um clique, assistente de SEO, gerador de imagens, chatbot ou runtime de fluxo de trabalho. Workflows de produto e ability callbacks pertencem a plugins provider, adapter ou de produto separados.

### Requisitos e integrações

Core funciona melhor com WordPress 7.0 ou superior e provedores da WordPress Abilities API. O provider de referência de primeira parte é o Npcink Abilities Toolkit, mas o ciclo básico de governança também pode governar provedores externos da WordPress Abilities API que exponham ability ids, schemas, permission callbacks, risk metadata e dry-run previews estáveis.

Core expõe endpoints REST de governança em `/wp-json/npcink-governance-core/v1/`. Adapters e plugins host confiáveis podem usar esses endpoints para criar proposals, aprovar ou rejeitar proposals, solicitar commit preflight e registrar resultados de execução externa.

### Privacidade e dados

Npcink Governance Core não chama serviços externos de IA, não carrega recursos remotos e não envia dados do site para terceiros. Ele armazena registros de governança localmente em tabelas do banco de dados WordPress, incluindo proposal data, audit events, app-key metadata e rate-limit state. App-key secrets são armazenados com hash, e bearer tokens de uso único são exibidos apenas no momento da criação.

### Limites

Core não gera conteúdo, não roteia modelos, não executa MCP nem workflow runtimes, não armazena provider credentials, não faz proxy de ability execution e não executa mutações finais no WordPress. As gravações finais pertencem a um host, adapter ou runtime confiável fora do Core depois que a etapa de governança é concluída.

## Installation

1. Envie o plugin para `wp-content/plugins/npcink-governance-core`.
2. Ative o Npcink Governance Core no WordPress.
3. Abra Npcink AI > Core para revisar status de governança, proposals, audit entries e controles avançados de Core app-key.

## FAQ

### Para quem é este plugin?

É para administradores de sites, plugins host, adapters e desenvolvedores que precisam de governança revisável para operações WordPress assistidas por IA. Ele é especialmente útil quando ferramentas de IA podem preparar alterações, mas o site ainda precisa de aprovação, commit preflight e evidência de auditoria antes que essas alterações sejam aplicadas.

### Este é um gerador de conteúdo por IA?

Não. Core não escreve artigos, não gera mídia, não cria textos de SEO, não responde comentários, não escolhe modelos de IA e não armazena provider credentials. Ele governa proposed operations criadas por ferramentas, adapters ou provedores da WordPress Abilities API separados.

### Core executa gravações de IA?

Não. Core registra proposals de governança e retorna contexto de commit-preflight. As gravações finais pertencem a um host, adapter ou runtime confiável fora do Core.

### O que é uma proposal?

Uma proposal é uma solicitação armazenada para uma operação WordPress assistida por IA. Ela inclui a ability de destino, resumo de entrada, preview ou dry-run evidence, status, caller metadata e audit trail necessário para revisão.

### O que é commit preflight?

Commit preflight é a verificação de governança executada depois da aprovação e antes que um componente externo confiável realize a gravação final. Ele retorna contexto limitado, correlation data e input binding para que o componente downstream verifique que está agindo sobre a solicitação aprovada.

### Preciso de outro plugin para abilities?

Para uma ability intake completa, sim. Core governa abilities expostas por provedores da WordPress Abilities API. Npcink Abilities Toolkit é o provider de referência, e provedores externos também podem se integrar expondo ability ids, schemas, permissions, risk metadata e dry-run previews estáveis.

### Este plugin envia dados para um serviço externo de IA?

Não. Core não chama serviços externos de IA e não envia dados do site para terceiros. Ele armazena registros de governança localmente em tabelas do banco de dados WordPress.

### Quais dados o Core armazena?

Core armazena proposal records, approval e rejection decisions, commit-preflight evidence, execution-result handoff records, audit events, app-key metadata e rate-limit state. App-key secrets são armazenados com hash, e bearer tokens de uso único são exibidos apenas no momento da criação.

### Para que servem scoped app keys?

Scoped app keys permitem que clientes confiáveis de governança chamem endpoints REST específicos do Core sem receber amplo acesso de administrador. Elas são destinadas a hosts controlados, adapters e clientes internos de governança.

### OpenClaw deve se conectar diretamente ao Core?

Uma configuração productizada do OpenClaw deve se conectar por meio de um adapter confiável. Core app keys diretas são apenas para clientes internos de governança e fallback testing.

### Provedores externos de abilities podem usar Core?

Sim. O proposal lifecycle básico é provider-neutral. Provedores externos podem expor WordPress Abilities API definitions com schemas, permission callbacks, risk metadata e dry-run previews, e então enviar operações de gravação ou destrutivas para revisão do Core.

## Changelog

### 0.1.0

Plugin inicial de governança com ability intake, proposals, approval/rejection, commit preflight, scoped app keys, rate limiting e audit records.
