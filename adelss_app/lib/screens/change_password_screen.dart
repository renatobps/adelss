import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../session/session_controller.dart';
import '../widgets/ui.dart';

class ChangePasswordScreen extends StatefulWidget {
  const ChangePasswordScreen({super.key});

  @override
  State<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends State<ChangePasswordScreen> {
  final _form = GlobalKey<FormState>();
  final _current = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  bool _busy = false;

  @override
  void dispose() {
    _current.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_form.currentState!.validate()) {
      return;
    }
    final session = context.read<SessionController>();
    setState(() => _busy = true);
    try {
      await session.changePassword(
        currentPassword: session.mustChangePassword ? null : _current.text,
        password: _password.text,
      );
      if (mounted) {
        showSnack(context, 'Senha atualizada.');
        if (context.canPop()) {
          context.pop();
        } else {
          context.go('/home');
        }
      }
    } on ApiException catch (error) {
      if (mounted) {
        showSnack(context, error.message, error: true);
      }
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final forced = context.watch<SessionController>().mustChangePassword;
    return Scaffold(
      appBar: AppBar(
        title: Text(forced ? 'Nova senha' : 'Trocar senha'),
        automaticallyImplyLeading: !forced,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
        child: Form(
          key: _form,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                forced
                    ? 'Sua conta ainda usa a senha padrão. Escolha outra com pelo menos 8 caracteres.'
                    : 'A nova senha precisa ter pelo menos 8 caracteres.',
              ),
              const SizedBox(height: 16),
              if (!forced) ...[
                TextFormField(
                  controller: _current,
                  obscureText: true,
                  decoration: const InputDecoration(labelText: 'Senha atual'),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Informe a senha atual.';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 12),
              ],
              TextFormField(
                controller: _password,
                obscureText: true,
                decoration: const InputDecoration(labelText: 'Nova senha'),
                validator: (value) {
                  if (value == null || value.length < 8) {
                    return 'Use pelo menos 8 caracteres.';
                  }
                  if (value == '123456') {
                    return 'Não use a senha padrão.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _confirm,
                obscureText: true,
                decoration: const InputDecoration(labelText: 'Confirmar senha'),
                validator: (value) {
                  if (value != _password.text) {
                    return 'A confirmação não confere.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),
              FilledButton(
                onPressed: _busy ? null : _submit,
                child: Text(_busy ? 'Salvando...' : 'Salvar senha'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
