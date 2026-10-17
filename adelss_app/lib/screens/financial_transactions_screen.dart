import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../api/models.dart';
import '../api/repository.dart';
import '../session/session_controller.dart';
import '../theme.dart';
import '../utils/format.dart';
import '../widgets/financial_attachments.dart';
import '../widgets/ui.dart';
import 'financial_shell.dart';

class FinancialTransactionsScreen extends StatefulWidget {
  const FinancialTransactionsScreen({super.key});

  @override
  State<FinancialTransactionsScreen> createState() => _FinancialTransactionsScreenState();
}

class _FinancialTransactionsScreenState extends State<FinancialTransactionsScreen> {
  String? _type;
  late Future<FinancialTxList> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  AdelssRepository get _api => context.read<SessionController>().api;

  Future<FinancialTxList> _load() => _api.financialTransactions(type: _type);

  void _reload() {
    setState(() {
      _future = _load();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FinancialScaffold(
      current: 'transacoes',
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _newMenu(context),
        icon: const Icon(Icons.add),
        label: const Text('Lançar'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
            child: Wrap(
              spacing: 8,
              children: [
                ChoiceChip(
                  label: const Text('Todas'),
                  selected: _type == null,
                  onSelected: (_) {
                    setState(() {
                      _type = null;
                      _future = _load();
                    });
                  },
                ),
                ChoiceChip(
                  label: const Text('Receitas'),
                  selected: _type == 'receita',
                  onSelected: (_) {
                    setState(() {
                      _type = 'receita';
                      _future = _load();
                    });
                  },
                ),
                ChoiceChip(
                  label: const Text('Despesas'),
                  selected: _type == 'despesa',
                  onSelected: (_) {
                    setState(() {
                      _type = 'despesa';
                      _future = _load();
                    });
                  },
                ),
              ],
            ),
          ),
          Expanded(
            child: FutureBuilder<FinancialTxList>(
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
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 88),
                  children: [
                    Row(
                      children: [
                        Expanded(child: _Mini(label: 'Recebido', value: data.totalReceived)),
                        const SizedBox(width: 8),
                        Expanded(child: _Mini(label: 'Pago', value: data.totalPaid)),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Expanded(child: _Mini(label: 'A receber', value: data.toReceive)),
                        const SizedBox(width: 8),
                        Expanded(child: _Mini(label: 'A pagar', value: data.toPay)),
                      ],
                    ),
                    const SizedBox(height: 12),
                    if (data.items.isEmpty)
                      const EmptyState(
                        icon: Icons.receipt_long_outlined,
                        message: 'Nenhuma transação neste período.',
                      )
                    else
                      ...data.items.map(
                        (item) => Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: SurfaceCard(
                            onTap: () => context.push('/financeiro/transacoes/${item.id}'),
                            child: Row(
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(item.description, style: Theme.of(context).textTheme.titleMedium),
                                      const SizedBox(height: 4),
                                      Text(
                                        [
                                          item.typeLabel,
                                          item.party,
                                          item.category,
                                          if (item.date != null) formatIsoDate(item.date),
                                        ].where((part) => part != null && part.isNotEmpty).join(' · '),
                                      ),
                                    ],
                                  ),
                                ),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    Text(
                                      formatMoney(item.amount),
                                      style: TextStyle(
                                        fontWeight: FontWeight.w700,
                                        color: item.type == 'receita'
                                            ? AdelssColors.success
                                            : AdelssColors.danger,
                                      ),
                                    ),
                                    StatusPill(
                                      label: item.statusLabel ?? (item.paid ? 'Pago' : 'Pendente'),
                                      tone: item.paid ? StatusTone.success : StatusTone.pending,
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
          ),
        ],
      ),
    );
  }

  Future<void> _newMenu(BuildContext context) async {
    final type = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.south_west, color: AdelssColors.success),
              title: const Text('Nova receita'),
              onTap: () => Navigator.pop(context, 'receita'),
            ),
            ListTile(
              leading: const Icon(Icons.north_east, color: AdelssColors.danger),
              title: const Text('Nova despesa'),
              onTap: () => Navigator.pop(context, 'despesa'),
            ),
          ],
        ),
      ),
    );
    if (type == null || !context.mounted) return;
    await context.push('/financeiro/transacoes/nova?type=$type');
    _reload();
  }
}

class FinancialTransactionFormScreen extends StatefulWidget {
  const FinancialTransactionFormScreen({super.key, this.id, this.type = 'receita'});

  final int? id;
  final String type;

  @override
  State<FinancialTransactionFormScreen> createState() => _FinancialTransactionFormScreenState();
}

