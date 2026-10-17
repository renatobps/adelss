import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/models.dart';
import '../session/session_controller.dart';
import '../theme.dart';
import '../utils/format.dart';
import '../widgets/ui.dart';
import 'financial_shell.dart';

class FinancialScreen extends StatefulWidget {
  const FinancialScreen({super.key});

  @override
  State<FinancialScreen> createState() => _FinancialScreenState();
}

class _FinancialScreenState extends State<FinancialScreen> {
  String _period = '1month';
  late Future<FinancialSummary> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<FinancialSummary> _load() {
    return context.read<SessionController>().api.financialSummary(period: _period);
  }

  void _reload() {
    setState(() {
      _future = _load();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FinancialScaffold(
      current: 'resumo',
      body: FutureBuilder<FinancialSummary>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorRetry(message: '${snapshot.error}', onRetry: _reload);
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final data = snapshot.data!;
          final periods = data.periods.isEmpty
              ? const [
                  FinancialOption(value: 'today', label: 'Hoje'),
                  FinancialOption(value: '7days', label: 'Últimos 7 dias'),
                  FinancialOption(value: '1month', label: 'Mês atual'),
                  FinancialOption(value: 'last_month', label: 'Mês anterior'),
                  FinancialOption(value: '3months', label: 'Últimos 3 meses'),
                ]
              : data.periods;
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
            children: [
              Wrap(
                spacing: 8,
                children: periods
                    .map(
                      (period) => ChoiceChip(
                        label: Text(period.label),
                        selected: period.value == _period,
                        onSelected: (_) {
                          setState(() {
                            _period = period.value;
                            _future = _load();
                          });
                        },
                      ),
                    )
                    .toList(),
              ),
              const SizedBox(height: 12),
              Text(capitalize(data.periodLabel), style: Theme.of(context).textTheme.bodyMedium),
              const SizedBox(height: 12),
              SurfaceCard(
                color: AdelssColors.ink,
                outlined: false,
                padding: const EdgeInsets.all(22),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Resultado do período', style: TextStyle(color: Colors.white70)),
                    const SizedBox(height: 6),
                    Text(
                      formatMoney(data.result),
                      style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                            color: Colors.white,
                          ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(child: _MoneyCard(label: 'Entradas', value: data.inTotal)),
                  const SizedBox(width: 10),
                  Expanded(child: _MoneyCard(label: 'Saídas', value: data.outTotal)),
                ],
              ),
              const SectionHeader('Contas e caixas'),
              if (data.accounts.isEmpty)
                const EmptyState(icon: Icons.account_balance_wallet_outlined, message: 'Nenhuma conta cadastrada.')
              else
                ...data.accounts.map(
                  (account) => Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: SurfaceCard(
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  account.label ?? account.name,
                                  style: Theme.of(context).textTheme.titleMedium,
                                ),
                                Text(
                                  account.type == 'caixa'
                                      ? 'Saídas ${formatMoney(account.outflow)}'
                                      : (account.typeLabel ?? ''),
                                  style: Theme.of(context).textTheme.bodyMedium,
                                ),
                              ],
                            ),
                          ),
                          Text(
                            formatMoney(account.balance),
                            style: Theme.of(context).textTheme.titleMedium,
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

class _MoneyCard extends StatelessWidget {
  const _MoneyCard({required this.label, required this.value});

  final String label;
  final double value;

  @override
  Widget build(BuildContext context) {
    return SurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label),
          const SizedBox(height: 6),
          Text(formatMoney(value), style: Theme.of(context).textTheme.titleLarge),
        ],
      ),
    );
  }
}
