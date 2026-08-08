# Infraestrutura e configurações manuais de produção

> **Por que este documento existe**: algumas configurações críticas foram aplicadas **manualmente em produção** e não estão versionadas em nenhum arquivo do repositório. Se um container for recriado ou um banco for restaurado de backup, elas se perdem **silenciosamente** e os problemas voltam. Este documento registra cada uma com o comando exato para verificar e reaplicar.

## Visão geral do ambiente

- **Hospedagem**: EasyPanel. O serviço da aplicação é do tipo **Box** — container persistente, sem Dockerfile próprio; o código fica em `/code` e é atualizado via `git pull`.
- **Banco da aplicação**: MySQL.
- **Evolution Go** (WhatsApp): serviço separado (`evolutiongo`) com **PostgreSQL 17** próprio (serviço `postgres`, banco `evogo_auth`).

---

## 1. PostgreSQL do Evolution Go — `idle_session_timeout` ⚠️ CRÍTICO

### Incidente

O PostgreSQL do Evolution Go esgotava o limite de conexões (`max_connections = 100`), derrubando **toda** a integração de WhatsApp: comprovantes de dízimo, alertas ao tesoureiro, enquetes, notificações de escala e publicações agendadas em grupo paravam de funcionar. O log do Postgres exibia repetidamente:

```
FATAL: sorry, too many clients already
```

### Causa

A biblioteca `whatsmeow` (usada internamente pelo Evolution Go para o protocolo do WhatsApp) abre um par de conexões ao banco `evogo_auth` a cada evento de sincronização de sessão — aproximadamente a cada 40–60 minutos — e **não as devolve ao pool**. As conexões ficam em estado `idle` por horas, acumulando até esgotar o limite em poucos dias.

### Correção aplicada (manual, não versionada)

No terminal do serviço `postgres` (PostgreSQL 17):

```sql
ALTER SYSTEM SET idle_session_timeout = '15min';
SELECT pg_reload_conf();
```

> **Observação prática**: pelo PgWeb, o `ALTER SYSTEM` precisa ser executado **sozinho** (sem outras instruções na mesma execução), pois não pode rodar dentro de um bloco de transação.

### Como verificar se ainda está ativo

```sql
SHOW idle_session_timeout;   -- deve retornar 15min
SELECT state, count(*) FROM pg_stat_activity GROUP BY state;
```

Se `idle_session_timeout` retornar `0`, a configuração **se perdeu** (container recriado, backup restaurado) e precisa ser reaplicada com os comandos acima.

### Limpeza manual (se as conexões já esgotaram)

```sql
SELECT pg_terminate_backend(pid)
FROM pg_stat_activity
WHERE datname = 'evogo_auth' AND state = 'idle'
  AND state_change < now() - interval '15 minutes';
```

### ⏳ Pendência conhecida

A correção trata o **sintoma**, não a causa. A solução definitiva é limitar o pool de conexões nas variáveis de ambiente do serviço `evolutiongo` (procurar por variáveis do tipo `DB_MAX_OPEN_CONNS` / `CONN_MAX_LIFETIME`). **Item em aberto.**

---

## 2. Cron / Scheduler no EasyPanel ⚠️ CRÍTICO

O serviço da aplicação é do tipo **Box** (container persistente, sem Dockerfile próprio), e o cron **não sobe automaticamente** quando o container reinicia.

A linha `service cron start || true` foi adicionada ao **Script de Implantação** do EasyPanel, mas isso só cobre reinícios causados por **deploy** — **não cobre** reinício do servidor ou queda do container.

### Verificação (rodar após qualquer reinício suspeito)

```bash
ps aux | grep cron          # deve mostrar /usr/sbin/cron rodando
crontab -l                  # deve conter a linha do schedule:run
```

Linha esperada no crontab:

```
* * * * * cd /code && php artisan schedule:run >> /dev/null 2>&1
```

Se o cron não estiver rodando:

```bash
service cron start
```

### Sintoma quando isso quebra

Publicações agendadas ficam presas em "publicando"/"pendente" indefinidamente, alertas não são enviados, lembretes não disparam — tudo **silenciosamente**, sem erro visível na interface.

---

## 3. Cache de configuração do Laravel ⚠️

O projeto roda com `config:cache` em produção. Duas consequências práticas:

1. **Chamadas a `env()` fora de arquivos de config retornam `null`** — sempre usar `config()` no código da aplicação (e criar a chave correspondente em `config/*.php` quando precisar de uma variável nova do `.env`).
2. **Após qualquer alteração no `.env`**, é obrigatório rodar:

```bash
php artisan config:clear && php artisan config:cache
```

> **Incidente registrado**: o `LOG_CHANNEL` ficou vazio no cache de config, fazendo o sistema de log falhar (`Log [] is not defined`) e derrubando funcionalidades aparentemente sem relação (qualquer código que tentasse logar quebrava junto).

Outros pontos do `.env` de produção que já causaram problema:

- `APP_URL` deve ser `https://adelss.com.br` — **com https e sem barra no final**. Errado, gera URLs `http://...//storage/...` (Mixed Content nas imagens).
- `MAIL_MAILER=smtp` (já ficou com valor inválido e derrubou envio de e-mail).

---

## 4. Comandos agendados (`app/Console/Kernel.php`)

| Comando | Frequência | O que faz | O que quebra se parar |
|---|---|---|---|
| `events:check-past` | a cada hora | Marca eventos passados como concluídos | Eventos ficam "agendados" para sempre |
| `financial:notify-due-expenses` | a cada hora (horário efetivo vem da automação) | Lembretes de despesas a vencer via WhatsApp | Tesouraria não recebe lembretes |
| `campaigns:notify-due-installments` | diário 09:00 | Lembrete WhatsApp de parcelas de campanha a vencer | Patrocinadores não são lembrados |
| `campaigns:send-scheduled-messages` | diário 09:10 | Mensagem programada da campanha (ex.: aviso do dia do pagamento) | Mensagem programada não sai na data |
| `financial:send-smart-summary` | a cada hora | Resumo financeiro inteligente via WhatsApp | Resumos deixam de chegar |
| `cultos:check-pastoral-alerts` | diário 09:00 | Alertas pastorais a partir dos relatórios de culto | Alertas pastorais não disparam |
| `midia:publish-instagram-posts` | a cada 5 min, `withoutOverlapping(30)` | Publica posts agendados no Instagram e grupos WhatsApp (o `withoutOverlapping` evita envio duplicado ao WhatsApp) | Publicações travam em "publicando" |
| `midia:remove-expired-instagram-posts` | a cada 15 min | Remove publicações expiradas | Posts expirados permanecem no ar |
| `members:geocode-addresses --limit=20` | a cada hora | Geocodifica endereços de membros (Nominatim, ~1 req/s) | Mapa de membros fica desatualizado |
| `whatsapp:check-connection` | a cada 10 min | Monitora conexão do WhatsApp; alerta por **e-mail** após 2 falhas consecutivas (nunca por WhatsApp) e registra em `whatsapp_connection_logs` | Desconexão do WhatsApp passa despercebida |
| `logs:purge-old` | domingo 03:00 | Purga logs antigos de notificação e conexão | Tabelas de log crescem sem limite |
