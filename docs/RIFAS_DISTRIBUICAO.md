# Distribuição de cartelas por departamento

## Uso

O cadastro e a edição incluem **Chave PIX** e **Nome do recebedor**. Informe
os dois campos para disponibilizar os dados de pagamento nos detalhes da rifa,
nas cartelas impressas, no PDF e na legenda enviada por WhatsApp. Ambos podem
ficar vazios para preservar rifas sem PIX. Esses dados são informativos; não
geram uma cobrança automática nem confirmam pagamentos.

1. No cadastro de uma rifa ativa, escolha o departamento responsável e/ou
   marque vendedores em **Adicionar vendedores da lista de membros**. Use
   a busca por nome para localizar os membros. É possível trabalhar somente
   com membros selecionados, sem escolher departamento.
2. O sistema cria os números e distribui cartelas inteiras, equilibrando a
   quantidade de cartelas por vendedor. São considerados os membros ativos
   do departamento e os selecionados individualmente, sem duplicar uma pessoa
   presente em mais de uma lista. Os vínculos do departamento principal e da
   lista de membros do departamento são considerados.
3. Em **Ver cartelas**, confira o vendedor responsável, filtre por vendedor
   e use **Baixar cartela em PDF** ou **Enviar por WhatsApp**.
4. O envio utiliza a conexão WhatsApp já configurada e o telefone do membro.
   O botão envia somente a cartela escolhida, em PDF, com os números, o vendedor,
   o valor e a data do sorteio. Não inclui os dados dos compradores.
5. Em **Distribuição e novos números**, informe quantos números deseja adicionar.
   A sequência continua do último número, com novas cartelas distribuídas entre
   os membros ativos elegíveis. Nenhum número, venda ou cartela existente é recriado.

O envio não é automático na criação da rifa. O último envio confirmado é exibido
na cartela; falhas não são marcadas como sucesso. Reenvios são manuais e o serviço
limita a dez solicitações de envio por minuto por usuário.

### Enviar para todos os vendedores

Em **Ver cartelas**, use **Enviar cartelas para todos por WhatsApp**. É necessário
ter chave PIX e nome do recebedor cadastrados. O botão considera todos os
vendedores com cartelas atribuídas na rifa, inclusive em outras páginas ou fora
do filtro atual. Pessoas sem cartelas atribuídas não recebem mensagens.

Cada vendedor recebe um único PDF com todas as suas cartelas; a chave PIX e o nome
do recebedor aparecem em cada cartela do arquivo e na legenda da mensagem.
O navegador faz um envio por vez e exibe progresso, sucessos, falhas e vendedores
sem telefone. Mantenha a página aberta até terminar. Esse fluxo não depende de
um worker de filas nem mantém uma única requisição aberta durante todo o lote.
O endpoint de envio agrupado permite até 120 solicitações por minuto por usuário.

O envio individual continua disponível. Um novo lote inclui novamente as
cartelas que já foram enviadas, mediante confirmação. Não há repetição automática
de pedidos sem confirmação: se houver falha de conexão, confira o resultado antes
de reenviar, pois o provedor pode ter recebido o arquivo sem devolver a resposta.

Uma cartela distribuída continua com números disponíveis: atribuir um vendedor
não é uma venda nem reserva. Ao clicar em um número na tela de cartelas, o vendedor
responsável já é selecionado na venda rápida. A venda registra o vendedor efetivo.
Em rifas com distribuição configurada, novas vendas exigem um vendedor ativo
elegível: membro do departamento, selecionado individualmente ou responsável
por cartelas já distribuídas.

## Rifas existentes

As rifas antigas continuam sem departamento e sem distribuição automática. Edite
a rifa, selecione um departamento e/ou membros individuais e salve. Depois use **Distribuir cartelas
disponíveis**. Essa ação só atribui cartelas sem vendedor e com todos os números
disponíveis; não redistribui cartelas já atribuídas, vendidas ou reservadas.

Depois de distribuir cartelas, o departamento deve ser preservado. A distribuição
e a inclusão de números só são permitidas em rifas ativas. O limite total da
numeração continua sendo 100.000. Cartelas antigas que não estavam completas
permanecem como foram entregues; novos números sempre geram novas cartelas.

É possível adicionar vendedores individuais na edição mesmo depois da distribuição.
As cartelas antigas mantêm seus responsáveis. Vendedores adicionados participam
da próxima distribuição ou da inclusão de novos números. Remover uma seleção
individual não transfere cartelas que já foram atribuídas; o antigo responsável
continua disponível para registrar as vendas dessas cartelas, enquanto estiver ativo.

Gerenciar a distribuição, acrescentar números e enviar cartelas exige permissão
de edição/gestão da rifa. Visualizar ou baixar a cartela exige acesso à rifa.

## Publicação por Git

No servidor, como root, após obter o commit:

```bash
cd /code
git pull --ff-only origin master
su -s /bin/sh www-data -c 'cd /code && php artisan migrate --path=database/migrations/2026_11_14_130000_add_department_and_seller_to_rifas.php --force'
su -s /bin/sh www-data -c 'cd /code && php artisan migrate --path=database/migrations/2026_11_14_140000_create_rifa_vendedores_table.php --force'
su -s /bin/sh www-data -c 'cd /code && php artisan migrate --path=database/migrations/2026_11_14_150000_add_pix_details_to_rifas.php --force'
su -s /bin/sh www-data -c 'cd /code && php artisan view:clear && php artisan route:clear'
```

Use o usuário real do PHP se for diferente de `www-data`. A migração é aditiva:
adiciona o departamento à rifa, o vendedor responsável e a data de envio às
cartelas existentes, inicialmente vazios. Não dispara mensagens.
