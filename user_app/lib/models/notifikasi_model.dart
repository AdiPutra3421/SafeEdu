class NotifikasiModel {
  final int id;
  final int userId;
  final int? pengaduanId;
  final String type;
  final String judul;
  final String pesan;
  final bool isRead;
  final String? createdAt;

  NotifikasiModel({
    required this.id,
    required this.userId,
    this.pengaduanId,
    this.type = 'info',
    required this.judul,
    required this.pesan,
    this.isRead = false,
    this.createdAt,
  });

  factory NotifikasiModel.fromMap(Map<String, dynamic> map) {
    return NotifikasiModel(
      id: map['id'] ?? 0,
      userId: map['user_id'] ?? 0,
      pengaduanId: map['pengaduan_id'],
      type: map['type'] ?? 'info',
      judul: map['judul'] ?? '',
      pesan: map['pesan'] ?? '',
      isRead: map['is_read'] == 1 || map['is_read'] == true,
      createdAt: map['created_at'],
    );
  }
}