class _FinancialTransactionFormScreenState extends State<FinancialTransactionFormScreen> {
  final _form = GlobalKey<FormState>();
  final _description = TextEditingController();
  final _amount = TextEditingController();
  final _other = TextEditingController();
  final _notes = TextEditingController();
  DateTime _date = DateTime.now();
  bool _paid = true;
  int? _accountId;
  int? _categoryId;
  int? _memberId;
  int? _contactId;
  int? _costCenterId;
  bool _loading = true;
  bool _saving = false;
  String? _error;
  FinancialOptions? _options;
  late String _type;
  final List<PendingUpload> _pending = [];
  List<FinancialAttachment> _existingFiles = [];

  @override
  void initState() {
    super.initState();
    _type = widget.type == 'despesa' ? 'despesa' : 'receita';
    _boot();
  }

  @override
  void dispose() {
    _description.dispose();
    _amount.dispose();
    _other.dispose();
    _notes.dispose();
    super.dispose();
  }

  AdelssRepository get _api => context.read<SessionController>().api;

  Future<void> _boot() async {
    try {
      final options = await _api.financialOptions();
      FinancialTx? existing;
      if (widget.id != null) {
        existing = await _api.financialTransaction(widget.id!);
      }
      if (!mounted) return;
      setState(() {
        _options = options;
        if (existing != null) {
          _type = existing.type;
          _description.text = existing.description;
          _amount.text = existing.amount.toStringAsFixed(2).replaceAll('.', ',');
          _other.text = existing.receivedFromOther ?? '';
          _notes.text = existing.notes ?? '';
          _date = existing.date ?? DateTime.now();
          _paid = existing.paid;
          _accountId = existing.accountId;
          _categoryId = existing.categoryId;
          _memberId = existing.memberId;
          _contactId = existing.contactId;
          _costCenterId = existing.costCenterId;
          _existingFiles = existing.attachments;
        }
        _loading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _error = '$error';
        _loading = false;
      });
    }
  }

