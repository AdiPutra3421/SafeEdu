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
      idFasilitas: _asInt(json['id'] ?? json['id_fasilitas']),
      namaFasilitas: (json['nama'] ?? json['nama_fasilitas'] ?? '').toString(),
      lokasi: json['lokasi'] ?? '',
      kondisi: (json['status_kondisi'] ?? json['kondisi'] ?? 'Bisa Digunakan')
          .toString(),
      deskripsi: json['deskripsi']?.toString(),
    );
  }

  static int _asInt(dynamic value) =>
      value is int ? value : int.tryParse('$value') ?? 0;

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
