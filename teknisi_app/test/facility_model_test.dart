import 'package:flutter_test/flutter_test.dart';
import 'package:teknisi_app/models/facility_model.dart';

void main() {
  test('reads the facility API field names', () {
    final facility = FacilityModel.fromJson({
      'id': 7,
      'nama': 'Ruang Laboratorium',
      'lokasi': 'Gedung Utama',
      'status_kondisi': 'Dalam Perbaikan',
      'deskripsi': 'Pendingin ruangan rusak.',
    });

    expect(facility.idFasilitas, 7);
    expect(facility.namaFasilitas, 'Ruang Laboratorium');
    expect(facility.kondisi, 'Dalam Perbaikan');
  });
}
