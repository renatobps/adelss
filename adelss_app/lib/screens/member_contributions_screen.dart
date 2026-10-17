import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/models.dart';
import '../session/session_controller.dart';
import '../theme.dart';
import '../utils/format.dart';
import '../widgets/ui.dart';

class MemberContributionsScreen extends StatefulWidget {
  const MemberContributionsScreen({super.key});

  @override
  State<MemberContributionsScreen> createState() => _MemberContributionsScreenState();
}

class _MemberContributionsScreenState extends State<MemberContributionsScreen> {
  late Future<ContributionSummary> _future;

  @override
  void initState() {
    super.initState();
    _future = context.read<SessionController>().api.contributions();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Dízimos e ofertas')),
      body: FutureBuilder<ContributionSummary>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorRetry(
              message: '${snapshot.error}',
              onRetry: () {
                setState(() {
                  _future = context.read<SessionController>().api.contributions();
                });
              },
            );
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
                padding: const EdgeInsets.all(22),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Total registrado', style: TextStyle(color: Colors.white70)),
                    const SizedBox(height: 6),
                    Text(
                      formatMoney(data.total),
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
                  Expanded(
                    child: SurfaceCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Dízimo'),
                          const SizedBox(height: 4),
                          Text(
                            formatMoney(data.dizimoTotal),
                            style: Theme.of(context).textTheme.titleLarge,
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: SurfaceCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Ofertas'),
                          const SizedBox(height: 4),
                          Text(
                            formatMoney(data.ofertaTotal),
                            style: Theme.of(context).textTheme.titleLarge,
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
              const SectionHeader('Lançamentos'),
              if (data.items.isEmpty)
                const SurfaceCard(child: Text('Nenhuma contribuição encontrada.'))
              else
                ...data.items.map(
                  (item) => Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: SurfaceCard(
                      child: Row(
                        children: [
                          DateStamp(date: item.date),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  item.category ?? item.description ?? 'Contribuição',
                                  style: Theme.of(context).textTheme.titleMedium,
                                ),
                                if (item.description != null && item.description != item.category)
                                  Text(item.description!),
                                if (item.account != null) Text(item.account!),
                              ],
                            ),
                          ),
                          Text(
                            formatMoney(item.amount),
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
