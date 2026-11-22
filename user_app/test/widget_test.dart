import 'package:flutter_test/flutter_test.dart';

import 'package:user_app/main.dart';

void main() {
  testWidgets('Login screen renders for unauthenticated user', (tester) async {
    await tester.pumpWidget(const SafeEduUserApp());

    expect(find.text('SafeEdu'), findsOneWidget);
    expect(find.text('PORTAL SISWA'), findsOneWidget);
    expect(find.text('Masuk ke akun'), findsOneWidget);

    await tester.pump();
  });
}
