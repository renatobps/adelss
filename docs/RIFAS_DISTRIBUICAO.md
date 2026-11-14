# Distribuição de cartelas por departamento

## Uso

1. No cadastro de uma rifa ativa, escolha o departamento responsável.
2. O sistema cria os números e distribui cartelas inteiras, equilibrando a
   quantidade de cartelas por vendedor. São considerados os membros ativos
   vinculados pelo departamento principal ou pela lista de membros do departamento,
   sem duplicar uma pessoa que possua os dois vínculos.
3. Em **Ver cartelas**, confira o vendedor responsável, filtre por vendedor
   e use **Baixar cartela em PDF** ou **Enviar por WhatsApp**.
4. O envio utiliza a conexão WhatsApp já configurada e o telefone do membro.
   O botão envia somente a cartela escolhida, em PDF, com os números, o vendedor,
   o valor e a data do sorteio. Não inclui os dados dos compradores.
5. Em **Distribuição e novos números**, informe quantos números deseja adicionar.
   A sequência continua do último número, com novas cartelas distribuídas entre
   os membros ativos. Nenhum número, venda ou cartela existente é recriado.

O envio não é automático na criação da rifa. O último envio confirmado é exibido
na cartela; falhas não são marcadas como sucesso. Reenvios são manuais e o serviço
limita a dez solicitações de envio por minuto por usuário.

Uma cartela distribuída continua com números disponíveis: atribuir um vendedor
não é uma venda nem reserva. Ao clicar em um número na tela de cartelas, o vendedor
responsável já é selecionado na venda rápida. A venda registra o vendedor efetivo.
Em rifas com departamento, novas vendas exigem um vendedor ativo desse departamento.

## Rifas existentes

As rifas antigas continuam sem departamento e sem distribuição automática. Edite
a rifa, selecione um departamento e salve. Depois use **Distribuir cartelas
disponíveis**. Essa ação só atribui cartelas sem vendedor e com todos os números
disponíveis; não redistribui cartelas já atribuídas, vendidas ou reservadas.

Depois de distribuir cartelas, o departamento deve ser preservado. A distribuição
e a inclusão de números só são permitidas em rifas ativas. O limite total da
numeração continua sendo 100.000. Cartelas antigas que não estavam completas
permanecem como foram entregues; novos números sempre geram novas cartelas.

Gerenciar a distribuição, acrescentar números e enviar cartelas exige permissão
de edição/gestão da rifa. Visualizar ou baixar a cartela exige acesso à rifa.

## Publicação por Git

No servidor, como root, após obter o commit:

```bash
cd /code
git pull --ff-only origin master
su -s /bin/sh www-data -c 'cd /code && php artisan migrate --path=database/migrations/2026_11_14_130000_add_department_and_seller_to_rifas.php --force'
su -s /bin/sh www-data -c 'cd /code && php artisan view:clear && php artisan route:clear'
```

Use o usuário real do PHP se for diferente de `www-data`. A migração é aditiva:
adiciona o departamento à rifa, o vendedor responsável e a data de envio às
cartelas existentes, inicialmente vazios. Não dispara mensagens.
