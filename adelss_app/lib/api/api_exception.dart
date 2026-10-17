class ApiException implements Exception {
  const ApiException(this.message, {this.status, this.code});

  final String message;
  final int? status;
  final String? code;

  bool get mustChangePassword => code == 'must_change_password';
  bool get unauthorized => status == 401;

  @override
  String toString() => message;
}
