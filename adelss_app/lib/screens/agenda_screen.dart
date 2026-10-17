import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/models.dart';
import '../session/session_controller.dart';
import '../widgets/cards.dart';
import '../widgets/ui.dart';

class AgendaScreen extends StatefulWidget {
  const AgendaScreen({super.key});

  @override
  State<AgendaScreen> createState() => _AgendaScreenState();
}

class _AgendaScreenState extends State<AgendaScreen> {
  late Future<List<EventItem>> _future;

  @override
  void initState() {
    super.initState();
    _future = context.read<SessionController>().api.events();
  }

  Future<void> _reload() async {
    setState(() {
      _future = context.read<SessionController>().api.events();
    });
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 20),
              child: PageHeader(
                title: 'Agenda',
                subtitle: 'Cultos e eventos dos próximos 45 dias.',
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: _reload,
                child: FutureBuilder<List<EventItem>>(
                  future: _future,
                  builder: (context, snapshot) {
                    if (snapshot.hasError) {
                      return ListView(
                        children: [
                          SizedBox(
                            height: 360,
                            child: ErrorRetry(
                              message: '${snapshot.error}',
                              onRetry: _reload,
                            ),
                          ),
                        ],
                      );
                    }
                    if (!snapshot.hasData) {
                      return const Center(child: CircularProgressIndicator());
                    }
                    final events = snapshot.data!;
                    if (events.isEmpty) {
                      return ListView(
                        children: const [
                          SizedBox(height: 80),
                          EmptyState(
                            icon: Icons.calendar_today_outlined,
                            message: 'Nenhum evento nos próximos 45 dias.',
                          ),
                        ],
                      );
                    }
                    return ListView.separated(
                      padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
                      itemCount: events.length,
                      separatorBuilder: (_, _) => const SizedBox(height: 12),
                      itemBuilder: (context, index) {
                        final event = events[index];
                        return EventCard(
                          event: event,
                          onTap: () => context.push('/evento/${event.id}'),
                        );
                      },
                    );
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
