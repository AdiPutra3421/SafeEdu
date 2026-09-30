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

  /// Panggil POST /api/auth/login, simpan session user jika berhasil.
  /// Mengembalikan pesan error jika gagal, null jika sukses.
  Future<String?> login({
    required String email,
    required String password,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/api/auth/login'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'email': email.trim(),
          'password': password,
        }),
      );

      final data = json.decode(res.body);

      if (res.statusCode != 200 || data['success'] != true) {
        return data['message'] ?? 'Email atau password salah.';
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

  /// Panggil POST /api/auth/register, simpan session user jika berhasil.
  /// Mengembalikan pesan error jika gagal, null jika sukses.
  Future<String?> register({
    required String nama,
    required String email,
    required String password,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/api/auth/register'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'nama': nama.trim(),
          'email': email.trim(),
          'password': password,
        }),
      );

      final data = json.decode(res.body);

      if (res.statusCode != 201 || data['success'] != true) {
        if (data['errors'] != null && data['errors'] is Map) {
          final firstError = (data['errors'] as Map).values.first;
          if (firstError is List && firstError.isNotEmpty) {
            return firstError.first.toString();
          }
          return firstError.toString();
        }
        return data['message'] ?? 'Pendaftaran gagal.';
      }

      final user = data['data'];
      userId = user['id'];
      this.nama = user['nama'];
      this.email = user['email'];
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