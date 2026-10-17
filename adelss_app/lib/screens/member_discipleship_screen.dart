import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/models.dart';
import '../session/session_controller.dart';
import '../theme.dart';
import '../utils/format.dart';
import '../widgets/ui.dart';

class MemberDiscipleshipScreen extends StatefulWidget {
  const MemberDiscipleshipScreen({super.key});

  @override
  State<MemberDiscipleshipScreen> createState() => _MemberDiscipleshipScreenState();
}

class _MemberDiscipleshipScreenState extends State<MemberDiscipleshipScreen> {
  late Future<DiscipleshipPayload> _future;

  @override
  void initState() {
    super.initState();
    _future = context.read<SessionController>().api.discipleship();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Discipulado')),
      body: FutureBuilder<DiscipleshipPayload>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorRetry(
              message: '${snapshot.error}',
              onRetry: () {
                setState(() {
                  _future = context.read<SessionController>().api.discipleship();
                });
              },
            );
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final data = snapshot.data!;
          if (data.isEmpty) {
            return const EmptyState(
              icon: Icons.favorite_outline,
              message: 'Nenhum acompanhamento de discipulado encontrado.',
            );
          }
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
            children: [
              if (data.beingDiscipled.isNotEmpty) ...[
                const SectionHeader('Meu discipulador'),
                ...data.beingDiscipled.map((item) => _Card(item: item, asDisciple: true)),
              ],
              if (data.discipling.isNotEmpty) ...[
                const SectionHeader('Quem eu discipulo'),
                ...data.discipling.map((item) => _Card(item: item, asDisciple: false)),
              ],
            ],
          );
        },
      ),
    );
  }
}

class _Card extends StatelessWidget {
  const _Card({required this.item, required this.asDisciple});

  final DiscipleshipItem item;
  final bool asDisciple;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: SurfaceCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    asDisciple ? (item.mentor ?? 'Sem discipulador') : (item.disciple ?? 'Membro'),
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                ),
                if (item.statusLabel.isNotEmpty)
                  StatusPill(
                    label: item.statusLabel,
                    tone: item.status == 'ativo' ? StatusTone.success : StatusTone.neutral,
                  ),
              ],
            ),
            if (item.cycle != null) ...[
              const SizedBox(height: 6),
              Text(item.cycle!, style: const TextStyle(color: AdelssColors.muted)),
            ],
            if (item.startedAt != null)
              Text('Desde ${formatIsoDate(item.startedAt)}'),
          ],
        ),
      ),
    );
  }
}
