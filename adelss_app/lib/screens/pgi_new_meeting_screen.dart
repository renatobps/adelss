import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../session/session_controller.dart';
import '../utils/format.dart';
import '../widgets/ui.dart';

class PgiNewMeetingScreen extends StatefulWidget {
  const PgiNewMeetingScreen({super.key});

  @override
  State<PgiNewMeetingScreen> createState() => _PgiNewMeetingScreenState();
}

class _PgiNewMeetingScreenState extends State<PgiNewMeetingScreen> {
  DateTime _date = DateTime.now();
  final _subject = TextEditingController();
  final _notes = TextEditingController();
  bool _busy = false;

  @override
  void dispose() {
    _subject.dispose();
    _notes.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) {
      setState(() => _date = picked);
    }
  }

  Future<void> _save() async {
    setState(() => _busy = true);
    try {
      await context.read<SessionController>().api.createMeeting(
            date: _date,
            subject: _subject.text.trim(),
            notes: _notes.text.trim(),
          );
      if (mounted) {
        showSnack(context, 'Reunião criada.');
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Nova reunião')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
        children: [
          SurfaceCard(
            onTap: _pickDate,
            child: Row(
              children: [
                DateStamp(date: _date),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Data da reunião'),
                      Text(formatDate(_date), style: Theme.of(context).textTheme.titleMedium),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _subject,
            decoration: const InputDecoration(labelText: 'Assunto (opcional)'),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _notes,
            maxLines: 3,
            decoration: const InputDecoration(labelText: 'Observações'),
          ),
          const SizedBox(height: 20),
          FilledButton(
            onPressed: _busy ? null : _save,
            child: Text(_busy ? 'Salvando...' : 'Criar reunião'),
          ),
        ],
      ),
    );
  }
}
