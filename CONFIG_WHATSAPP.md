# Configuração do envio de mensagens por WhatsApp (Z-API)

O módulo de Notificações usa a **Z-API** para enviar mensagens. Siga os passos abaixo para configurar.

---

## 1. Obter credenciais na Z-API

1. Acesse [https://developer.z-api.io](https://developer.z-api.io) e crie uma conta ou faça login.
2. Crie uma **instância** (ou use uma existente).
3. Anote:
   - **Client Token** (token do cliente)
   - **Instance ID** (ID da instância)
   - **Instance Token** (token da instância)
4. A **URL base da API** costuma ser: `https://api.z-api.io` (confirme no painel da Z-API).

---

## 2. Configurar o arquivo .env

No arquivo **.env** na raiz do projeto, adicione ou edite:

```env
# WhatsApp (Z-API)
WHATSAPP_API_URL=https://api.z-api.io
WHATSAPP_CLIENT_TOKEN=seu_client_token_aqui
WHATSAPP_INSTANCE_ID=seu_instance_id_aqui
WHATSAPP_INSTANCE_TOKEN=seu_instance_token_aqui
```

- **WHATSAPP_API_URL**: URL base da API (ex.: `https://api.z-api.io`).
- **WHATSAPP_CLIENT_TOKEN**: Client Token do painel Z-API.
- **WHATSAPP_INSTANCE_ID**: ID da instância (ex.: `3B0XXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX`).
- **WHATSAPP_INSTANCE_TOKEN**: Instance Token da instância.

Não deixe valores em branco; use aspas se tiver caracteres especiais. Exemplo:

```env
WHATSAPP_API_URL="https://api.z-api.io"
WHATSAPP_CLIENT_TOKEN="abc123..."
WHATSAPP_INSTANCE_ID="3B0..."
WHATSAPP_INSTANCE_TOKEN="xyz789..."
```

---

## 3. Conectar o WhatsApp na instância

1. No painel da Z-API, abra sua instância.
2. Leia o **QR Code** com o WhatsApp (celular que será usado para envio).
3. Após conectado, o status da instância ficará “Conectado” e você poderá enviar mensagens.

---

## 4. Testar no sistema

1. Acesse **Notificações** → **Configuração WPP**.
2. Se as variáveis estiverem corretas no .env, aparecerá: *“API WhatsApp configurada (Z-API)”*.
3. No campo **Telefone**, informe um número com DDD (ex.: `11999999999` ou `5511999999999`).
4. Clique em **Enviar teste**. Deve chegar a mensagem: *“Teste de conexão - ADELSS Notificações.”*

---

## 5. Erros comuns

| Erro | Solução |
|------|--------|
| *“Cannot assign null to property...”* | Todas as variáveis WHATSAPP_* devem estar definidas no .env (podem ser vazias temporariamente; o sistema avisa que não está configurado). |
| *“API não configurada”* | Preencha WHATSAPP_API_URL, WHATSAPP_CLIENT_TOKEN, WHATSAPP_INSTANCE_ID e WHATSAPP_INSTANCE_TOKEN no .env. |
| *“Erro ao enviar mensagem”* | Verifique se a instância está conectada (QR Code lido), se o número está com DDD (11...) e se os tokens estão corretos. |
| Mensagem não chega | Confirme que o número está no formato internacional (55 + DDD + número, sem espaços ou traços). |

---

## 6. Uso no sistema

- **Painel de Notificações**: envie mensagens para membros ou grupos (membros com telefone cadastrado).
- **Enquetes**: envie enquetes para membros ou grupos.
- Os **membros** usados são os do cadastro do sistema (menu Membros); o campo **Telefone** é usado para o envio.

Se algo falhar, confira o arquivo `storage/logs/laravel.log` para mais detalhes do erro.