  Future<void> _save() async {
    if (!_form.currentState!.validate()) return;
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      final saved = await _api.saveFinancialTransaction({
        'type': _type,
        'transaction_date': _date.toIso8601String().split('T').first,
        'description': _description.text.trim(),
        'amount': double.parse(_amount.text.trim().replaceAll('.', '').replaceAll(',', '.')),
        'is_paid': _paid,
        'account_id': _accountId,
        'category_id': _categoryId,
        'member_id': _memberId,
        'contact_id': _contactId,
        'cost_center_id': _costCenterId,
        if (_other.text.trim().isNotEmpty) 'received_from_other': _other.text.trim(),
        if (_notes.text.trim().isNotEmpty) 'notes': _notes.text.trim(),
      }, id: widget.id);
      if (_pending.isNotEmpty) {
        await uploadPickedReceipts(
          api: _api,
          transactionId: saved.id,
          files: _pending,
        );
      }
      if (!mounted) return;
      context.pop();
    } on ApiException catch (error) {
      setState(() {
        _error = error.message;
        _saving = false;
      });
    } catch (error) {
      setState(() {
        _error = '$error';
        _saving = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = widget.id == null
        ? (_type == 'despesa' ? 'Nova despesa' : 'Nova receita')
        : 'Editar lançamento';
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Form(
              key: _form,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
                children: [
                  if (_error != null) ...[
                    Text(_error!, style: const TextStyle(color: AdelssColors.danger)),
                    const SizedBox(height: 12),
                  ],
                  TextFormField(
                    controller: _description,
                    decoration: const InputDecoration(labelText: 'Descrição'),
                    validator: (value) =>
                        value == null || value.trim().isEmpty ? 'Informe a descrição' : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _amount,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: const InputDecoration(labelText: 'Valor'),
                    validator: (value) {
                      final parsed = double.tryParse(
                        (value ?? '').trim().replaceAll('.', '').replaceAll(',', '.'),
                      );
                      if (parsed == null || parsed <= 0) return 'Informe um valor válido';
                      return null;
                    },
                  ),
                  const SizedBox(height: 12),
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: const Text('Data'),
                    subtitle: Text(formatIsoDate(_date)),
                    trailing: const Icon(Icons.calendar_today_outlined),
                    onTap: () async {
                      final picked = await showDatePicker(
                        context: context,
                        initialDate: _date,
                        firstDate: DateTime(2018),
                        lastDate: DateTime.now().add(const Duration(days: 365)),
                      );
                      if (picked != null) setState(() => _date = picked);
                    },
                  ),
                  SwitchListTile(
                    contentPadding: EdgeInsets.zero,
                    title: Text(_type == 'despesa' ? 'Já pago' : 'Já recebido'),
                    value: _paid,
                    onChanged: (value) => setState(() => _paid = value),
                  ),
                  DropdownButtonFormField<int?>(
                    key: ValueKey('account-$_accountId'),
                    initialValue: _accountId,
                    decoration: const InputDecoration(labelText: 'Conta / forma'),
                    items: [
                      const DropdownMenuItem(value: null, child: Text('Selecione')),
                      ...?_options?.accounts.map(
                        (item) => DropdownMenuItem(value: item.id, child: Text(item.label ?? item.name)),
                      ),
                    ],
                    onChanged: (value) => setState(() => _accountId = value),
                    validator: (value) => value == null ? 'Escolha a conta' : null,
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<int?>(
                    key: ValueKey('category-$_categoryId'),
                    initialValue: _categoryId,
                    decoration: const InputDecoration(labelText: 'Categoria'),
                    items: [
                      const DropdownMenuItem(value: null, child: Text('Sem categoria')),
                      ...?_options?.categories
                          .where((item) => item.type == _type)
                          .map((item) => DropdownMenuItem(value: item.id, child: Text(item.name))),
                    ],
                    onChanged: (value) => setState(() => _categoryId = value),
                  ),
                  const SizedBox(height: 12),
                  if (_type == 'receita') ...[
                    DropdownButtonFormField<int?>(
                      key: ValueKey('member-$_memberId'),
                      initialValue: _memberId,
                      decoration: const InputDecoration(labelText: 'Membro'),
                      items: [
                        const DropdownMenuItem(value: null, child: Text('Outra pessoa')),
                        ...?_options?.members.map(
                          (item) => DropdownMenuItem(value: item.id, child: Text(item.name)),
                        ),
                      ],
                      onChanged: (value) => setState(() => _memberId = value),
                    ),
                    if (_memberId == null) ...[
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _other,
                        decoration: const InputDecoration(labelText: 'Recebido de'),
                      ),
                    ],
                  ] else
                    DropdownButtonFormField<int?>(
                      key: ValueKey('contact-$_contactId'),
                      initialValue: _contactId,
                      decoration: const InputDecoration(labelText: 'Fornecedor'),
                      items: [
                        const DropdownMenuItem(value: null, child: Text('Não informado')),
                        ...?_options?.contacts.map(
                          (item) => DropdownMenuItem(value: item.id, child: Text(item.name)),
                        ),
                      ],
                      onChanged: (value) => setState(() => _contactId = value),
                    ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<int?>(
                    key: ValueKey('cc-$_costCenterId'),
                    initialValue: _costCenterId,
                    decoration: const InputDecoration(labelText: 'Centro de custo'),
                    items: [
                      const DropdownMenuItem(value: null, child: Text('Nenhum')),
                      ...?_options?.costCenters.map(
                        (item) => DropdownMenuItem(value: item.id, child: Text(item.name)),
                      ),
                    ],
                    onChanged: (value) => setState(() => _costCenterId = value),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _notes,
                    maxLines: 3,
                    decoration: const InputDecoration(labelText: 'Observações'),
                  ),
                  const SizedBox(height: 16),
                  OutlinedButton.icon(
                    onPressed: () async {
                      await showReceiptSourceSheet(
                        context: context,
                        onPicked: (files) async {
                          if (!mounted) return;
                          final remaining = 5 - _existingFiles.length - _pending.length;
                          if (remaining <= 0) {
                            showUploadError(context, 'Este lançamento já tem o máximo de 5 arquivos.');
                            return;
                          }
                          setState(() => _pending.addAll(files.take(remaining)));
                        },
                      );
                    },
                    icon: const Icon(Icons.upload_file_outlined),
                    label: const Text('Anexar recibo'),
                  ),
                  if (_existingFiles.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    ..._existingFiles.map(
                      (file) => ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.attach_file),
                        title: Text(file.fileName),
                        subtitle: const Text('Já enviado'),
                      ),
                    ),
                  ],
                  if (_pending.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    ..._pending.asMap().entries.map(
                      (entry) => ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.insert_drive_file_outlined),
                        title: Text(entry.value.filename),
                        subtitle: const Text('Será enviado ao salvar'),
                        trailing: IconButton(
                          onPressed: () => setState(() => _pending.removeAt(entry.key)),
                          icon: const Icon(Icons.close),
                        ),
                      ),
                    ),
                  ],
                  const SizedBox(height: 20),
                  FilledButton(
                    onPressed: _saving ? null : _save,
                    child: Text(_saving ? 'Salvando...' : 'Salvar'),
                  ),
                ],
              ),
            ),
    );
  }
}

