import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/models.dart';
import '../api/repository.dart';
import '../session/session_controller.dart';
import '../theme.dart';
import '../utils/format.dart';
import '../widgets/financial_attachments.dart';
import '../widgets/ui.dart';
import 'financial_shell.dart';

class FinancialReceiptsScreen extends StatefulWidget {
  const FinancialReceiptsScreen({super.key});

  @override
  State<FinancialReceiptsScreen> createState() => _FinancialReceiptsScreenState();
}

class _FinancialReceiptsScreenState extends State<FinancialReceiptsScreen> {
  String _tab = 'arquivos';
  String _period = '1month';
  late Future<List<FinancialReceiptItem>> _future;
  bool _uploading = false;
  bool _opening = false;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  AdelssRepository get _api => context.read<SessionController>().api;
  Future<List<FinancialReceiptItem>> _load() => _api.financialReceipts(tab: _tab, period: _period);
  void _reload() {
    setState(() {
      _future = _load();
    });
  }

  Future<void> _uploadFor(FinancialReceiptItem item) async {
    final transactionId = item.transactionId ?? item.id;
    await showReceiptSourceSheet(
      context: context,
      onPicked: (files) async {
        setState(() => _uploading = true);
        try {
          await uploadPickedReceipts(
            api: _api,
            transactionId: transactionId,
            files: files,
          );
          if (!mounted) return;
          setState(() {
            _tab = 'arquivos';
            _future = _load();
          });
        } catch (error) {
          if (mounted) showUploadError(context, error);
        } finally {
          if (mounted) setState(() => _uploading = false);
        }
      },
    );
  }

