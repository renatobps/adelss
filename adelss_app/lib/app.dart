import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'screens/agenda_screen.dart';
import 'screens/change_password_screen.dart';
import 'screens/event_detail_screen.dart';
import 'screens/financial_catalog_screens.dart';
import 'screens/financial_ops_screens.dart';
import 'screens/financial_screen.dart';
import 'screens/financial_transactions_screen.dart';
import 'screens/forgot_password_screen.dart';
import 'screens/home_screen.dart';
import 'screens/login_screen.dart';
import 'screens/member_contributions_screen.dart';
import 'screens/member_data_screen.dart';
import 'screens/member_discipleship_screen.dart';
import 'screens/member_edit_screen.dart';
import 'screens/member_ensino_screen.dart';
import 'screens/members_screen.dart';
import 'screens/pgi_attendance_screen.dart';
import 'screens/pgi_meeting_screen.dart';
import 'screens/pgi_new_meeting_screen.dart';
import 'screens/pgi_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/schedules_screen.dart';
import 'screens/shell_screen.dart';
import 'session/session_controller.dart';
import 'theme.dart';

class AdelssApp extends StatefulWidget {
  const AdelssApp({super.key, required this.session});

  final SessionController session;

  @override
  State<AdelssApp> createState() => _AdelssAppState();
}

class _AdelssAppState extends State<AdelssApp> {
  late GoRouter _router;

  @override
  void initState() {
    super.initState();
    _router = _createRouter(widget.session);
  }

  @override
  void reassemble() {
    super.reassemble();
    _router = _createRouter(widget.session);
  }

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider.value(
      value: widget.session,
      child: MaterialApp.router(
        title: 'ADELSS',
        theme: AdelssTheme.light(),
        locale: const Locale('pt', 'BR'),
        supportedLocales: const [Locale('pt', 'BR')],
        localizationsDelegates: const [
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        routerConfig: _router,
      ),
    );
  }
}

GoRouter _createRouter(SessionController session) {
  return GoRouter(
    initialLocation: '/home',
    refreshListenable: session,
    redirect: (context, state) {
      final location = state.matchedLocation;
      final loggingIn = location == '/login' || location == '/esqueci-senha';
      final changingPassword = location == '/trocar-senha';

      if (!session.isLoggedIn) {
        return loggingIn ? null : '/login';
      }
      if (session.mustChangePassword && !changingPassword) {
        return '/trocar-senha';
      }
      if (!session.mustChangePassword && loggingIn) {
        return '/home';
      }
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      GoRoute(
        path: '/esqueci-senha',
        builder: (context, state) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: '/trocar-senha',
        builder: (context, state) => const ChangePasswordScreen(),
      ),
      GoRoute(
        path: '/evento/:id',
        builder: (context, state) => EventDetailScreen(
          eventId: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
        ),
      ),
      GoRoute(
        path: '/financeiro',
        builder: (context, state) => FinancialHubScreen(
          key: ValueKey(state.uri.queryParameters['s'] ?? 'resumo'),
          section: state.uri.queryParameters['s'] ?? 'resumo',
        ),
        routes: [
          GoRoute(
            path: 'transacoes/nova',
            builder: (context, state) => FinancialTransactionFormScreen(
              type: state.uri.queryParameters['type'] ?? 'receita',
            ),
          ),
          GoRoute(
            path: 'transacoes/:id',
            builder: (context, state) => FinancialTransactionDetailScreen(
              id: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
            ),
            routes: [
              GoRoute(
                path: 'editar',
                builder: (context, state) => FinancialTransactionFormScreen(
                  id: int.tryParse(state.pathParameters['id'] ?? ''),
                ),
              ),
            ],
          ),
          GoRoute(
            path: 'campanhas/:id',
            builder: (context, state) => FinancialCampaignDetailScreen(
              id: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
            ),
          ),
        ],
      ),
      GoRoute(
        path: '/conta/dados',
        builder: (context, state) => const MemberDataScreen(),
      ),
      GoRoute(
        path: '/conta/editar',
        builder: (context, state) => const MemberEditScreen(),
      ),
      GoRoute(
        path: '/membros/novo',
        builder: (context, state) => const MemberEditScreen(isNew: true),
      ),
      GoRoute(
        path: '/membros/:id/editar',
        builder: (context, state) => MemberEditScreen(
          memberId: int.tryParse(state.pathParameters['id'] ?? ''),
        ),
      ),
      GoRoute(
        path: '/membros/:id',
        builder: (context, state) => MemberDataScreen(
          memberId: int.tryParse(state.pathParameters['id'] ?? ''),
        ),
      ),
      GoRoute(
        path: '/conta/dizimos',
        builder: (context, state) => const MemberContributionsScreen(),
      ),
      GoRoute(
        path: '/conta/ensino',
        builder: (context, state) => const MemberEnsinoScreen(),
      ),
      GoRoute(
        path: '/conta/discipulado',
        builder: (context, state) => const MemberDiscipleshipScreen(),
      ),
      GoRoute(
        path: '/pgi/nova',
        builder: (context, state) => const PgiNewMeetingScreen(),
      ),
      GoRoute(
        path: '/pgi/reuniao/:id',
        builder: (context, state) => PgiMeetingScreen(
          meetingId: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
        ),
      ),
      GoRoute(
        path: '/pgi/reuniao/:id/chamada',
        builder: (context, state) => PgiAttendanceScreen(
          meetingId: int.tryParse(state.pathParameters['id'] ?? '') ?? 0,
        ),
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) {
          return ShellScreen(navigationShell: navigationShell);
        },
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(path: '/home', builder: (context, state) => const HomeScreen()),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/membros',
                builder: (context, state) => const MembersScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/escalas',
                builder: (context, state) => const SchedulesScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/agenda',
                builder: (context, state) => const AgendaScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(path: '/pgi', builder: (context, state) => const PgiScreen()),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(path: '/conta', builder: (context, state) => const ProfileScreen()),
            ],
          ),
        ],
      ),
    ],
    errorBuilder: (context, state) {
      final parts = state.uri.path.split('/').where((part) => part.isNotEmpty).toList();
      if (parts.isNotEmpty && parts.first == 'financeiro') {
        final section = parts.length > 1 ? parts[1] : 'resumo';
        return FinancialHubScreen(section: section);
      }
      return Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text('Página não encontrada', style: Theme.of(context).textTheme.headlineSmall),
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: () => context.go('/home'),
                  child: const Text('Voltar ao início'),
                ),
              ],
            ),
          ),
        ),
      );
    },
  );
}

class FinancialHubScreen extends StatelessWidget {
  const FinancialHubScreen({super.key, required this.section});

  final String section;

  @override
  Widget build(BuildContext context) {
    switch (section) {
      case 'transacoes':
        return const FinancialTransactionsScreen();
      case 'recibos':
        return const FinancialReceiptsScreen();
      case 'correcao':
        return const FinancialCorrectionScreen();
      case 'despesas-fixas':
        return const FinancialFixedExpensesScreen();
      case 'contas':
        return const FinancialAccountsScreen();
      case 'categorias':
        return const FinancialCategoriesScreen();
      case 'centros-custo':
        return const FinancialCostCentersScreen();
      case 'fornecedores':
        return const FinancialContactsScreen();
      case 'campanhas':
        return const FinancialCampaignsScreen();
      case 'relatorios':
        return const FinancialReportsScreen();
      case 'automacoes':
        return const FinancialAutomationsScreen();
      default:
        return const FinancialScreen();
    }
  }
}
