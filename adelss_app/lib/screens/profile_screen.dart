import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../api/models.dart';
import '../session/session_controller.dart';
import '../theme.dart';
import '../utils/format.dart';
import '../widgets/ui.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late Future<MemberProfileBundle?> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<MemberProfileBundle?> _load() async {
    final session = context.read<SessionController>();
    try {
      final bundle = await session.api.memberProfile();
      session.applyMember(bundle.member);
      return bundle;
    } on ApiException catch (error) {
      if (error.code == 'member_required') {
        return null;
      }
      rethrow;
    }
  }

  @override
  Widget build(BuildContext context) {
    final session = context.watch<SessionController>();
    final user = session.profile?.user;
    final fallbackName = session.profile?.member?.name ?? user?.name ?? 'Usuário';

    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          color: AdelssColors.ink,
          onRefresh: () async {
            setState(() {
              _future = _load();
            });
            await _future;
          },
          child: FutureBuilder<MemberProfileBundle?>(
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
                      height: 360,
                      child: ErrorRetry(
                        message: '$error',
                        onRetry: () {
                          setState(() {
                            _future = _load();
                          });
                        },
                      ),
                    ),
                  ],
                );
              }
              if (!snapshot.hasData && snapshot.connectionState != ConnectionState.done) {
                return const Center(child: CircularProgressIndicator());
              }

              final bundle = snapshot.data;
              final member = bundle?.member;
              final name = member?.name ?? fallbackName;

              return ListView(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
                children: [
                  const PageHeader(
                    title: 'Meu perfil',
                    subtitle: 'Seus dados, contribuições e acompanhamento.',
                  ),
                  const SizedBox(height: 8),
                  SurfaceCard(
                    child: Row(
                      children: [
                        MemberAvatar(name: name, photoUrl: member?.photoUrl, radius: 32),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(name, style: Theme.of(context).textTheme.titleMedium),
                              Text(member?.email ?? user?.email ?? ''),
                              if (member?.role != null || member?.statusLabel != null) ...[
                                const SizedBox(height: 6),
                                Wrap(
                                  spacing: 8,
                                  runSpacing: 6,
                                  children: [
                                    if (member?.role != null)
                                      StatusPill(label: member!.role!),
                                    if (member?.statusLabel != null)
                                      StatusPill(
                                        label: member!.statusLabel!,
                                        tone: member.status == 'ativo'
                                            ? StatusTone.success
                                            : StatusTone.neutral,
                                      ),
                                  ],
                                ),
                              ],
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (bundle == null) ...[
                    const SizedBox(height: 16),
                    const SurfaceCard(
                      child: Text(
                        'Esta conta ainda não está vinculada a um cadastro de membro.',
                      ),
                    ),
                  ] else ...[
                    const SizedBox(height: 16),
                    _MenuRow(
                      icon: Icons.badge_outlined,
                      label: 'Meus dados',
                      subtitle: 'Contato, endereço e documentos',
                      onTap: () async {
                        final changed = await context.push<bool>('/conta/dados');
                        if (changed == true && mounted) {
                          setState(() {
                            _future = _load();
                          });
                        }
                      },
                    ),
                    _MenuRow(
                      icon: Icons.volunteer_activism_outlined,
                      label: 'Dízimos e ofertas',
                      subtitle: formatMoney(bundle.contributions.total),
                      onTap: () => context.push('/conta/dizimos'),
                    ),
                    _MenuRow(
                      icon: Icons.menu_book_outlined,
                      label: 'Ensino',
                      subtitle: bundle.ensinoCount == 0
                          ? 'Nenhuma turma'
                          : '${bundle.ensinoCount} turma${bundle.ensinoCount == 1 ? '' : 's'}',
                      onTap: () => context.push('/conta/ensino'),
                    ),
                    _MenuRow(
                      icon: Icons.favorite_outline,
                      label: 'Discipulado',
                      subtitle: bundle.discipleshipCount == 0
                          ? 'Sem acompanhamento'
                          : '${bundle.discipleshipCount} registro${bundle.discipleshipCount == 1 ? '' : 's'}',
                      onTap: () => context.push('/conta/discipulado'),
                    ),
                  ],
                  if (session.canViewFinancial)
                    _MenuRow(
                      icon: Icons.account_balance_wallet_outlined,
                      label: 'Financeiro',
                      subtitle: 'Gestão financeira completa',
                      onTap: () => context.push('/financeiro'),
                    ),
                  _MenuRow(
                    icon: Icons.lock_outline,
                    label: 'Trocar senha',
                    onTap: () => context.push('/trocar-senha'),
                  ),
                  const SizedBox(height: 16),
                  OutlinedButton(
                    onPressed: () => session.logout(),
                    child: const Text('Sair'),
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}

class _MenuRow extends StatelessWidget {
  const _MenuRow({
    required this.icon,
    required this.label,
    required this.onTap,
    this.subtitle,
  });

  final IconData icon;
  final String label;
  final String? subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: SurfaceCard(
        onTap: onTap,
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: AdelssColors.cream,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(icon, color: AdelssColors.ink),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label, style: Theme.of(context).textTheme.titleMedium),
                  if (subtitle != null) Text(subtitle!),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: AdelssColors.muted),
          ],
        ),
      ),
    );
  }
}
