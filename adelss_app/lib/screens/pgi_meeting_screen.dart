import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/models.dart';
import '../session/session_controller.dart';
import '../theme.dart';
import '../utils/format.dart';
import '../widgets/ui.dart';

class PgiMeetingScreen extends StatefulWidget {
  const PgiMeetingScreen({super.key, required this.meetingId});

  final int meetingId;

  @override
  State<PgiMeetingScreen> createState() => _PgiMeetingScreenState();
}

class _PgiMeetingScreenState extends State<PgiMeetingScreen> {
  late Future<MeetingItem> _future;

  @override
  void initState() {
    super.initState();
    _future = context.read<SessionController>().api.meeting(widget.meetingId);
  }

  @override
  Widget build(BuildContext context) {
    final canManage = context.watch<SessionController>().canManagePgi;
    return Scaffold(
      appBar: AppBar(title: const Text('Reunião')),
      body: FutureBuilder<MeetingItem>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorRetry(
              message: '${snapshot.error}',
              onRetry: () {
                setState(() {
                  _future = context.read<SessionController>().api.meeting(widget.meetingId);
                });
              },
            );
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final meeting = snapshot.data!;
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
            children: [
              SurfaceCard(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      meeting.subject?.isNotEmpty == true ? meeting.subject! : 'Reunião',
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    const SizedBox(height: 8),
                    Text(formatDate(meeting.meetingDate)),
                    const SizedBox(height: 8),
                    Text(
                      '${meeting.participantsCount} participantes · ${meeting.visitorsCount} visitantes',
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                  ],
                ),
              ),
              if (meeting.notes != null && meeting.notes!.isNotEmpty) ...[
                const SectionHeader('Observações'),
                SurfaceCard(child: Text(meeting.notes!)),
              ],
              if (meeting.attendances.isNotEmpty) ...[
                const SectionHeader('Presenças'),
                ...meeting.attendances.map(
                  (person) => Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: SurfaceCard(
                      child: Row(
                        children: [
                          CircleAvatar(
                            backgroundColor: AdelssColors.cream,
                            foregroundColor: AdelssColors.ink,
                            child: Text(initials(person.name)),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(person.name, style: Theme.of(context).textTheme.titleMedium),
                                Text(person.type == 'visitor' ? 'Visitante' : 'Membro'),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
              if (canManage) ...[
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: () async {
                    final saved = await context.push<bool>(
                      '/pgi/reuniao/${widget.meetingId}/chamada',
                    );
                    if (saved == true && mounted) {
                      setState(() {
                        _future = context.read<SessionController>().api.meeting(widget.meetingId);
                      });
                    }
                  },
                  child: Text(
                    meeting.attendanceRegistered ? 'Editar chamada' : 'Fazer chamada',
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
