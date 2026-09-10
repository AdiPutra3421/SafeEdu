class FacilityModel {
  final int idFasilitas;
  final String namaFasilitas;
  final String lokasi;
  String kondisi;
  final String? deskripsi;

  FacilityModel({
    required this.idFasilitas,
    required this.namaFasilitas,
    required this.lokasi,
    required this.kondisi,
    this.deskripsi,
  });

  factory FacilityModel.fromJson(Map<String, dynamic> json) {
    return FacilityModel(
      idFasilitas: json['id_fasilitas'],
      namaFasilitas: json['nama_fasilitas'],
      lokasi: json['lokasi'] ?? '',
      kondisi: json['kondisi'] ?? 'Bisa Digunakan',
      deskripsi: json['deskripsi'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id_fasilitas': idFasilitas,
      'nama_fasilitas': namaFasilitas,
      'lokasi': lokasi,
      'kondisi': kondisi,
      'deskripsi': deskripsi,
    };
  }
}
