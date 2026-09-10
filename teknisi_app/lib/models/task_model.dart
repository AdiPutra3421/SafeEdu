class PenugasanModel {
  final int idPenugasan;
  final int idPengaduan;
  final int idTeknisi;
  final int idAdmin;
  final String tanggalPenugasan;
  String statusPenugasan; // 'ditugaskan', 'diterima', 'selesai'
  final String? catatanAdmin;
  
  // Joined fields for display
  final String judulLaporan;
  final String lokasi;
  final String deskripsiLaporan;
  final String pelapor;
  
  // Extra fields from status_pengerjaan for UI mock
  String? keteranganTeknisi;
  String? tanggalUpdate;

  PenugasanModel({
    required this.idPenugasan,
    required this.idPengaduan,
    required this.idTeknisi,
    required this.idAdmin,
    required this.tanggalPenugasan,
    required this.statusPenugasan,
    this.catatanAdmin,
    required this.judulLaporan,
    required this.lokasi,
    required this.deskripsiLaporan,
    required this.pelapor,
    this.keteranganTeknisi,
    this.tanggalUpdate,
  });
}
