
import '../services/api_config.dart';


class CampusFacilityCatalog {
  final String id;
  final String name;
  final String category;
  final String location;
  final String operatingHours;
  final String status;
  final String iconType;
  final String description;
  final String? imageUrl;

  const CampusFacilityCatalog({
    required this.id,
    required this.name,
    required this.category,
    required this.location,
    required this.operatingHours,
    required this.status,
    required this.iconType,
    required this.description,
    this.imageUrl,
  });
}


class CampusNews {
  final String id;
  final String title;
  final String category;
  final String date;
  final String readTime;
  final String summary;
  final String fullContent;
  final String author;
  final String? imageUrl;

  const CampusNews({
    required this.id,
    required this.title,
    required this.category,
    required this.date,
    required this.readTime,
    required this.summary,
    required this.fullContent,
    required this.author,
    this.imageUrl,
  });

  factory CampusNews.fromMap(Map<String, dynamic> map) {
    final tipe = map['tipe']?.toString() ?? 'berita';
    final gambar = map['gambar']?.toString();

    return CampusNews(
      id: '${map['id'] ?? ''}',
      title: map['judul']?.toString() ?? '',
      category: tipe == 'panduan' ? 'Panduan' : 'Berita',
      date: map['created_at']?.toString().split('T').first ?? '',
      readTime: '',
      summary: map['ringkasan']?.toString() ??
          map['isi']?.toString() ?? '',
      fullContent: map['isi']?.toString() ?? '',
      author: 'Sekolah',
      imageUrl: gambar != null && gambar.isNotEmpty
          ? '${ApiConfig.baseUrl}/api/gambar/$gambar'
          : null,
    );
  }
}
