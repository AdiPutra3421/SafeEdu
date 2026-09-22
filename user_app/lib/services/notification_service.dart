import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/notifikasi_model.dart';
import 'api_config.dart';

class NotificationService {
  static Future<List<NotifikasiModel>> fetchNotifications(int userId) async {
    try {
      final res = await http.get(Uri.parse(
          '${ApiConfig.baseUrl}/api/notifikasi?user_id=$userId'));
      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (data['success'] == true) {
          return (data['data'] as List)
              .map((e) => NotifikasiModel.fromMap(e))
              .toList();
        }
      }
    } catch (e) {
      print('Error fetching notifications: $e');
    }
    return [];
  }

  static Future<int> fetchUnreadCount(int userId) async {
    try {
      final res = await http.get(Uri.parse(
          '${ApiConfig.baseUrl}/api/notifikasi/unread-count?user_id=$userId'));
      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (data['success'] == true) {
          return data['count'] ?? 0;
        }
      }
    } catch (e) {
      print('Error fetching unread count: $e');
    }
    return 0;
  }

  static Future<bool> markAsRead(int notifId) async {
    try {
      final res = await http.post(Uri.parse(
          '${ApiConfig.baseUrl}/api/notifikasi/$notifId/read'));
      return res.statusCode == 200;
    } catch (e) {
      print('Error marking notification read: $e');
      return false;
    }
  }

  static Future<bool> markAllRead(int userId) async {
    try {
      final res = await http.post(Uri.parse(
          '${ApiConfig.baseUrl}/api/notifikasi/read-all?user_id=$userId'));
      return res.statusCode == 200;
    } catch (e) {
      print('Error marking all notifications read: $e');
      return false;
    }
  }
}