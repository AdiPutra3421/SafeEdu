class PengaduanModel {
  final int id;
  final int userId;
  final int kategoriId;
  final int? fasilitasId;
  final String judul;
  final String deskripsi;
  final String? lokasiKejadian;
  final String status;
  final bool isAnonim;
  final String? fotoBukti;
  int? ratingKepuasan;
  String? ulasanPelapor;
  final String? createdAt;
  
  // Custom fields from joins (with/load in Laravel)
  final String? namaKategori;
  final String? namaFasilitas;

  PengaduanModel({
    required this.id,
    required this.userId,
    required this.kategoriId,
    this.fasilitasId,
    required this.judul,
    required this.deskripsi,
    this.lokasiKejadian,
    this.status = 'menunggu',
    this.isAnonim = false,
    this.fotoBukti,
    this.ratingKepuasan,
    this.ulasanPelapor,
    this.createdAt,
    this.namaKategori,
    this.namaFasilitas,
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'user_id': userId,
      'kategori_id': kategoriId,
      'fasilitas_id': fasilitasId,
      'judul': judul,
      'deskripsi': deskripsi,
      'lokasi_kejadian': lokasiKejadian,
      'status': status,
      'is_anonim': isAnonim,
      'foto_bukti': fotoBukti,
      'rating_kepuasan': ratingKepuasan,
      'ulasan_pelapor': ulasanPelapor,
    };
  }

  factory PengaduanModel.fromMap(Map<String, dynamic> map) {
    return PengaduanModel(
      id: map['id'] ?? 0,
      userId: map['user_id'] ?? 0,
      kategoriId: map['kategori_id'] ?? 0,
      fasilitasId: map['fasilitas_id'],
      judul: map['judul'] ?? '',
      deskripsi: map['deskripsi'] ?? '',
      lokasiKejadian: map['lokasi_kejadian'],
      status: map['status'] ?? 'menunggu',
      isAnonim: map['is_anonim'] == 1 || map['is_anonim'] == true,
      fotoBukti: map['foto_bukti'],
      ratingKepuasan: map['rating_kepuasan'],
      ulasanPelapor: map['ulasan_pelapor'],
      createdAt: map['created_at'],
      namaKategori: map['kategori']?['nama'],
      namaFasilitas: map['fasilitas']?['nama'],
    );
  }
}