class FinancialTransactionDetailScreen extends StatefulWidget {
  const FinancialTransactionDetailScreen({super.key, required this.id});

  final int id;

  @override
  State<FinancialTransactionDetailScreen> createState() => _FinancialTransactionDetailScreenState();
}

class _FinancialTransactionDetailScreenState extends State<FinancialTransactionDetailScreen> {
  late Future<FinancialTx> _future;
  bool _uploading = false;

  @override
  void initState() {
    super.initState();
    _future = context.read<SessionController>().api.financialTransaction(widget.id);
  }

  AdelssRepository get _api => context.read<SessionController>().api;

  void _reload() {
    setState(() {
      _future = _api.financialTransaction(widget.id);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Lançamento')),
      body: FutureBuilder<FinancialTx>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorRetry(message: '${snapshot.error}', onRetry: _reload);
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final item = snapshot.data!;
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
            children: [
              Text(item.description, style: Theme.of(context).textTheme.headlineSmall),
              const SizedBox(height: 8),
              Text(
                formatMoney(item.amount),
                style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                      color: item.type == 'receita' ? AdelssColors.success : AdelssColors.danger,
                    ),
              ),
              const SizedBox(height: 16),
              _Row('Tipo', item.typeLabel),
              _Row('Status', item.statusLabel ?? (item.paid ? 'Pago' : 'Pendente')),
              _Row('Data', formatIsoDate(item.date)),
              if (item.party != null) _Row(item.type == 'despesa' ? 'Fornecedor' : 'De', item.party!),
              if (item.category != null) _Row('Categoria', item.category!),
              if (item.account != null) _Row('Conta', item.account!),
              if (item.notes != null && item.notes!.isNotEmpty) _Row('Notas', item.notes!),
              const SizedBox(height: 16),
              OutlinedButton.icon(
                onPressed: () => openFinancialApiFile(
                  context: context,
                  api: _api,
                  path: '/financial/transactions/${item.id}/receipt',
                  filename: 'recibo-${item.id}.pdf',
                  isImage: false,
                ),
                icon: const Icon(Icons.receipt_long_outlined),
                label: const Text('Ver recibo'),
              ),
              const SizedBox(height: 16),
              AttachmentSection(
                api: _api,
                attachments: item.attachments,
                busy: _uploading,
                onAdd: () async {
                  await showReceiptSourceSheet(
                    context: context,
                    onPicked: (files) async {
                      setState(() => _uploading = true);
                      try {
                        await uploadPickedReceipts(
                          api: _api,
                          transactionId: item.id,
                          files: files,
                        );
                        _reload();
                      } catch (error) {
                        if (context.mounted) showUploadError(context, error);
                      } finally {
                        if (mounted) setState(() => _uploading = false);
                      }
                    },
                  );
                },
                onDelete: (file) async {
                  try {
                    await _api.deleteFinancialAttachment(
                      transactionId: item.id,
                      attachmentId: file.id,
                    );
                    _reload();
                  } catch (error) {
                    if (context.mounted) showUploadError(context, error);
                  }
                },
              ),
              const SizedBox(height: 20),
              if (!item.paid)
                FilledButton(
                  onPressed: () async {
                    await _api.payFinancialTransaction(item.id);
                    _reload();
                  },
                  child: Text(item.type == 'despesa' ? 'Marcar como pago' : 'Marcar como recebido'),
                ),
              const SizedBox(height: 8),
              OutlinedButton(
                onPressed: () async {
                  await context.push('/financeiro/transacoes/${item.id}/editar');
                  _reload();
                },
                child: const Text('Editar'),
              ),
              const SizedBox(height: 8),
              OutlinedButton(
                onPressed: () async {
                  await _api.duplicateFinancialTransaction(item.id);
                  if (context.mounted) context.pop();
                },
                child: const Text('Duplicar'),
              ),
              const SizedBox(height: 8),
              TextButton(
                onPressed: () async {
                  await _api.deleteFinancialTransaction(item.id);
                  if (context.mounted) context.pop();
                },
                child: const Text('Excluir'),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _Mini extends StatelessWidget {
  const _Mini({required this.label, required this.value});

  final String label;
  final double value;

  @override
  Widget build(BuildContext context) {
    return SurfaceCard(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: Theme.of(context).textTheme.bodyMedium),
          Text(formatMoney(value), style: Theme.of(context).textTheme.titleMedium),
        ],
      ),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row(this.label, this.value);

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 110, child: Text(label, style: Theme.of(context).textTheme.bodyMedium)),
          Expanded(child: Text(value, style: Theme.of(context).textTheme.titleMedium)),
        ],
      ),
    );
  }
}
