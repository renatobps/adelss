import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../api/models.dart';
import '../api/repository.dart';

class SessionController extends ChangeNotifier {
  SessionController({
    FlutterSecureStorage? storage,
    ApiClient? client,
  }) : _storage = storage ?? const FlutterSecureStorage() {
    _client = client ?? ApiClient(readToken: () => token);
    api = AdelssRepository(_client);
  }

  static const _tokenKey = 'adelss_token';

  final FlutterSecureStorage _storage;
  late final ApiClient _client;
  late final AdelssRepository api;

  String? token;
  AuthPayload? profile;
  String? lastError;
  bool canViewFinancial = false;
  bool canManagePgi = false;
  bool canViewMembers = false;
  bool canCreateMembers = false;
  bool canEditMembers = false;

  bool get isLoggedIn => token != null && token!.isNotEmpty;
  bool get mustChangePassword => profile?.mustChangePassword ?? false;

  Future<void> restore() async {
    token = await _storage.read(key: _tokenKey);
    if (!isLoggedIn) {
      return;
    }
    try {
      profile = await api.me();
      _syncAccess();
    } on ApiException catch (error) {
      if (error.unauthorized) {
        await logout(remote: false);
      } else {
        lastError = error.message;
      }
    }
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    lastError = null;
    final payload = await api.login(
      email: email.trim(),
      password: password,
      deviceName: await _deviceName(),
    );
    token = payload.token;
    profile = payload;
    _syncAccess();
    if (token != null) {
      await _storage.write(key: _tokenKey, value: token);
    }
    notifyListeners();
  }

  Future<void> changePassword({
    String? currentPassword,
    required String password,
  }) async {
    await api.changePassword(
      currentPassword: currentPassword,
      password: password,
    );
    if (profile != null) {
      profile = profile!.copyWith(
        user: profile!.user.copyWith(mustChangePassword: false),
      );
    }
    notifyListeners();
  }

  Future<String> forgotPassword(String email) {
    return api.forgotPassword(email.trim());
  }

  Future<void> logout({bool remote = true}) async {
    if (remote && isLoggedIn) {
      await api.logout();
    }
    token = null;
    profile = null;
    canViewFinancial = false;
    canManagePgi = false;
    canViewMembers = false;
    canCreateMembers = false;
    canEditMembers = false;
    await _storage.delete(key: _tokenKey);
    notifyListeners();
  }

  void applyHome(HomePayload home) {
    canViewFinancial = home.canViewFinancial;
    canManagePgi = home.pgi?.canManageMeetings ?? false;
    canViewMembers = home.canViewMembers || (home.profile.canViewMembers);
    canCreateMembers = home.canCreateMembers || home.profile.canCreateMembers;
    canEditMembers = home.profile.canEditMembers;
    profile = home.profile.copyWith(token: token);
    notifyListeners();
  }

  void _syncAccess() {
    final current = profile;
    if (current == null) {
      return;
    }
    canViewMembers = current.canViewMembers;
    canCreateMembers = current.canCreateMembers;
    canEditMembers = current.canEditMembers;
  }

  void applyMember(MemberDetail member) {
    if (profile == null) {
      return;
    }
    profile = profile!.copyWith(
      user: profile!.user,
      member: MemberProfile(
        id: member.id,
        name: member.name,
        phone: member.phone,
        status: member.status,
        pgiId: member.pgiId,
        photoUrl: member.photoUrl,
      ),
    );
    notifyListeners();
  }

  Future<void> refreshMe() async {
    profile = await api.me();
    _syncAccess();
    notifyListeners();
  }

  void setPgiAccess({required bool canManage}) {
    canManagePgi = canManage;
    notifyListeners();
  }

  Future<void> handleApiError(ApiException error) async {
    if (error.unauthorized) {
      await logout(remote: false);
    }
  }

  Future<String> _deviceName() async {
    try {
      final info = await DeviceInfoPlugin().deviceInfo;
      final data = info.data;
      final parts = [
        data['brand'],
        data['model'],
        data['name'],
        data['computerName'],
      ].whereType<String>().where((part) => part.trim().isNotEmpty);
      if (parts.isNotEmpty) {
        final name = parts.take(2).join(' ');
        return name.length <= 80 ? name : name.substring(0, 80);
      }
    } catch (_) {}
    if (kIsWeb) {
      return 'web';
    }
    return 'android';
  }
}
