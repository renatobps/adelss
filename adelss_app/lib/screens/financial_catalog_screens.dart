import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../api/models.dart';
import '../api/repository.dart';
import '../session/session_controller.dart';
import '../utils/format.dart';
import '../widgets/ui.dart';
import 'financial_shell.dart';

class FinancialAccountsScreen extends StatefulWidget {
  const FinancialAccountsScreen({super.key});

  @override
  State<FinancialAccountsScreen> createState() => _FinancialAccountsScreenState();
}

class _FinancialAccountsScreenState extends State<FinancialAccountsScreen> {
  late Future<FinancialAccountsPayload> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  AdelssRepository get _api => context.read<SessionController>().api;
  Future<FinancialAccountsPayload> _load() => _api.financialAccounts();
  void _reload() {
    setState(() {
      _future = _load();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FinancialScaffold(
      current: 'contas',
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _editAccount(context),
        icon: const Icon(Icons.add),
        label: const Text('Conta'),
      ),
      body: FutureBuilder<FinancialAccountsPayload>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorRetry(message: '${snapshot.error}', onRetry: _reload);
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final data = snapshot.data!;
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 88),
            children: [
              if (data.canManage)
                Align(
                  alignment: Alignment.centerLeft,
                  child: TextButton.icon(
                    onPressed: () => _transfer(context, data.items),
                    icon: const Icon(Icons.swap_horiz),
                    label: const Text('Transferir entre contas'),
                  ),
                ),
              const SectionHeader('Saldos'),
              if (data.items.isEmpty)
                const EmptyState(icon: Icons.account_balance_wallet_outlined, message: 'Nenhuma conta.')
              else
                ...data.items.map(
                  (item) => Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: SurfaceCard(
                      onTap: data.canManage ? () => _editAccount(context, item) : null,
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(item.label ?? item.name, style: Theme.of(context).textTheme.titleMedium),
                                Text(
                                  item.type == 'caixa'
                                      ? 'Saídas ${formatMoney(item.outflow)}'
                                      : (item.typeLabel ?? item.type ?? ''),
                                ),
                              ],
                            ),
                          ),
                          Text(formatMoney(item.balance), style: Theme.of(context).textTheme.titleMedium),
                        ],
                      ),
                    ),
                  ),
                ),
              const SectionHeader('Transferências recentes'),
              if (data.transfers.isEmpty)
                const Text('Nenhuma transferência.')
              else
                ...data.transfers.map(
                  (item) => Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: SurfaceCard(
                      child: Row(
                        children: [
                          Expanded(
                            child: Text('${item.fromAccount} → ${item.toAccount}\n${formatIsoDate(item.date)}'),
                          ),
                          Text(formatMoney(item.amount)),
                        ],
                      ),
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _editAccount(BuildContext context, [FinancialAccount? item]) async {
    final name = TextEditingController(text: item?.name ?? '');
    final bank = TextEditingController(text: item?.bankName ?? '');
    final initial = TextEditingController(
      text: (item?.initialBalance ?? 0).toStringAsFixed(2).replaceAll('.', ','),
    );
    String type = item?.type ?? 'caixa';
    final saved = await showFinancialFormSheet<bool>(
      context: context,
      title: item == null ? 'Nova conta' : 'Editar conta',
      child: StatefulBuilder(
        builder: (context, setLocal) {
          return Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(controller: name, decoration: const InputDecoration(labelText: 'Nome')),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                key: ValueKey('type-$type'),
                initialValue: type,
                decoration: const InputDecoration(labelText: 'Tipo'),
                items: const [
                  DropdownMenuItem(value: 'caixa', child: Text('Caixa')),
                  DropdownMenuItem(value: 'conta_corrente', child: Text('Conta corrente')),
                  DropdownMenuItem(value: 'poupanca', child: Text('Poupança')),
                  DropdownMenuItem(value: 'investimento', child: Text('Investimento')),
                  DropdownMenuItem(value: 'mercado_pago', child: Text('Mercado Pago')),
                ],
                onChanged: (value) => setLocal(() => type = value ?? type),
              ),
              const SizedBox(height: 12),
              TextField(controller: bank, decoration: const InputDecoration(labelText: 'Banco')),
              const SizedBox(height: 12),
              TextField(
                controller: initial,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(labelText: 'Saldo inicial'),
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: () async {
                  await _api.saveFinancialAccount({
                    'name': name.text.trim(),
                    'type': type,
                    'bank_name': bank.text.trim(),
                    'initial_balance': double.tryParse(initial.text.replaceAll('.', '').replaceAll(',', '.')) ?? 0,
                    'is_active': true,
                  }, id: item?.id);
                  if (context.mounted) Navigator.pop(context, true);
                },
                child: const Text('Salvar'),
              ),
            ],
          );
        },
      ),
    );
    if (saved == true) _reload();
  }

  Future<void> _transfer(BuildContext context, List<FinancialAccount> accounts) async {
    int? from = accounts.isNotEmpty ? accounts.first.id : null;
    int? to = accounts.length > 1 ? accounts[1].id : null;
    final amount = TextEditingController();
    DateTime date = DateTime.now();
    final saved = await showFinancialFormSheet<bool>(
      context: context,
      title: 'Transferência',
      child: StatefulBuilder(
        builder: (context, setLocal) {
          return Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              DropdownButtonFormField<int>(
                key: ValueKey('from-$from'),
                initialValue: from,
                decoration: const InputDecoration(labelText: 'De'),
                items: accounts
                    .map((item) => DropdownMenuItem(value: item.id, child: Text(item.name)))
                    .toList(),
                onChanged: (value) => setLocal(() => from = value),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int>(
                key: ValueKey('to-$to'),
                initialValue: to,
                decoration: const InputDecoration(labelText: 'Para'),
                items: accounts
                    .map((item) => DropdownMenuItem(value: item.id, child: Text(item.name)))
                    .toList(),
                onChanged: (value) => setLocal(() => to = value),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: amount,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(labelText: 'Valor'),
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: () async {
                  await _api.saveFinancialTransfer({
                    'from_account_id': from,
                    'to_account_id': to,
                    'transfer_date': date.toIso8601String().split('T').first,
                    'amount': double.parse(amount.text.replaceAll('.', '').replaceAll(',', '.')),
                  });
                  if (context.mounted) Navigator.pop(context, true);
                },
                child: const Text('Registrar'),
              ),
            ],
          );
        },
      ),
    );
    if (saved == true) _reload();
  }
}

