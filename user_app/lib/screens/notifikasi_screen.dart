import 'package:flutter/material.dart';
import '../models/notifikasi_model.dart';
import '../services/auth_service.dart';
import '../services/notification_service.dart';
import '../theme/app_theme.dart';
import '../utils/formatters.dart';

class NotifikasiScreen extends StatefulWidget {
  const NotifikasiScreen({super.key});

  @override
  State<NotifikasiScreen> createState() => _NotifikasiScreenState();
}

class _NotifikasiScreenState extends State<NotifikasiScreen> {
  List<NotifikasiModel> _notifs = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _isLoading = true);
    final userId = AuthService.instance.userId;
    if (userId == null) {
      setState(() => _isLoading = false);
      return;
    }
    final notifs = await NotificationService.fetchNotifications(userId);
    if (!mounted) return;
    setState(() {
      _notifs = notifs;
      _isLoading = false;
    });
  }

  Future<void> _markRead(NotifikasiModel notif) async {
    if (notif.isRead) return;
    await NotificationService.markAsRead(notif.id);
    setState(() {
      final idx = _notifs.indexWhere((n) => n.id == notif.id);
      if (idx != -1) {
        final updated = NotifikasiModel(
          id: _notifs[idx].id,
          userId: _notifs[idx].userId,
          pengaduanId: _notifs[idx].pengaduanId,
          type: _notifs[idx].type,
          judul: _notifs[idx].judul,
          pesan: _notifs[idx].pesan,
          isRead: true,
          createdAt: _notifs[idx].createdAt,
        );
        _notifs[idx] = updated;
      }
    });
  }

  Future<void> _markAllRead() async {
    final userId = AuthService.instance.userId;
    if (userId == null) return;
    await NotificationService.markAllRead(userId);
    setState(() {
      _notifs = _notifs
          .map((n) => NotifikasiModel(
                id: n.id,
                userId: n.userId,
                pengaduanId: n.pengaduanId,
                type: n.type,
                judul: n.judul,
                pesan: n.pesan,
                isRead: true,
                createdAt: n.createdAt,
              ))
          .toList();
    });
  }

  (IconData, Color) _iconFor(String type) {
    switch (type) {
      case 'success':
        return (Icons.check_circle_rounded, AppColors.statusSuccessText);
      case 'warning':
        return (Icons.handyman_rounded, AppColors.statusProcessText);
      case 'danger':
        return (Icons.error_rounded, AppColors.statusDangerText);
      default:
        return (Icons.info_rounded, AppColors.primary);
    }
  }

  String _keterangan(String type) {
    switch (type) {
      case 'success':
        return 'Laporan selesai';
      case 'warning':
        return 'Laporan ditangani';
      case 'danger':
        return 'Perlu perhatian';
      default:
        return 'Pembaruan status';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: const Text('Notifikasi'),
        actions: [
          IconButton(
            tooltip: 'Tandai semua dibaca',
            icon: const Icon(Icons.done_all_rounded),
            onPressed: _notifs.isEmpty ? null : _markAllRead,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _notifs.isEmpty
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(18),
                          decoration: BoxDecoration(
                            color: AppColors.primaryLight,
                            borderRadius: BorderRadius.circular(22),
                          ),
                          child: const Icon(
                            Icons.notifications_none_rounded,
                            size: 34,
                            color: AppColors.primary,
                          ),
                        ),
                        const SizedBox(height: 16),
                        const Text(
                          'Belum ada notifikasi',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: AppColors.textMain,
                          ),
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Pembaruan laporan akan muncul di sini.',
                          style: TextStyle(
                            fontSize: 13,
                            color: AppColors.textMuted,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  color: AppColors.primary,
                  child: ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    itemCount: _notifs.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (context, index) {
                      final notif = _notifs[index];
                      final (icon, color) = _iconFor(notif.type);
                      return Material(
                        color: Colors.transparent,
                        borderRadius: BorderRadius.circular(18),
                        child: InkWell(
                          borderRadius: BorderRadius.circular(18),
                          onTap: () => _markRead(notif),
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 200),
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: notif.isRead
                                  ? AppColors.surface
                                  : AppColors.primaryLight,
                              borderRadius: BorderRadius.circular(18),
                              border: Border.all(
                                color: notif.isRead
                                    ? AppColors.border
                                    : AppColors.primary.withValues(alpha: 0.3),
                              ),
                            ),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Container(
                                  width: 42,
                                  height: 42,
                                  decoration: BoxDecoration(
                                    color: color.withValues(alpha: 0.12),
                                    borderRadius: BorderRadius.circular(14),
                                  ),
                                  child: Icon(icon, color: color, size: 20),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Expanded(
                                            child: Text(
                                              notif.judul,
                                              style: TextStyle(
                                                fontSize: 13.5,
                                                fontWeight: FontWeight.w700,
                                                color: notif.isRead
                                                    ? AppColors.textMain
                                                    : AppColors.primaryDark,
                                              ),
                                            ),
                                          ),
                                          if (!notif.isRead)
                                            Container(
                                              width: 8,
                                              height: 8,
                                              decoration: const BoxDecoration(
                                                color: AppColors.primary,
                                                shape: BoxShape.circle,
                                              ),
                                            ),
                                        ],
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        notif.pesan,
                                        style: const TextStyle(
                                          fontSize: 12,
                                          color: AppColors.textMuted,
                                          height: 1.4,
                                        ),
                                      ),
                                      const SizedBox(height: 8),
                                      Wrap(
                                        spacing: 10,
                                        runSpacing: 4,
                                        crossAxisAlignment:
                                            WrapCrossAlignment.center,
                                        children: [
                                          Row(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              const Icon(
                                                Icons.access_time_rounded,
                                                size: 12,
                                                color: AppColors.textLight,
                                              ),
                                              const SizedBox(width: 4),
                                              Text(
                                                formatTimestamp(
                                                    notif.createdAt),
                                                style: const TextStyle(
                                                  fontSize: 11,
                                                  color: AppColors.textLight,
                                                ),
                                              ),
                                            ],
                                          ),
                                          Text(
                                            _keterangan(notif.type),
                                            style: TextStyle(
                                              fontSize: 11,
                                              fontWeight: FontWeight.w600,
                                              color: color,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
    );
  }
}
