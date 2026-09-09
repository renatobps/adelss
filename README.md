# ADELSS — Sistema de Gestão Eclesiástica

Sistema de gestão da ADEL São Sebastião, desenvolvido em **Laravel 10 / PHP 8.1**, com Blade + **Bootstrap 5.3** (tema Porto Admin). Hospedado no **EasyPanel** (serviço tipo Box), com **MySQL** como banco da aplicação.

> **Documentação complementar** (leitura obrigatória para quem opera a produção):
>
> - [`docs/INFRAESTRUTURA.md`](docs/INFRAESTRUTURA.md) — **configurações manuais de produção** (Postgres do Evolution Go, cron, cache de config) e comandos agendados. Se um container for recriado, é este documento que evita regressões silenciosas.
> - [`docs/INTEGRACOES.md`](docs/INTEGRACOES.md) — WhatsApp (Evolution Go), Google Drive, Instagram, Mercado Pago e SMTP: fluxo de autenticação escolhido, onde ficam as credenciais e limitações conhecidas.
> - [`docs/TROUBLESHOOTING.md`](docs/TROUBLESHOOTING.md) — incidentes já vividos, com sintoma → causa → solução.
>
> Os arquivos `.md` soltos na raiz do projeto (`CONFIG_WHATSAPP.md`, `DOCUMENTACAO_MIDIA.md`, etc.) são registros históricos de implementações passadas e podem estar desatualizados — em caso de conflito, vale o que está neste README e na pasta `docs/`.

## Tecnologias

- Laravel 10.x / PHP 8.1+
- MySQL (aplicação) — o Evolution Go usa um PostgreSQL próprio (ver infraestrutura)
- Blade + Bootstrap 5.3.3 (tema Porto Admin) + Font Awesome / Boxicons
- DomPDF (`barryvdh/laravel-dompdf`) para PDFs
- `chillerlan/php-qrcode` para QR Codes (PNG via GD, SVG)
- Mercado Pago (`mercadopago/dx-php`) para pagamentos PIX/cartão
- Google API Client (Drive) e Evolution Go (WhatsApp)

## Instalação (desenvolvimento)

```bash
composer install
cp .env.example .env
php artisan key:generate
# configurar DB_* no .env
php artisan migrate
php artisan db:seed --class=PermissionSeeder
php artisan storage:link
php artisan serve
```

## Deploy (produção — EasyPanel)

O serviço é um container persistente (Box); o código vive em `/code` e é atualizado por `git pull`:

```bash
cd /code
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear && php artisan config:cache
php artisan view:clear
```

⚠️ O projeto roda com `config:cache`: **toda alteração no `.env` exige `config:clear && config:cache`**, e o código nunca deve usar `env()` fora de arquivos de config — detalhes em [`docs/INFRAESTRUTURA.md`](docs/INFRAESTRUTURA.md).

## Arquitetura em uma olhada

- A landing **pública** é servida em `/` (módulo Página Principal); o dashboard interno vive em `/dashboard` (rota nomeada `dashboard`).
- Rotas de módulos ficam em `routes/modules/*.php`, agrupadas sob `auth` + middleware `module.access:{modulo}`.
- Permissões seguem o padrão `{modulo}.{recurso}.{acao}`, seedadas em `database/seeders/PermissionSeeder.php` e verificadas por policies/authorizers.
- Comandos agendados em `app/Console/Kernel.php` — o cron do container **não sobe sozinho** após reinício (ver infraestrutura).

## Módulos

Organizados nas três categorias do menu lateral.

### Administração

| Módulo | Descrição | Permissões |
|---|---|---|
| **Página Principal** | Configuração da landing pública servida em `/` (banners, seções, agenda semanal, eventos do mês). O painel interno fica em `/dashboard`. | `pagina-principal.manage` |

### Módulos

