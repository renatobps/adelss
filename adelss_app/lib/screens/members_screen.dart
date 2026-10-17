import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../api/models.dart';
import '../session/session_controller.dart';
import '../theme.dart';
import '../widgets/ui.dart';

class MembersScreen extends StatefulWidget {
  const MembersScreen({super.key});

  @override
  State<MembersScreen> createState() => _MembersScreenState();
}

class _MembersScreenState extends State<MembersScreen> {
  final _search = TextEditingController();
  Timer? _debounce;
  String? _status;
  late Future<MemberDirectory> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    super.dispose();
  }

  Future<MemberDirectory> _load({int page = 1}) {
    return context.read<SessionController>().api.members(
          search: _search.text.trim(),
          status: _status,
          page: page,
        );
  }

  void _reload() {
    setState(() {
      _future = _load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final session = context.watch<SessionController>();

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
              child: PageHeader(
                title: 'Membros',
                subtitle: 'Cadastro da igreja.',
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
              child: TextField(
                controller: _search,
                decoration: const InputDecoration(
                  hintText: 'Buscar nome, e-mail, telefone ou CPF',
                  prefixIcon: Icon(Icons.search),
                ),
                onChanged: (_) {
                  _debounce?.cancel();
                  _debounce = Timer(const Duration(milliseconds: 350), _reload);
                },
              ),
            ),
            const SizedBox(height: 8),
            SizedBox(
              height: 40,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 20),
                children: [
                  _Chip(
                    label: 'Todos',
                    selected: _status == null,
                    onTap: () {
                      setState(() => _status = null);
                      _reload();
                    },
                  ),
                  _Chip(
                    label: 'Ativos',
                    selected: _status == 'ativo',
                    onTap: () {
                      setState(() => _status = 'ativo');
                      _reload();
                    },
                  ),
                  _Chip(
                    label: 'Visitantes',
                    selected: _status == 'visitante',
                    onTap: () {
                      setState(() => _status = 'visitante');
                      _reload();
                    },
                  ),
                  _Chip(
                    label: 'Pendentes',
                    selected: _status == 'pendente',
                    onTap: () {
                      setState(() => _status = 'pendente');
                      _reload();
                    },
                  ),
                ],
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                color: AdelssColors.ink,
                onRefresh: () async {
                  _reload();
                  await _future;
                },
                child: FutureBuilder<MemberDirectory>(
                  future: _future,
                  builder: (context, snapshot) {
                    if (snapshot.hasError) {
                      final error = snapshot.error;
                      if (error is ApiException) {
                        session.handleApiError(error);
                      }
                      return ListView(
                        children: [
                          SizedBox(
                            height: 320,
                            child: ErrorRetry(
                              message: '$error',
                              onRetry: _reload,
                            ),
                          ),
                        ],
                      );
                    }
                    if (!snapshot.hasData) {
                      return const Center(child: CircularProgressIndicator());
                    }
                    final data = snapshot.data!;
                    if (data.items.isEmpty) {
                      return ListView(
                        children: const [
                          SizedBox(height: 80),
                          EmptyState(
                            icon: Icons.people_outline,
                            message: 'Nenhum membro encontrado.',
                          ),
                        ],
                      );
                    }
                    return ListView(
                      padding: const EdgeInsets.fromLTRB(20, 12, 20, 88),
                      children: [
                        Row(
                          children: [
                            _Kpi(label: 'Ativos', value: '${data.ativos}'),
                            const SizedBox(width: 8),
                            _Kpi(label: 'Visitantes', value: '${data.visitantes}'),
                            const SizedBox(width: 8),
                            _Kpi(label: 'Total', value: '${data.total}'),
                          ],
                        ),
                        const SizedBox(height: 14),
                        ...data.items.map(
                          (item) => Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: SurfaceCard(
                              onTap: () => context.push('/membros/${item.id}'),
                              child: Row(
                                children: [
                                  MemberAvatar(
                                    name: item.name,
                                    photoUrl: item.photoUrl,
                                    radius: 24,
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(item.name, style: Theme.of(context).textTheme.titleMedium),
                                        Text(
                                          [
                                            item.role,
                                            item.pgiName,
                                            item.phone,
                                          ].whereType<String>().where((part) => part.isNotEmpty).join(' · '),
                                        ),
                                      ],
                                    ),
                                  ),
                                  if (item.statusLabel != null)
                                    StatusPill(
                                      label: item.statusLabel!,
                                      tone: item.status == 'ativo'
                                          ? StatusTone.success
                                          : StatusTone.neutral,
                                    ),
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
          ],
        ),
      ),
      floatingActionButton: session.canCreateMembers
          ? FloatingActionButton.extended(
              onPressed: () async {
                final created = await context.push<bool>('/membros/novo');
                if (created == true && mounted) {
                  _reload();
                }
              },
              icon: const Icon(Icons.person_add_alt_1),
              label: const Text('Novo'),
            )
          : null,
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({required this.label, required this.selected, required this.onTap});

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: FilterChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => onTap(),
      ),
    );
  }
}

class _Kpi extends StatelessWidget {
  const _Kpi({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: SurfaceCard(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label),
            Text(value, style: Theme.of(context).textTheme.titleLarge),
          ],
        ),
      ),
    );
  }
}
