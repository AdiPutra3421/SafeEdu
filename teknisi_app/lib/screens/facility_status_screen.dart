import 'package:flutter/material.dart';
import '../services/api_config.dart';
import '../services/auth_service.dart';
import '../theme/app_theme.dart';
import 'auth/login_screen.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class FacilityStatusScreen extends StatefulWidget {
  const FacilityStatusScreen({super.key});

  @override
  State<FacilityStatusScreen> createState() => _FacilityStatusScreenState();
}

class _FacilityStatusScreenState extends State<FacilityStatusScreen> {
  List<dynamic> _facilities = [];
  bool _isLoading = false;
  String searchQuery = '';

  @override
  void initState() {
    super.initState();
    _fetchFacilities();
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

  List<dynamic> get filteredFacilities {
    if (searchQuery.isEmpty) return _facilities;
    return _facilities
        .where((f) =>
            (f['nama'] ?? '')
                .toLowerCase()
                .contains(searchQuery.toLowerCase()) ||
            (f['lokasi'] ?? '')
                .toLowerCase()
                .contains(searchQuery.toLowerCase()))
        .toList();
  }

  Future<void> _updateKondisi(dynamic facility, String newKondisi) async {
    final facilityId = facility['id'];
    try {
      final res = await http.put(
        Uri.parse('${ApiConfig.baseUrl}/api/fasilitas/$facilityId'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'status_kondisi': newKondisi,
        }),
      );

      if (res.statusCode == 200) {
        setState(() {
          facility['status_kondisi'] = newKondisi;
        });
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Status fasilitas berhasil diperbarui di server'),
            backgroundColor: AppColors.statusSuccessText,
          ),
        );
      } else {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Gagal memperbarui status fasilitas di server'),
            backgroundColor: AppColors.statusDangerText,
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error: $e'),
          backgroundColor: AppColors.statusDangerText,
        ),
      );
    }
  }

  Future<void> _confirmLogout(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.logout_rounded, color: AppColors.statusDangerText),
            SizedBox(width: 10),
            Text(
              'Konfirmasi Keluar',
              style: TextStyle(fontWeight: FontWeight.w700, fontSize: 17),
            ),
          ],
        ),
        content: const Text(
          'Apakah Anda yakin ingin keluar dari akun Teknisi? Anda perlu login kembali untuk menerima dan memproses penugasan.',
          style: TextStyle(fontSize: 13.5, color: AppColors.textMuted),
        ),
        actionsPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        actions: [
          OutlinedButton(
            onPressed: () => Navigator.pop(ctx, false),
            style: OutlinedButton.styleFrom(
              side: const BorderSide(color: AppColors.border),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Batal',
                style: TextStyle(color: AppColors.textMain)),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.statusDangerText,
              foregroundColor: Colors.white,
              elevation: 0,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Ya, Keluar',
                style: TextStyle(fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      AuthService.instance.logout();
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => const LoginScreen()),
        (route) => false,
      );
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Anda telah berhasil keluar dari akun Teknisi.'),
          backgroundColor: AppColors.secondary,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: const Text('Status Fasilitas'),
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout_rounded,
                color: AppColors.statusDangerText),
            tooltip: 'Keluar dari Akun Teknisi',
            onPressed: () => _confirmLogout(context),
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 4),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Kondisi fasilitas',
                  style: TextStyle(
                    color: AppColors.textMain,
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Cari fasilitas dan perbarui status kondisinya.',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: AppColors.textMuted,
                      ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 16),
            child: TextField(
              decoration: const InputDecoration(
                hintText: 'Cari fasilitas atau lokasi',
                prefixIcon: Icon(Icons.search_rounded),
              ),
              onChanged: (value) => setState(() => searchQuery = value),
            ),
          ),
          Expanded(
            child: _isLoading
                ? const Center(
                    child: CircularProgressIndicator(),
                  )
                : filteredFacilities.isEmpty
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
                                  Icons.search_off_rounded,
                                  color: AppColors.primary,
                                  size: 34,
                                ),
                              ),
                              const SizedBox(height: 16),
                              const Text(
                                'Fasilitas tidak ditemukan',
                                style: TextStyle(
                                  color: AppColors.textMain,
                                  fontSize: 16,
                                  fontWeight: FontWeight.w700,
                                ),
                                textAlign: TextAlign.center,
                              ),
                              const SizedBox(height: 6),
                              const Text(
                                'Coba kata kunci atau lokasi yang berbeda.',
                                style: TextStyle(
                                  color: AppColors.textMuted,
                                  fontSize: 13,
                                ),
                                textAlign: TextAlign.center,
                              ),
                            ],
                          ),
                        ),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: filteredFacilities.length,
                        itemBuilder: (context, index) {
                          final facility = filteredFacilities[index];
                          final isOk =
                              facility['status_kondisi'] == 'Bisa Digunakan';
                          final status = facility['status_kondisi'] ?? '-';
                          final statusColor = isOk
                              ? AppColors.statusSuccessText
                              : status == 'Dalam Perbaikan'
                                  ? AppColors.statusProcessText
                                  : AppColors.statusDangerText;
                          final statusBackground = isOk
                              ? AppColors.statusSuccessBg
                              : status == 'Dalam Perbaikan'
                                  ? AppColors.statusProcessBg
                                  : AppColors.statusDangerBg;

                          return Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            child: Padding(
                              padding: const EdgeInsets.all(16),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Container(
                                        width: 42,
                                        height: 42,
                                        decoration: BoxDecoration(
                                          color: AppColors.primaryLight,
                                          borderRadius:
                                              BorderRadius.circular(13),
                                        ),
                                        child: const Icon(
                                          Icons.apartment_rounded,
                                          color: AppColors.primary,
                                          size: 21,
                                        ),
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              facility['nama'] ?? '-',
                                              style: const TextStyle(
                                                fontSize: 15,
                                                fontWeight: FontWeight.w700,
                                                color: AppColors.textMain,
                                              ),
                                            ),
                                            const SizedBox(height: 5),
                                            Row(
                                              children: [
                                                const Icon(
                                                  Icons.location_on_outlined,
                                                  size: 15,
                                                  color: AppColors.textMuted,
                                                ),
                                                const SizedBox(width: 4),
                                                Expanded(
                                                  child: Text(
                                                    facility['lokasi'] ?? '-',
                                                    maxLines: 2,
                                                    overflow:
                                                        TextOverflow.ellipsis,
                                                    style: const TextStyle(
                                                      fontSize: 12,
                                                      color:
                                                          AppColors.textMuted,
                                                    ),
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ],
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Container(
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 10,
                                          vertical: 6,
                                        ),
                                        decoration: BoxDecoration(
                                          color: statusBackground,
                                          borderRadius:
                                              BorderRadius.circular(20),
                                        ),
                                        child: ConstrainedBox(
                                          constraints: const BoxConstraints(
                                              maxWidth: 140),
                                          child: Text(
                                            status,
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            style: TextStyle(
                                              fontSize: 11,
                                              fontWeight: FontWeight.w700,
                                              color: statusColor,
                                            ),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 16),
                                  Row(
                                    children: [
                                      const Expanded(
                                        child: Text(
                                          'Ubah status',
                                          style: TextStyle(
                                            color: AppColors.textMuted,
                                            fontSize: 12,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                      ),
                                      Expanded(
                                        flex: 2,
                                        child: InputDecorator(
                                          decoration: const InputDecoration(
                                            isDense: true,
                                            contentPadding:
                                                EdgeInsets.symmetric(
                                              horizontal: 12,
                                              vertical: 4,
                                            ),
                                          ),
                                          child: DropdownButtonHideUnderline(
                                            child: DropdownButton<String>(
                                              value: <String>[
                                                'Bisa Digunakan',
                                                'Dalam Perbaikan',
                                                'Tidak Bisa Digunakan',
                                              ].contains(facility[
                                                      'status_kondisi'])
                                                  ? facility['status_kondisi']
                                                  : 'Bisa Digunakan',
                                              isExpanded: true,
                                              items: const [
                                                DropdownMenuItem(
                                                  value: 'Bisa Digunakan',
                                                  child: Text('Bisa Digunakan'),
                                                ),
                                                DropdownMenuItem(
                                                  value: 'Dalam Perbaikan',
                                                  child:
                                                      Text('Dalam Perbaikan'),
                                                ),
                                                DropdownMenuItem(
                                                  value: 'Tidak Bisa Digunakan',
                                                  child: Text(
                                                      'Tidak Bisa Digunakan'),
                                                ),
                                              ],
                                              onChanged: (newValue) {
                                                if (newValue != null &&
                                                    newValue !=
                                                        facility[
                                                            'status_kondisi']) {
                                                  _updateKondisi(
                                                      facility, newValue);
                                                }
                                              },
                                            ),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }
}
