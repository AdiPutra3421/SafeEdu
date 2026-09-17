import 'package:flutter/material.dart';
import '../models/facility_model.dart';
import '../theme/app_theme.dart';
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
          await http.get(Uri.parse('http://127.0.0.1:8000/api/fasilitas'));
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

  void _updateKondisi(dynamic facility, String newKondisi) {
    // API logic will go here if needed, but for now we just change state locally to simulate
    setState(() {
      facility['status_kondisi'] = newKondisi;
    });
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: const Text('Status fasilitas diperbarui'),
        backgroundColor: AppColors.statusSuccessText,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: const Text('Status Fasilitas'),
        elevation: 0,
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
                      final isOk =
                          facility['status_kondisi'] == 'Bisa Digunakan';

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
                                    value: facility['status_kondisi'] ?? '-',
                                    style: const TextStyle(
                                        fontSize: 13,
                                        color: AppColors.textMain),
                                    underline: const SizedBox(),
                                    items: <String>[
                                      'Bisa Digunakan',
                                      'Dalam Perbaikan',
                                      'Tidak Bisa Digunakan'
                                    ].map((String value) {
                                      return DropdownMenuItem<String>(
                                        value: value,
                                        child: Text(value),
                                      );
                                    }).toList(),
                                    onChanged: (String? newValue) {
                                      if (newValue != null &&
                                          newValue !=
                                              (facility['status_kondisi'] ??
                                                  '-')) {
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
