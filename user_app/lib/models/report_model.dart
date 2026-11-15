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
  final String? fileBuktiUrl;
  int? ratingKepuasan;
  String? ulasanPelapor;
  final String? createdAt;
  final String? verifiedAt;
  final String? assignedAt;
  final String? workedAt;
  final String? completedAt;
  final String? namaKategori;
  final String? kategoriJenis;
  final String? namaFasilitas;
  final String? penugasanStatus;

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
    this.fileBuktiUrl,
    this.ratingKepuasan,
    this.ulasanPelapor,
    this.createdAt,
    this.verifiedAt,
    this.assignedAt,
    this.workedAt,
    this.completedAt,
    this.namaKategori,
    this.kategoriJenis,
    this.namaFasilitas,
    this.penugasanStatus,
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
      id: _asInt(map['id']),
      userId: _asInt(map['user_id']),
      kategoriId: _asInt(map['kategori_id']),
      fasilitasId:
          map['fasilitas_id'] == null ? null : _asInt(map['fasilitas_id']),
      judul: map['judul'] ?? '',
      deskripsi: map['deskripsi'] ?? '',
      lokasiKejadian: map['lokasi_kejadian'],
      status: map['status'] ?? 'menunggu',
      isAnonim: map['is_anonim'] == 1 || map['is_anonim'] == true,
      fotoBukti: map['foto_bukti'],
      fileBuktiUrl: map['file_bukti_url'],
      ratingKepuasan: map['rating_kepuasan'] == null
          ? null
          : _asInt(map['rating_kepuasan']),
      ulasanPelapor: map['ulasan_pelapor'],
      createdAt: map['created_at'],
      verifiedAt: map['verified_at'],
      assignedAt: map['assigned_at'],
      workedAt: map['worked_at'],
      completedAt: map['completed_at'],
      namaKategori: (map['kategori'] as Map?)?['nama']?.toString(),
      kategoriJenis: (map['kategori'] as Map?)?['jenis']?.toString(),
      namaFasilitas: (map['fasilitas'] as Map?)?['nama']?.toString(),
      penugasanStatus:
          map['penugasans'] is List && (map['penugasans'] as List).isNotEmpty
              ? ((map['penugasans'] as List).first as Map)['status']?.toString()
              : null,
    );
  }

  static int _asInt(dynamic value) =>
      value is int ? value : int.tryParse('$value') ?? 0;
}
