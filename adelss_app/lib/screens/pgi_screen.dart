import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../api/models.dart';
import '../session/session_controller.dart';
import '../theme.dart';
import '../utils/format.dart';
import '../widgets/ui.dart';

class PgiScreen extends StatefulWidget {
  const PgiScreen({super.key});

  @override
  State<PgiScreen> createState() => _PgiScreenState();
}

class _PgiScreenState extends State<PgiScreen> {
  late Future<_PgiPage> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_PgiPage> _load() async {
    final api = context.read<SessionController>().api;
    final pgi = await api.pgi();
    final meetings = await api.meetings();
    if (mounted) {
      context.read<SessionController>().setPgiAccess(canManage: pgi.canManageMeetings);
    }
    return _PgiPage(pgi: pgi, meetings: meetings);
  }

  Future<void> _reload() async {
    setState(() {
      _future = _load();
    });
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      floatingActionButton: FutureBuilder<_PgiPage>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.data?.pgi.canManageMeetings != true) {
            return const SizedBox.shrink();
          }
          return FloatingActionButton.extended(
            onPressed: () async {
              final created = await context.push<bool>('/pgi/nova');
              if (created == true && mounted) {
                await _reload();
              }
            },
            icon: const Icon(Icons.add),
            label: const Text('Reunião'),
          );
        },
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _reload,
          child: FutureBuilder<_PgiPage>(
            future: _future,
            builder: (context, snapshot) {
              if (snapshot.hasError) {
                final error = snapshot.error;
                final message = error is ApiException && error.status == 404
                    ? 'Você não está vinculado a um PGI.'
                    : '$error';
                return ListView(
                  children: [
                    const PageHeader(title: 'PGI'),
                    SizedBox(
                      height: 320,
                      child: error is ApiException && error.status == 404
                          ? EmptyState(icon: Icons.groups_outlined, message: message)
                          : ErrorRetry(message: message, onRetry: _reload),
                    ),
                  ],
                );
              }
              if (!snapshot.hasData) {
                return const Center(child: CircularProgressIndicator());
              }
              final page = snapshot.data!;
              final pgi = page.pgi;
              return ListView(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 88),
                children: [
                  const PageHeader(
                    title: 'Meu PGI',
                    subtitle: 'Grupo, liderança e reuniões.',
                  ),
                  SurfaceCard(
                    color: AdelssColors.ink,
                    outlined: false,
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          pgi.name,
                          style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                                color: Colors.white,
                              ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          [
                            if (pgi.dayOfWeek != null) capitalize(pgi.dayOfWeek!),
                            if (pgi.timeSchedule != null) pgi.timeSchedule,
                          ].join(' · '),
                          style: const TextStyle(color: Colors.white70),
                        ),
                        if (pgi.address != null && pgi.address!.isNotEmpty)
                          Padding(
                            padding: const EdgeInsets.only(top: 8),
                            child: Text(pgi.address!, style: const TextStyle(color: Colors.white70)),
                          ),
                        if (pgi.leaders.isNotEmpty)
                          Padding(
                            padding: const EdgeInsets.only(top: 12),
                            child: Text(
                              'Liderança: ${pgi.leaders.join(', ')}',
                              style: const TextStyle(color: AdelssColors.gold),
                            ),
                          ),
                      ],
                    ),
                  ),
                  const SectionHeader('Reuniões'),
                  if (page.meetings.isEmpty)
                    const SurfaceCard(
                      child: Text('Nenhuma reunião registrada.'),
                    )
                  else
                    ...page.meetings.map(
                      (meeting) => Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: SurfaceCard(
                          onTap: () => context.push('/pgi/reuniao/${meeting.id}'),
                          child: Row(
                            children: [
                              DateStamp(date: meeting.meetingDate),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      meeting.subject?.isNotEmpty == true
                                          ? meeting.subject!
                                          : 'Reunião',
                                      style: Theme.of(context).textTheme.titleMedium,
                                    ),
                                    Text(
                                      '${meeting.participantsCount} presentes · ${meeting.visitorsCount} visitantes',
                                    ),
                                  ],
                                ),
                              ),
                              const Icon(Icons.chevron_right, color: AdelssColors.muted),
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
      ),
    );
  }
}

class _PgiPage {
  const _PgiPage({required this.pgi, required this.meetings});

  final PgiSummary pgi;
  final List<MeetingItem> meetings;
}
