import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../api/models.dart';
import '../session/session_controller.dart';
import '../theme.dart';
import '../utils/format.dart';
import '../widgets/cards.dart';
import '../widgets/ui.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late Future<HomePayload> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<HomePayload> _load() {
    final session = context.read<SessionController>();
    return session.api.home().then((home) {
      session.applyHome(home);
      return home;
    });
  }

  @override
  Widget build(BuildContext context) {
    final session = context.watch<SessionController>();
    final fullName = session.profile?.member?.name ?? session.profile?.user.name ?? '';

    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          color: AdelssColors.ink,
          onRefresh: () async {
            setState(() {
              _future = _load();
            });
            await _future;
          },
          child: FutureBuilder<HomePayload>(
            future: _future,
            builder: (context, snapshot) {
              if (snapshot.hasError) {
                final error = snapshot.error;
                if (error is ApiException) {
                  session.handleApiError(error);
                }
                return ListView(
                  children: [
                    SizedBox(
                      height: 360,
                      child: ErrorRetry(
                        message: '$error',
                        onRetry: () {
                          setState(() {
                            _future = _load();
                          });
                        },
                      ),
                    ),
                  ],
                );
              }
              if (!snapshot.hasData) {
                return const Center(child: CircularProgressIndicator());
              }
              final home = snapshot.data!;
              return ListView(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
                children: [
                  Text(
                    '${greetingFor(DateTime.now())}, ${firstName(fullName)}',
                    style: Theme.of(context).textTheme.headlineSmall,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    formatLongDate(DateTime.now()),
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                  const SizedBox(height: 16),
                  SurfaceCard(
                    onTap: () => context.go('/membros'),
                    child: Row(
                      children: [
                        Container(
                          width: 48,
                          height: 48,
                          decoration: BoxDecoration(
                            color: AdelssColors.cream,
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: const Icon(Icons.people_outline, color: AdelssColors.ink),
                        ),
                        const SizedBox(width: 14),
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Membros', style: TextStyle(fontWeight: FontWeight.w700)),
                              Text('Lista, cadastro e ficha de cada pessoa'),
                            ],
                          ),
                        ),
                        const Icon(Icons.chevron_right, color: AdelssColors.muted),
                      ],
                    ),
                  ),
                  const SizedBox(height: 10),
                  SurfaceCard(
                    onTap: () => context.go('/conta'),
                    child: Row(
                      children: [
                        Container(
                          width: 48,
                          height: 48,
                          decoration: BoxDecoration(
                            color: AdelssColors.cream,
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: const Icon(Icons.person_outline, color: AdelssColors.ink),
                        ),
                        const SizedBox(width: 14),
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Meu perfil', style: TextStyle(fontWeight: FontWeight.w700)),
                              Text('Dados, dízimos, ensino e discipulado'),
                            ],
                          ),
                        ),
                        const Icon(Icons.chevron_right, color: AdelssColors.muted),
                      ],
                    ),
                  ),
                  if (home.pendingSchedules > 0) ...[
                    const SizedBox(height: 20),
                    SurfaceCard(
                      color: AdelssColors.ink,
                      outlined: false,
                      onTap: () => context.go('/escalas'),
                      child: Row(
                        children: [
                          Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: AdelssColors.gold.withValues(alpha: 0.2),
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: const Icon(Icons.notifications_active_outlined, color: AdelssColors.gold),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  home.pendingSchedules == 1
                                      ? '1 escala para confirmar'
                                      : '${home.pendingSchedules} escalas para confirmar',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.w700,
                                    fontSize: 16,
                                  ),
                                ),
                                const Text(
                                  'Toque para responder agora',
                                  style: TextStyle(color: Colors.white70),
                                ),
                              ],
                            ),
                          ),
                          const Icon(Icons.chevron_right, color: Colors.white70),
                        ],
                      ),
                    ),
                  ],
                  SectionHeader(
                    'Próximas escalas',
                    action: TextButton(
                      onPressed: () => context.go('/escalas'),
                      child: const Text('Ver todas'),
                    ),
                  ),
                  if (home.nextSchedules.isEmpty)
                    const SurfaceCard(
                      child: Text('Nenhuma escala nos próximos dias.'),
                    )
                  else
                    ...home.nextSchedules.take(3).map(
                          (item) => Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: ScheduleCard(item: item),
                          ),
                        ),
                  if (home.pgi != null) ...[
                    const SectionHeader('Meu PGI'),
                    SurfaceCard(
                      onTap: () => context.go('/pgi'),
                      child: Row(
                        children: [
                          Container(
                            width: 48,
                            height: 48,
                            decoration: BoxDecoration(
                              color: AdelssColors.cream,
                              borderRadius: BorderRadius.circular(16),
                            ),
                            child: const Icon(Icons.groups_rounded, color: AdelssColors.ink),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(home.pgi!.name, style: Theme.of(context).textTheme.titleMedium),
                                Text(
                                  home.pgi!.isLeader ? 'Você lidera este grupo' : 'Participante',
                                ),
                              ],
                            ),
                          ),
                          const Icon(Icons.chevron_right, color: AdelssColors.muted),
                        ],
                      ),
                    ),
                  ],
                  SectionHeader(
                    'Agenda',
                    action: TextButton(
                      onPressed: () => context.go('/agenda'),
                      child: const Text('Ver todas'),
                    ),
                  ),
                  if (home.nextEvents.isEmpty)
                    const SurfaceCard(
                      child: Text('Nenhum evento próximo.'),
                    )
                  else
                    ...home.nextEvents.take(3).map(
                          (event) => Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: EventCard(
                              event: event,
                              onTap: () => context.push('/evento/${event.id}'),
                            ),
                          ),
                        ),
                  if (home.canViewFinancial) ...[
                    const SectionHeader('Financeiro'),
                    SurfaceCard(
                      onTap: () => context.push('/financeiro'),
                      child: Row(
                        children: [
                          Container(
                            width: 48,
                            height: 48,
                            decoration: BoxDecoration(
                              color: AdelssColors.cream,
                              borderRadius: BorderRadius.circular(16),
                            ),
                            child: const Icon(
                              Icons.account_balance_wallet_outlined,
                              color: AdelssColors.ink,
                            ),
                          ),
                          const SizedBox(width: 14),
                          const Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Gestão financeira', style: TextStyle(fontWeight: FontWeight.w700)),
                                Text('Resumo, lançamentos, contas e campanhas'),
                              ],
                            ),
                          ),
                          const Icon(Icons.chevron_right, color: AdelssColors.muted),
                        ],
                      ),
                    ),
                  ],
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}
