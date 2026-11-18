import 'dart:async';
import 'package:flutter/material.dart';
import '../models/catalog_model.dart';
import '../services/api_config.dart';
import '../services/auth_service.dart';
import '../services/notification_service.dart';
import '../theme/app_theme.dart';
import 'notifikasi_screen.dart';
import 'school_content_screen.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class HomeScreen extends StatefulWidget {
  final Function(int) onNavigateToTab;

  const HomeScreen({super.key, required this.onNavigateToTab});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final List<CampusNews> _newsList = [];

  List<dynamic> _facilities = [];

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
    try {
      final res =
          await http.get(Uri.parse('${ApiConfig.baseUrl}/api/fasilitas'));
      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (data['success'] == true && mounted) {
          setState(() {
            _facilities = data['data'];
          });
        }
      }
    } catch (e) {
      print('Error: $e');
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
    return _facilities.map((f) {
      String statusKondisi = f['status_kondisi'] ?? 'Bisa Digunakan';

      final foto = f['foto_fasilitas']?.toString();

      return CampusFacilityCatalog(
        id: f['id'].toString(),
        name: f['nama'] ?? '-',
        category: 'Umum',
        location: f['lokasi'] ?? '-',
        operatingHours: '07:30 - 15:30 WIB',
        status: statusKondisi,
        iconType: 'image',
        description: f['deskripsi'] ?? '-',
        imageUrl: foto != null && foto.isNotEmpty
            ? '${ApiConfig.baseUrl}/api/gambar/$foto'
            : null,
      );
    }).toList();
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
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item.name,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textMain,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 4,
                          ),
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
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: AppColors.textMuted),
                    onPressed: () => Navigator.pop(ctx),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: item.imageUrl != null && item.imageUrl!.isNotEmpty
                    ? Image.network(
                        item.imageUrl!,
                        height: 220,
                        width: double.infinity,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => const SizedBox(
                          height: 220,
                          width: double.infinity,
                        ),
                      )
                    : const SizedBox(
                        height: 220,
                        width: double.infinity,
                      ),
              ),
              const SizedBox(height: 20),
              const Text(
                'Deskripsi',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 14,
                  color: AppColors.textMain,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                item.description.isNotEmpty
                    ? item.description
                    : 'Tidak ada deskripsi tambahan.',
                style: const TextStyle(
                  fontSize: 14,
                  height: 1.5,
                  color: AppColors.textMain,
                ),
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
                    widget.onNavigateToTab(1);
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
                Text('SafeEdu',
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
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Halo, ${AuthService.instance.nama ?? 'User'}',
                                style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 18,
                                    fontWeight: FontWeight.w700),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                AuthService.instance.email ?? '-',
                                style: const TextStyle(
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
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => SchoolContentScreen(
                              news: List.unmodifiable(_newsList),
                              onNewsTap: _showNewsDetail,
                            ),
                          ),
                        );
                      },
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
                height: 220,
                child: _newsList.isEmpty
                    ? const Center(
                        child: Text(
                          'Belum ada berita & panduan sekolah',
                          style: TextStyle(
                              fontSize: 12, color: AppColors.textLight),
                        ),
                      )
                    : ListView.separated(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        scrollDirection: Axis.horizontal,
                        itemCount: _newsList.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 12),
                        itemBuilder: (context, index) {
                          final news = _newsList[index];
                          final isPanduan = news.category == 'Panduan';
                          return Container(
                            width: 270,
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
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(14),
                              child: Material(
                                color: Colors.transparent,
                                child: InkWell(
                                  onTap: () => _showNewsDetail(context, news),
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      SizedBox(
                                        height: 105,
                                        width: double.infinity,
                                        child: (news.imageUrl != null &&
                                                news.imageUrl!.isNotEmpty)
                                            ? Image.network(
                                                news.imageUrl!,
                                                fit: BoxFit.cover,
                                                errorBuilder: (_, __, ___) =>
                                                    _buildFallbackBanner(news),
                                              )
                                            : _buildFallbackBanner(news),
                                      ),
                                      Expanded(
                                        child: Padding(
                                          padding: const EdgeInsets.all(12),
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              Row(
                                                mainAxisAlignment:
                                                    MainAxisAlignment
                                                        .spaceBetween,
                                                children: [
                                                  Container(
                                                    padding: const EdgeInsets
                                                        .symmetric(
                                                        horizontal: 8,
                                                        vertical: 3),
                                                    decoration: BoxDecoration(
                                                      color: isPanduan
                                                          ? AppColors.ethicsBg
                                                          : AppColors
                                                              .facilityBg,
                                                      borderRadius:
                                                          BorderRadius.circular(
                                                              6),
                                                    ),
                                                    child: Text(
                                                      news.category,
                                                      style: TextStyle(
                                                        fontSize: 10,
                                                        fontWeight:
                                                            FontWeight.w700,
                                                        color: isPanduan
                                                            ? AppColors
                                                                .ethicsText
                                                            : AppColors
                                                                .facilityText,
                                                      ),
                                                    ),
                                                  ),
                                                  Text(
                                                    news.date,
                                                    style: const TextStyle(
                                                        fontSize: 10.5,
                                                        color: AppColors
                                                            .textLight),
                                                  ),
                                                ],
                                              ),
                                              const SizedBox(height: 6),
                                              Text(
                                                news.title,
                                                maxLines: 2,
                                                overflow: TextOverflow.ellipsis,
                                                style: const TextStyle(
                                                  fontSize: 12.5,
                                                  fontWeight: FontWeight.w700,
                                                  color: AppColors.textMain,
                                                  height: 1.25,
                                                ),
                                              ),
                                              const Spacer(),
                                              Text(
                                                news.summary,
                                                maxLines: 1,
                                                overflow: TextOverflow.ellipsis,
                                                style: const TextStyle(
                                                    fontSize: 10.5,
                                                    color: AppColors.textMuted),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          );
                        },
                      ),
              ),

              const SizedBox(height: 24),

              // Section: Katalog Fasilitas & Sarana
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

              const SizedBox(height: 16),

              // REVISI: Filter kategori dihapus.
              // Mengubah ListView menjadi GridView (2 kolom) untuk tampilan "kotak-kotak"
              GridView.builder(
                shrinkWrap:
                    true, // Agar tidak error di dalam SingleChildScrollView
                physics: const NeverScrollableScrollPhysics(),
                padding: const EdgeInsets.symmetric(horizontal: 16),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2, // 2 kotak berjejer ke samping
                  crossAxisSpacing: 12, // Jarak antar kotak ke samping
                  mainAxisSpacing: 12, // Jarak antar kotak ke bawah
                  childAspectRatio: 1.1, // Rasio lebar dibanding tinggi kotak
                ),
                itemCount: filteredFacilities.length,
                itemBuilder: (context, index) {
                  final item = filteredFacilities[index];

                  // Widget kotak fasilitas
                  return GestureDetector(
                    onTap: () => _showFacilityDetail(item),
                    child: Container(
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppColors.border),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.04),
                            blurRadius: 4,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: Stack(
                        children: [
                          // 1. Latar Belakang Kotak (Gambar atau Warna Fallback)
                          // 1. Latar Belakang Kotak (Gambar atau Warna Fallback)
                          ClipRRect(
                            borderRadius: BorderRadius.circular(14),
                            child: item.imageUrl != null
                                ? Image.network(
                                    item.imageUrl!,
                                    width: double.infinity,
                                    height: double.infinity,
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, __, ___) => Container(
                                      color: Colors.grey[300],
                                      child: const Icon(
                                        Icons.image_not_supported,
                                        size: 40,
                                        color: Colors.grey,
                                      ),
                                    ),
                                  )
                                : Container(
                                    color: Colors.grey[300],
                                    child: const Icon(
                                      Icons.business_rounded,
                                      size: 40,
                                      color: Colors.grey,
                                    ),
                                  ), // Background abu-abu default
                          ),

                          // 3. Status Badge di Pojok Kanan Atas
                          Positioned(
                            top: 8,
                            right: 8,
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 6, vertical: 3),
                              decoration: BoxDecoration(
                                color: item.status == 'Bisa Digunakan'
                                    ? AppColors.statusSuccessBg
                                        .withValues(alpha: 0.9)
                                    : (item.status == 'Tidak Bisa Digunakan'
                                        ? AppColors.statusDangerBg
                                            .withValues(alpha: 0.9)
                                        : AppColors.statusPendingBg
                                            .withValues(alpha: 0.9)),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                item.status,
                                style: TextStyle(
                                  fontSize: 9,
                                  fontWeight: FontWeight.w700,
                                  color: item.status == 'Bisa Digunakan'
                                      ? AppColors.statusSuccessText
                                      : (item.status == 'Tidak Bisa Digunakan'
                                          ? AppColors.statusDangerText
                                          : AppColors.statusPendingText),
                                ),
                              ),
                            ),
                          ),

                          // 4. Nama Fasilitas di Kiri Bawah
                          Positioned(
                            left: 10,
                            right: 10,
                            bottom: 12,
                            child: Text(
                              item.name,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w700,
                                fontSize: 13,
                                height: 1.2,
                              ),
                            ),
                          ),
                        ],
                      ),
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

  Widget _buildFallbackBanner(CampusNews news) {
    final isPanduan = news.category == 'Panduan';
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: isPanduan
              ? [const Color(0xFFE0E7FF), const Color(0xFFC7D2FE)]
              : [const Color(0xFFDCFCE7), const Color(0xFFBBF7D0)],
        ),
      ),
      child: Center(
        child: Icon(
          isPanduan ? Icons.menu_book_rounded : Icons.campaign_rounded,
          size: 36,
          color: isPanduan ? const Color(0xFF4338CA) : const Color(0xFF15803D),
        ),
      ),
    );
  }

  void _showNewsDetail(BuildContext context, CampusNews news) {
    final isPanduan = news.category == 'Panduan';
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.75,
        maxChildSize: 0.95,
        minChildSize: 0.5,
        builder: (_, scrollController) => Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: ListView(
            controller: scrollController,
            padding: const EdgeInsets.all(20),
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              if (news.imageUrl != null && news.imageUrl!.isNotEmpty) ...[
                ConstrainedBox(
                  constraints: const BoxConstraints(maxHeight: 360),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: Image.network(
                      news.imageUrl!,
                      width: double.infinity,
                      fit: BoxFit.contain,
                      errorBuilder: (_, __, ___) => _buildFallbackBanner(news),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
              ],
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color:
                          isPanduan ? AppColors.ethicsBg : AppColors.facilityBg,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      news.category,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: isPanduan
                            ? AppColors.ethicsText
                            : AppColors.facilityText,
                      ),
                    ),
                  ),
                  Text(news.date,
                      style: const TextStyle(
                          fontSize: 12, color: AppColors.textLight)),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                news.title,
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textMain,
                  height: 1.3,
                ),
              ),
              const Divider(height: 24),
              Text(
                news.fullContent.isNotEmpty ? news.fullContent : news.summary,
                style: const TextStyle(
                  fontSize: 14,
                  color: AppColors.textMain,
                  height: 1.6,
                ),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }
}
