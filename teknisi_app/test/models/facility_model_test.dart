import 'package:flutter_test/flutter_test.dart';
import 'package:teknisi_app/models/facility_model.dart';

void main() {
  test('parses facility fields returned by the API', () {
    final facility = FacilityModel.fromJson({
      'id': 12,
      'nama': 'Ruang Kelas 1A',
      'lokasi': 'Gedung A',
      'status_kondisi': 'Dalam Perbaikan',
      'deskripsi': 'Kondisi fasilitas.',
    });

    expect(facility.idFasilitas, 12);
    expect(facility.namaFasilitas, 'Ruang Kelas 1A');
    expect(facility.kondisi, 'Dalam Perbaikan');
  });
}
