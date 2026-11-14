# Contas Mercado Pago por evento

O menu **Administração → Pagamentos** permite cadastrar e alterar perfis de
recebimento. Somente usuários com `is_admin` podem acessar a tela ou alterar
a conta de um evento. As credenciais padrão em `config/mercadopago.php`
continuam atendendo o Financeiro e os eventos sem perfil selecionado.

## Publicação por Git

Na raiz Laravel, após atualizar o código:

```bash
git pull --ff-only origin master
php artisan migrate --path=database/migrations/2026_11_14_120000_create_mercado_pago_accounts_table.php --force
php artisan view:clear
php artisan route:clear
```

Esta migração adiciona os perfis e referências opcionais. Não copia nem
substitui as credenciais de produção existentes. Não há configuração real,
token ou conta de recebimento incluídos no commit.

## Configuração

1. Em Pagamentos, identifique a conta e informe Public Key, Access Token e
   assinatura secreta do webhook da **mesma aplicação** Mercado Pago.
2. Preencha os demais campos e confirme com sua senha de administrador.
   O servidor valida o token consultando `/users/me`; nenhuma cobrança é
   criada nessa verificação. Ela não comprova o pareamento da Public Key
   nem a configuração do webhook, que devem ser conferidos no Mercado Pago.
3. Copie a URL exibida no card do perfil e configure-a nos Webhooks da
   respectiva aplicação Mercado Pago. Ela inclui `?account=<uuid>`.
4. Edite o evento e selecione o perfil em **Conta de pagamento do evento**.

A moeda suportada nesta tela é BRL. A URL de notificação precisa pertencer
à própria aplicação, com caminho `/webhooks/mercado-pago`; em produção,
HTTPS é obrigatório. `MP_SANDBOX` identifica a configuração como teste:
**não transforma credenciais de produção em credenciais de teste**.

## Segurança e troca de credenciais

- Access Token e assinatura do webhook são criptografados no banco com a
  chave Laravel e ocultados na serialização e nos formulários de edição.
  Campos vazios na edição preservam os segredos já cadastrados.
- Senha de administrador, autenticação, CSRF, HTTPS em produção e limite
  de cinco alterações por minuto protegem a tela. Alterações são auditadas
  por IDs, sem registrar segredos. Erros de validação não os guardam na sessão.
- Editar um perfil cria uma nova versão e passa os eventos vinculados para
  ela. As cobranças existentes preservam a referência à versão original.
  Perfis anteriores não são excluídos; seus webhooks continuam exigindo a
  assinatura original e usam o token original para consultar o pagamento.
- Não revogue o token de uma conta antiga enquanto precisar acompanhar suas
  cobranças, estornos ou contestações. Guardar a versão não revalida tokens
  revogados pelo provedor.
- Os webhooks de perfis não modificam pagamentos de outras contas nem os
  lançamentos do Financeiro padrão. O valor recebido é consultado na API;
  o corpo da notificação não é usado como prova de pagamento.
- Em produção, notificações sem assinatura válida são rejeitadas, inclusive
  se o indicador de sandbox estiver ativo. A compatibilidade com callbacks
  sem assinatura fica restrita ao ambiente local/testing e à conta padrão.
- A `APP_KEY` deve ser preservada fora do repositório e guardada com segurança.
  Sem ela, não é possível recuperar as credenciais criptografadas. Proteja
  também backups, o servidor e o acesso dos administradores; use MFA onde
  disponível. Quem comprometer a aplicação e sua chave poderá descriptografar
  os segredos. Para infraestrutura de maior criticidade, considere um cofre
  de segredos com controle de acesso e rotação externos.

Referências do provedor:

- https://www.mercadopago.com.br/developers/pt/docs/loja-integrada/additional-content/your-integrations/credentials
- https://www.mercadopago.com.br/developers/pt/docs/checkout-pro-preferences/payment-notifications
