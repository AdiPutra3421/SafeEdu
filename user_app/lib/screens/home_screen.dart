import 'dart:async';
import 'package:flutter/material.dart';
import '../models/catalog_model.dart';
import '../services/api_config.dart';
import '../services/auth_service.dart';
import '../services/notification_service.dart';
import '../theme/app_theme.dart';
import 'notifikasi_screen.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class HomeScreen extends StatefulWidget {
  final Function(int) onNavigateToTab;

  const HomeScreen({super.key, required this.onNavigateToTab});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  String selectedCatalogFilter = 'Semua';

  final List<CampusNews> _newsList = [];

  List<dynamic> _facilities = [];
  bool _isLoading = false;

  int _unreadCount = 0;
  Timer? _notifTimer;

  @override
  void initState() {
    super.initState();
    _fetchFacilities();
    _fetchNews();
    _fetchUnreadCount();
    // Polling notifikasi setiap 30 detik saat app aktif
    _notifTimer = Timer.periodic(
      const Duration(seconds: 30),
      (_) => _fetchUnreadCount(),
    );
  }

  @override
  void dispose() {
    _notifTimer?.cancel();
    super.dispose();
  }

  Future<void> _fetchUnreadCount() async {
    final userId = AuthService.instance.userId;
    if (userId == null) return;
    final count = await NotificationService.fetchUnreadCount(userId);
    if (!mounted) return;
    setState(() => _unreadCount = count);
  }

  Future<void> _openNotifikasi() async {
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => const NotifikasiScreen()),
    );
    _fetchUnreadCount();
  }

  Future<void> _fetchFacilities() async {
    setState(() => _isLoading = true);
    try {
      final res =
          await http.get(Uri.parse('${ApiConfig.baseUrl}/api/fasilitas'));
      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (data['success'] == true) {
          setState(() {
            _facilities = data['data'];
          });
        }
      }
    } catch (e) {
      print('Error: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _fetchNews() async {
    try {
      final res =
          await http.get(Uri.parse('${ApiConfig.baseUrl}/api/konten-sekolah'));
      if (res.statusCode != 200) return;
      final data = json.decode(res.body);
      if (data['success'] != true || data['data'] is! List || !mounted) return;
      setState(() {
        _newsList
          ..clear()
          ..addAll(
              (data['data'] as List).map((item) => CampusNews.fromMap(item)));
      });
    } catch (e) {
      print('Error fetching school content: $e');
    }
  }

  List<CampusFacilityCatalog> get filteredFacilities {
    final mapped = _facilities.map((f) {
      String statusKondisi = f['status_kondisi'] ?? 'Bisa Digunakan';

      return CampusFacilityCatalog(
        id: f['id'].toString(),
        name: f['nama'] ?? '-',
        category: 'Umum',
        location: f['lokasi'] ?? '-',
        operatingHours: '07:30 - 15:30 WIB',
        status: statusKondisi,
        iconType: 'book',
        description: f['deskripsi'] ?? '-',
      );
    }).toList();
    if (selectedCatalogFilter == 'Semua') return mapped;
    return mapped;
  }

  void _showFacilityDetail(CampusFacilityCatalog item) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          padding: const EdgeInsets.all(24),
          decoration: const BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 44,
                  height: 4,
                  decoration: BoxDecoration(
                    color: AppColors.border,
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: item.status == 'Bisa Digunakan'
                          ? AppColors.statusSuccessBg
                          : (item.status == 'Tidak Bisa Digunakan'
                              ? AppColors.statusDangerBg
                              : AppColors.statusPendingBg),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      item.status,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: item.status == 'Bisa Digunakan'
                            ? AppColors.statusSuccessText
                            : (item.status == 'Tidak Bisa Digunakan'
                                ? AppColors.statusDangerText
                                : AppColors.statusPendingText),
                      ),
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: AppColors.textMuted),
                    onPressed: () => Navigator.pop(ctx),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                item.name,
                style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textMain),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.location_on_outlined,
                      size: 16, color: AppColors.textMuted),
                  const SizedBox(width: 4),
                  Text(item.location,
                      style: const TextStyle(
                          fontSize: 13, color: AppColors.textMuted)),
                ],
              ),
              const SizedBox(height: 6),
              Row(
                children: [
                  const Icon(Icons.access_time_rounded,
                      size: 16, color: AppColors.textMuted),
                  const SizedBox(width: 4),
                  Text(item.operatingHours,
                      style: const TextStyle(
                          fontSize: 13, color: AppColors.textMuted)),
                ],
              ),
              const SizedBox(height: 16),
              Text(
                item.description,
                style: const TextStyle(
                    fontSize: 13.5,
                    color: AppColors.secondaryMuted,
                    height: 1.5),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  icon: const Icon(Icons.report_problem_outlined, size: 18),
                  label: const Text('Laporkan Kerusakan pada Fasilitas Ini'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                  ),
                  onPressed: () {
                    Navigator.pop(ctx);
                    widget.onNavigateToTab(1); // switch to Tab 1 (Pelaporan)
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(7),
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.shield_outlined,
                  color: Colors.white, size: 20),
            ),
            const SizedBox(width: 10),
            const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('SafeEdu Siswa',
                    style:
                        TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
                Text('Portal Pelaporan & Keselamatan',
                    style: TextStyle(
                        fontSize: 11,
                        color: AppColors.textMuted,
                        fontWeight: FontWeight.normal)),
              ],
            ),
          ],
        ),
        actions: [
          Stack(
            clipBehavior: Clip.none,
            children: [
              IconButton(
                icon: const Icon(Icons.notifications_none_rounded,
                    color: AppColors.textMain),
                onPressed: _openNotifikasi,
              ),
              if (_unreadCount > 0)
                Positioned(
                  right: 2,
                  top: 2,
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                    decoration: BoxDecoration(
                      color: const Color(0xFFE11D48),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    constraints: const BoxConstraints(minWidth: 16),
                    child: Text(
                      _unreadCount > 99 ? '99+' : '$_unreadCount',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _fetchFacilities,
        color: AppColors.primary,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Student Welcome & Quick Card
              Padding(
                padding: const EdgeInsets.all(16),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFF1E3A8A), Color(0xFF2563EB)],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(18),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.primary.withValues(alpha: 0.3),
                        blurRadius: 12,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Halo, Budi Siswa',
                                style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 18,
                                    fontWeight: FontWeight.w700),
                              ),
                              SizedBox(height: 2),
                              Text(
                                'NISN: 20240911 • XII RPL 1',
                                style: TextStyle(
                                    color: Color(0xFFBFDBFE), fontSize: 12),
                              ),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 10, vertical: 5),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: const Row(
                              children: [
                                Icon(Icons.check_circle,
                                    color: Color(0xFF4ADE80), size: 14),
                                SizedBox(width: 4),
                                Text('Aktif',
                                    style: TextStyle(
                                        color: Colors.white,
                                        fontSize: 11,
                                        fontWeight: FontWeight.w600)),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 18),
                      const Text(
                        'Menemukan kerusakan sarana atau pelanggaran etika di sekolah?',
                        style: TextStyle(
                            color: Color(0xFFE2E8F0),
                            fontSize: 13,
                            height: 1.4),
                      ),
                      const SizedBox(height: 14),
                      ElevatedButton.icon(
                        icon: const Icon(Icons.campaign_rounded,
                            size: 18, color: AppColors.primaryDark),
                        label: const Text('Buat Laporan Baru',
                            style: TextStyle(
                                color: AppColors.primaryDark,
                                fontWeight: FontWeight.w700)),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(
                              horizontal: 18, vertical: 12),
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: () => widget.onNavigateToTab(1),
                      ),
                    ],
                  ),
                ),
              ),

              // Section: Berita & Panduan Edukasi
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Berita & Panduan Sekolah',
                      style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: AppColors.textMain),
                    ),
                    TextButton(
                      onPressed: () {},
                      child: const Text('Lihat Semua',
                          style: TextStyle(
                              fontSize: 12,
                              color: AppColors.primary,
                              fontWeight: FontWeight.w600)),
                    ),
                  ],
                ),
              ),

              SizedBox(
                height: 180,
                child: ListView.separated(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  scrollDirection: Axis.horizontal,
                  itemCount: _newsList.length,
                  separatorBuilder: (_, __) => const SizedBox(width: 12),
                  itemBuilder: (context, index) {
                    final news = _newsList[index];
                    final isEthics = news.category == 'Kode Etik';
                    return Container(
                      width: 290,
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppColors.border),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.03),
                            blurRadius: 6,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: isEthics
                                      ? AppColors.ethicsBg
                                      : AppColors.facilityBg,
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  news.category,
                                  style: TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.w700,
                                    color: isEthics
                                        ? AppColors.ethicsText
                                        : AppColors.facilityText,
                                  ),
                                ),
                              ),
                              Text(news.date,
                                  style: const TextStyle(
                                      fontSize: 11,
                                      color: AppColors.textLight)),
                            ],
                          ),
                          const SizedBox(height: 10),
                          Text(
                            news.title,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                                color: AppColors.textMain,
                                height: 1.3),
                          ),
                          const Spacer(),
                          Text(
                            news.summary,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                                fontSize: 11, color: AppColors.textMuted),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),

              const SizedBox(height: 24),

              // Section: Katalog Fasilitas & Layanan
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 16),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Katalog Fasilitas & Sarana',
                      style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: AppColors.textMain),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 12),

              // Filter chips for catalog
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Row(
                  children: [
                    _buildCatalogFilterChip('Semua'),
                    const SizedBox(width: 8),
                    _buildCatalogFilterChip('Akademik'),
                    const SizedBox(width: 8),
                    _buildCatalogFilterChip('Olahraga'),
                    const SizedBox(width: 8),
                    _buildCatalogFilterChip('Layanan'),
                  ],
                ),
              ),

              const SizedBox(height: 12),

              // Facility List
              ListView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                padding: const EdgeInsets.symmetric(horizontal: 16),
                itemCount: filteredFacilities.length,
                itemBuilder: (context, index) {
                  final item = filteredFacilities[index];
                  IconData leadingIcon = Icons.domain;
                  if (item.iconType == 'computer') leadingIcon = Icons.computer;
                  if (item.iconType == 'book') {
                    leadingIcon = Icons.menu_book_rounded;
                  }
                  if (item.iconType == 'sports') {
                    leadingIcon = Icons.sports_basketball_rounded;
                  }
                  if (item.iconType == 'counseling') {
                    leadingIcon = Icons.favorite_outline_rounded;
                  }
                  if (item.iconType == 'sanitation') {
                    leadingIcon = Icons.water_drop_outlined;
                  }

                  return Card(
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 6),
                      leading: Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: AppColors.primaryLight,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Icon(leadingIcon,
                            color: AppColors.primary, size: 22),
                      ),
                      title: Text(
                        item.name,
                        style: const TextStyle(
                            fontWeight: FontWeight.w700, fontSize: 14),
                      ),
                      subtitle: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const SizedBox(height: 3),
                          Text('📍 ${item.location}',
                              style: const TextStyle(
                                  fontSize: 12, color: AppColors.textMuted)),
                          const SizedBox(height: 2),
                          Text('🕒 ${item.operatingHours}',
                              style: const TextStyle(
                                  fontSize: 11, color: AppColors.textLight)),
                        ],
                      ),
                      trailing: Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: item.status == 'Bisa Digunakan'
                              ? AppColors.statusSuccessBg
                              : (item.status == 'Tidak Bisa Digunakan'
                                  ? AppColors.statusDangerBg
                                  : AppColors.statusPendingBg),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          item.status,
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: item.status == 'Bisa Digunakan'
                                ? AppColors.statusSuccessText
                                : (item.status == 'Tidak Bisa Digunakan'
                                    ? AppColors.statusDangerText
                                    : AppColors.statusPendingText),
                          ),
                        ),
                      ),
                      onTap: () => _showFacilityDetail(item),
                    ),
                  );
                },
              ),

              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCatalogFilterChip(String label) {
    final isSelected = selectedCatalogFilter == label;
    return GestureDetector(
      onTap: () => setState(() => selectedCatalogFilter = label),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.secondary : AppColors.surface,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
              color: isSelected ? AppColors.secondary : AppColors.border),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? Colors.white : AppColors.textMain,
            fontSize: 12,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
    );
  }
}