class FinancialCategoriesScreen extends StatelessWidget {
  const FinancialCategoriesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return _CatalogScreen(
      current: 'categorias',
      title: 'Categorias',
      loader: (api) => api.financialCategories(),
      onSave: (api, body, id) => api.saveFinancialCategory(body, id: id),
      onDelete: (api, id) => api.deleteFinancialCategory(id),
      fields: const _CatalogFields.category(),
    );
  }
}

class FinancialContactsScreen extends StatelessWidget {
  const FinancialContactsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return _CatalogScreen(
      current: 'fornecedores',
      title: 'Fornecedores',
      loader: (api) => api.financialContacts(),
      onSave: (api, body, id) => api.saveFinancialContact(body, id: id),
      onDelete: (api, id) => api.deleteFinancialContact(id),
      fields: const _CatalogFields.contact(),
    );
  }
}

class FinancialCostCentersScreen extends StatelessWidget {
  const FinancialCostCentersScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return _CatalogScreen(
      current: 'centros-custo',
      title: 'Centros de custo',
      loader: (api) => api.financialCostCenters(),
      onSave: (api, body, id) => api.saveFinancialCostCenter(body, id: id),
      onDelete: (api, id) => api.deleteFinancialCostCenter(id),
      fields: const _CatalogFields.costCenter(),
    );
  }
}

class _CatalogFields {
  const _CatalogFields.category()
      : kind = 'category';
  const _CatalogFields.contact()
      : kind = 'contact';
  const _CatalogFields.costCenter()
      : kind = 'cost';

  final String kind;
}

class _CatalogScreen extends StatefulWidget {
  const _CatalogScreen({
    required this.current,
    required this.title,
    required this.loader,
    required this.onSave,
    required this.onDelete,
    required this.fields,
  });

  final String current;
  final String title;
  final Future<FinancialCatalogList> Function(AdelssRepository api) loader;
  final Future<void> Function(AdelssRepository api, Map<String, dynamic> body, int? id) onSave;
  final Future<void> Function(AdelssRepository api, int id) onDelete;
  final _CatalogFields fields;

