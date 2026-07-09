# Documentação - Estrutura do Menu Notificações

Guia completo para replicar o módulo **Notificações** em outro projeto Laravel, com base na implementação atual.

Tela de referência: [Inscrições do evento](https://c6d7-2804-3c60-828b-d100-24dd-bf2b-2206-5c58.ngrok-free.app/agenda/eventos/184/inscricoes)

---

## 1) Visão geral do menu

Arquivo do menu:
- `resources/views/layouts/porto.blade.php`

Submenus implementados:
1. **Grupos**
2. **Enquetes**
3. **Notificações (Painel)**
4. **Configuração WPP**
5. **Templates**

Critério de exibição no menu:
- usuário admin, ou permissões `notificacoes.view` / `notificacoes.manage`

---

## 2) Rotas do módulo

Arquivo:
- `routes/web.php`

Prefixo:
- `/notificacoes` (dentro de `auth`)

### 2.1 Grupos
- `GET /notificacoes/grupos` -> `notificacoes.grupos.index` -> `GrupoController@index`
- `GET /notificacoes/grupos/create` -> `notificacoes.grupos.create` -> `GrupoController@create`
- `POST /notificacoes/grupos` -> `notificacoes.grupos.store` -> `GrupoController@store`
- `GET /notificacoes/grupos/{grupo}/edit` -> `notificacoes.grupos.edit` -> `GrupoController@edit`
- `PUT/PATCH /notificacoes/grupos/{grupo}` -> `notificacoes.grupos.update` -> `GrupoController@update`
- `DELETE /notificacoes/grupos/{grupo}` -> `notificacoes.grupos.destroy` -> `GrupoController@destroy`
- JSON auxiliar:
  - `GET /notificacoes/grupos-lista-json` -> `notificacoes.grupos.lista-json`
  - `GET /notificacoes/departamentos-lista-json` -> `notificacoes.departamentos.lista-json`

### 2.2 Enquetes
- `GET /notificacoes/enquetes` -> `notificacoes.enquetes.index` -> `EnqueteController@index`
- `GET /notificacoes/enquetes/create` -> `notificacoes.enquetes.create` -> `EnqueteController@create`
- `POST /notificacoes/enquetes` -> `notificacoes.enquetes.store` -> `EnqueteController@store`
- `GET /notificacoes/enquetes/{enquete}` -> `notificacoes.enquetes.show` -> `EnqueteController@show`
- `GET /notificacoes/enquetes/{enquete}/edit` -> `notificacoes.enquetes.edit` -> `EnqueteController@edit`
- `PUT/PATCH /notificacoes/enquetes/{enquete}` -> `notificacoes.enquetes.update` -> `EnqueteController@update`
- `DELETE /notificacoes/enquetes/{enquete}` -> `notificacoes.enquetes.destroy` -> `EnqueteController@destroy`
- `POST /notificacoes/enquetes/{enquete}/enviar` -> `notificacoes.enquetes.enviar` -> `EnqueteController@enviar`

### 2.3 Painel
- `GET /notificacoes/painel` -> `notificacoes.painel.index` -> `PainelController@index`
- `POST /notificacoes/painel/enviar` -> `notificacoes.painel.enviar` -> `PainelController@enviar`

### 2.4 Configuração WPP
- `GET /notificacoes/config` -> `notificacoes.config.index` -> `ConfigController@index`
- `GET /notificacoes/config/status` -> `notificacoes.config.status` -> `ConfigController@status`
- `GET /notificacoes/config/conectar` -> `notificacoes.config.conectar` -> `ConfigController@conectar`
- `GET /notificacoes/config/instances` -> `notificacoes.config.instances` -> `ConfigController@listarInstancias`
- `POST /notificacoes/config/instances` -> `notificacoes.config.instances.store` -> `ConfigController@criarInstancia`
- `POST /notificacoes/config/instances/{instanceName}/select` -> `notificacoes.config.instances.select` -> `ConfigController@selecionarInstancia`
- `POST /notificacoes/config/instances/{instanceName}/restart` -> `notificacoes.config.instances.restart` -> `ConfigController@reiniciarInstancia`
- `DELETE /notificacoes/config/instances/{instanceName}` -> `notificacoes.config.instances.destroy` -> `ConfigController@deletarInstancia`
- `GET /notificacoes/config/instances/{instanceName}/status` -> `notificacoes.config.instances.status` -> `ConfigController@statusInstancia`
- `PUT /notificacoes/config/webhook-received` -> `notificacoes.config.webhook-received` -> `ConfigController@configurarWebhookReceived`
- `PUT /notificacoes/config/webhook-delivery` -> `notificacoes.config.webhook-delivery` -> `ConfigController@configurarWebhookDelivery`
- `POST /notificacoes/config/teste` -> `notificacoes.config.teste` -> `ConfigController@enviarTeste`

### 2.5 Templates
- `GET /notificacoes/templates` -> `notificacoes.templates.index` -> `TemplateController@index`
- `POST /notificacoes/templates` -> `notificacoes.templates.store` -> `TemplateController@store`
- `PUT /notificacoes/templates/{template}` -> `notificacoes.templates.update` -> `TemplateController@update`
- `DELETE /notificacoes/templates/{template}` -> `notificacoes.templates.destroy` -> `TemplateController@destroy`

---

## 3) Controllers e funções

Diretório:
- `app/Http/Controllers/Notificacoes/`

### 3.1 `GrupoController`
- CRUD de grupos e vínculo de membros.
- Usa `NotificacaoGrupo` + pivot `grupo_member`.

### 3.2 `EnqueteController`
- CRUD de enquete.
- Tela de resultados e envio da enquete para membros/departamentos.
- Disparo usa `EnqueteService`.

### 3.3 `PainelController`
- Tela de envio geral (texto/mídia).
- Filtros e histórico de envios (`NotificacaoEnviada`).
- Envio para membros, departamentos e telefones manuais.

### 3.4 `ConfigController`
- Gestão de conexão com Evolution API.
- Leitura de status, QR Code, instância ativa e reinício da instância.
- Teste de envio.

### 3.5 `TemplateController`
- CRUD de templates de mensagens (`ConfiguracaoMensagem`).

---

## 4) Models, tabelas e relacionamentos

### 4.1 Grupos
- Model: `app/Models/NotificacaoGrupo.php`
- Migrations:
  - `database/migrations/2026_02_09_100000_create_notificacao_grupos_table.php`
  - `database/migrations/2026_02_09_100001_create_grupo_member_table.php`
- Relação:
  - `NotificacaoGrupo` <-> `Member` (N:N via `grupo_member`)

### 4.2 Enquetes
- Models:
  - `app/Models/Enquete.php`
  - `app/Models/EnqueteResposta.php`
  - `app/Models/EnqueteEnvio.php`
- Migrations:
  - `2026_02_09_100002_create_notificacao_enquetes_table.php`
  - `2026_02_09_100003_create_enquete_respostas_table.php`
  - `2026_02_09_100004_create_enquete_envios_table.php`

### 4.3 Histórico e templates
- Models:
  - `app/Models/NotificacaoEnviada.php`
  - `app/Models/ConfiguracaoMensagem.php`
- Migrations:
  - `2026_02_09_100005_create_notificacoes_enviadas_table.php`
  - `2026_02_09_100006_create_configuracoes_mensagens_table.php`

---

## 5) Services e integração WhatsApp

Arquivos:
- `app/Services/WhatsAppService.php`
- `app/Services/NotificacaoService.php`
- `app/Services/EnqueteService.php`

### 5.1 `WhatsAppService`
Funções principais:
- normalização de telefone (`55...`)
- envio de texto
- envio de enquete em botões
- envio de mídia
- validação de configuração ativa

### 5.2 `NotificacaoService`
Funções principais:
- envio para membro, lista de membros, departamento, telefone manual
- registro de cada envio em `notificacoes_enviadas`

### 5.3 `EnqueteService`
Funções principais:
- resolve destinatários da enquete
- envia enquete (botões) no WhatsApp
- salva `enquete_envios`

---

## 6) Views (telas do módulo)

Diretório:
- `resources/views/notificacoes/`

### 6.1 Grupos
- `grupos/index.blade.php`
- `grupos/create.blade.php`
- `grupos/edit.blade.php`

### 6.2 Enquetes
- `enquetes/index.blade.php`
- `enquetes/create.blade.php`
- `enquetes/edit.blade.php`
- `enquetes/show.blade.php`

### 6.3 Painel
- `painel/index.blade.php`

### 6.4 Config
- `config/index.blade.php`

### 6.5 Templates
- `templates/index.blade.php`

---

## 7) Configuração necessária (`.env`)

Arquivos:
- `config/whatsapp.php`
- `.env.example`

Variáveis principais:
- `WHATSAPP_API_URL`
- `WHATSAPP_API_KEY` (ou `WHATSAPP_GLOBAL_API_KEY`)
- `WHATSAPP_INSTANCE_NAME`
- `WHATSAPP_WEBHOOK_URL`
- `WHATSAPP_DEFAULT_DELAY`
- `WHATSAPP_TIMEOUT`

---

## 8) Passo a passo para replicar em outro projeto

1. Copiar e executar as migrations do módulo de notificações.
2. Criar/copiar os models e relacionamentos (incluindo `Member` <-> `NotificacaoGrupo`).
3. Copiar `config/whatsapp.php` e adicionar variáveis no `.env`.
4. Copiar os services (`WhatsAppService`, `NotificacaoService`, `EnqueteService`).
5. Copiar os controllers de `app/Http/Controllers/Notificacoes`.
6. Adicionar route model binding de `grupo` para `NotificacaoGrupo` em `RouteServiceProvider`.
7. Registrar rotas do prefixo `/notificacoes` no `routes/web.php`.
8. Copiar as views de `resources/views/notificacoes`.
9. Adicionar o bloco de menu no layout (`layouts/porto.blade.php`).
10. Adicionar permissões no seeder (`PermissionSeeder`) e rodar seed.
11. Validar envio de teste no submenu Configuração WPP.
12. Testar fluxos dos 5 submenus (CRUD, envio, histórico).

---

## 9) Observações importantes antes de portar

- O módulo depende de `Member` e `Department` com relacionamento e campo de telefone.
- O envio de enquete usa botões WhatsApp via Evolution API (`sendButtons`).
- Webhook `POST /webhook` processa respostas de enquete (`MESSAGES_UPSERT`).
- No projeto atual, o menu aplica permissão visual, mas revise middlewares de autorização no destino.