  Future<void> _openItem(FinancialReceiptItem item) async {
    final path = _tab == 'arquivos'
        ? (item.apiPath ??
            '/financial/transactions/${item.transactionId ?? item.id}/attachments/${item.id}')
        : (item.receiptApiPath ?? '/financial/transactions/${item.transactionId ?? item.id}/receipt');
    setState(() => _opening = true);
    try {
      await openFinancialApiFile(
        context: context,
        api: _api,
        path: path,
        filename: item.fileName ?? 'recibo-${item.id}.pdf',
        isImage: item.isImage && _tab == 'arquivos',
      );
    } finally {
      if (mounted) setState(() => _opening = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    const periods = [
      ('1month', 'Mês atual'),
      ('last_month', 'Mês anterior'),
      ('3months', '3 meses'),
    ];
    return FinancialScaffold(
      current: 'recibos',
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Wrap(
                  spacing: 8,
                  children: [
                    for (final tab in const [
                      ('arquivos', 'Arquivos'),
                      ('pendentes', 'Pendentes'),
                      ('receitas', 'Receitas'),
                    ])
                      ChoiceChip(
                        label: Text(tab.$2),
                        selected: _tab == tab.$1,
                        onSelected: (_) {
                          setState(() {
                            _tab = tab.$1;
                            _future = _load();
                          });
                        },
                      ),
                  ],
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  children: [
                    for (final period in periods)
                      ChoiceChip(
                        label: Text(period.$2),
                        selected: _period == period.$1,
                        onSelected: (_) {
                          setState(() {
                            _period = period.$1;
                            _future = _load();
                          });
                        },
                      ),
                  ],
                ),
              ],
            ),
          ),
          Expanded(
            child: FutureBuilder<List<FinancialReceiptItem>>(
              future: _future,
              builder: (context, snapshot) {
                if (snapshot.hasError) {
                  return ErrorRetry(message: '${snapshot.error}', onRetry: _reload);
                }
                if (!snapshot.hasData) {
                  return const Center(child: CircularProgressIndicator());
                }
                final items = snapshot.data!;
                if (items.isEmpty) {
                  return EmptyState(
                    icon: Icons.receipt_long_outlined,
                    message: switch (_tab) {
                      'pendentes' => 'Nenhuma despesa paga sem comprovante neste período.',
                      'receitas' => 'Nenhuma receita recebida neste período.',
                      _ => 'Nenhum arquivo de recibo neste período.',
                    },
                  );
                }
                return ListView.builder(
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 28),
                  itemCount: items.length,
                  itemBuilder: (context, index) {
                    final item = items[index];
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: SurfaceCard(
                        onTap: () {
                          if (_tab == 'pendentes') {
                            _uploadFor(item);
                            return;
                          }
                          _openItem(item);
                        },
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(item.description, style: Theme.of(context).textTheme.titleMedium),
                                  Text([
                                    if (item.fileName != null) item.fileName!,
                                    if (item.date != null) formatIsoDate(item.date),
                                    if (_tab == 'arquivos' && !item.available && !item.generated)
                                      'arquivo ausente',
                                  ].join(' · ')),
                                ],
                              ),
                            ),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Text(formatMoney(item.amount)),
                                if (_tab == 'pendentes')
                                  TextButton(
                                    onPressed: _uploading ? null : () => _uploadFor(item),
                                    child: const Text('Enviar recibo'),
                                  )
                                else
                                  TextButton(
                                    onPressed: _opening ? null : () => _openItem(item),
                                    child: Text(_tab == 'arquivos' ? 'Abrir' : 'Ver recibo'),
                                  ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class FinancialCorrectionScreen extends StatefulWidget {
  const FinancialCorrectionScreen({super.key});

  @override
  State<FinancialCorrectionScreen> createState() => _FinancialCorrectionScreenState();
}

class _FinancialCorrectionScreenState extends State<FinancialCorrectionScreen> {
  late Future<FinancialTxList> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  AdelssRepository get _api => context.read<SessionController>().api;
  Future<FinancialTxList> _load() => _api.financialTransactions();
  void _reload() {
    setState(() {
      _future = _load();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FinancialScaffold(
      current: 'correcao',
      body: FutureBuilder<FinancialTxList>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorRetry(message: '${snapshot.error}', onRetry: _reload);
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final items = snapshot.data!.items;
          if (items.isEmpty) {
            return const EmptyState(icon: Icons.build_outlined, message: 'Nada para corrigir neste mês.');
          }
          return ListView.builder(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
            itemCount: items.length,
            itemBuilder: (context, index) {
              final item = items[index];
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: SurfaceCard(
                  onTap: () => _correct(item),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(item.description, style: Theme.of(context).textTheme.titleMedium),
                            Text('${item.typeLabel} · ${item.party ?? item.category ?? ''}'),
                          ],
                        ),
                      ),
                      Text(formatMoney(item.amount)),
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

  Future<void> _correct(FinancialTx item) async {
    final description = TextEditingController(text: item.description);
    final amount = TextEditingController(text: item.amount.toStringAsFixed(2).replaceAll('.', ','));
    final saved = await showFinancialFormSheet<bool>(
      context: context,
      title: 'Corrigir lançamento',
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          TextField(controller: description, decoration: const InputDecoration(labelText: 'Descrição')),
          const SizedBox(height: 12),
          TextField(
            controller: amount,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: const InputDecoration(labelText: 'Valor'),
          ),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: () async {
              await _api.correctFinancialTransaction(item.id, {
                'description': description.text.trim(),
                'amount': double.parse(amount.text.replaceAll('.', '').replaceAll(',', '.')),
              });
              if (!context.mounted) return;
              Navigator.pop(context, true);
            },
            child: const Text('Salvar correção'),
          ),
        ],
      ),
    );
    if (saved == true) _reload();
  }
}

class FinancialFixedExpensesScreen extends StatefulWidget {
  const FinancialFixedExpensesScreen({super.key});

  @override
  State<FinancialFixedExpensesScreen> createState() => _FinancialFixedExpensesScreenState();
}

class _FinancialFixedExpensesScreenState extends State<FinancialFixedExpensesScreen> {
  late Future<FinancialFixedPayload> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  AdelssRepository get _api => context.read<SessionController>().api;
  Future<FinancialFixedPayload> _load() => _api.financialFixedExpenses();
  void _reload() {
    setState(() {
      _future = _load();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FinancialScaffold(
      current: 'despesas-fixas',
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _edit(context),
        icon: const Icon(Icons.add),
        label: const Text('Fixa'),
      ),
      body: FutureBuilder<FinancialFixedPayload>(
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
              Text(capitalize(data.monthLabel), style: Theme.of(context).textTheme.bodyMedium),
              if (data.canManage)
                Align(
                  alignment: Alignment.centerLeft,
                  child: TextButton.icon(
                    onPressed: () async {
                      final created = await _api.generateFinancialFixedExpenses(month: data.month);
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(content: Text('$created despesa(s) gerada(s).')),
                        );
                      }
                      _reload();
                    },
                    icon: const Icon(Icons.sync),
                    label: const Text('Gerar mês'),
                  ),
                ),
              if (data.items.isEmpty)
                const EmptyState(icon: Icons.sync_outlined, message: 'Nenhuma despesa fixa.')
              else
                ...data.items.map(
                  (item) => Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: SurfaceCard(
                      onTap: data.canManage ? () => _edit(context, item) : null,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(item.description, style: Theme.of(context).textTheme.titleMedium),
                              ),
                              Text(formatMoney(item.amount)),
                            ],
                          ),
                          Text('Vence dia ${item.dueDay} · ${item.category ?? ''}'),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              StatusPill(
                                label: item.paid
                                    ? 'Paga'
                                    : item.generated
                                        ? 'Gerada'
                                        : 'Não gerada',
                                tone: item.paid
                                    ? StatusTone.success
                                    : item.generated
                                        ? StatusTone.pending
                                        : StatusTone.neutral,
                              ),
                              const Spacer(),
                              if (data.canManage && item.generated && !item.paid && item.transactionId != null)
                                TextButton(
                                  onPressed: () async {
                                    await _api.payFinancialFixedExpense(item.transactionId!);
                                    _reload();
                                  },
                                  child: const Text('Pagar'),
                                ),
                            ],
                          ),
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

  Future<void> _edit(BuildContext context, [FinancialFixedItem? item]) async {
    final options = await _api.financialOptions();
    if (!mounted) return;
    final description = TextEditingController(text: item?.description ?? '');
    final amount = TextEditingController(
      text: (item?.amount ?? 0).toStringAsFixed(2).replaceAll('.', ','),
    );
    final due = TextEditingController(text: '${item?.dueDay ?? 10}');
    final despesaCategories = options.categories.where((row) => row.type == 'despesa').toList();
    int? categoryId = item?.categoryId ?? (despesaCategories.isEmpty ? null : despesaCategories.first.id);
    int? accountId = item?.accountId ?? (options.accounts.isEmpty ? null : options.accounts.first.id);
    final saved = await showFinancialFormSheet<bool>(
      context: context,
      title: item == null ? 'Nova despesa fixa' : 'Editar despesa fixa',
      child: StatefulBuilder(
        builder: (context, setLocal) {
          return Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(controller: description, decoration: const InputDecoration(labelText: 'Descrição')),
              const SizedBox(height: 12),
              TextField(
                controller: amount,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(labelText: 'Valor'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: due,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Dia do vencimento (1-28)'),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int?>(
                key: ValueKey('cat-$categoryId'),
                initialValue: categoryId,
                decoration: const InputDecoration(labelText: 'Categoria'),
                items: options.categories
                    .where((row) => row.type == 'despesa')
                    .map((row) => DropdownMenuItem(value: row.id, child: Text(row.name)))
                    .toList(),
                onChanged: (value) => setLocal(() => categoryId = value),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int?>(
                key: ValueKey('acc-$accountId'),
                initialValue: accountId,
                decoration: const InputDecoration(labelText: 'Conta'),
                items: options.accounts
                    .map((row) => DropdownMenuItem(value: row.id, child: Text(row.name)))
                    .toList(),
                onChanged: (value) => setLocal(() => accountId = value),
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: () async {
                  await _api.saveFinancialFixedExpense({
                    'description': description.text.trim(),
                    'amount': double.parse(amount.text.replaceAll('.', '').replaceAll(',', '.')),
                    'due_day': int.tryParse(due.text) ?? 10,
                    'category_id': categoryId,
                    'account_id': accountId,
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
}

class FinancialCampaignsScreen extends StatefulWidget {
  const FinancialCampaignsScreen({super.key});

  @override
  State<FinancialCampaignsScreen> createState() => _FinancialCampaignsScreenState();
}

class _FinancialCampaignsScreenState extends State<FinancialCampaignsScreen> {
  late Future<List<FinancialCampaign>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  AdelssRepository get _api => context.read<SessionController>().api;
  Future<List<FinancialCampaign>> _load() => _api.financialCampaigns();
  void _reload() {
    setState(() {
      _future = _load();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FinancialScaffold(
      current: 'campanhas',
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _create(context),
        icon: const Icon(Icons.add),
        label: const Text('Campanha'),
      ),
      body: FutureBuilder<List<FinancialCampaign>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorRetry(message: '${snapshot.error}', onRetry: _reload);
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final items = snapshot.data!;
          if (items.isEmpty) {
            return const EmptyState(icon: Icons.favorite_outline, message: 'Nenhuma campanha.');
          }
          return ListView.builder(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 88),
            itemCount: items.length,
            itemBuilder: (context, index) {
              final item = items[index];
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: SurfaceCard(
                  onTap: () => context.push('/financeiro/campanhas/${item.id}'),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(item.name, style: Theme.of(context).textTheme.titleMedium),
                      Text(
                        '${item.status} · ${item.sponsorsCount} patrocinador(es) · parcela ${formatMoney(item.installmentAmount)}',
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

  Future<void> _create(BuildContext context) async {
    final name = TextEditingController();
    final amount = TextEditingController(text: '50,00');
    final count = TextEditingController(text: '12');
    final saved = await showFinancialFormSheet<bool>(
      context: context,
      title: 'Nova campanha',
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          TextField(controller: name, decoration: const InputDecoration(labelText: 'Nome')),
          const SizedBox(height: 12),
          TextField(
            controller: amount,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: const InputDecoration(labelText: 'Valor da parcela'),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: count,
            keyboardType: TextInputType.number,
            decoration: const InputDecoration(labelText: 'Quantidade de parcelas'),
          ),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: () async {
              await _api.saveFinancialCampaign({
                'name': name.text.trim(),
                'installment_amount': double.parse(amount.text.replaceAll('.', '').replaceAll(',', '.')),
                'installments_count': int.tryParse(count.text) ?? 1,
                'status': 'ativa',
              });
              if (context.mounted) Navigator.pop(context, true);
            },
            child: const Text('Criar'),
          ),
        ],
      ),
    );
    if (saved == true) _reload();
  }
}

class FinancialCampaignDetailScreen extends StatefulWidget {
  const FinancialCampaignDetailScreen({super.key, required this.id});

  final int id;

  @override
  State<FinancialCampaignDetailScreen> createState() => _FinancialCampaignDetailScreenState();
}

class _FinancialCampaignDetailScreenState extends State<FinancialCampaignDetailScreen> {
  late Future<FinancialCampaignDetail> _future;

  @override
  void initState() {
    super.initState();
    _future = context.read<SessionController>().api.financialCampaign(widget.id);
  }

  AdelssRepository get _api => context.read<SessionController>().api;
  void _reload() {
    setState(() {
      _future = _api.financialCampaign(widget.id);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Campanha')),
      body: FutureBuilder<FinancialCampaignDetail>(
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
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
            children: [
              Text(data.campaign.name, style: Theme.of(context).textTheme.headlineSmall),
              Text('${data.campaign.status} · parcela ${formatMoney(data.campaign.installmentAmount)}'),
              const SectionHeader('Patrocinadores'),
              if (data.sponsors.isEmpty)
                const Text('Nenhum patrocinador ainda. Cadastre no painel web para gerar o carnê.')
              else
                ...data.sponsors.map(
                  (sponsor) => Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: SurfaceCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(sponsor.name, style: Theme.of(context).textTheme.titleMedium),
                          Text('Pago ${formatMoney(sponsor.paidTotal)} · ${sponsor.pendingCount} pendente(s)'),
                          const SizedBox(height: 8),
                          ...sponsor.installments.map(
                            (row) => ListTile(
                              contentPadding: EdgeInsets.zero,
                              dense: true,
                              title: Text('Parcela ${row.number} · ${formatMoney(row.amount)}'),
                              subtitle: Text(row.status == 'pago'
                                  ? 'Pago ${row.receiptNumber ?? ''}'
                                  : 'Vence ${formatIsoDate(row.dueDate)}'),
                              trailing: data.canManage
                                  ? TextButton(
                                      onPressed: () async {
                                        if (row.status == 'pago') {
                                          await _api.reverseFinancialInstallment(row.id);
                                        } else {
                                          await _api.payFinancialInstallment(row.id, method: 'pix');
                                        }
                                        _reload();
                                      },
                                      child: Text(row.status == 'pago' ? 'Estornar' : 'Pagar'),
                                    )
                                  : null,
                            ),
                          ),
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
}

class FinancialReportsScreen extends StatefulWidget {
  const FinancialReportsScreen({super.key});

  @override
  State<FinancialReportsScreen> createState() => _FinancialReportsScreenState();
}

class _FinancialReportsScreenState extends State<FinancialReportsScreen> {
  late Future<FinancialReport> _future;

  @override
  void initState() {
    super.initState();
    _future = context.read<SessionController>().api.financialReports();
  }

  void _reload() {
    setState(() {
      _future = context.read<SessionController>().api.financialReports();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FinancialScaffold(
      current: 'relatorios',
      body: FutureBuilder<FinancialReport>(
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
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
            children: [
              SurfaceCard(
                color: AdelssColors.ink,
                outlined: false,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Resultado', style: TextStyle(color: Colors.white70)),
                    Text(
                      formatMoney(data.result),
                      style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w700),
                    ),
                    Text(
                      'Entradas ${formatMoney(data.inTotal)} · Saídas ${formatMoney(data.outTotal)}',
                      style: const TextStyle(color: Colors.white70),
                    ),
                  ],
                ),
              ),
              const SectionHeader('Receitas por categoria'),
              ...data.receitas.map((row) => _ReportRow(row: row, color: AdelssColors.success)),
              const SectionHeader('Despesas por categoria'),
              ...data.despesas.map((row) => _ReportRow(row: row, color: AdelssColors.danger)),
            ],
          );
        },
      ),
    );
  }
}

class _ReportRow extends StatelessWidget {
  const _ReportRow({required this.row, required this.color});

  final FinancialReportRow row;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: SurfaceCard(
        child: Row(
          children: [
            Expanded(child: Text(row.category, style: Theme.of(context).textTheme.titleMedium)),
            Text(formatMoney(row.total), style: TextStyle(color: color, fontWeight: FontWeight.w700)),
          ],
        ),
      ),
    );
  }
}

class FinancialAutomationsScreen extends StatefulWidget {
  const FinancialAutomationsScreen({super.key});

  @override
  State<FinancialAutomationsScreen> createState() => _FinancialAutomationsScreenState();
}

class _FinancialAutomationsScreenState extends State<FinancialAutomationsScreen> {
  late Future<List<FinancialAutomationItem>> _future;

  @override
  void initState() {
    super.initState();
    _future = context.read<SessionController>().api.financialAutomations();
  }

  AdelssRepository get _api => context.read<SessionController>().api;
  void _reload() {
    setState(() {
      _future = _api.financialAutomations();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FinancialScaffold(
      current: 'automacoes',
      body: FutureBuilder<List<FinancialAutomationItem>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorRetry(message: '${snapshot.error}', onRetry: _reload);
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final items = snapshot.data!;
          if (items.isEmpty) {
            return const EmptyState(icon: Icons.auto_awesome_outlined, message: 'Nenhuma automação.');
          }
          return ListView.builder(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
            itemCount: items.length,
            itemBuilder: (context, index) {
              final item = items[index];
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: SurfaceCard(
                  child: SwitchListTile(
                    contentPadding: EdgeInsets.zero,
                    title: Text(item.name, style: Theme.of(context).textTheme.titleMedium),
                    subtitle: Text(item.enabled ? 'Ativa' : 'Desligada'),
                    value: item.enabled,
                    onChanged: (value) async {
                      await _api.toggleFinancialAutomation(item.id, enabled: value);
                      _reload();
                    },
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
