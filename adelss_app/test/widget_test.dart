import 'package:flutter_test/flutter_test.dart';

import 'package:adelss_app/api/models.dart';

void main() {
  test('interpreta escala devolvida pela API v1', () {
    final item = ScheduleItem.fromJson({
      'id': 'moriah:12',
      'assignment_id': 12,
      'type': 'moriah',
      'title': 'Louvor',
      'area': 'Moriah',
      'location': 'Templo',
      'starts_at': '2026-09-20T19:00:00-03:00',
      'status': 'pendente',
      'can_confirm': true,
      'can_reject': true,
    });

    expect(item.assignmentId, 12);
    expect(item.typeLabel, 'Moriah');
    expect(item.canConfirm, isTrue);
    expect(item.startsAt, isNotNull);
  });

  test('interpreta home com tesouraria e PGI', () {
    final home = HomePayload.fromJson({
      'profile': {
        'user': {
          'id': 1,
          'name': 'Ana',
          'email': 'ana@igreja.com',
          'is_admin': false,
          'must_change_password': false,
        },
        'permissions': [],
        'member': {'id': 9, 'name': 'Ana Silva'},
        'modules': [],
      },
      'pending_schedules': 2,
      'next_schedules': [],
      'next_events': [],
      'pgi': {'id': 3, 'name': 'PGI Centro', 'is_leader': true, 'can_manage_meetings': true},
      'can_view_financial': true,
    });

    expect(home.pendingSchedules, 2);
    expect(home.pgi?.name, 'PGI Centro');
    expect(home.canViewFinancial, isTrue);
    expect(home.profile.user.email, 'ana@igreja.com');
  });

  test('interpreta o hub financeiro com as 12 funções', () {
    final module = FinancialModule.fromJson({
      'capabilities': {
        'can_view_summary': true,
        'can_view_transactions': true,
        'can_create_receita': true,
        'can_create_despesa': true,
        'can_view_receipts': true,
        'can_view_correction': true,
        'can_view_fixed_expenses': true,
        'can_manage_fixed_expenses': true,
        'can_view_accounts': true,
        'can_manage_accounts': true,
        'can_view_categories': true,
        'can_manage_categories': true,
        'can_view_cost_centers': true,
        'can_manage_cost_centers': true,
        'can_view_contacts': true,
        'can_manage_contacts': true,
        'can_view_campaigns': true,
        'can_manage_campaigns': true,
        'can_view_reports': true,
        'can_view_automations': true,
        'can_manage_automations': true,
      },
      'nav': [
        {'key': 'resumo', 'label': 'Resumo', 'icon': 'chart'},
        {'key': 'transacoes', 'label': 'Transações', 'icon': 'dollar'},
      ],
    });
    expect(module.capabilities.canViewSummary, isTrue);
    expect(module.nav.first.label, 'Resumo');
  });
}
