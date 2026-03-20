# Integração NotifiADel → ADELSS (Notificações WhatsApp)

Este documento descreve a análise do sistema **NotifiADel** (`C:\Users\renat\Documents\REPOSITORIOS\NotifiADel`) e o plano para integrar notificações via WhatsApp ao sistema **ADELSS**, usando os **membros já cadastrados** e o **menu Notificações** com submenus.

---

## O que já foi feito no ADELSS

1. **Menu Notificações** no layout (`resources/views/layouts/porto.blade.php`):
   - Item principal **Notificações** (ícone sino)
   - Submenus: **Grupos**, **Enquetes**, **Notificações** (painel), **Configuração WhatsApp**, **Templates**
   - Visível para administradores e para usuários com permissão `notificacoes.view` ou `notificacoes.manage`

2. **Rotas** em `routes/web.php` (dentro do grupo `auth`):
   - `GET /notificacoes/grupos` → `notificacoes.grupos.index`
   - `GET /notificacoes/enquetes` → `notificacoes.enquetes.index`
   - `GET /notificacoes/painel` → `notificacoes.painel.index`
   - `GET /notificacoes/config` → `notificacoes.config.index`
   - `GET /notificacoes/templates` → `notificacoes.templates.index`

3. **Controller** `App\Http\Controllers\Notificacoes\NotificacoesController` com métodos que retornam views placeholder.

4. **Views** em `resources/views/notificacoes/` (grupos, enquetes, painel, config, templates) com páginas simples.

5. **Permissões** no `PermissionSeeder`: módulo **Notificações** com `notificacoes.view` e `notificacoes.manage`.

6. **Correção** no menu Moriah: removido item duplicado “Indisponibilidades”.

---

## Análise do NotifiADel

### Estrutura principal

- **API WhatsApp**: Z-API (config: `config/whatsapp.php`, env: `WHATSAPP_API_URL`, `WHATSAPP_CLIENT_TOKEN`, `WHATSAPP_INSTANCE_ID`, `WHATSAPP_INSTANCE_TOKEN`).
- **Modelos**: `Membro` (nome, sobrenome, telefone, email, categoria_id, ativo), `Grupo` (nome, descricao, ativo), `Enquete`, `EnqueteResposta`, `EnqueteEnvio`, `NotificacaoEnviada`, `ConfiguracaoMensagem`, `ConfiguracaoWhatsapp`, etc.
- **Serviços**: `WhatsAppService` (envio de mensagem, arquivo, etc.), `NotificacaoService`, `EnqueteService`.
- **Controllers Web**: `GrupoWebController`, `EnqueteWebController`, `NotificacaoWebController`, `TemplateWebController`, `WhatsAppWebController`, `MembroWebController`.
- **Rotas**: prefixos `membros`, `grupos`, `notificacoes`, `whatsapp`, `templates`, `enquetes`; webhook em `POST /whatsapp/webhook/{path?}` (sem middleware web).

### Diferença de modelos: Membro (NotifiADel) vs Member (ADELSS)

| NotifiADel (Membro) | ADELSS (Member) |
|---------------------|-----------------|
| `telefone`          | `phone`         |
| `nome` + `sobrenome`| `name`          |
| `categoria_id`       | (opcional: department, role, etc.) |
| `grupos()` (pivot `grupo_membro`) | (a criar: `grupos` com pivot `grupo_member`) |

Na integração, **sempre usar o model `Member`** e o campo **`phone`** para envio. Onde o NotifiADel usa `membro_id`, usar `member_id` e tabelas pivot `grupo_member` em vez de `grupo_membro`.

---

## Próximos passos para integração completa

### 1. Configuração e ambiente

- Copiar `config/whatsapp.php` do NotifiADel para o ADELSS e ajustar se necessário.
- Adicionar ao `.env.example` e `.env`:  
  `WHATSAPP_API_URL`, `WHATSAPP_CLIENT_TOKEN`, `WHATSAPP_INSTANCE_ID`, `WHATSAPP_INSTANCE_TOKEN`, `WHATSAPP_WEBHOOK_URL` (se usar webhook).

### 2. Banco de dados (migrations)

Criar no ADELSS (nomes em inglês e uso de `member_id`):

