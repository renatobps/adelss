import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/models.dart';
import '../session/session_controller.dart';
import '../widgets/ui.dart';

class MemberEnsinoScreen extends StatefulWidget {
  const MemberEnsinoScreen({super.key});

  @override
  State<MemberEnsinoScreen> createState() => _MemberEnsinoScreenState();
}

class _MemberEnsinoScreenState extends State<MemberEnsinoScreen> {
  late Future<List<EnsinoClass>> _future;

  @override
  void initState() {
    super.initState();
    _future = context.read<SessionController>().api.ensino();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Ensino')),
      body: FutureBuilder<List<EnsinoClass>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorRetry(
              message: '${snapshot.error}',
              onRetry: () {
                setState(() {
                  _future = context.read<SessionController>().api.ensino();
                });
              },
            );
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final items = snapshot.data!;
          if (items.isEmpty) {
            return const EmptyState(
              icon: Icons.menu_book_outlined,
              message: 'Você ainda não está em nenhuma turma.',
            );
          }
          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
            itemCount: items.length,
            separatorBuilder: (_, _) => const SizedBox(height: 10),
            itemBuilder: (context, index) {
              final item = items[index];
              return SurfaceCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(item.name, style: Theme.of(context).textTheme.titleMedium),
                        ),
                        StatusPill(label: item.roleLabel ?? 'Aluno'),
                      ],
                    ),
                    if (item.school != null) ...[
                      const SizedBox(height: 6),
                      Text(item.school!),
                    ],
                    if (item.schedule != null) Text(item.schedule!),
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}
