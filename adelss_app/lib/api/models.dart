DateTime? parseDate(dynamic value) {
  if (value is! String || value.trim().isEmpty) {
    return null;
  }
  return DateTime.tryParse(value)?.toLocal();
}

int? parseInt(dynamic value) {
  if (value is int) {
    return value;
  }
  if (value is num) {
    return value.toInt();
  }
  if (value is String) {
    return int.tryParse(value);
  }
  return null;
}

double parseDouble(dynamic value) {
  if (value is num) {
    return value.toDouble();
  }
  if (value is String) {
    return double.tryParse(value.replaceAll(',', '.')) ?? 0;
  }
  return 0;
}

class UserProfile {
  const UserProfile({
    required this.id,
    required this.name,
    required this.email,
    required this.isAdmin,
    required this.mustChangePassword,
  });

  final int id;
  final String name;
  final String email;
  final bool isAdmin;
  final bool mustChangePassword;

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    return UserProfile(
      id: parseInt(json['id']) ?? 0,
      name: (json['name'] ?? '') as String,
      email: (json['email'] ?? '') as String,
      isAdmin: json['is_admin'] == true,
      mustChangePassword: json['must_change_password'] == true,
    );
  }

  UserProfile copyWith({bool? mustChangePassword}) {
    return UserProfile(
      id: id,
      name: name,
      email: email,
      isAdmin: isAdmin,
      mustChangePassword: mustChangePassword ?? this.mustChangePassword,
    );
  }
}

class MemberProfile {
  const MemberProfile({
    required this.id,
    required this.name,
    this.phone,
    this.status,
    this.pgiId,
    this.photoUrl,
  });

  final int id;
  final String name;
  final String? phone;
  final String? status;
  final int? pgiId;
  final String? photoUrl;

  factory MemberProfile.fromJson(Map<String, dynamic> json) {
    return MemberProfile(
      id: parseInt(json['id']) ?? 0,
      name: (json['name'] ?? '') as String,
      phone: json['phone'] as String?,
      status: json['status'] as String?,
      pgiId: parseInt(json['pgi_id']),
      photoUrl: json['photo_url'] as String?,
    );
  }
}

String? parseString(dynamic value) {
  if (value == null) {
    return null;
  }
  final text = '$value'.trim();
  return text.isEmpty ? null : text;
}

class MemberDetail {
  const MemberDetail({
    required this.id,
    required this.name,
    this.email,
    this.phone,
    this.gender,
    this.genderLabel,
    this.maritalStatus,
    this.maritalStatusLabel,
    this.birthDate,
    this.marriageDate,
    this.membershipDate,
    this.photoUrl,
    this.status,
    this.statusLabel,
    this.cpf,
    this.rg,
    this.address,
    this.city,
    this.state,
    this.zipCode,
    this.fullAddress,
    this.notes,
    this.pgiId,
    this.pgiName,
    this.departmentId,
    this.department,
    this.roleId,
    this.role,
  });

  final int id;
  final String name;
  final String? email;
  final String? phone;
  final String? gender;
  final String? genderLabel;
  final String? maritalStatus;
  final String? maritalStatusLabel;
  final DateTime? birthDate;
  final DateTime? marriageDate;
  final DateTime? membershipDate;
  final String? photoUrl;
  final String? status;
  final String? statusLabel;
  final String? cpf;
  final String? rg;
  final String? address;
  final String? city;
  final String? state;
  final String? zipCode;
  final String? fullAddress;
  final String? notes;
  final int? pgiId;
  final String? pgiName;
  final int? departmentId;
  final String? department;
  final int? roleId;
  final String? role;

  factory MemberDetail.fromJson(Map<String, dynamic> json) {
    return MemberDetail(
      id: parseInt(json['id']) ?? 0,
      name: (json['name'] ?? '') as String,
      email: parseString(json['email']),
      phone: parseString(json['phone']),
      gender: parseString(json['gender']),
      genderLabel: parseString(json['gender_label']),
      maritalStatus: parseString(json['marital_status']),
      maritalStatusLabel: parseString(json['marital_status_label']),
      birthDate: parseDate(json['birth_date']),
      marriageDate: parseDate(json['marriage_date']),
      membershipDate: parseDate(json['membership_date']),
      photoUrl: parseString(json['photo_url']),
      status: parseString(json['status']),
      statusLabel: parseString(json['status_label']),
      cpf: parseString(json['cpf']),
      rg: parseString(json['rg']),
      address: parseString(json['address']),
      city: parseString(json['city']),
      state: parseString(json['state']),
      zipCode: parseString(json['zip_code']),
      fullAddress: parseString(json['full_address']),
      notes: parseString(json['notes']),
      pgiId: parseInt(json['pgi_id']),
      pgiName: parseString(json['pgi_name']),
      departmentId: parseInt(json['department_id']),
      department: parseString(json['department']),
      roleId: parseInt(json['role_id']),
      role: parseString(json['role']),
    );
  }
}

class ContributionSummary {
  const ContributionSummary({
    required this.dizimoTotal,
    required this.ofertaTotal,
    required this.total,
    this.items = const [],
  });

  final double dizimoTotal;
  final double ofertaTotal;
  final double total;
  final List<ContributionItem> items;

