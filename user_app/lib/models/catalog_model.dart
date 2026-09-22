class CampusFacilityCatalog {
  final String id;
  final String name;
  final String category;
  final String location;
  final String operatingHours;
  final String status; // 'Tersedia', 'Perbaikan', 'Terbatas'
  final String iconType; // 'computer', 'book', 'sports', 'counseling', 'health'
  final String description;

  const CampusFacilityCatalog({
    required this.id,
    required this.name,
    required this.category,
    required this.location,
    required this.operatingHours,
    required this.status,
    required this.iconType,
    required this.description,
  });
}

class CampusNews {
  final String id;
  final String title;
  final String category; // 'Kode Etik', 'Fasilitas', 'Keselamatan'
  final String date;
  final String readTime;
  final String summary;
  final String author;

  const CampusNews({
    required this.id,
    required this.title,
    required this.category,
    required this.date,
    required this.readTime,
    required this.summary,
    required this.author,
  });

  factory CampusNews.fromMap(Map<String, dynamic> map) {
    final tipe = map['tipe']?.toString() ?? 'berita';
    return CampusNews(
      id: '${map['id'] ?? ''}',
      title: map['judul']?.toString() ?? '',
      category: tipe == 'panduan' ? 'Panduan' : 'Berita',
      date: map['created_at']?.toString().split('T').first ?? '',
      readTime: '',
      summary: map['ringkasan']?.toString() ?? map['isi']?.toString() ?? '',
      author: 'Sekolah',
    );
  }
}
