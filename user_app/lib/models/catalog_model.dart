import '../services/api_config.dart';

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
  final String category; // 'Kode Etik', 'Fasilitas', 'Keselamatan', 'Berita', 'Panduan'
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
    final rawImage = map['gambar_url']?.toString() ?? map['gambar']?.toString();
    String? resolvedImage;
    if (rawImage != null && rawImage.isNotEmpty && rawImage != 'null') {
      if (rawImage.startsWith('http://') || rawImage.startsWith('https://')) {
        final uri = Uri.tryParse(rawImage);
        if (uri != null && (uri.host == 'localhost' || uri.host == '127.0.0.1')) {
          final baseUri = Uri.tryParse(ApiConfig.baseUrl);
          if (baseUri != null) {
            resolvedImage = uri.replace(scheme: baseUri.scheme, host: baseUri.host, port: baseUri.hasPort ? baseUri.port : null).toString();
          } else {
            resolvedImage = rawImage;
          }
        } else {
          resolvedImage = rawImage;
        }
      } else {
        resolvedImage = '${ApiConfig.baseUrl}/storage/$rawImage';
      }
    }

    return CampusNews(
      id: '${map['id'] ?? ''}',
      title: map['judul']?.toString() ?? '',
      category: tipe == 'panduan' ? 'Panduan' : 'Berita',
      date: map['created_at']?.toString().split('T').first ?? '',
      readTime: '',
      summary: map['ringkasan']?.toString() ?? map['isi']?.toString() ?? '',
      fullContent: map['isi']?.toString() ?? '',
      author: 'Sekolah',
      imageUrl: resolvedImage,
    );
  }
}
