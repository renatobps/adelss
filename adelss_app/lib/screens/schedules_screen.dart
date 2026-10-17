import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../api/models.dart';
import '../session/session_controller.dart';
import '../widgets/cards.dart';
import '../widgets/ui.dart';

class SchedulesScreen extends StatefulWidget {
  const SchedulesScreen({super.key});

  @override
  State<SchedulesScreen> createState() => _SchedulesScreenState();
}

class _SchedulesScreenState extends State<SchedulesScreen> {
  late Future<List<ScheduleItem>> _future;
  final _busyIds = <String>{};

  @override
  void initState() {
    super.initState();
    _future = context.read<SessionController>().api.schedules();
  }

  Future<void> _reload() async {
    setState(() {
      _future = context.read<SessionController>().api.schedules();
    });
    await _future;
  }

  Future<void> _run(ScheduleItem item, Future<ScheduleItem> Function() action) async {
    setState(() => _busyIds.add(item.id));
    try {
      await action();
      if (mounted) {
        showSnack(context, 'Escala atualizada.');
        await _reload();
      }
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }
      await context.read<SessionController>().handleApiError(error);
      if (mounted) {
        showSnack(context, error.message, error: true);
      }
    } finally {
      if (mounted) {
        setState(() => _busyIds.remove(item.id));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final api = context.read<SessionController>().api;
    return Scaffold(
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 20),
              child: PageHeader(
                title: 'Escalas',
                subtitle: 'Confirme as próximas vezes em que você serve.',
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: _reload,
                child: FutureBuilder<List<ScheduleItem>>(
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
                    final items = snapshot.data!;
                    if (items.isEmpty) {
                      return ListView(
                        children: const [
                          SizedBox(height: 80),
                          EmptyState(
                            icon: Icons.event_available_outlined,
                            message: 'Você não tem escalas à frente.',
                          ),
                        ],
                      );
                    }
                    return ListView.separated(
                      padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
                      itemCount: items.length,
                      separatorBuilder: (_, _) => const SizedBox(height: 12),
                      itemBuilder: (context, index) {
                        final item = items[index];
                        return ScheduleCard(
                          item: item,
                          busy: _busyIds.contains(item.id),
                          onConfirm: () => _run(item, () => api.confirmSchedule(item)),
                          onReject: () => _run(item, () => api.rejectSchedule(item)),
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