  factory ContributionSummary.fromJson(Map<String, dynamic> json) {
    return ContributionSummary(
      dizimoTotal: parseDouble(json['dizimo_total']),
      ofertaTotal: parseDouble(json['oferta_total']),
      total: parseDouble(json['total']),
      items: ((json['items'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => ContributionItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
    );
  }
}

class ContributionItem {
  const ContributionItem({
    required this.id,
    required this.amount,
    required this.paid,
    this.date,
    this.description,
    this.category,
    this.account,
  });

  final int id;
  final DateTime? date;
  final String? description;
  final double amount;
  final bool paid;
  final String? category;
  final String? account;

  factory ContributionItem.fromJson(Map<String, dynamic> json) {
    return ContributionItem(
      id: parseInt(json['id']) ?? 0,
      date: parseDate(json['date']),
      description: parseString(json['description']),
      amount: parseDouble(json['amount']),
      paid: json['paid'] == true,
      category: parseString(json['category']),
      account: parseString(json['account']),
    );
  }
}

class EnsinoClass {
  const EnsinoClass({
    required this.id,
    required this.name,
    required this.role,
    this.roleLabel,
    this.school,
    this.schedule,
    this.status,
  });

  final int id;
  final String name;
  final String? school;
  final String? schedule;
  final String? status;
  final String role;
  final String? roleLabel;

  factory EnsinoClass.fromJson(Map<String, dynamic> json) {
    return EnsinoClass(
      id: parseInt(json['id']) ?? 0,
      name: (json['name'] ?? 'Turma') as String,
      school: parseString(json['school']),
      schedule: parseString(json['schedule']),
      status: parseString(json['status']),
      role: (json['role'] ?? 'aluno') as String,
      roleLabel: parseString(json['role_label']),
    );
  }
}

class DiscipleshipItem {
  const DiscipleshipItem({
    required this.id,
    required this.role,
    this.status,
    this.startedAt,
    this.endedAt,
    this.cycle,
    this.mentor,
    this.disciple,
  });

  final int id;
  final String role;
  final String? status;
  final DateTime? startedAt;
  final DateTime? endedAt;
  final String? cycle;
  final String? mentor;
  final String? disciple;

  factory DiscipleshipItem.fromJson(Map<String, dynamic> json) {
    return DiscipleshipItem(
      id: parseInt(json['id']) ?? 0,
      role: (json['role'] ?? 'discipulado') as String,
      status: parseString(json['status']),
      startedAt: parseDate(json['started_at']),
      endedAt: parseDate(json['ended_at']),
      cycle: parseString(json['cycle']),
      mentor: parseString(json['mentor']),
      disciple: parseString(json['disciple']),
    );
  }

  String get statusLabel {
    switch (status) {
      case 'ativo':
        return 'Ativo';
      case 'concluido':
        return 'Concluído';
      case 'pausado':
        return 'Pausado';
      default:
        return status ?? '';
    }
  }
}

class DiscipleshipPayload {
  const DiscipleshipPayload({
    this.beingDiscipled = const [],
    this.discipling = const [],
  });

  final List<DiscipleshipItem> beingDiscipled;
  final List<DiscipleshipItem> discipling;

  bool get isEmpty => beingDiscipled.isEmpty && discipling.isEmpty;

  factory DiscipleshipPayload.fromJson(Map<String, dynamic> json) {
    return DiscipleshipPayload(
      beingDiscipled: ((json['being_discipled'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => DiscipleshipItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      discipling: ((json['discipling'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => DiscipleshipItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
    );
  }
}

class MemberProfileBundle {
  const MemberProfileBundle({
    required this.member,
    required this.ensinoCount,
    required this.discipleshipCount,
    required this.contributions,
    this.canEdit = true,
    this.canEditChurchFields = false,
  });

  final MemberDetail member;
  final int ensinoCount;
  final int discipleshipCount;
  final ContributionSummary contributions;
  final bool canEdit;
  final bool canEditChurchFields;

  factory MemberProfileBundle.fromJson(Map<String, dynamic> json) {
    return MemberProfileBundle(
      member: MemberDetail.fromJson(
        Map<String, dynamic>.from(json['member'] as Map? ?? const {}),
      ),
      ensinoCount: parseInt(json['ensino_count']) ?? 0,
      discipleshipCount: parseInt(json['discipleship_count']) ?? 0,
      contributions: ContributionSummary.fromJson(
        Map<String, dynamic>.from(json['contributions'] as Map? ?? const {}),
      ),
      canEdit: json['can_edit'] != false,
      canEditChurchFields: json['can_edit_church_fields'] == true,
    );
  }
}

class MemberListItem {
  const MemberListItem({
    required this.id,
    required this.name,
    this.email,
    this.phone,
    this.status,
    this.statusLabel,
    this.photoUrl,
    this.pgiName,
    this.role,
    this.city,
  });

  final int id;
  final String name;
  final String? email;
  final String? phone;
  final String? status;
  final String? statusLabel;
  final String? photoUrl;
  final String? pgiName;
  final String? role;
  final String? city;

  factory MemberListItem.fromJson(Map<String, dynamic> json) {
    return MemberListItem(
      id: parseInt(json['id']) ?? 0,
      name: (json['name'] ?? '') as String,
      email: parseString(json['email']),
      phone: parseString(json['phone']),
      status: parseString(json['status']),
      statusLabel: parseString(json['status_label']),
      photoUrl: parseString(json['photo_url']),
      pgiName: parseString(json['pgi_name']),
      role: parseString(json['role']),
      city: parseString(json['city']),
    );
  }
}

class MemberDirectory {
  const MemberDirectory({
    required this.items,
    required this.page,
    required this.lastPage,
    required this.total,
    required this.canCreate,
    required this.canEdit,
    this.ativos = 0,
    this.visitantes = 0,
    this.pendentes = 0,
  });

  final List<MemberListItem> items;
  final int page;
  final int lastPage;
  final int total;
  final bool canCreate;
  final bool canEdit;
  final int ativos;
  final int visitantes;
  final int pendentes;

  factory MemberDirectory.fromJson(Map<String, dynamic> json) {
    final kpis = json['kpis'];
    return MemberDirectory(
      items: ((json['items'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => MemberListItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      page: parseInt(json['page']) ?? 1,
      lastPage: parseInt(json['last_page']) ?? 1,
      total: parseInt(json['total']) ?? 0,
      canCreate: json['can_create'] == true,
      canEdit: json['can_edit'] == true,
      ativos: kpis is Map ? parseInt(kpis['ativos']) ?? 0 : 0,
      visitantes: kpis is Map ? parseInt(kpis['visitantes']) ?? 0 : 0,
      pendentes: kpis is Map ? parseInt(kpis['pendentes']) ?? 0 : 0,
    );
  }
}

class IdNameOption {
  const IdNameOption({required this.id, required this.name});

  final int id;
  final String name;

  factory IdNameOption.fromJson(Map<String, dynamic> json) {
    return IdNameOption(
      id: parseInt(json['id']) ?? 0,
      name: (json['name'] ?? json['label'] ?? '') as String,
    );
  }
}

class MemberFormOptions {
  const MemberFormOptions({
    this.statuses = const [],
    this.pgis = const [],
    this.roles = const [],
    this.departments = const [],
  });

  final List<StatusOption> statuses;
  final List<IdNameOption> pgis;
  final List<IdNameOption> roles;
  final List<IdNameOption> departments;

  factory MemberFormOptions.fromJson(Map<String, dynamic> json) {
    return MemberFormOptions(
      statuses: ((json['statuses'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => StatusOption.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      pgis: ((json['pgis'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => IdNameOption.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      roles: ((json['roles'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => IdNameOption.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      departments: ((json['departments'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => IdNameOption.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
    );
  }
}

class StatusOption {
  const StatusOption({required this.value, required this.label});

  final String value;
  final String label;

  factory StatusOption.fromJson(Map<String, dynamic> json) {
    return StatusOption(
      value: '${json['value'] ?? ''}',
      label: '${json['label'] ?? json['value'] ?? ''}',
    );
  }
}

class ModuleItem {
  const ModuleItem({required this.key, required this.label});

  final String key;
  final String label;

  factory ModuleItem.fromJson(Map<String, dynamic> json) {
    return ModuleItem(
      key: (json['key'] ?? '') as String,
      label: (json['label'] ?? '') as String,
    );
  }
}

class AuthPayload {
  const AuthPayload({
    required this.user,
    required this.permissions,
    required this.modules,
    this.member,
    this.token,
  });

  final UserProfile user;
  final List<String> permissions;
  final MemberProfile? member;
  final List<ModuleItem> modules;
  final String? token;

  bool get mustChangePassword => user.mustChangePassword;

  bool hasPermission(String key) {
    if (user.isAdmin || permissions.contains('*')) {
      return true;
    }
    return permissions.contains(key);
  }

  bool get canViewMembers =>
      hasPermission('members.index.view') ||
      hasPermission('members.view') ||
      hasPermission('members.index.manage') ||
      modules.any((item) => item.key == 'members');

  bool get canCreateMembers =>
      hasPermission('members.index.create') ||
      hasPermission('members.create') ||
      hasPermission('members.index.manage');

  bool get canEditMembers =>
      hasPermission('members.index.edit') ||
      hasPermission('members.edit') ||
      hasPermission('members.index.manage');

  factory AuthPayload.fromJson(Map<String, dynamic> json) {
    final memberJson = json['member'];
    return AuthPayload(
      user: UserProfile.fromJson(
        Map<String, dynamic>.from(json['user'] as Map? ?? const {}),
      ),
      permissions: ((json['permissions'] as List?) ?? const [])
          .map((item) => '$item')
          .toList(),
      member: memberJson is Map
          ? MemberProfile.fromJson(Map<String, dynamic>.from(memberJson))
          : null,
      modules: ((json['modules'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => ModuleItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      token: json['token'] as String?,
    );
  }

  AuthPayload copyWith({
    UserProfile? user,
    String? token,
    MemberProfile? member,
  }) {
    return AuthPayload(
      user: user ?? this.user,
      permissions: permissions,
      member: member ?? this.member,
      modules: modules,
      token: token ?? this.token,
    );
  }
}

class ScheduleItem {
  const ScheduleItem({
    required this.id,
    required this.assignmentId,
    required this.type,
    required this.title,
    required this.status,
    required this.canConfirm,
    required this.canReject,
    this.area,
    this.location,
    this.startsAt,
  });

  final String id;
  final int assignmentId;
  final String type;
  final String title;
  final String? area;
  final String? location;
  final DateTime? startsAt;
  final String status;
  final bool canConfirm;
  final bool canReject;

  factory ScheduleItem.fromJson(Map<String, dynamic> json) {
    return ScheduleItem(
      id: '${json['id'] ?? ''}',
      assignmentId: parseInt(json['assignment_id']) ?? 0,
      type: (json['type'] ?? '') as String,
      title: (json['title'] ?? 'Escala') as String,
      area: json['area'] as String?,
      location: json['location'] as String?,
      startsAt: parseDate(json['starts_at']),
      status: (json['status'] ?? 'pendente') as String,
      canConfirm: json['can_confirm'] == true,
      canReject: json['can_reject'] == true,
    );
  }

  String get typeLabel {
    switch (type) {
      case 'moriah':
        return 'Moriah';
      case 'monthly':
        return 'Culto';
      case 'service':
        return 'Serviço';
      default:
        return type;
    }
  }

  String get statusLabel {
    switch (status) {
      case 'confirmado':
        return 'Confirmada';
      case 'recusado':
        return 'Recusada';
      default:
        return 'Pendente';
    }
  }
}

class EventItem {
  const EventItem({
    required this.id,
    required this.title,
    required this.allDay,
    required this.status,
    this.description,
    this.startsAt,
    this.endsAt,
    this.location,
    this.categoryName,
    this.categoryColor,
  });

  final int id;
  final String title;
  final String? description;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final bool allDay;
  final String? location;
  final String status;
  final String? categoryName;
  final String? categoryColor;

  factory EventItem.fromJson(Map<String, dynamic> json) {
    final category = json['category'];
    return EventItem(
      id: parseInt(json['id']) ?? 0,
      title: (json['title'] ?? 'Evento') as String,
      description: json['description'] as String?,
      startsAt: parseDate(json['starts_at']),
      endsAt: parseDate(json['ends_at']),
      allDay: json['all_day'] == true,
      location: json['location'] as String?,
      status: (json['status'] ?? '') as String,
      categoryName: category is Map ? category['name'] as String? : null,
      categoryColor: category is Map ? category['color'] as String? : null,
    );
  }
}

class PgiSummary {
  const PgiSummary({
    required this.id,
    required this.name,
    required this.isLeader,
    required this.canManageMeetings,
    this.dayOfWeek,
    this.timeSchedule,
    this.address,
    this.neighborhood,
    this.leaders = const [],
  });

  final int id;
  final String name;
  final bool isLeader;
  final bool canManageMeetings;
  final String? dayOfWeek;
  final String? timeSchedule;
  final String? address;
  final String? neighborhood;
  final List<String> leaders;

  factory PgiSummary.fromJson(Map<String, dynamic> json) {
    return PgiSummary(
      id: parseInt(json['id']) ?? 0,
      name: (json['name'] ?? 'PGI') as String,
      isLeader: json['is_leader'] == true,
      canManageMeetings: json['can_manage_meetings'] == true,
      dayOfWeek: json['day_of_week']?.toString(),
      timeSchedule: json['time_schedule'] as String?,
      address: json['address'] as String?,
      neighborhood: json['neighborhood'] as String?,
      leaders: ((json['leaders'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => '${item['name'] ?? ''}')
          .where((name) => name.isNotEmpty)
          .toList(),
    );
  }
}

class MeetingItem {
  const MeetingItem({
    required this.id,
    required this.attendanceRegistered,
    this.meetingDate,
    this.subject,
    this.notes,
    this.participantsCount = 0,
    this.visitorsCount = 0,
    this.attendances = const [],
  });

  final int id;
  final DateTime? meetingDate;
  final String? subject;
  final String? notes;
  final int participantsCount;
  final int visitorsCount;
  final bool attendanceRegistered;
  final List<AttendancePerson> attendances;

  factory MeetingItem.fromJson(Map<String, dynamic> json) {
    return MeetingItem(
      id: parseInt(json['id']) ?? 0,
      meetingDate: parseDate(json['meeting_date']),
      subject: json['subject'] as String?,
      notes: json['notes'] as String?,
      participantsCount: parseInt(json['participants_count']) ?? 0,
      visitorsCount: parseInt(json['visitors_count']) ?? 0,
      attendanceRegistered: json['attendance_registered'] == true,
      attendances: ((json['attendances'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => AttendancePerson.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
    );
  }
}

class AttendancePerson {
  const AttendancePerson({
    required this.type,
    required this.name,
    this.memberId,
    this.phone,
    this.present = false,
  });

  final String type;
  final int? memberId;
  final String name;
  final String? phone;
  final bool present;

  factory AttendancePerson.fromJson(Map<String, dynamic> json) {
    return AttendancePerson(
      type: (json['type'] ?? 'member') as String,
      memberId: parseInt(json['member_id'] ?? json['id']),
      name: (json['name'] ?? '') as String,
      phone: json['phone'] as String?,
      present: json['present'] == true,
    );
  }
}

class AttendanceForm {
  const AttendanceForm({
    required this.meeting,
    required this.members,
    required this.visitors,
    this.notes,
  });

  final MeetingItem meeting;
  final List<AttendancePerson> members;
  final List<AttendancePerson> visitors;
  final String? notes;

  factory AttendanceForm.fromJson(Map<String, dynamic> json) {
    return AttendanceForm(
      meeting: MeetingItem.fromJson(
        Map<String, dynamic>.from(json['meeting'] as Map? ?? const {}),
      ),
      members: ((json['members'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => AttendancePerson.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      visitors: ((json['visitors'] as List?) ?? const [])
          .whereType<Map>()
          .map(
            (item) => AttendancePerson.fromJson({
              ...Map<String, dynamic>.from(item),
              'type': 'visitor',
            }),
          )
          .toList(),
      notes: json['notes'] as String?,
    );
  }
}

class HomePayload {
  const HomePayload({
    required this.profile,
    required this.pendingSchedules,
    required this.nextSchedules,
    required this.nextEvents,
    required this.canViewFinancial,
    this.canViewMembers = false,
    this.canCreateMembers = false,
    this.pgi,
  });

  final AuthPayload profile;
  final int pendingSchedules;
  final List<ScheduleItem> nextSchedules;
  final List<EventItem> nextEvents;
  final PgiSummary? pgi;
  final bool canViewFinancial;
  final bool canViewMembers;
  final bool canCreateMembers;

  factory HomePayload.fromJson(Map<String, dynamic> json) {
    final pgiJson = json['pgi'];
    return HomePayload(
      profile: AuthPayload.fromJson(
        Map<String, dynamic>.from(json['profile'] as Map? ?? const {}),
      ),
      pendingSchedules: parseInt(json['pending_schedules']) ?? 0,
      nextSchedules: ((json['next_schedules'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => ScheduleItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      nextEvents: ((json['next_events'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => EventItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      pgi: pgiJson is Map
          ? PgiSummary.fromJson(Map<String, dynamic>.from(pgiJson))
          : null,
      canViewFinancial: json['can_view_financial'] == true,
      canViewMembers: json['can_view_members'] == true,
      canCreateMembers: json['can_create_members'] == true,
    );
  }
}

class FinancialAccount {
  const FinancialAccount({
    required this.id,
    required this.name,
    required this.balance,
    this.label,
    this.type,
    this.typeLabel,
    this.bankName,
    this.color,
    this.isActive = true,
    this.initialBalance = 0,
    this.inflow = 0,
    this.outflow = 0,
    this.description,
  });

  final int id;
  final String name;
  final String? label;
  final String? type;
  final String? typeLabel;
  final String? bankName;
  final String? color;
  final bool isActive;
  final double initialBalance;
  final double inflow;
  final double outflow;
  final double balance;
  final String? description;

  factory FinancialAccount.fromJson(Map<String, dynamic> json) {
    return FinancialAccount(
      id: parseInt(json['id']) ?? 0,
      name: (json['name'] ?? '') as String,
      label: json['label'] as String?,
      type: json['type'] as String?,
      typeLabel: json['type_label'] as String?,
      bankName: json['bank_name'] as String?,
      color: json['color'] as String?,
      isActive: json['is_active'] != false,
      initialBalance: parseDouble(json['initial_balance']),
      inflow: parseDouble(json['inflow']),
      outflow: parseDouble(json['outflow']),
      balance: parseDouble(json['balance']),
      description: json['description'] as String?,
    );
  }
}

class FinancialSummary {
  const FinancialSummary({
    required this.periodLabel,
    required this.inTotal,
    required this.outTotal,
    required this.result,
    required this.accounts,
    this.periodKey = '1month',
    this.periods = const [],
  });

  final String periodLabel;
  final String periodKey;
  final double inTotal;
  final double outTotal;
  final double result;
  final List<FinancialAccount> accounts;
  final List<FinancialOption> periods;

  factory FinancialSummary.fromJson(Map<String, dynamic> json) {
    final period = json['period'];
    return FinancialSummary(
      periodLabel: period is Map
          ? (period['label'] ?? 'Este mês') as String
          : 'Este mês',
      periodKey: period is Map ? (period['key'] ?? '1month') as String : '1month',
      inTotal: parseDouble(json['in_total']),
      outTotal: parseDouble(json['out_total']),
      result: parseDouble(json['result']),
      accounts: ((json['accounts'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialAccount.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      periods: ((json['periods'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialOption.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
    );
  }
}

class FinancialOption {
  const FinancialOption({required this.value, required this.label});

  final String value;
  final String label;

  factory FinancialOption.fromJson(Map<String, dynamic> json) {
    return FinancialOption(
      value: '${json['value'] ?? json['key'] ?? ''}',
      label: (json['label'] ?? '') as String,
    );
  }
}

class FinancialNavItem {
  const FinancialNavItem({
    required this.key,
    required this.label,
    required this.icon,
  });

  final String key;
  final String label;
  final String icon;

  factory FinancialNavItem.fromJson(Map<String, dynamic> json) {
    return FinancialNavItem(
      key: (json['key'] ?? '') as String,
      label: (json['label'] ?? '') as String,
      icon: (json['icon'] ?? '') as String,
    );
  }
}

class FinancialCapabilities {
  const FinancialCapabilities({
    required this.canViewSummary,
    required this.canViewTransactions,
    required this.canCreateReceita,
    required this.canCreateDespesa,
    required this.canViewReceipts,
    required this.canViewCorrection,
    required this.canViewFixedExpenses,
    required this.canManageFixedExpenses,
    required this.canViewAccounts,
    required this.canManageAccounts,
    required this.canViewCategories,
    required this.canManageCategories,
    required this.canViewCostCenters,
    required this.canManageCostCenters,
    required this.canViewContacts,
    required this.canManageContacts,
    required this.canViewCampaigns,
    required this.canManageCampaigns,
    required this.canViewReports,
    required this.canViewAutomations,
    required this.canManageAutomations,
  });

  final bool canViewSummary;
  final bool canViewTransactions;
  final bool canCreateReceita;
  final bool canCreateDespesa;
  final bool canViewReceipts;
  final bool canViewCorrection;
  final bool canViewFixedExpenses;
  final bool canManageFixedExpenses;
  final bool canViewAccounts;
  final bool canManageAccounts;
  final bool canViewCategories;
  final bool canManageCategories;
  final bool canViewCostCenters;
  final bool canManageCostCenters;
  final bool canViewContacts;
  final bool canManageContacts;
  final bool canViewCampaigns;
  final bool canManageCampaigns;
  final bool canViewReports;
  final bool canViewAutomations;
  final bool canManageAutomations;

  factory FinancialCapabilities.fromJson(Map<String, dynamic> json) {
    bool flag(String key) => json[key] == true;
    return FinancialCapabilities(
      canViewSummary: flag('can_view_summary'),
      canViewTransactions: flag('can_view_transactions'),
      canCreateReceita: flag('can_create_receita'),
      canCreateDespesa: flag('can_create_despesa'),
      canViewReceipts: flag('can_view_receipts'),
      canViewCorrection: flag('can_view_correction'),
      canViewFixedExpenses: flag('can_view_fixed_expenses'),
      canManageFixedExpenses: flag('can_manage_fixed_expenses'),
      canViewAccounts: flag('can_view_accounts'),
      canManageAccounts: flag('can_manage_accounts'),
      canViewCategories: flag('can_view_categories'),
      canManageCategories: flag('can_manage_categories'),
      canViewCostCenters: flag('can_view_cost_centers'),
      canManageCostCenters: flag('can_manage_cost_centers'),
      canViewContacts: flag('can_view_contacts'),
      canManageContacts: flag('can_manage_contacts'),
      canViewCampaigns: flag('can_view_campaigns'),
      canManageCampaigns: flag('can_manage_campaigns'),
      canViewReports: flag('can_view_reports'),
      canViewAutomations: flag('can_view_automations'),
      canManageAutomations: flag('can_manage_automations'),
    );
  }
}

class FinancialModule {
  const FinancialModule({required this.capabilities, required this.nav});

  final FinancialCapabilities capabilities;
  final List<FinancialNavItem> nav;

  factory FinancialModule.fromJson(Map<String, dynamic> json) {
    return FinancialModule(
      capabilities: FinancialCapabilities.fromJson(
        Map<String, dynamic>.from(json['capabilities'] as Map? ?? const {}),
      ),
      nav: ((json['nav'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialNavItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
    );
  }
}

class FinancialNamed {
  const FinancialNamed({required this.id, required this.name, this.type});

  final int id;
  final String name;
  final String? type;

  factory FinancialNamed.fromJson(Map<String, dynamic> json) {
    return FinancialNamed(
      id: parseInt(json['id']) ?? 0,
      name: (json['name'] ?? '') as String,
      type: json['type'] as String?,
    );
  }
}

class FinancialOptions {
  const FinancialOptions({
    required this.accounts,
    required this.categories,
    required this.contacts,
    required this.costCenters,
    required this.members,
    required this.accountTypes,
  });

  final List<FinancialAccount> accounts;
  final List<FinancialNamed> categories;
  final List<FinancialNamed> contacts;
  final List<FinancialNamed> costCenters;
  final List<FinancialNamed> members;
  final List<FinancialOption> accountTypes;

  factory FinancialOptions.fromJson(Map<String, dynamic> json) {
    List<FinancialNamed> named(String key) => ((json[key] as List?) ?? const [])
        .whereType<Map>()
        .map((item) => FinancialNamed.fromJson(Map<String, dynamic>.from(item)))
        .toList();
    return FinancialOptions(
      accounts: ((json['accounts'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialAccount.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      categories: named('categories'),
      contacts: named('contacts'),
      costCenters: named('cost_centers'),
      members: named('members'),
      accountTypes: ((json['account_types'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialOption.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
    );
  }
}

class FinancialTx {
  const FinancialTx({
    required this.id,
    required this.type,
    required this.typeLabel,
    required this.description,
    required this.amount,
    required this.paid,
    this.date,
    this.dueDate,
    this.status,
    this.statusLabel,
    this.categoryId,
    this.category,
    this.accountId,
    this.account,
    this.memberId,
    this.member,
    this.contactId,
    this.contact,
    this.receivedFromOther,
    this.costCenterId,
    this.notes,
    this.party,
    this.attachments = const [],
    this.attachmentsCount = 0,
  });

  final int id;
  final String type;
  final String typeLabel;
  final String description;
  final double amount;
  final bool paid;
  final DateTime? date;
  final DateTime? dueDate;
  final String? status;
  final String? statusLabel;
  final int? categoryId;
  final String? category;
  final int? accountId;
  final String? account;
  final int? memberId;
  final String? member;
  final int? contactId;
  final String? contact;
  final String? receivedFromOther;
  final int? costCenterId;
  final String? notes;
  final String? party;
  final List<FinancialAttachment> attachments;
  final int attachmentsCount;

  factory FinancialTx.fromJson(Map<String, dynamic> json) {
    return FinancialTx(
      id: parseInt(json['id']) ?? 0,
      type: (json['type'] ?? '') as String,
      typeLabel: (json['type_label'] ?? json['type'] ?? '') as String,
      description: (json['description'] ?? '') as String,
      amount: parseDouble(json['amount']),
      paid: json['paid'] == true,
      date: parseDate(json['date']),
      dueDate: parseDate(json['due_date']),
      status: json['status'] as String?,
      statusLabel: json['status_label'] as String?,
      categoryId: parseInt(json['category_id']),
      category: json['category'] as String?,
      accountId: parseInt(json['account_id']),
      account: json['account'] as String?,
      memberId: parseInt(json['member_id']),
      member: json['member'] as String?,
      contactId: parseInt(json['contact_id']),
      contact: json['contact'] as String?,
      receivedFromOther: json['received_from_other'] as String?,
      costCenterId: parseInt(json['cost_center_id']),
      notes: json['notes'] as String?,
      party: json['party'] as String?,
      attachments: ((json['attachments'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialAttachment.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      attachmentsCount: parseInt(json['attachments_count']) ??
          ((json['attachments'] as List?) ?? const []).length,
    );
  }
}

class FinancialAttachment {
  const FinancialAttachment({
    required this.id,
    required this.fileName,
    this.transactionId,
    this.fileType,
    this.fileSize = 0,
    this.url,
    this.apiPath,
    this.generated = false,
    this.isImage = false,
    this.available = false,
    this.canOpen = false,
  });

  final int id;
  final int? transactionId;
  final String fileName;
  final String? fileType;
  final int fileSize;
  final String? url;
  final String? apiPath;
  final bool generated;
  final bool isImage;
  final bool available;
  final bool canOpen;

  factory FinancialAttachment.fromJson(Map<String, dynamic> json) {
    final type = json['file_type'] as String?;
    final generated = json['generated'] == true;
    final available = json['available'] == true;
    return FinancialAttachment(
      id: parseInt(json['id']) ?? 0,
      transactionId: parseInt(json['transaction_id']),
      fileName: (json['file_name'] ?? 'arquivo') as String,
      fileType: type,
      fileSize: parseInt(json['file_size']) ?? 0,
      url: json['url'] as String?,
      apiPath: json['api_path'] as String?,
      generated: generated,
      isImage: json['is_image'] == true || (type?.startsWith('image/') ?? false),
      available: available,
      canOpen: json['can_open'] == true || available || generated,
    );
  }
}

class FinancialTxList {
  const FinancialTxList({
    required this.items,
    required this.totalReceived,
    required this.totalPaid,
    required this.toReceive,
    required this.toPay,
    required this.page,
    required this.lastPage,
    required this.total,
  });

  final List<FinancialTx> items;
  final double totalReceived;
  final double totalPaid;
  final double toReceive;
  final double toPay;
  final int page;
  final int lastPage;
  final int total;

  factory FinancialTxList.fromJson(Map<String, dynamic> json) {
    final summary = json['summary'] is Map
        ? Map<String, dynamic>.from(json['summary'] as Map)
        : const <String, dynamic>{};
    return FinancialTxList(
      items: ((json['items'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialTx.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      totalReceived: parseDouble(summary['total_received']),
      totalPaid: parseDouble(summary['total_paid']),
      toReceive: parseDouble(summary['to_receive']),
      toPay: parseDouble(summary['to_pay']),
      page: parseInt(json['page']) ?? 1,
      lastPage: parseInt(json['last_page']) ?? 1,
      total: parseInt(json['total']) ?? 0,
    );
  }
}

class FinancialTransferItem {
  const FinancialTransferItem({
    required this.id,
    required this.amount,
    this.fromAccount,
    this.toAccount,
    this.date,
    this.notes,
  });

  final int id;
  final double amount;
  final String? fromAccount;
  final String? toAccount;
  final DateTime? date;
  final String? notes;

  factory FinancialTransferItem.fromJson(Map<String, dynamic> json) {
    return FinancialTransferItem(
      id: parseInt(json['id']) ?? 0,
      amount: parseDouble(json['amount']),
      fromAccount: json['from_account'] as String?,
      toAccount: json['to_account'] as String?,
      date: parseDate(json['date']),
      notes: json['notes'] as String?,
    );
  }
}

class FinancialAccountsPayload {
  const FinancialAccountsPayload({
    required this.items,
    required this.transfers,
    required this.types,
    required this.canManage,
  });

  final List<FinancialAccount> items;
  final List<FinancialTransferItem> transfers;
  final List<FinancialOption> types;
  final bool canManage;

  factory FinancialAccountsPayload.fromJson(Map<String, dynamic> json) {
    return FinancialAccountsPayload(
      items: ((json['items'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialAccount.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      transfers: ((json['transfers'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialTransferItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      types: ((json['types'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialOption.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      canManage: json['can_manage'] == true,
    );
  }
}

class FinancialCatalogItem {
  const FinancialCatalogItem({
    required this.id,
    required this.name,
    this.type,
    this.typeLabel,
    this.description,
    this.phone,
    this.email,
    this.sendsReceipt = false,
  });

  final int id;
  final String name;
  final String? type;
  final String? typeLabel;
  final String? description;
  final String? phone;
  final String? email;
  final bool sendsReceipt;

  factory FinancialCatalogItem.fromJson(Map<String, dynamic> json) {
    return FinancialCatalogItem(
      id: parseInt(json['id']) ?? 0,
      name: (json['name'] ?? '') as String,
      type: json['type'] as String?,
      typeLabel: json['type_label'] as String?,
      description: json['description'] as String?,
      phone: json['phone'] as String?,
      email: json['email'] as String?,
      sendsReceipt: json['sends_receipt'] == true,
    );
  }
}

class FinancialCatalogList {
  const FinancialCatalogList({required this.items, required this.canManage});

  final List<FinancialCatalogItem> items;
  final bool canManage;

  factory FinancialCatalogList.fromJson(Map<String, dynamic> json) {
    return FinancialCatalogList(
      items: ((json['items'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialCatalogItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      canManage: json['can_manage'] == true,
    );
  }
}

class FinancialFixedItem {
  const FinancialFixedItem({
    required this.id,
    required this.description,
    required this.amount,
    required this.dueDay,
    required this.isActive,
    required this.generated,
    required this.paid,
    this.category,
    this.account,
    this.contact,
    this.transactionId,
    this.categoryId,
    this.accountId,
    this.contactId,
    this.costCenterId,
    this.notes,
    this.amountVariable = false,
  });

  final int id;
  final String description;
  final double amount;
  final int dueDay;
  final bool isActive;
  final bool generated;
  final bool paid;
  final bool amountVariable;
  final String? category;
  final String? account;
  final String? contact;
  final int? transactionId;
  final int? categoryId;
  final int? accountId;
  final int? contactId;
  final int? costCenterId;
  final String? notes;

  factory FinancialFixedItem.fromJson(Map<String, dynamic> json) {
    return FinancialFixedItem(
      id: parseInt(json['id']) ?? 0,
      description: (json['description'] ?? '') as String,
      amount: parseDouble(json['amount']),
      dueDay: parseInt(json['due_day']) ?? 1,
      isActive: json['is_active'] == true,
      generated: json['generated'] == true,
      paid: json['paid'] == true,
      amountVariable: json['amount_variable'] == true,
      category: json['category'] as String?,
      account: json['account'] as String?,
      contact: json['contact'] as String?,
      transactionId: parseInt(json['transaction_id']),
      categoryId: parseInt(json['category_id']),
      accountId: parseInt(json['account_id']),
      contactId: parseInt(json['contact_id']),
      costCenterId: parseInt(json['cost_center_id']),
      notes: json['notes'] as String?,
    );
  }
}

class FinancialFixedPayload {
  const FinancialFixedPayload({
    required this.month,
    required this.monthLabel,
    required this.items,
    required this.canManage,
  });

  final String month;
  final String monthLabel;
  final List<FinancialFixedItem> items;
  final bool canManage;

  factory FinancialFixedPayload.fromJson(Map<String, dynamic> json) {
    return FinancialFixedPayload(
      month: (json['month'] ?? '') as String,
      monthLabel: (json['month_label'] ?? '') as String,
      items: ((json['items'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialFixedItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      canManage: json['can_manage'] == true,
    );
  }
}

class FinancialCampaign {
  const FinancialCampaign({
    required this.id,
    required this.name,
    required this.status,
    required this.installmentAmount,
    required this.installmentsCount,
    required this.sponsorsCount,
    this.description,
    this.goalAmount = 0,
    this.department,
  });

  final int id;
  final String name;
  final String? description;
  final String status;
  final double goalAmount;
  final double installmentAmount;
  final int installmentsCount;
  final String? department;
  final int sponsorsCount;

  factory FinancialCampaign.fromJson(Map<String, dynamic> json) {
    return FinancialCampaign(
      id: parseInt(json['id']) ?? 0,
      name: (json['name'] ?? '') as String,
      description: json['description'] as String?,
      status: (json['status'] ?? '') as String,
      goalAmount: parseDouble(json['goal_amount']),
      installmentAmount: parseDouble(json['installment_amount']),
      installmentsCount: parseInt(json['installments_count']) ?? 0,
      department: json['department'] as String?,
      sponsorsCount: parseInt(json['sponsors_count']) ?? 0,
    );
  }
}

class FinancialInstallment {
  const FinancialInstallment({
    required this.id,
    required this.number,
    required this.amount,
    required this.status,
    this.dueDate,
    this.paidAt,
    this.paymentMethod,
    this.receiptNumber,
  });

  final int id;
  final int number;
  final double amount;
  final String status;
  final DateTime? dueDate;
  final DateTime? paidAt;
  final String? paymentMethod;
  final String? receiptNumber;

  factory FinancialInstallment.fromJson(Map<String, dynamic> json) {
    return FinancialInstallment(
      id: parseInt(json['id']) ?? 0,
      number: parseInt(json['number']) ?? 0,
      amount: parseDouble(json['amount']),
      status: (json['status'] ?? '') as String,
      dueDate: parseDate(json['due_date']),
      paidAt: parseDate(json['paid_at']),
      paymentMethod: json['payment_method'] as String?,
      receiptNumber: json['receipt_number'] as String?,
    );
  }
}

class FinancialSponsor {
  const FinancialSponsor({
    required this.id,
    required this.name,
    required this.paidTotal,
    required this.pendingCount,
    required this.installments,
    this.phone,
  });

  final int id;
  final String name;
  final String? phone;
  final double paidTotal;
  final int pendingCount;
  final List<FinancialInstallment> installments;

  factory FinancialSponsor.fromJson(Map<String, dynamic> json) {
    return FinancialSponsor(
      id: parseInt(json['id']) ?? 0,
      name: (json['name'] ?? '') as String,
      phone: json['phone'] as String?,
      paidTotal: parseDouble(json['paid_total']),
      pendingCount: parseInt(json['pending_count']) ?? 0,
      installments: ((json['installments'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialInstallment.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
    );
  }
}

class FinancialCampaignDetail {
  const FinancialCampaignDetail({
    required this.campaign,
    required this.sponsors,
    required this.canManage,
  });

  final FinancialCampaign campaign;
  final List<FinancialSponsor> sponsors;
  final bool canManage;

  factory FinancialCampaignDetail.fromJson(Map<String, dynamic> json) {
    return FinancialCampaignDetail(
      campaign: FinancialCampaign.fromJson(
        Map<String, dynamic>.from(json['campaign'] as Map? ?? const {}),
      ),
      sponsors: ((json['sponsors'] as List?) ?? const [])
          .whereType<Map>()
          .map((item) => FinancialSponsor.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      canManage: json['can_manage'] == true,
    );
  }
}

class FinancialReport {
  const FinancialReport({
    required this.inTotal,
    required this.outTotal,
    required this.result,
    required this.receitas,
    required this.despesas,
  });

  final double inTotal;
  final double outTotal;
  final double result;
  final List<FinancialReportRow> receitas;
  final List<FinancialReportRow> despesas;

  factory FinancialReport.fromJson(Map<String, dynamic> json) {
    List<FinancialReportRow> rows(String key) => ((json[key] as List?) ?? const [])
        .whereType<Map>()
        .map((item) => FinancialReportRow.fromJson(Map<String, dynamic>.from(item)))
        .toList();
    return FinancialReport(
      inTotal: parseDouble(json['in_total']),
      outTotal: parseDouble(json['out_total']),
      result: parseDouble(json['result']),
      receitas: rows('receitas'),
      despesas: rows('despesas'),
    );
  }
}

class FinancialReportRow {
  const FinancialReportRow({required this.category, required this.total});

  final String category;
  final double total;

  factory FinancialReportRow.fromJson(Map<String, dynamic> json) {
    return FinancialReportRow(
      category: (json['category'] ?? 'Sem categoria') as String,
      total: parseDouble(json['total']),
    );
  }
}

class FinancialAutomationItem {
  const FinancialAutomationItem({
    required this.id,
    required this.key,
    required this.name,
    required this.enabled,
  });

  final int id;
  final String key;
  final String name;
  final bool enabled;

  factory FinancialAutomationItem.fromJson(Map<String, dynamic> json) {
    return FinancialAutomationItem(
      id: parseInt(json['id']) ?? 0,
      key: (json['key'] ?? '') as String,
      name: (json['name'] ?? '') as String,
      enabled: json['enabled'] == true,
    );
  }
}

class FinancialReceiptItem {
  const FinancialReceiptItem({
    required this.id,
    required this.description,
    required this.amount,
    this.date,
    this.type,
    this.fileName,
    this.fileType,
    this.url,
    this.apiPath,
    this.receiptApiPath,
    this.isImage = false,
    this.transactionId,
    this.attachmentsCount = 0,
    this.available = false,
    this.canOpen = false,
    this.canViewReceipt = false,
    this.generated = false,
  });

  final int id;
  final String description;
  final double amount;
  final DateTime? date;
  final String? type;
  final String? fileName;
  final String? fileType;
  final String? url;
  final String? apiPath;
  final String? receiptApiPath;
  final bool isImage;
  final int? transactionId;
  final int attachmentsCount;
  final bool available;
  final bool canOpen;
  final bool canViewReceipt;
  final bool generated;

  factory FinancialReceiptItem.fromJson(Map<String, dynamic> json) {
    final type = json['file_type'] as String?;
    final generated = json['generated'] == true;
    final available = json['available'] == true;
    return FinancialReceiptItem(
      id: parseInt(json['id']) ?? 0,
      description: (json['description'] ?? json['file_name'] ?? '') as String,
      amount: parseDouble(json['amount']),
      date: parseDate(json['date']),
      type: json['type'] as String?,
      fileName: json['file_name'] as String?,
      fileType: type,
      url: json['url'] as String?,
      apiPath: json['api_path'] as String?,
      receiptApiPath: json['receipt_api_path'] as String?,
      isImage: json['is_image'] == true || (type?.startsWith('image/') ?? false),
      transactionId: parseInt(json['transaction_id']) ?? parseInt(json['id']),
      attachmentsCount: parseInt(json['attachments_count']) ?? 0,
      available: available,
      canOpen: json['can_open'] == true || available || generated,
      canViewReceipt: json['can_view_receipt'] == true,
      generated: generated,
    );
  }
}
