import 'package:flutter/material.dart';

import '../models/catalog_model.dart';
import '../theme/app_theme.dart';

class SchoolContentScreen extends StatelessWidget {
  final List<CampusNews> news;
  final void Function(BuildContext, CampusNews) onNewsTap;

  const SchoolContentScreen({
    super.key,
    required this.news,
    required this.onNewsTap,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: const Text('Berita & Panduan'),
      ),
      body: news.isEmpty
          ? const Center(
              child: Text(
                'Belum ada berita & panduan sekolah',
                style: TextStyle(color: AppColors.textLight),
              ),
            )
          : ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: news.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final item = news[index];
                final isPanduan = item.category == 'Panduan';

                return Material(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(14),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(14),
                    onTap: () => onNewsTap(context, item),
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        border: Border.all(color: AppColors.border),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: Row(
                        children: [
                          _NewsThumbnail(news: item),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 8,
                                        vertical: 3,
                                      ),
                                      decoration: BoxDecoration(
                                        color: isPanduan
                                            ? AppColors.ethicsBg
                                            : AppColors.facilityBg,
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text(
                                        item.category,
                                        style: TextStyle(
                                          fontSize: 10,
                                          fontWeight: FontWeight.w700,
                                          color: isPanduan
                                              ? AppColors.ethicsText
                                              : AppColors.facilityText,
                                        ),
                                      ),
                                    ),
                                    const Spacer(),
                                    Text(
                                      item.date,
                                      style: const TextStyle(
                                        fontSize: 10,
                                        color: AppColors.textLight,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  item.title,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.textMain,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  item.summary,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontSize: 12,
                                    color: AppColors.textMuted,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
    );
  }
}

class _NewsThumbnail extends StatelessWidget {
  final CampusNews news;

  const _NewsThumbnail({required this.news});

  @override
  Widget build(BuildContext context) {
    final isPanduan = news.category == 'Panduan';
    final fallback = Container(
      color: isPanduan ? AppColors.ethicsBg : AppColors.facilityBg,
      child: Icon(
        isPanduan ? Icons.menu_book_rounded : Icons.campaign_rounded,
        color: isPanduan ? AppColors.ethicsText : AppColors.facilityText,
      ),
    );

    return ClipRRect(
      borderRadius: BorderRadius.circular(10),
      child: SizedBox(
        width: 76,
        height: 76,
        child: news.imageUrl == null || news.imageUrl!.isEmpty
            ? fallback
            : Image.network(
                news.imageUrl!,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => fallback,
              ),
      ),
    );
  }
}
