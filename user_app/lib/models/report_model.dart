class UserReport {
  final String id;
  final String type; // 'fasilitas' | 'kode_etik'
  final String title;
  final String category;
  final String location;
  final String urgency; // 'Rendah' | 'Sedang' | 'Mendesak'
  final String description;
  final String date;
  final bool isAnonymous;
  final String? attachmentName;
  final String status; // 'Menunggu Verifikasi', 'Diteruskan ke Teknisi', 'Selesai'
  final String? responseNote;

  UserReport({
    required this.id,
    required this.type,
    required this.title,
    required this.category,
    required this.location,
    required this.urgency,
    required this.description,
    required this.date,
    this.isAnonymous = false,
    this.attachmentName,
    required this.status,
    this.responseNote,
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'type': type,
      'title': title,
      'category': category,
      'location': location,
      'urgency': urgency,
      'description': description,
      'date': date,
      'is_anonymous': isAnonymous,
      'attachment_name': attachmentName,
      'status': status,
      'response_note': responseNote,
    };
  }

  factory UserReport.fromMap(Map<String, dynamic> map) {
    return UserReport(
      id: map['id'] ?? '',
      type: map['type'] ?? 'fasilitas',
      title: map['title'] ?? '',
      category: map['category'] ?? '',
      location: map['location'] ?? '',
      urgency: map['urgency'] ?? 'Sedang',
      description: map['description'] ?? '',
      date: map['date'] ?? '',
      isAnonymous: map['is_anonymous'] ?? false,
      attachmentName: map['attachment_name'],
      status: map['status'] ?? 'Menunggu Verifikasi',
      responseNote: map['response_note'],
    );
  }
}
