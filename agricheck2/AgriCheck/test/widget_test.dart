import 'package:agricheck/main.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('Agricheck starts on splash screen', (tester) async {
    final state = AgricheckAppState();
    await tester.pumpWidget(
      AgricheckScope(state: state, child: const AgricheckApp()),
    );

    expect(find.text('AGRICHECK'), findsOneWidget);
    expect(find.text('Votre récolte, notre priorité.'), findsOneWidget);
  });
}
