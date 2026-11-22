import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../models/catalog_model.dart';
import '../services/api_config.dart';
import '../services/auth_service.dart';
import '../services/notification_service.dart';
import '../theme/app_theme.dart';
import 'notifikasi_screen.dart';
import 'school_content_screen.dart';

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
      debugPrint('Error: $e');
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
      debugPrint('Error fetching school content: $e');
    }
  }

  String? _getFacilityImageUrl(String facilityId) {
    for (final f in _facilities) {
      if (f['id'].toString() == facilityId) {
        final foto = f['foto_fasilitas']?.toString();
        if (foto != null && foto.isNotEmpty) {
          if (foto.startsWith('http://') || foto.startsWith('https://')) {
            return foto;
          }
          final cleanFoto = foto
              .replaceFirst(RegExp(r'^/+'), '')
              .replaceFirst(RegExp(r'^storage/'), '');
          return '${ApiConfig.baseUrl}/api/gambar/$cleanFoto';
        }
      }
    }
    return null;
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
    final imageUrl = _getFacilityImageUrl(item.id);
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        final screenSize = MediaQuery.sizeOf(ctx);
        final imageHeight = (screenSize.height * 0.25).clamp(150.0, 260.0);

        return Center(
          heightFactor: 1,
          child: ConstrainedBox(
            constraints: BoxConstraints(
              maxWidth: 600,
              maxHeight: screenSize.height * 0.85,
            ),
            child: Container(
              padding: const EdgeInsets.all(24),
              decoration: const BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: SingleChildScrollView(
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
                          child: Text(
                            item.name,
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: AppColors.textMain,
                            ),
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close,
                              color: AppColors.textMuted),
                          onPressed: () => Navigator.pop(ctx),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: imageUrl != null && imageUrl.isNotEmpty
                          ? Image.network(
                              imageUrl,
                              height: imageHeight,
                              width: double.infinity,
                              fit: BoxFit.cover,
                              errorBuilder: (_, __, ___) => Container(
                                height: imageHeight,
                                width: double.infinity,
                                color: AppColors.facilityBg,
                                child: const Center(
                                  child: Icon(Icons.business_rounded,
                                      size: 50, color: AppColors.facilityText),
                                ),
                              ),
                            )
                          : Container(
                              height: imageHeight,
                              width: double.infinity,
                              color: AppColors.facilityBg,
                              child: const Center(
                                child: Icon(Icons.business_rounded,
                                    size: 50, color: AppColors.facilityText),
                              ),
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
                    const SizedBox(height: 16),
                  ],
                ),
              ),
            ),
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
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    'SafeEdu',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  Text(
                    'Portal Pelaporan & Keselamatan',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 11,
                      color: AppColors.textMuted,
                      fontWeight: FontWeight.normal,
                    ),
                  ),
                ],
              ),
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
                    borderRadius: BorderRadius.circular(22),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.primary.withValues(alpha: 0.22),
                        blurRadius: 20,
                        offset: const Offset(0, 8),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Halo, ${AuthService.instance.nama ?? 'User'}',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 18,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(height: 3),
                                Text(
                                  AuthService.instance.email ?? '-',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: Color(0xFFBFDBFE),
                                    fontSize: 12,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 10),
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
                              borderRadius: BorderRadius.circular(14)),
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
                              borderRadius: BorderRadius.circular(18),
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
                              borderRadius: BorderRadius.circular(18),
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

              // GridView Responsif menyesuaikan lebar layar perangkat
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: LayoutBuilder(
                  builder: (context, constraints) {
                    final width = constraints.maxWidth;
                    final crossAxisCount = width >= 900
                        ? 4
                        : width >= 600
                            ? 3
                            : 2;
                    final isCompact = width < 360;

                    if (filteredFacilities.isEmpty) {
                      return Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(vertical: 32),
                        alignment: Alignment.center,
                        child: const Text(
                          'Belum ada data fasilitas & sarana',
                          style: TextStyle(
                            fontSize: 12,
                            color: AppColors.textLight,
                          ),
                        ),
                      );
                    }

                    return GridView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: crossAxisCount,
                        crossAxisSpacing: 12,
                        mainAxisSpacing: 12,
                        childAspectRatio: width >= 600 ? 1.2 : 1.1,
                      ),
                      itemCount: filteredFacilities.length,
                      itemBuilder: (context, index) {
                        final item = filteredFacilities[index];
                        final imageUrl = _getFacilityImageUrl(item.id);

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
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(14),
                                  child: imageUrl != null && imageUrl.isNotEmpty
                                      ? Image.network(
                                          imageUrl,
                                          width: double.infinity,
                                          height: double.infinity,
                                          fit: BoxFit.cover,
                                          errorBuilder: (_, __, ___) =>
                                              Container(
                                            width: double.infinity,
                                            height: double.infinity,
                                            color: AppColors.facilityBg,
                                            child: const Icon(
                                              Icons.business_rounded,
                                              size: 40,
                                              color: AppColors.facilityText,
                                            ),
                                          ),
                                        )
                                      : Container(
                                          width: double.infinity,
                                          height: double.infinity,
                                          color: AppColors.facilityBg,
                                          child: const Icon(
                                            Icons.business_rounded,
                                            size: 40,
                                            color: AppColors.facilityText,
                                          ),
                                        ),
                                ),
                                Positioned.fill(
                                  child: DecoratedBox(
                                    decoration: BoxDecoration(
                                      borderRadius: BorderRadius.circular(14),
                                      gradient: LinearGradient(
                                        begin: Alignment.topCenter,
                                        end: Alignment.bottomCenter,
                                        colors: [
                                          Colors.transparent,
                                          Colors.black.withValues(alpha: 0.6),
                                        ],
                                      ),
                                    ),
                                  ),
                                ),
                                Positioned(
                                  left: 10,
                                  right: 10,
                                  bottom: 12,
                                  child: Text(
                                    item.name,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: TextStyle(
                                      color: Colors.white,
                                      fontWeight: FontWeight.w700,
                                      fontSize: isCompact ? 12 : 13,
                                      height: 1.2,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    );
                  },
                ),
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
