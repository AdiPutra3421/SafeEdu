import 'package:flutter_test/flutter_test.dart';
import 'package:user_app/models/report_model.dart';

void main() {
  test('reads category type from the report API relationship', () {
    final report = PengaduanModel.fromMap({
      'id': 1,
      'user_id': 2,
      'kategori_id': 8,
      'judul': 'Laporan fasilitas',
      'deskripsi': 'Keterangan laporan.',
      'kategori': {
        'nama': 'Kerusakan Fasilitas',
        'jenis': 'fasilitas',
      },
    });

    expect(report.kategoriId, 8);
    expect(report.kategoriJenis, 'fasilitas');
  });
}