  @override
  State<_CatalogScreen> createState() => _CatalogScreenState();
}

class _CatalogScreenState extends State<_CatalogScreen> {
  late Future<FinancialCatalogList> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.loader(context.read<SessionController>().api);
  }

  AdelssRepository get _api => context.read<SessionController>().api;

  void _reload() {
    setState(() {
      _future = widget.loader(_api);
    });
  }

  @override
  Widget build(BuildContext context) {
    return FinancialScaffold(
      current: widget.current,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _edit(context),
        icon: const Icon(Icons.add),
        label: const Text('Novo'),
      ),
      body: FutureBuilder<FinancialCatalogList>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorRetry(message: '${snapshot.error}', onRetry: _reload);
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final data = snapshot.data!;
          if (data.items.isEmpty) {
            return EmptyState(icon: Icons.folder_outlined, message: 'Nenhum cadastro em ${widget.title.toLowerCase()}.');
          }
          return ListView.builder(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 88),
            itemCount: data.items.length,
            itemBuilder: (context, index) {
              final item = data.items[index];
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: SurfaceCard(
                  onTap: data.canManage ? () => _edit(context, item) : null,
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(item.name, style: Theme.of(context).textTheme.titleMedium),
                            Text(item.typeLabel ?? item.type ?? item.description ?? item.phone ?? ''),
                          ],
                        ),
                      ),
                      if (data.canManage)
                        IconButton(
                          onPressed: () async {
                            await widget.onDelete(_api, item.id);
                            _reload();
                          },
                          icon: const Icon(Icons.delete_outline),
                        ),
                    ],
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }

  Future<void> _edit(BuildContext context, [FinancialCatalogItem? item]) async {
    final name = TextEditingController(text: item?.name ?? '');
    final extra = TextEditingController(text: item?.description ?? item?.phone ?? '');
    String type = item?.type ?? (widget.fields.kind == 'contact' ? 'pessoa_juridica' : 'receita');
    try {
      final saved = await showFinancialFormSheet<bool>(
        context: context,
        title: item == null ? 'Novo' : 'Editar',
        child: StatefulBuilder(
          builder: (context, setLocal) {
            return Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(controller: name, decoration: const InputDecoration(labelText: 'Nome')),
                if (widget.fields.kind == 'category') ...[
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    key: ValueKey(type),
                    initialValue: type,
                    items: const [
                      DropdownMenuItem(value: 'receita', child: Text('Receita')),
                      DropdownMenuItem(value: 'despesa', child: Text('Despesa')),
                    ],
                    onChanged: (value) => setLocal(() => type = value ?? type),
                    decoration: const InputDecoration(labelText: 'Tipo'),
                  ),
                ],
                if (widget.fields.kind == 'contact') ...[
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    key: ValueKey(type),
                    initialValue: type,
                    items: const [
                      DropdownMenuItem(value: 'pessoa_fisica', child: Text('Pessoa física')),
                      DropdownMenuItem(value: 'pessoa_juridica', child: Text('Pessoa jurídica')),
                    ],
                    onChanged: (value) => setLocal(() => type = value ?? type),
                    decoration: const InputDecoration(labelText: 'Tipo'),
                  ),
                  const SizedBox(height: 12),
                  TextField(controller: extra, decoration: const InputDecoration(labelText: 'Telefone')),
                ],
                if (widget.fields.kind == 'cost') ...[
                  const SizedBox(height: 12),
                  TextField(controller: extra, decoration: const InputDecoration(labelText: 'Descrição')),
                ],
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: () async {
                    final body = <String, dynamic>{'name': name.text.trim()};
                    if (widget.fields.kind == 'category') body['type'] = type;
                    if (widget.fields.kind == 'contact') {
                      body['type'] = type;
                      body['phone_1'] = extra.text.trim();
                    }
                    if (widget.fields.kind == 'cost') body['description'] = extra.text.trim();
                    await widget.onSave(_api, body, item?.id);
                    if (context.mounted) Navigator.pop(context, true);
                  },
                  child: const Text('Salvar'),
                ),
              ],
            );
          },
        ),
      );
      if (saved == true) _reload();
    } on ApiException catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
      }
    }
  }
}
