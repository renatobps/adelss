import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/models.dart';
import '../session/session_controller.dart';
import '../utils/format.dart';
import '../widgets/ui.dart';

class EventDetailScreen extends StatefulWidget {
  const EventDetailScreen({super.key, required this.eventId});

  final int eventId;

  @override
  State<EventDetailScreen> createState() => _EventDetailScreenState();
}

class _EventDetailScreenState extends State<EventDetailScreen> {
  late Future<EventItem> _future;

  @override
  void initState() {
    super.initState();
    _future = context.read<SessionController>().api.event(widget.eventId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Evento')),
      body: FutureBuilder<EventItem>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorRetry(
              message: '${snapshot.error}',
              onRetry: () {
                setState(() {
                  _future = context.read<SessionController>().api.event(widget.eventId);
                });
              },
            );
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final event = snapshot.data!;
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
            children: [
              SurfaceCard(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (event.categoryName != null)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: StatusPill(label: event.categoryName!),
                      ),
                    Text(event.title, style: Theme.of(context).textTheme.headlineSmall),
                    const SizedBox(height: 12),
                    Text(formatDateTime(event.startsAt, allDay: event.allDay)),
                    if (event.location != null && event.location!.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(event.location!),
                      ),
                  ],
                ),
              ),
              if (event.description != null && event.description!.trim().isNotEmpty) ...[
                const SectionHeader('Sobre'),
                SurfaceCard(
                  child: Text(
                    event.description!,
                    style: Theme.of(context).textTheme.bodyLarge,
                  ),
                ),
              ],
            ],
          );
        },
      ),
    );
  }
}