| Módulo | Descrição | Permissões |
|---|---|---|
| **Membros** | Cadastro completo de membros (fotos, filtros, KPIs, exportações), cargos, link público de cadastro com token (`/cadastro-membro/{token}`) e geocodificação de endereços para o mapa. | `members.index.*`, `members.roles.*` |
| **PGIs** | Pequenos Grupos de Interesse: cadastro, participantes e vínculo com membros; reuniões com chamada de presença e visitantes (tela otimizada para celular), recorrência semanal, indicadores do grupo, alerta de ausências consecutivas, mapa do endereço, multiplicação (PGI filho) e relatório em PDF. Ajustes em `config/pgis.php`. | `pgis.index.*` |
| **Ensino** | Estudos, escolas e turmas. | `ensino.estudos.*`, `ensino.escolas.*`, `ensino.turmas.*` |
| **Financeiro** | Receitas (dízimos/ofertas com comprovante por WhatsApp), despesas, categorias, contas, contatos, centros de custo e relatórios. Cobrança PIX/cartão via Mercado Pago. | `financial.receitas.*`, `financial.despesas.*`, `financial.categories.*`, `financial.accounts.*`, `financial.contacts.*`, `financial.cost-centers.*`, `financial.reports.view` |
| **Financeiro › Campanhas** | Campanhas de arrecadação com patrocinadores e parcelas (carnê): carnês em PDF, recibo por WhatsApp, estorno auditado, lembretes automáticos e prestação de contas própria (PDF/Excel). **Isolado por decisão de produto**: não gera `FinancialTransaction` e não aparece em nenhum relatório financeiro da igreja. | `financial.campanhas.view/create/edit/delete/pagar/estornar` |
| **Relatórios de Culto** | Relatórios pós-culto (presenças, decisões, observações) com alertas pastorais automáticos. | `cultos.relatorios.*`, `cultos.configuracoes.manage` |
| **Agenda / Eventos** | Calendário (FullCalendar) e eventos com landing pública (`/evento/{slug}`): programação em timeline, inscrição online (gratuita ou paga via Mercado Pago), comprovante em PDF com número de inscrição e QR Code enviado por WhatsApp, check-in por QR Code na entrada, QR/cartaz A4 de divulgação. | `agenda.events.*`, `agenda.categories.*` |
| **Serviço** | Departamentos e voluntários: cadastro, áreas de serviço, disponibilidade, escalas (com notificação), histórico e relatórios. | `servico.departments.*`, `servico.voluntarios.{cadastro,areas,disponibilidade,escalas,historico,relatorios}.*` |
| **Rifas** | Cadastro de rifas, venda de números e relatórios. | `rifas.index.*`, `rifas.sales.*`, `rifas.reports.view` |
| **Moriah** | Ministério de louvor: repertório e escalas. | `moriah.view`, `moriah.manage` |
| **Discipulado** | Ciclos de discipulado, membros vinculados, encontros, indicadores, propósitos/metas e feedbacks. | `discipleship.{cycles,members,meetings,indicators,goals,feedbacks}.*` |

### Comunicação

| Módulo | Descrição | Permissões |
|---|---|---|
| **Mídia** | Arquivos no Google Drive (upload, pastas, thumbnails), publicações agendadas no Instagram e em grupos de WhatsApp, remoção automática de posts expirados. | `midia.arquivos.*`, `midia.pastas.manage`, `midia.configuracoes.manage`, `midia.instagram.*`, `midia.whatsapp.schedule` |
| **Notificações** | Central de WhatsApp: envio a membros/grupos, enquetes, templates, histórico, configuração da instância Evolution Go e monitor de conexão (banner + histórico em `/notificacoes/config`). | `notificacoes.view`, `notificacoes.manage`, `notificacoes.historico.manage` |

## Convenções de desenvolvimento

- **Permissões**: formato `{modulo}.{recurso}.{acao}`, seedadas em `PermissionSeeder.php`. Acesso a módulo via middleware `module.access:{modulo}`.
- **Rotas**: toda rota autenticada fica dentro do grupo `auth`. Exceções legítimas (e únicas): login, landing pública (`/`), página pública de evento (`/evento/{slug}` e inscrição), cadastro público de membro (`/cadastro-membro/{token}`) e webhooks (`/webhook`, `/webhooks/mercado-pago`).
- **Configuração**: nunca usar `env()` fora de `config/*.php` — produção roda com `config:cache` e `env()` retorna `null`.
- **PDFs (DomPDF)**: sem flexbox/grid (usar tabelas), margem **apenas** em `@page` (nunca também no `body`), fonte `DejaVu Sans`, imagens embutidas em base64, sem emoji (usar `App\Support\PdfText::stripEmoji`).
- **QR Codes**: usar `App\Support\QrCode` (baseado em GD — o servidor não tem imagick).
- **PDF de comprovante no fechamento**: o FPDI gratuito não importa PDF 1.5+. Sem Imagick, a rasterização usa Ghostscript se estiver no PATH (ou `GHOSTSCRIPT_PATH`); senão extrai JPEG/PNG embutidos no arquivo.
- **Mobile-first**: tabelas devem ter alternativa em card, ações em tabela colapsam em menu `⋮`, área de toque mínima de 44×44px.
- **Assets CSS**: arquivos alterados com frequência usam cache-busting `?v=filemtime` no link (ex.: `custom.css`, `event-landing.css`).

## Licença

MIT
