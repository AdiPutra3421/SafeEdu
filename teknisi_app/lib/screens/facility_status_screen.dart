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
  static const _conditionOptions = [
    'Bisa Digunakan',
    'Dalam Perbaikan',
    'Tidak Bisa Digunakan',
  ];

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
          // Search Bar
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: TextField(
              decoration: InputDecoration(
                hintText: 'Cari fasilitas atau lokasi...',
                prefixIcon:
                    const Icon(Icons.search, color: AppColors.textMuted),
                filled: true,
                fillColor: AppColors.surface,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
              ),
              onChanged: (val) {
                setState(() {
                  searchQuery = val;
                });
              },
            ),
          ),

          // List of Facilities
          Expanded(
            child: filteredFacilities.isEmpty
                ? const Center(
                    child: Text(
                      'Tidak ada fasilitas ditemukan.',
                      style: TextStyle(color: AppColors.textMuted),
                    ),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: _isLoading ? 0 : filteredFacilities.length,
                    itemBuilder: (context, index) {
                      final facility = filteredFacilities[index];
                      final currentCondition = facility['status_kondisi'];
                      final selectedCondition =
                          _conditionOptions.contains(currentCondition)
                              ? currentCondition as String
                              : _conditionOptions.first;
                      final isOk = selectedCondition == 'Bisa Digunakan';

                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  Expanded(
                                    child: Text(
                                      facility['nama'] ?? '-',
                                      style: const TextStyle(
                                        fontSize: 15,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.textMain,
                                      ),
                                    ),
                                  ),
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 8, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: isOk
                                          ? AppColors.statusSuccessBg
                                          : AppColors.statusProcessBg,
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Text(
                                      facility['status_kondisi'] ?? '-',
                                      style: TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.w600,
                                        color: isOk
                                            ? AppColors.statusSuccessText
                                            : AppColors.statusProcessText,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              Row(
                                children: [
                                  const Icon(Icons.location_on_outlined,
                                      size: 14, color: AppColors.textMuted),
                                  const SizedBox(width: 4),
                                  Text(
                                    facility['lokasi'] ?? '-',
                                    style: const TextStyle(
                                        fontSize: 12,
                                        color: AppColors.textMuted),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),

                              // Action Row
                              Row(
                                mainAxisAlignment: MainAxisAlignment.end,
                                children: [
                                  const Text(
                                    'Ubah Status:',
                                    style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w600),
                                  ),
                                  const SizedBox(width: 8),
                                  DropdownButton<String>(
                                    value: selectedCondition,
                                    style: const TextStyle(
                                        fontSize: 13,
                                        color: AppColors.textMain),
                                    underline: const SizedBox(),
                                    items:
                                        _conditionOptions.map((String value) {
                                      return DropdownMenuItem<String>(
                                        value: value,
                                        child: Text(value),
                                      );
                                    }).toList(),
                                    onChanged: (String? newValue) {
                                      if (newValue != null &&
                                          newValue != currentCondition) {
                                        _updateKondisi(facility, newValue);
                                      }
                                    },
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
