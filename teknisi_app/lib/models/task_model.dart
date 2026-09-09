class TechnicianTask {
  final String id;
  final String title;
  final String category;
  final String location;
  final String urgency;
  final String reporterName;
  final String dateReceived;
  final String adminNotes;
  String status; // 'Tugas Baru', 'Dalam Pengerjaan', 'Selesai'
  String? technicianNotes;
  String? estimatedCompletion;
  String? confirmedAt;

  TechnicianTask({
    required this.id,
    required this.title,
    required this.category,
    required this.location,
    required this.urgency,
    required this.reporterName,
    required this.dateReceived,
    required this.adminNotes,
    required this.status,
    this.technicianNotes,
    this.estimatedCompletion,
    this.confirmedAt,
  });

  TechnicianTask copyWith({
    String? status,
    String? technicianNotes,
    String? estimatedCompletion,
    String? confirmedAt,
  }) {
    return TechnicianTask(
      id: id,
      title: title,
      category: category,
      location: location,
      urgency: urgency,
      reporterName: reporterName,
      dateReceived: dateReceived,
      adminNotes: adminNotes,
      status: status ?? this.status,
      technicianNotes: technicianNotes ?? this.technicianNotes,
      estimatedCompletion: estimatedCompletion ?? this.estimatedCompletion,
      confirmedAt: confirmedAt ?? this.confirmedAt,
    );
  }
}
