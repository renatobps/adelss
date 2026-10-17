import 'dart:convert';
import 'dart:typed_data';

import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';

import '../config.dart';
import 'api_exception.dart';

class ApiClient {
  ApiClient({required this.readToken, http.Client? httpClient})
      : _http = httpClient ?? http.Client();

  final String? Function() readToken;
  final http.Client _http;

  Uri _uri(String path, [Map<String, String>? query]) {
    final base = AppConfig.apiBaseUrl.replaceAll(RegExp(r'/$'), '');
    return Uri.parse('$base${AppConfig.apiPrefix}$path').replace(
      queryParameters: query == null || query.isEmpty ? null : query,
    );
  }

  Map<String, String> _headers({bool jsonBody = false}) {
    final headers = <String, String>{
      'Accept': 'application/json',
    };
    if (jsonBody) {
      headers['Content-Type'] = 'application/json';
    }
    final token = readToken();
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    return headers;
  }

  Future<dynamic> get(String path, {Map<String, String>? query}) {
    return _send(_http.get(_uri(path, query), headers: _headers()));
  }

  Uri uri(String path, [Map<String, String>? query]) => _uri(path, query);

  Map<String, String> authHeaders() => _headers();

  Future<Uint8List> getBytes(String path) async {
    late http.Response response;
    try {
      response = await _http.get(
        _uri(path),
        headers: {
          ..._headers(),
          'Accept': '*/*',
        },
      );
    } catch (_) {
      throw const ApiException(
        'Não foi possível conectar. Verifique a internet e a URL da API.',
      );
    }
    if (response.statusCode >= 400) {
      dynamic decoded = {};
      if (response.body.isNotEmpty) {
        try {
          decoded = jsonDecode(response.body);
        } catch (_) {}
      }
      throw ApiException(
        _errorMessage(decoded, response.statusCode),
        status: response.statusCode,
      );
    }
    return response.bodyBytes;
  }

  Future<dynamic> post(String path, {Map<String, dynamic>? body}) {
    return _send(
      _http.post(
        _uri(path),
        headers: _headers(jsonBody: true),
        body: jsonEncode(body ?? const {}),
      ),
    );
  }

  Future<dynamic> put(String path, {Map<String, dynamic>? body}) {
    return _send(
      _http.put(
        _uri(path),
        headers: _headers(jsonBody: true),
        body: jsonEncode(body ?? const {}),
      ),
    );
  }

  Future<dynamic> delete(String path, {Map<String, dynamic>? body}) {
    return _send(
      _http.delete(
        _uri(path),
        headers: _headers(jsonBody: true),
        body: jsonEncode(body ?? const {}),
      ),
    );
  }

  Future<dynamic> postMultipart(
    String path, {
    required List<MultipartUpload> files,
  }) async {
    final request = http.MultipartRequest('POST', _uri(path));
    request.headers.addAll(_headers());
    for (final file in files) {
      request.files.add(
        http.MultipartFile.fromBytes(
          file.field,
          file.bytes,
          filename: file.filename,
          contentType: _mediaType(file.mime, file.filename),
        ),
      );
    }

    late http.Response response;
    try {
      response = await http.Response.fromStream(await _http.send(request));
    } catch (_) {
      throw const ApiException(
        'Não foi possível conectar. Verifique a internet e a URL da API.',
      );
    }
    return _parse(response);
  }

  MediaType? _mediaType(String? mime, String filename) {
    if (mime != null && mime.contains('/')) {
      final parts = mime.split('/');
      return MediaType(parts.first, parts.sublist(1).join('/'));
    }
    final lower = filename.toLowerCase();
    if (lower.endsWith('.png')) return MediaType('image', 'png');
    if (lower.endsWith('.webp')) return MediaType('image', 'webp');
    if (lower.endsWith('.gif')) return MediaType('image', 'gif');
    if (lower.endsWith('.pdf')) return MediaType('application', 'pdf');
    if (lower.endsWith('.heic') || lower.endsWith('.heif')) {
      return MediaType('image', 'heic');
    }
    return MediaType('image', 'jpeg');
  }

  Future<dynamic> _send(Future<http.Response> request) async {
    late http.Response response;
    try {
      response = await request;
    } catch (_) {
      throw const ApiException(
        'Não foi possível conectar. Verifique a internet e a URL da API.',
      );
    }
    return _parse(response);
  }

  dynamic _parse(http.Response response) {
    dynamic decoded = {};
    if (response.body.isNotEmpty) {
      try {
        decoded = jsonDecode(response.body);
      } catch (_) {
        throw ApiException(
          'Resposta inválida do servidor (${response.statusCode}).',
          status: response.statusCode,
        );
      }
    }

    if (response.statusCode >= 400) {
      throw ApiException(
        _errorMessage(decoded, response.statusCode),
        status: response.statusCode,
        code: decoded is Map ? decoded['code'] as String? : null,
      );
    }

    if (decoded is Map && decoded.containsKey('data')) {
      return decoded['data'];
    }
    return decoded;
  }

  String _errorMessage(dynamic decoded, int status) {
    if (decoded is Map) {
      final error = decoded['error'];
      if (error is String && error.trim().isNotEmpty) {
        return error;
      }
      final message = decoded['message'];
      if (message is String && message.trim().isNotEmpty) {
        return message;
      }
      final errors = decoded['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) {
          return '${first.first}';
        }
        return '$first';
      }
    }
    if (status == 401) {
      return 'E-mail ou senha inválidos.';
    }
    if (status == 403) {
      return 'Você não tem permissão para isso.';
    }
    if (status >= 500) {
      return 'O servidor encontrou um erro ao entrar.';
    }
    return 'Não foi possível concluir a operação.';
  }
}

class MultipartUpload {
  const MultipartUpload({
    required this.filename,
    required this.bytes,
    this.field = 'files[]',
    this.mime,
  });

  final String field;
  final String filename;
  final List<int> bytes;
  final String? mime;
}