- **grupos**: id, nome, descricao, ativo, timestamps.
- **grupo_member**: grupo_id, member_id, timestamps (pivot).
- **enquetes**: id, titulo, descricao, tipo, opcoes (json), ativa, inicio_em, fim_em, timestamps.
- **enquete_respostas**: id, enquete_id, member_id, resposta, timestamps.
- **enquete_envios**: id, enquete_id, member_id, telefone, status, enviado_em, etc.
- **notificacoes_enviadas**: id, member_id (nullable), mensagem, telefone, status, data_envio, resposta_api, etc.
- **configuracoes_mensagens**: tipo_notificacao, template, ativo, etc.
- **configuracoes_whatsapp**: conforme NotifiADel (ou só usar config/env).

Adaptar migrations do NotifiADel trocando `membro_id` por `member_id` e tabelas `*_membro` por `*_member`.

### 3. Models

- **Grupo**: `belongsToMany(Member::class, 'grupo_member', 'grupo_id', 'member_id')`.
- **Member**: adicionar `grupos()` → `belongsToMany(Grupo::class, 'grupo_member', 'member_id', 'grupo_id')`.
- **Enquete**, **EnqueteResposta**, **EnqueteEnvio**: relações com `Member` e `member_id`.
- **NotificacaoEnviada**, **ConfiguracaoMensagem** (e **ConfiguracaoWhatsapp** se existir): adaptar para usar `member_id` onde fizer sentido.

### 4. Serviços

- Copiar `WhatsAppService`, `NotificacaoService` e `EnqueteService` do NotifiADel para o ADELSS (namespace `App\Services` ou `App\Services\Notificacoes`).
- Em todo uso de “membro” e telefone:
  - Usar `Member` e `$member->phone`.
  - Normalizar número (código 55, etc.) como no NotifiADel.

### 5. Controllers

- Substituir/estender o `NotificacoesController` atual por controllers específicos (ou manter um só e chamar lógica em serviços):
  - **Grupos**: CRUD de grupos e associação de membros (lista de `Member` com `phone`).
  - **Enquetes**: CRUD de enquetes, envio, listagem de respostas (por `member_id`/telefone).
  - **Painel**: Envio de notificação (por membro ou grupo), histórico (notificacoes_enviadas).
  - **Config**: Página de configuração WhatsApp (conectar, status, webhook); pode incluir “Configuração de PAO” como texto/link se for apenas nome de tela.
  - **Templates**: CRUD de templates de mensagem (configuracoes_mensagens ou tabela equivalente).
- Em todos: usar apenas `Member` (e `member_id`) e `phone`; nunca criar ou usar model `Membro` do NotifiADel.

### 6. Views

- Copiar e adaptar as views do NotifiADel de grupos, enquetes, notificações, templates e WhatsApp/config para dentro de `resources/views/notificacoes/`.
- Layout: usar `@extends('layouts.porto')` e as seções já usadas no ADELSS (title, page-title, breadcrumbs, content).
- Listagens e formulários: onde o NotifiADel lista “membros” ou “voluntários”, passar coleção de `Member` e exibir nome e telefone.

### 7. Webhook

- Registrar rota de webhook fora do middleware web/CSRF (como no NotifiADel):  
  `POST /whatsapp/webhook/{path?}`.
- Reutilizar a lógica do `WhatsAppWebController::webhook` do NotifiADel, adaptando para os models do ADELSS (EnqueteResposta, etc., por `member_id`).

### 8. Permissões

- O menu e as rotas já consideram `notificacoes.view` e `notificacoes.manage`.
- Rodar `php artisan db:seed --class=PermissionSeeder` para criar as permissões do módulo Notificações.
- Atribuir “Notificações” (ver/gerenciar) aos cargos desejados em Permissões.

---

## Resumo

- **Menu Notificações** no ADELSS já existe com submenus: Grupos, Enquetes, Notificações, Configuração WhatsApp, Templates.
- **Membros**: usar sempre o model **Member** e o campo **phone**; nenhuma duplicação de cadastro.
- **NotifiADel** serve de referência para migrations, models, serviços, controllers e views; a adaptação consiste em trocar `Membro`/`membro_id`/`telefone` por `Member`/`member_id`/`phone` e usar o layout e a estrutura de rotas do ADELSS.

Se quiser, o próximo passo pode ser: criar as migrations (grupos, grupo_member, enquetes, etc.) e o model `Grupo` com relacionamento em `Member`, e em seguida o `WhatsAppService` e a tela de Configuração WhatsApp.
