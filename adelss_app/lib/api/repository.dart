import 'dart:typed_data';

import '../config.dart';
import 'api_client.dart';
import 'models.dart';

class AdelssRepository {
  AdelssRepository(this._client);

  final ApiClient _client;

  Future<AuthPayload> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    final data = await _client.post('/login', body: {
      'email': email,
      'password': password,
      'device_name': deviceName,
      'platform': AppConfig.platform,
    });
    return AuthPayload.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> logout() async {
    try {
      await _client.post('/logout');
    } catch (_) {
      // Revoga localmente mesmo se o servidor já estiver inacessível.
    }
  }

  Future<AuthPayload> me() async {
    final data = await _client.get('/me');
    return AuthPayload.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> changePassword({
    String? currentPassword,
    required String password,
  }) async {
    await _client.post('/password', body: {
      if (currentPassword != null && currentPassword.isNotEmpty)
        'current_password': currentPassword,
      'password': password,
      'password_confirmation': password,
    });
  }

  Future<String> forgotPassword(String email) async {
    await _client.post('/password/forgot', body: {'email': email});
    return 'Se o e-mail existir, enviamos o link para redefinir a senha.';
  }

  Future<HomePayload> home() async {
    final data = await _client.get('/me/home');
    return HomePayload.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<List<ScheduleItem>> schedules() async {
    final data = await _client.get('/me/schedules');
    return (data as List)
        .whereType<Map>()
        .map((item) => ScheduleItem.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<ScheduleItem> confirmSchedule(ScheduleItem item) async {
    final data = await _client.post(_confirmPath(item));
    return ScheduleItem.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<ScheduleItem> rejectSchedule(ScheduleItem item) async {
    if (item.type != 'moriah') {
      throw ArgumentError('Só a escala Moriah pode ser recusada.');
    }
    final data = await _client.post(
      '/me/schedules/moriah/${item.assignmentId}/reject',
    );
    return ScheduleItem.fromJson(Map<String, dynamic>.from(data as Map));
  }

  String _confirmPath(ScheduleItem item) {
    switch (item.type) {
      case 'moriah':
        return '/me/schedules/moriah/${item.assignmentId}/confirm';
      case 'monthly':
        return '/me/schedules/monthly/${item.assignmentId}/confirm';
      default:
        return '/me/schedules/service/${item.assignmentId}/confirm';
    }
  }

  Future<PgiSummary> pgi() async {
    final data = await _client.get('/me/pgi');
    return PgiSummary.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<List<MeetingItem>> meetings() async {
    final data = await _client.get('/me/pgi/meetings');
    return (data as List)
        .whereType<Map>()
        .map((item) => MeetingItem.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<MeetingItem> meeting(int id) async {
    final data = await _client.get('/me/pgi/meetings/$id');
    return MeetingItem.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<MeetingItem> createMeeting({
    required DateTime date,
    String? subject,
    String? notes,
  }) async {
    final data = await _client.post('/me/pgi/meetings', body: {
      'meeting_date': date.toIso8601String().split('T').first,
      if (subject != null && subject.isNotEmpty) 'subject': subject,
      if (notes != null && notes.isNotEmpty) 'notes': notes,
    });
    return MeetingItem.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<AttendanceForm> attendanceForm(int meetingId) async {
    final data = await _client.get('/me/pgi/meetings/$meetingId/attendance');
    return AttendanceForm.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<MeetingItem> saveAttendance({
    required int meetingId,
    required List<int> participants,
    required List<Map<String, String>> visitors,
    String? notes,
  }) async {
    final data = await _client.put(
      '/me/pgi/meetings/$meetingId/attendance',
      body: {
        'participants': participants,
        'visitors': visitors,
        'notes': ?notes,
      },
    );
    return MeetingItem.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<List<EventItem>> events() async {
    final data = await _client.get('/agenda/events');
    return (data as List)
        .whereType<Map>()
        .map((item) => EventItem.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<EventItem> event(int id) async {
    final data = await _client.get('/agenda/events/$id');
    return EventItem.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<FinancialModule> financialModule() async {
    final data = await _client.get('/financial/module');
    return FinancialModule.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<FinancialSummary> financialSummary({String period = '1month'}) async {
    final data = await _client.get('/financial/summary', query: {'period': period});
    return FinancialSummary.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<FinancialOptions> financialOptions() async {
    final data = await _client.get('/financial/options');
    return FinancialOptions.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<FinancialTxList> financialTransactions({
    String? type,
    String? q,
    String? paid,
    DateTime? start,
    DateTime? end,
    int page = 1,
  }) async {
    final data = await _client.get('/financial/transactions', query: {
      'page': '$page',
      'per_page': '40',
      if (type != null && type.isNotEmpty) 'type': type,
      if (q != null && q.isNotEmpty) 'q': q,
      if (paid != null) 'paid': paid,
      if (start != null) 'start_date': start.toIso8601String().split('T').first,
      if (end != null) 'end_date': end.toIso8601String().split('T').first,
    });
    return FinancialTxList.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<FinancialTx> financialTransaction(int id) async {
    final data = await _client.get('/financial/transactions/$id');
    return FinancialTx.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<FinancialTx> saveFinancialTransaction(Map<String, dynamic> body, {int? id}) async {
    final data = id == null
        ? await _client.post('/financial/transactions', body: body)
        : await _client.put('/financial/transactions/$id', body: body);
    return FinancialTx.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> deleteFinancialTransaction(int id) async {
    await _client.delete('/financial/transactions/$id');
  }

  Future<FinancialTx> payFinancialTransaction(int id, {double? amount}) async {
    final data = await _client.post('/financial/transactions/$id/pay', body: {
      if (amount != null) 'amount': amount,
    });
    return FinancialTx.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<FinancialTx> duplicateFinancialTransaction(int id) async {
    final data = await _client.post('/financial/transactions/$id/duplicate');
    return FinancialTx.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<FinancialTx> correctFinancialTransaction(int id, Map<String, dynamic> body) async {
    final data = await _client.put('/financial/transactions/$id/correction', body: body);
    return FinancialTx.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<FinancialTx> uploadFinancialAttachments({
    required int transactionId,
    required List<MultipartUpload> files,
  }) async {
    final data = await _client.postMultipart(
      '/financial/transactions/$transactionId/attachments',
      files: files
          .map(
            (file) => MultipartUpload(
              field: 'files[]',
              filename: file.filename,
              bytes: file.bytes,
              mime: file.mime,
            ),
          )
          .toList(),
    );
    return FinancialTx.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<FinancialTx> deleteFinancialAttachment({
    required int transactionId,
    required int attachmentId,
  }) async {
    final data = await _client.delete(
      '/financial/transactions/$transactionId/attachments/$attachmentId',
    );
    return FinancialTx.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<Uint8List> financialFileBytes(String apiPath) {
    return _client.getBytes(apiPath);
  }

  Map<String, String> authHeaders() => _client.authHeaders();

  Future<List<FinancialReceiptItem>> financialReceipts({
    String tab = 'arquivos',
    String period = '1month',
    DateTime? start,
    DateTime? end,
  }) async {
    final data = await _client.get('/financial/receipts', query: {
      'tab': tab,
      'period': period,
      if (start != null) 'start_date': start.toIso8601String().split('T').first,
      if (end != null) 'end_date': end.toIso8601String().split('T').first,
    });
    final map = Map<String, dynamic>.from(data as Map);
    return ((map['items'] as List?) ?? const [])
        .whereType<Map>()
        .map((item) => FinancialReceiptItem.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<FinancialAccountsPayload> financialAccounts({String status = 'ativas'}) async {
    final data = await _client.get('/financial/accounts', query: {'status': status});
    return FinancialAccountsPayload.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> saveFinancialAccount(Map<String, dynamic> body, {int? id}) async {
    if (id == null) {
      await _client.post('/financial/accounts', body: body);
    } else {
      await _client.put('/financial/accounts/$id', body: body);
    }
  }

  Future<void> toggleFinancialAccount(int id) async {
    await _client.post('/financial/accounts/$id/toggle');
  }

  Future<void> deleteFinancialAccount(int id) async {
    await _client.delete('/financial/accounts/$id');
  }

  Future<void> saveFinancialTransfer(Map<String, dynamic> body) async {
    await _client.post('/financial/transfers', body: body);
  }

  Future<void> deleteFinancialTransfer(int id) async {
    await _client.delete('/financial/transfers/$id');
  }

  Future<FinancialCatalogList> financialCategories() async {
    final data = await _client.get('/financial/categories');
    return FinancialCatalogList.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> saveFinancialCategory(Map<String, dynamic> body, {int? id}) async {
    if (id == null) {
      await _client.post('/financial/categories', body: body);
    } else {
      await _client.put('/financial/categories/$id', body: body);
    }
  }

  Future<void> deleteFinancialCategory(int id) async {
    await _client.delete('/financial/categories/$id');
  }

  Future<FinancialCatalogList> financialContacts() async {
    final data = await _client.get('/financial/contacts');
    return FinancialCatalogList.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> saveFinancialContact(Map<String, dynamic> body, {int? id}) async {
    if (id == null) {
      await _client.post('/financial/contacts', body: body);
    } else {
      await _client.put('/financial/contacts/$id', body: body);
    }
  }

  Future<void> deleteFinancialContact(int id) async {
    await _client.delete('/financial/contacts/$id');
  }

  Future<FinancialCatalogList> financialCostCenters() async {
    final data = await _client.get('/financial/cost-centers');
    return FinancialCatalogList.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> saveFinancialCostCenter(Map<String, dynamic> body, {int? id}) async {
    if (id == null) {
      await _client.post('/financial/cost-centers', body: body);
    } else {
      await _client.put('/financial/cost-centers/$id', body: body);
    }
  }

  Future<void> deleteFinancialCostCenter(int id) async {
    await _client.delete('/financial/cost-centers/$id');
  }

  Future<FinancialFixedPayload> financialFixedExpenses({String? month}) async {
    final data = await _client.get('/financial/fixed-expenses', query: {
      if (month != null && month.isNotEmpty) 'month': month,
    });
    return FinancialFixedPayload.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> saveFinancialFixedExpense(Map<String, dynamic> body, {int? id}) async {
    if (id == null) {
      await _client.post('/financial/fixed-expenses', body: body);
    } else {
      await _client.put('/financial/fixed-expenses/$id', body: body);
    }
  }

  Future<void> toggleFinancialFixedExpense(int id) async {
    await _client.post('/financial/fixed-expenses/$id/toggle');
  }

  Future<void> deleteFinancialFixedExpense(int id) async {
    await _client.delete('/financial/fixed-expenses/$id');
  }

  Future<int> generateFinancialFixedExpenses({String? month}) async {
    final data = await _client.post('/financial/fixed-expenses/generate', body: {
      if (month != null) 'month': month,
    });
    return parseInt(Map<String, dynamic>.from(data as Map)['created']) ?? 0;
  }

  Future<void> payFinancialFixedExpense(int transactionId, {double? amount}) async {
    await _client.post('/financial/fixed-expenses/transactions/$transactionId/pay', body: {
      if (amount != null) 'amount': amount,
    });
  }

  Future<List<FinancialCampaign>> financialCampaigns() async {
    final data = await _client.get('/financial/campaigns');
    final map = Map<String, dynamic>.from(data as Map);
    return ((map['items'] as List?) ?? const [])
        .whereType<Map>()
        .map((item) => FinancialCampaign.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<FinancialCampaign> saveFinancialCampaign(Map<String, dynamic> body) async {
    final data = await _client.post('/financial/campaigns', body: body);
    return FinancialCampaign.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<FinancialCampaignDetail> financialCampaign(int id) async {
    final data = await _client.get('/financial/campaigns/$id');
    return FinancialCampaignDetail.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> payFinancialInstallment(int id, {required String method}) async {
    await _client.post('/financial/installments/$id/pay', body: {
      'payment_method': method,
    });
  }

  Future<void> reverseFinancialInstallment(int id) async {
    await _client.post('/financial/installments/$id/reverse');
  }

  Future<FinancialReport> financialReports({DateTime? start, DateTime? end}) async {
    final data = await _client.get('/financial/reports', query: {
      if (start != null) 'start_date': start.toIso8601String().split('T').first,
      if (end != null) 'end_date': end.toIso8601String().split('T').first,
    });
    return FinancialReport.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<List<FinancialAutomationItem>> financialAutomations() async {
    final data = await _client.get('/financial/automations');
    final map = Map<String, dynamic>.from(data as Map);
    return ((map['items'] as List?) ?? const [])
        .whereType<Map>()
        .map((item) => FinancialAutomationItem.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<void> toggleFinancialAutomation(int id, {required bool enabled}) async {
    await _client.post('/financial/automations/$id/toggle', body: {'enabled': enabled});
  }

  Future<MemberProfileBundle> memberProfile() async {
    final data = await _client.get('/me/profile');
    return MemberProfileBundle.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<MemberProfileBundle> updateMemberProfile(Map<String, dynamic> body) async {
    final data = await _client.put('/me/profile', body: body);
    return MemberProfileBundle.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<ContributionSummary> contributions({int limit = 30}) async {
    final data = await _client.get(
      '/me/contributions',
      query: {'limit': '$limit'},
    );
    return ContributionSummary.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<List<EnsinoClass>> ensino() async {
    final data = await _client.get('/me/ensino');
    return (data as List)
        .whereType<Map>()
        .map((item) => EnsinoClass.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<DiscipleshipPayload> discipleship() async {
    final data = await _client.get('/me/discipleship');
    return DiscipleshipPayload.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<MemberDirectory> members({
    String? search,
    String? status,
    int page = 1,
  }) async {
    final data = await _client.get('/members', query: {
      'page': '$page',
      'per_page': '50',
      if (search != null && search.isNotEmpty) 'search': search,
      if (status != null && status.isNotEmpty) 'status': status,
    });
    return MemberDirectory.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<MemberFormOptions> memberOptions() async {
    final data = await _client.get('/members/options');
    return MemberFormOptions.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<MemberProfileBundle> member(int id) async {
    final data = await _client.get('/members/$id');
    return MemberProfileBundle.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<MemberProfileBundle> createMember(Map<String, dynamic> body) async {
    final data = await _client.post('/members', body: body);
    return MemberProfileBundle.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<MemberProfileBundle> updateMember(int id, Map<String, dynamic> body) async {
    final data = await _client.put('/members/$id', body: body);
    return MemberProfileBundle.fromJson(Map<String, dynamic>.from(data as Map));
  }
}
