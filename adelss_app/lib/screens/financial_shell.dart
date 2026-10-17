import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/models.dart';
import '../session/session_controller.dart';
import '../theme.dart';
import '../widgets/ui.dart';

IconData financialIcon(String key) {
  switch (key) {
    case 'resumo':
      return Icons.bar_chart_rounded;
    case 'transacoes':
      return Icons.attach_money_rounded;
    case 'recibos':
      return Icons.receipt_long_outlined;
    case 'correcao':
      return Icons.build_outlined;
    case 'despesas-fixas':
      return Icons.sync_outlined;
    case 'contas':
      return Icons.account_balance_wallet_outlined;
    case 'categorias':
      return Icons.sell_outlined;
    case 'centros-custo':
      return Icons.gps_fixed_outlined;
    case 'fornecedores':
      return Icons.storefront_outlined;
    case 'campanhas':
      return Icons.favorite_outline;
    case 'relatorios':
      return Icons.description_outlined;
    case 'automacoes':
      return Icons.auto_awesome_outlined;
    default:
      return Icons.circle_outlined;
  }
}

String financialPath(String key) {
  if (key == 'resumo' || key.isEmpty) {
    return '/financeiro';
  }
  return '/financeiro?s=$key';
}

const fallbackFinancialNav = <FinancialNavItem>[
  FinancialNavItem(key: 'resumo', label: 'Resumo', icon: 'chart'),
  FinancialNavItem(key: 'transacoes', label: 'Transações', icon: 'dollar'),
  FinancialNavItem(key: 'recibos', label: 'Recibos', icon: 'receipt'),
  FinancialNavItem(key: 'correcao', label: 'Correção', icon: 'wrench'),
  FinancialNavItem(key: 'despesas-fixas', label: 'Despesas Fixas', icon: 'refresh'),
  FinancialNavItem(key: 'contas', label: 'Contas e Caixas', icon: 'wallet'),
  FinancialNavItem(key: 'categorias', label: 'Categorias', icon: 'tag'),
  FinancialNavItem(key: 'centros-custo', label: 'Centros de Custo', icon: 'target'),
  FinancialNavItem(key: 'fornecedores', label: 'Fornecedores', icon: 'store'),
  FinancialNavItem(key: 'campanhas', label: 'Campanhas', icon: 'favorite'),
  FinancialNavItem(key: 'relatorios', label: 'Relatórios', icon: 'file'),
  FinancialNavItem(key: 'automacoes', label: 'Automações', icon: 'spark'),
];

class FinancialScaffold extends StatefulWidget {
  const FinancialScaffold({
    super.key,
    required this.current,
    required this.body,
    this.floatingActionButton,
  });

  final String current;
  final Widget body;
  final Widget? floatingActionButton;

  @override
  State<FinancialScaffold> createState() => _FinancialScaffoldState();
}

class _FinancialScaffoldState extends State<FinancialScaffold> {
  late Future<FinancialModule> _future;

  @override
  void initState() {
    super.initState();
    _future = context.read<SessionController>().api.financialModule();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Financeiro')),
      floatingActionButton: widget.floatingActionButton,
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 4, 20, 0),
            child: PageHeader(
              title: 'Financeiro',
              subtitle: 'Gestão financeira completa',
            ),
          ),
          FutureBuilder<FinancialModule>(
            future: _future,
            builder: (context, snapshot) {
              final nav = snapshot.data?.nav ?? const <FinancialNavItem>[];
              final tiles = nav.isEmpty ? fallbackFinancialNav : nav;
              return Padding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                child: Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: tiles
                      .map(
                        (item) => _FinanceTile(
                          item: item,
                          selected: item.key == widget.current,
                          onTap: () => context.go(financialPath(item.key)),
                        ),
                      )
                      .toList(),
                ),
              );
            },
          ),
          const SizedBox(height: 8),
          Expanded(child: widget.body),
        ],
      ),
    );
  }
}

class _FinanceTile extends StatelessWidget {
  const _FinanceTile({
    required this.item,
    required this.selected,
    required this.onTap,
  });

  final FinancialNavItem item;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final bg = selected ? const Color(0xFFE8F1FF) : AdelssColors.paper;
    final fg = selected ? const Color(0xFF2563EB) : AdelssColors.ink;
    return Material(
      color: bg,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(
          color: selected ? const Color(0xFFBFDBFE) : AdelssColors.line,
        ),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(financialIcon(item.key), size: 18, color: fg),
              const SizedBox(width: 8),
              Text(
                item.label,
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: fg,
                  fontSize: 13,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

Future<T?> showFinancialFormSheet<T>({
  required BuildContext context,
  required String title,
  required Widget child,
}) {
  return showModalBottomSheet<T>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (context) {
      return Padding(
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          bottom: MediaQuery.viewInsetsOf(context).bottom + 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(title, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 16),
            child,
            const SizedBox(height: 12),
          ],
        ),
      );
    },
  );
}
