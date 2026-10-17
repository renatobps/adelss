import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../api/models.dart';
import '../session/session_controller.dart';
import '../theme.dart';
import '../widgets/ui.dart';

class PgiAttendanceScreen extends StatefulWidget {
  const PgiAttendanceScreen({super.key, required this.meetingId});

  final int meetingId;

  @override
  State<PgiAttendanceScreen> createState() => _PgiAttendanceScreenState();
}

class _PgiAttendanceScreenState extends State<PgiAttendanceScreen> {
  late Future<AttendanceForm> _future;
  Set<int> _present = {};
  final _visitors = <AttendancePerson>[];
  final _notes = TextEditingController();
  final _visitorName = TextEditingController();
  final _visitorPhone = TextEditingController();
  bool _loaded = false;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = context.read<SessionController>().api.attendanceForm(widget.meetingId);
  }

  @override
  void dispose() {
    _notes.dispose();
    _visitorName.dispose();
    _visitorPhone.dispose();
    super.dispose();
  }

  void _hydrate(AttendanceForm form) {
    if (_loaded) {
      return;
    }
    _loaded = true;
    _present = {
      for (final member in form.members)
        if (member.present && member.memberId != null) member.memberId!,
    };
    _visitors
      ..clear()
      ..addAll(form.visitors);
    _notes.text = form.notes ?? '';
  }

  Future<void> _save() async {
    setState(() => _busy = true);
    try {
      await context.read<SessionController>().api.saveAttendance(
            meetingId: widget.meetingId,
            participants: _present.toList(),
            visitors: _visitors
                .map((item) => {
                      'name': item.name,
                      'phone': ?item.phone,
                    })
                .toList(),
            notes: _notes.text.trim().isEmpty ? null : _notes.text.trim(),
          );
      if (mounted) {
        showSnack(context, 'Chamada salva.');
        context.pop(true);
      }
    } on ApiException catch (error) {
      if (mounted) {
        showSnack(context, error.message, error: true);
      }
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  void _addVisitor() {
    final name = _visitorName.text.trim();
    if (name.isEmpty) {
      return;
    }
    setState(() {
      _visitors.add(
        AttendancePerson(
          type: 'visitor',
          name: name,
          phone: _visitorPhone.text.trim().isEmpty ? null : _visitorPhone.text.trim(),
        ),
      );
      _visitorName.clear();
      _visitorPhone.clear();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Chamada')),
      body: FutureBuilder<AttendanceForm>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorRetry(
              message: '${snapshot.error}',
              onRetry: () {
                setState(() {
                  _loaded = false;
                  _future = context.read<SessionController>().api.attendanceForm(widget.meetingId);
                });
              },
            );
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final form = snapshot.data!;
          _hydrate(form);
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
            children: [
              const SectionHeader('Membros'),
              ...form.members.map((member) {
                final id = member.memberId;
                final checked = id != null && _present.contains(id);
                return Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: SurfaceCard(
                    onTap: id == null
                        ? null
                        : () {
                            setState(() {
                              if (checked) {
                                _present.remove(id);
                              } else {
                                _present.add(id);
                              }
                            });
                          },
                    child: Row(
                      children: [
                        Icon(
                          checked ? Icons.check_circle : Icons.circle_outlined,
                          color: checked ? AdelssColors.success : AdelssColors.muted,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(member.name, style: Theme.of(context).textTheme.titleMedium),
                        ),
                      ],
                    ),
                  ),
                );
              }),
              const SectionHeader('Visitantes'),
              ..._visitors.asMap().entries.map(
                    (entry) => Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: SurfaceCard(
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(entry.value.name, style: Theme.of(context).textTheme.titleMedium),
                                  if (entry.value.phone != null) Text(entry.value.phone!),
                                ],
                              ),
                            ),
                            IconButton(
                              icon: const Icon(Icons.delete_outline),
                              onPressed: () => setState(() => _visitors.removeAt(entry.key)),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
              SurfaceCard(
                child: Column(
                  children: [
                    TextField(
                      controller: _visitorName,
                      decoration: const InputDecoration(labelText: 'Nome do visitante'),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _visitorPhone,
                      keyboardType: TextInputType.phone,
                      decoration: const InputDecoration(labelText: 'Telefone (opcional)'),
                    ),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: TextButton.icon(
                        onPressed: _addVisitor,
                        icon: const Icon(Icons.person_add_alt),
                        label: const Text('Adicionar visitante'),
                      ),
                    ),
                  ],
                ),
              ),
              const SectionHeader('Observações'),
              TextField(
                controller: _notes,
                maxLines: 3,
                decoration: const InputDecoration(labelText: 'Observações'),
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: _busy ? null : _save,
                child: Text(_busy ? 'Salvando...' : 'Salvar chamada'),
              ),
            ],
          );
        },
      ),
    );
  }
}
