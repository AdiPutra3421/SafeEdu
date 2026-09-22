/// Format timestamp ISO (mis. "2026-09-22T06:05:00.000000Z")
/// menjadi "22 Sep 2026, 06:05". Kembalikan '' jika null/tidak valid.
String formatTimestamp(String? value) {
  if (value == null || value.isEmpty) return '';
  final dt = DateTime.tryParse(value);
  if (dt == null) return value;
  final local = dt.toLocal();
  const months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
  ];
  final hh = local.hour.toString().padLeft(2, '0');
  final mm = local.minute.toString().padLeft(2, '0');
  return '${local.day} ${months[local.month - 1]} ${local.year}, $hh:$mm';
}