import 'package:flutter_test/flutter_test.dart';
import 'package:user_app/models/report_model.dart';

void main() {
  test('reads report category type from the API relationship', () {
    final report = PengaduanModel.fromMap({
      'id': 5,
      'user_id': 2,
      'kategori_id': 9,
      'judul': 'Laporan fasilitas',
      'deskripsi': 'Deskripsi laporan',
      'kategori': {
        'nama': 'Kerusakan Fasilitas',
        'jenis': 'fasilitas',
      },
    });

    expect(report.kategoriId, 9);
    expect(report.namaKategori, 'Kerusakan Fasilitas');
    expect(report.jenisKategori, 'fasilitas');
  });
}
