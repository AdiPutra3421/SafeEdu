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
    final id = json['id_fasilitas'] ?? json['id'];
    final name = json['nama_fasilitas'] ?? json['nama'];
    if (id == null || name == null) {
      throw const FormatException(
          'Data fasilitas tidak memiliki ID atau nama.');
    }

    return FacilityModel(
      idFasilitas: id is int ? id : int.parse(id.toString()),
      namaFasilitas: name.toString(),
      lokasi: (json['lokasi'] ?? '').toString(),
      kondisi: (json['kondisi'] ?? json['status_kondisi'] ?? 'Bisa Digunakan')
          .toString(),
      deskripsi: json['deskripsi']?.toString(),
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
