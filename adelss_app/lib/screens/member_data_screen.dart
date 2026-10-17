import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/models.dart';
import '../session/session_controller.dart';
import '../theme.dart';
import '../utils/format.dart';
import '../widgets/ui.dart';

class MemberDataScreen extends StatefulWidget {
  const MemberDataScreen({super.key, this.memberId});

  final int? memberId;

  @override
  State<MemberDataScreen> createState() => _MemberDataScreenState();
}

class _MemberDataScreenState extends State<MemberDataScreen> {
  late Future<MemberProfileBundle> _future;
  var _changed = false;
  var _canEdit = true;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<MemberProfileBundle> _load() {
    final api = context.read<SessionController>().api;
    if (widget.memberId == null) {
      return api.memberProfile();
    }
    return api.member(widget.memberId!);
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) {
          context.pop(_changed);
        }
      },
      child: Scaffold(
        appBar: AppBar(
          title: Text(widget.memberId == null ? 'Meus dados' : 'Membro'),
          actions: [
            IconButton(
              tooltip: 'Editar',
              onPressed: _canEdit ? _edit : null,
              icon: const Icon(Icons.edit_outlined),
            ),
          ],
        ),
        body: FutureBuilder<MemberProfileBundle>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.hasError) {
              return ErrorRetry(
                message: '${snapshot.error}',
                onRetry: () {
                  setState(() {
                    _future = _load();
                  });
                },
              );
            }
            if (!snapshot.hasData) {
              return const Center(child: CircularProgressIndicator());
            }
            final bundle = snapshot.data!;
            final member = bundle.member;
            _canEdit = bundle.canEdit;
            return ListView(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
              children: [
                Center(
                  child: Column(
                    children: [
                      MemberAvatar(name: member.name, photoUrl: member.photoUrl, radius: 44),
                      const SizedBox(height: 12),
                      Text(member.name, style: Theme.of(context).textTheme.titleLarge),
                      if (member.role != null) Text(member.role!),
                    ],
                  ),
                ),
                if (bundle.canEdit) ...[
                  const SizedBox(height: 8),
                  FilledButton.tonal(
                    onPressed: _edit,
                    child: Text(widget.memberId == null ? 'Editar meus dados' : 'Editar membro'),
                  ),
                ],
                const SectionHeader('Igreja'),
                SurfaceCard(
                  child: Column(
                    children: [
                      InfoRow(label: 'Status', value: member.statusLabel),
                      InfoRow(label: 'Cargo', value: member.role),
                      InfoRow(label: 'Departamento', value: member.department),
                      InfoRow(label: 'PGI', value: member.pgiName),
                      InfoRow(label: 'Membro desde', value: member.membershipDate == null ? null : formatIsoDate(member.membershipDate)),
                    ],
                  ),
                ),
                const SectionHeader('Pessoal'),
                SurfaceCard(
                  child: Column(
                    children: [
                      InfoRow(label: 'E-mail', value: member.email),
                      InfoRow(label: 'Telefone', value: member.phone),
                      InfoRow(label: 'Gênero', value: member.genderLabel),
                      InfoRow(label: 'Estado civil', value: member.maritalStatusLabel),
                      InfoRow(label: 'Nascimento', value: member.birthDate == null ? null : formatIsoDate(member.birthDate)),
                      InfoRow(label: 'Casamento', value: member.marriageDate == null ? null : formatIsoDate(member.marriageDate)),
                    ],
                  ),
                ),
                const SectionHeader('Documentos'),
                SurfaceCard(
                  child: Column(
                    children: [
                      InfoRow(label: 'CPF', value: member.cpf),
                      InfoRow(label: 'RG', value: member.rg),
                    ],
                  ),
                ),
                const SectionHeader('Endereço'),
                SurfaceCard(
                  child: Column(
                    children: [
                      InfoRow(label: 'Endereço', value: member.address),
                      InfoRow(label: 'Cidade', value: member.city),
                      InfoRow(label: 'UF', value: member.state),
                      InfoRow(label: 'CEP', value: member.zipCode),
                    ],
                  ),
                ),
                if (member.notes != null && member.notes!.isNotEmpty) ...[
                  const SectionHeader('Observações'),
                  SurfaceCard(child: Text(member.notes!)),
                ],
                const SizedBox(height: 12),
                if (!bundle.canEditChurchFields)
                  Text(
                    'Status, PGI, cargo e departamentos só podem ser alterados por quem gerencia o cadastro.',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: AdelssColors.muted,
                        ),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }

  Future<void> _edit() async {
    final path = widget.memberId == null
        ? '/conta/editar'
        : '/membros/${widget.memberId}/editar';
    final changed = await context.push<bool>(path);
    if (changed == true && mounted) {
      setState(() {
        _changed = true;
        _future = _load();
      });
    }
  }
}
