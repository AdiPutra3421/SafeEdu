import 'dart:convert';
import 'package:http/http.dart' as http;
import 'api_config.dart';

class AuthService {
  AuthService._();

  static final AuthService instance = AuthService._();

  int? userId;
  String? nama;
  String? email;
  String? role;

  bool get isLoggedIn => userId != null;

  /// Panggil POST /api/auth/login dengan role 'teknisi'
  Future<String?> login({
    required String email,
    required String password,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/api/auth/login'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'email': email.trim(),
          'password': password,
          'role': 'teknisi',
        }),
      );

      final data = json.decode(res.body);

      if (res.statusCode != 200 || data['success'] != true) {
        return data['message'] ?? 'Email atau password teknisi salah.';
      }

      final user = data['data'];
      userId = user['id'];
      nama = user['nama'];
      email = user['email'];
      role = user['role'];
      return null;
    } catch (e) {
      return 'Tidak dapat terhubung ke server. Periksa koneksi Anda.';
    }
  }

  void logout() {
    userId = null;
    nama = null;
    email = null;
    role = null;
  }
}
