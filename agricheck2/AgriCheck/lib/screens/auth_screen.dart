import 'package:flutter/material.dart';

import '../main.dart';
import '../theme/app_theme.dart';

class AuthScreen extends StatefulWidget {
  const AuthScreen({super.key});

  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen> {
  final TextEditingController _fullNameController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _identifierController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _passwordConfirmController =
      TextEditingController();

  bool _isRegistering = true;
  bool _isLoading = false;
  String? _error;
  bool _obscurePassword = true ;
  bool _obscureConfirmPassword = true;


  @override
  void dispose() {
    _fullNameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _identifierController.dispose();
    _passwordController.dispose();
    _passwordConfirmController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final validationError = _validate();
    if (validationError != null) {
      setState(() => _error = validationError);
      return;
    }

    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final state = AgricheckScope.read(context);
      if (_isRegistering) {
        await state.register(
          fullName: _fullNameController.text.trim(),
          phone: _phoneController.text.trim(),
          email: _emailController.text.trim(),
          password: _passwordController.text,
        );
      } else {
        await state.login(
          identifier: _identifierController.text.trim(),
          password: _passwordController.text,
        );
      }

      if (!mounted) {
        return;
      }
      Navigator.of(context).pushReplacementNamed('/app');
    } catch (error) {
      setState(() => _error = _friendlyError(error));
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  String? _validate() {
    if (_isRegistering) {
      if (_fullNameController.text.trim().isEmpty) {
        return 'Entrez le nom complet.';
      }
      if (_phoneController.text.trim().isEmpty) {
        return 'Entrez le telephone.';
      }
      if (_emailController.text.trim().isEmpty ||
          !_emailController.text.contains('@')) {
        return 'Entrez un email valide.';
      }
    } else if (_identifierController.text.trim().isEmpty) {
      return 'Entrez votre telephone ou votre email.';
    }

    if (_passwordController.text.length < 8) {
      return 'Le mot de passe doit contenir au moins 8 caracteres.';
    }
    if (_isRegistering &&
        _passwordController.text != _passwordConfirmController.text) {
      return 'Les mots de passe ne correspondent pas.';
    }
    return null;
  }

  String _friendlyError(Object error) {
    final message = error.toString();
    if (message.contains('Connexion Agricheck indisponible') ||
        message.contains('Connection') ||
        message.contains('Socket')) {
      return 'Connexion a Agricheck Admin impossible. Lancez Agricheck Admin puis reessayez.';
    }
    return message.replaceFirst('Exception: ', '');
  }

  void _switchMode(bool register) {
    setState(() {
      _isRegistering = register;
      _error = null;
      _passwordController.clear();
      _passwordConfirmController.clear();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) {
            return SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: ConstrainedBox(
                constraints: BoxConstraints(
                  minHeight: constraints.maxHeight - 40,
                ),
                child: Center(
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 520),
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.94),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.55),
                        ),
                        boxShadow: <BoxShadow>[
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.18),
                            blurRadius: 32,
                            offset: const Offset(0, 18),
                          ),
                        ],
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(22),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: <Widget>[
                            Center(
                              child: Column(
                                children: <Widget>[
                                  Image.asset(
                                    'assets/images/agricheck_logo.png',
                                    width: 210,
                                  ),
                                  const SizedBox(height: 10),
                                  Text(
                                    'Votre recolte, notre priorite.',
                                    textAlign: TextAlign.center,
                                    style: Theme.of(context)
                                        .textTheme
                                        .titleMedium
                                        ?.copyWith(
                                          color: AppTheme.leaf,
                                          fontWeight: FontWeight.w900,
                                        ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 24),
                            Row(
                              children: <Widget>[
                                Expanded(
                                  child: _ModeButton(
                                    label: 'Inscription',
                                    selected: _isRegistering,
                                    onTap: () => _switchMode(true),
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: _ModeButton(
                                    label: 'Connexion',
                                    selected: !_isRegistering,
                                    onTap: () => _switchMode(false),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 22),
                            Text(
                              _isRegistering
                                  ? 'Creer un compte'
                                  : 'Se connecter',
                              style: Theme.of(context).textTheme.headlineSmall
                                  ?.copyWith(
                                    color: AppTheme.leaf,
                                    fontWeight: FontWeight.w900,
                                  ),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              _isRegistering
                                  ? 'Le compte sera enregistre dans Agricheck Admin.'
                                  : 'Connectez-vous avec le telephone ou l email du compte cree.',
                              style: Theme.of(context).textTheme.bodyLarge,
                            ),
                            const SizedBox(height: 22),
                            if (_isRegistering) ...<Widget>[
                              TextField(
                                controller: _fullNameController,
                                textInputAction: TextInputAction.next,
                                decoration: const InputDecoration(
                                  labelText: 'Nom complet',
                                  prefixIcon: Icon(Icons.person_outline),
                                ),
                              ),
                              const SizedBox(height: 12),
                              TextField(
                                controller: _phoneController,
                                keyboardType: TextInputType.phone,
                                textInputAction: TextInputAction.next,
                                decoration: const InputDecoration(
                                  labelText: 'Telephone',
                                  prefixIcon: Icon(Icons.phone_outlined),
                                ),
                              ),
                              const SizedBox(height: 12),
                              TextField(
                                controller: _emailController,
                                keyboardType: TextInputType.emailAddress,
                                textInputAction: TextInputAction.next,
                                decoration: const InputDecoration(
                                  labelText: 'Email',
                                  prefixIcon: Icon(Icons.mail_outline),
                                ),
                              ),
                            ] else ...<Widget>[
                              TextField(
                                controller: _identifierController,
                                keyboardType: TextInputType.emailAddress,
                                textInputAction: TextInputAction.next,
                                decoration: const InputDecoration(
                                  labelText: 'Telephone ou email',
                                  prefixIcon: Icon(
                                    Icons.account_circle_outlined,
                                  ),
                                ),
                              ),
                            ],
                            const SizedBox(height: 12),
                            TextField(
                              controller: _passwordController,
                              obscureText: _obscurePassword,
                              textInputAction: _isRegistering
                                  ? TextInputAction.next
                                  : TextInputAction.done,
                              onSubmitted: (_) {
                                if (!_isRegistering && !_isLoading) {
                                  _submit();
                                }
                              },
                              decoration: InputDecoration(
                                labelText: 'Mot de passe',
                                prefixIcon: const Icon(Icons.lock_outline),
                                suffixIcon: IconButton(
                                  icon: Icon(
                                    _obscurePassword
                                        ? Icons.visibility_off
                                        : Icons.visibility,
                                  ),
                                  onPressed: () {
                                    setState(() {
                                      _obscurePassword = !_obscurePassword;
                                    });
                                  },
                                ),
                              ),
                            ),
                            if (_isRegistering) ...<Widget>[
                              const SizedBox(height: 12),
                              TextField(
                                controller: _passwordConfirmController,
                                obscureText: _obscureConfirmPassword,
                                textInputAction: TextInputAction.done,
                                onSubmitted: (_) {
                                  if (!_isLoading) {
                                    _submit();
                                  }
                                },
                                decoration: InputDecoration(
                                  labelText: 'Confirmer le mot de passe',
                                  prefixIcon: const Icon(Icons.verified_user_outlined),
                                  suffixIcon: IconButton(
                                    icon: Icon(
                                      _obscureConfirmPassword
                                          ? Icons.visibility_off
                                          : Icons.visibility,
                                    ),
                                    onPressed: () {
                                      setState(() {
                                        _obscureConfirmPassword =
                                        !_obscureConfirmPassword;
                                      });
                                    },
                                  ),
                                ),
                              ),
                            ],
                            if (_error != null) ...<Widget>[
                              const SizedBox(height: 12),
                              Text(
                                _error!,
                                style: const TextStyle(color: Colors.red),
                              ),
                            ],
                            const SizedBox(height: 18),
                            FilledButton.icon(
                              onPressed: _isLoading ? null : _submit,
                              icon: Icon(
                                _isRegistering
                                    ? Icons.person_add_alt
                                    : Icons.login,
                              ),
                              label: Text(
                                _isLoading
                                    ? 'Veuillez patienter...'
                                    : _isRegistering
                                    ? 'Creer mon compte'
                                    : 'Se connecter',
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}

class _ModeButton extends StatelessWidget {
  const _ModeButton({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return OutlinedButton(
      onPressed: onTap,
      style: OutlinedButton.styleFrom(
        backgroundColor: selected ? AppTheme.leaf : Colors.white,
        foregroundColor: selected ? Colors.white : AppTheme.leaf,
        side: BorderSide(color: selected ? AppTheme.leaf : AppTheme.lightLeaf),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
      child: Text(label, style: const TextStyle(fontWeight: FontWeight.w800)),
    );
  }
}
