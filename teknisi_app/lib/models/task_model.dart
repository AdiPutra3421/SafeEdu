class PenugasanModel {
  final int idPenugasan;
  final int idPengaduan;
  final int idTeknisi;
  String statusPenugasan; // 'ditugaskan', 'diterima', 'selesai', 'ditolak'
  final String? catatanAdmin;
  final String urgency;
  final String? rejectionReason;
  
  // Joined fields for display
  final String judulLaporan;
  final String lokasi;
  final String deskripsiLaporan;
  final String pelapor;
  
  // Extra fields from status_pengerjaan for UI mock
  String? keteranganTeknisi;
  String? tanggalUpdate;
  String tanggalPenugasan;
  String? photoProof;

  PenugasanModel({
    required this.idPenugasan,
    required this.idPengaduan,
    required this.idTeknisi,
    required this.statusPenugasan,
    this.catatanAdmin,
    required this.urgency,
    this.rejectionReason,
    required this.judulLaporan,
    required this.lokasi,
    required this.deskripsiLaporan,
    required this.pelapor,
    this.keteranganTeknisi,
    this.tanggalUpdate,
    required this.tanggalPenugasan,
    this.photoProof,
  });

  factory PenugasanModel.fromJson(Map<String, dynamic> json) {
    final pengaduan = json['pengaduan'] ?? {};
    final logs = json['log_pengerjaans'] as List? ?? [];
    
    return PenugasanModel(
      idPenugasan: json['id'] ?? 0,
      idPengaduan: json['pengaduan_id'] ?? 0,
      idTeknisi: json['teknisi_id'] ?? 0,
      statusPenugasan: json['status'] ?? 'ditugaskan',
      catatanAdmin: json['catatan_admin'],
      urgency: json['urgency'] ?? pengaduan['urgency'] ?? 'medium',
      rejectionReason: json['rejection_reason'],
      judulLaporan: pengaduan['judul'] ?? 'Tanpa Judul',
      lokasi: pengaduan['lokasi_kejadian'] ?? 'Tidak diketahui',
      deskripsiLaporan: pengaduan['deskripsi'] ?? '',
      pelapor: (pengaduan['pelapor'] != null) ? pengaduan['pelapor']['nama'] : 'User',
      keteranganTeknisi: logs.isNotEmpty ? logs.last['catatan_teknisi'] : null,
      tanggalUpdate: logs.isNotEmpty ? logs.last['created_at'] : null,
      tanggalPenugasan: json['created_at'] ?? '',
      photoProof: logs.isNotEmpty ? logs.last['photo_proof'] : null,
    );
  }
}
