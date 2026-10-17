import 'package:flutter/foundation.dart';

class AppConfig {
  static const _fromEnv = String.fromEnvironment('API_URL');

  static String get apiBaseUrl {
    if (_fromEnv.isNotEmpty) {
      return _fromEnv;
    }
    if (kDebugMode) {
      if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
        return 'http://10.0.2.2:8001';
      }
      return 'http://127.0.0.1:8001';
    }
    return 'https://adelss.com.br';
  }

  static const apiPrefix = '/api/v1';

  static String get platform {
    if (kIsWeb) {
      return 'web';
    }
    if (defaultTargetPlatform == TargetPlatform.iOS) {
      return 'ios';
    }
    return 'android';
  }
}
