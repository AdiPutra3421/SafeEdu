import 'package:flutter/material.dart';
import '../models/facility_model.dart';
import '../theme/app_theme.dart';

class FacilityStatusScreen extends StatefulWidget {
  const FacilityStatusScreen({super.key});

  @override
  State<FacilityStatusScreen> createState() => _FacilityStatusScreenState();
}

class _FacilityStatusScreenState extends State<FacilityStatusScreen> {
  // Dummy data for testing
  final List<FacilityModel> _facilities = [
    FacilityModel(
      idFasilitas: 1,
      namaFasilitas: 'AC Lab Komputer 2',
      lokasi: 'Gedung B, Lantai 3',
      kondisi: 'Dalam Perbaikan',
      deskripsi: 'Air menetes mengenai meja server',
    ),
    FacilityModel(
      idFasilitas: 2,
      namaFasilitas: 'Proyektor Ruang 104',
      lokasi: 'Gedung C, Lantai 1',
      kondisi: 'Bisa Digunakan',
      deskripsi: 'Baru diganti lampunya',
    ),
    FacilityModel(
      idFasilitas: 3,
      namaFasilitas: 'Wastafel Toilet Pria',
      lokasi: 'Gedung A, Lantai 1',
      kondisi: 'Dalam Perbaikan',
      deskripsi: 'Kran patah',
    ),
    FacilityModel(
      idFasilitas: 4,
      namaFasilitas: 'Meja Guru Kelas 10A',
      lokasi: 'Gedung D, Lantai 2',
      kondisi: 'Bisa Digunakan',
      deskripsi: '-',
    ),
  ];

  String searchQuery = '';

  List<FacilityModel> get filteredFacilities {
    if (searchQuery.isEmpty) return _facilities;
    return _facilities.where((f) => 
      f.namaFasilitas.toLowerCase().contains(searchQuery.toLowerCase()) ||
      f.lokasi.toLowerCase().contains(searchQuery.toLowerCase())
    ).toList();
  }

  void _updateKondisi(FacilityModel facility, String newKondisi) {
    setState(() {
      facility.kondisi = newKondisi;
    });

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Status ${facility.namaFasilitas} diperbarui menjadi: $newKondisi'),
        behavior: SnackBarBehavior.floating,
        backgroundColor: newKondisi == 'Bisa Digunakan' ? AppColors.statusSuccessText : AppColors.statusProcessText,
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
                prefixIcon: const Icon(Icons.search, color: AppColors.textMuted),
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
                    itemCount: filteredFacilities.length,
                    itemBuilder: (context, index) {
                      final facility = filteredFacilities[index];
                      final isOk = facility.kondisi == 'Bisa Digunakan';

                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Expanded(
                                    child: Text(
                                      facility.namaFasilitas,
                                      style: const TextStyle(
                                        fontSize: 15,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.textMain,
                                      ),
                                    ),
                                  ),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: isOk ? AppColors.statusSuccessBg : AppColors.statusProcessBg,
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Text(
                                      facility.kondisi,
                                      style: TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.w600,
                                        color: isOk ? AppColors.statusSuccessText : AppColors.statusProcessText,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              Row(
                                children: [
                                  const Icon(Icons.location_on_outlined, size: 14, color: AppColors.textMuted),
                                  const SizedBox(width: 4),
                                  Text(
                                    facility.lokasi,
                                    style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
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
                                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                                  ),
                                  const SizedBox(width: 8),
                                  DropdownButton<String>(
                                    value: facility.kondisi,
                                    style: const TextStyle(fontSize: 13, color: AppColors.textMain),
                                    underline: const SizedBox(),
                                    items: <String>['Bisa Digunakan', 'Dalam Perbaikan', 'Tidak Bisa Digunakan']
                                        .map((String value) {
                                      return DropdownMenuItem<String>(
                                        value: value,
                                        child: Text(value),
                                      );
                                    }).toList(),
                                    onChanged: (String? newValue) {
                                      if (newValue != null && newValue != facility.kondisi) {
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
