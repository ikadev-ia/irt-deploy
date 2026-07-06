import 'package:shared_preferences/shared_preferences.dart';

import '../models/user_profile.dart';

class SessionStore {
  static const String _tokenKey = 'agricheck.session.token.v1';
  static const String _userKey = 'agricheck.session.user.v1';

  Future<({String token, UserProfile? user})> load() async {
    final preferences = await SharedPreferences.getInstance();
    final token = preferences.getString(_tokenKey) ?? '';
    final userRaw = preferences.getString(_userKey);
    return (
      token: token,
      user: userRaw == null || userRaw.trim().isEmpty
          ? null
          : UserProfile.decode(userRaw),
    );
  }

  Future<void> save({required String token, required UserProfile user}) async {
    final preferences = await SharedPreferences.getInstance();
    await preferences.setString(_tokenKey, token);
    await preferences.setString(_userKey, user.encode());
  }

  Future<void> clear() async {
    final preferences = await SharedPreferences.getInstance();
    await preferences.remove(_tokenKey);
    await preferences.remove(_userKey);
  }
}
