class PengaduanModel {
  final int idPengaduan;
  final int idUser;
  final int idKategori; // 1: kode_etik, 2: fasilitas
  final int? idFasilitas;
  final String judul;
  final String isiPengaduan;
  final String? lokasi;
  final String status; // 'menunggu', 'diverifikasi', 'ditolak', 'dalam pengerjaan', 'selesai', 'ditindaklanjuti'
  final String? alasanPenolakan;
  final String tanggalPengaduan;
  final String? tanggalSelesai;
  
  // Custom fields for UI that might join from other tables
  final String? namaKategori;
  final String? namaFasilitas;

  PengaduanModel({
    required this.idPengaduan,
    required this.idUser,
    required this.idKategori,
    this.idFasilitas,
    required this.judul,
    required this.isiPengaduan,
    this.lokasi,
    this.status = 'menunggu',
    this.alasanPenolakan,
    required this.tanggalPengaduan,
    this.tanggalSelesai,
    this.namaKategori,
    this.namaFasilitas,
  });

  Map<String, dynamic> toMap() {
    return {
      'id_pengaduan': idPengaduan,
      'id_user': idUser,
      'id_kategori': idKategori,
      'id_fasilitas': idFasilitas,
      'judul': judul,
      'isi_pengaduan': isiPengaduan,
      'lokasi': lokasi,
      'status': status,
      'alasan_penolakan': alasanPenolakan,
      'tanggal_pengaduan': tanggalPengaduan,
      'tanggal_selesai': tanggalSelesai,
    };
  }

  factory PengaduanModel.fromMap(Map<String, dynamic> map) {
    return PengaduanModel(
      idPengaduan: map['id_pengaduan'] ?? 0,
      idUser: map['id_user'] ?? 0,
      idKategori: map['id_kategori'] ?? 0,
      idFasilitas: map['id_fasilitas'],
      judul: map['judul'] ?? '',
      isiPengaduan: map['isi_pengaduan'] ?? '',
      lokasi: map['lokasi'],
      status: map['status'] ?? 'menunggu',
      alasanPenolakan: map['alasan_penolakan'],
      tanggalPengaduan: map['tanggal_pengaduan'] ?? '',
      tanggalSelesai: map['tanggal_selesai'],
      namaKategori: map['nama_kategori'],
      namaFasilitas: map['nama_fasilitas'],
    );
  }
}
