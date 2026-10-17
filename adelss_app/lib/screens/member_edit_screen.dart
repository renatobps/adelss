import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../api/models.dart';
import '../session/session_controller.dart';
import '../utils/format.dart';
import '../widgets/ui.dart';

class MemberEditScreen extends StatefulWidget {
  const MemberEditScreen({super.key, this.memberId, this.isNew = false});

  final int? memberId;
  final bool isNew;

  @override
  State<MemberEditScreen> createState() => _MemberEditScreenState();
}

class _MemberEditScreenState extends State<MemberEditScreen> {
  final _form = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _cpf = TextEditingController();
  final _rg = TextEditingController();
  final _address = TextEditingController();
  final _city = TextEditingController();
  final _state = TextEditingController();
  final _zip = TextEditingController();
  final _notes = TextEditingController();
  String? _gender;
  String? _marital;
  String? _status = 'ativo';
  int? _pgiId;
  int? _roleId;
  int? _departmentId;
  DateTime? _birth;
  DateTime? _marriage;
  DateTime? _membership;
  MemberFormOptions _options = const MemberFormOptions();
  bool _staff = false;
  bool _loading = true;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _fill();
  }

  Future<void> _fill() async {
    final session = context.read<SessionController>();
    try {
      _staff = widget.isNew || session.canEditMembers;
      if (_staff) {
        try {
          _options = await session.api.memberOptions();
        } catch (_) {}
      }
      if (widget.isNew) {
        return;
      }
      final bundle = widget.memberId == null
          ? await session.api.memberProfile()
          : await session.api.member(widget.memberId!);
      _staff = widget.isNew || bundle.canEditChurchFields || session.canEditMembers;
      final member = bundle.member;
      _name.text = member.name;
      _email.text = member.email ?? '';
      _phone.text = member.phone ?? '';
      _cpf.text = member.cpf ?? '';
      _rg.text = member.rg ?? '';
      _address.text = member.address ?? '';
      _city.text = member.city ?? '';
      _state.text = member.state ?? '';
      _zip.text = member.zipCode ?? '';
      _notes.text = member.notes ?? '';
      _gender = member.gender;
      _marital = member.maritalStatus;
      _birth = member.birthDate;
      _marriage = member.marriageDate;
      _membership = member.membershipDate;
      _status = member.status ?? 'ativo';
      _pgiId = member.pgiId;
      _roleId = member.roleId;
      _departmentId = member.departmentId;
    } on ApiException catch (error) {
      if (mounted) {
        showSnack(context, error.message, error: true);
      }
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _cpf.dispose();
    _rg.dispose();
    _address.dispose();
    _city.dispose();
    _state.dispose();
    _zip.dispose();
    _notes.dispose();
    super.dispose();
  }

  Future<void> _pickDate({required bool birth}) async {
    final current = birth ? _birth : _marriage;
    final picked = await showDatePicker(
      context: context,
      initialDate: current ?? DateTime(1990, 1, 1),
      firstDate: DateTime(1920),
      lastDate: DateTime.now(),
    );
    if (picked != null) {
      setState(() {
        if (birth) {
          _birth = picked;
        } else {
          _marriage = picked;
        }
      });
    }
  }

  Future<void> _save() async {
    if (!_form.currentState!.validate()) {
      return;
    }
    final session = context.read<SessionController>();
    setState(() => _busy = true);
    try {
      final body = <String, dynamic>{
        'name': _name.text.trim(),
        'email': _email.text.trim().isEmpty ? null : _email.text.trim(),
        'phone': _phone.text.trim().isEmpty ? null : _phone.text.trim(),
        'gender': _gender,
        'marital_status': _marital,
        'birth_date': _birth?.toIso8601String().split('T').first,
        'marriage_date': _marriage?.toIso8601String().split('T').first,
        'cpf': _cpf.text.trim().isEmpty ? null : _cpf.text.trim(),
        'rg': _rg.text.trim().isEmpty ? null : _rg.text.trim(),
        'address': _address.text.trim().isEmpty ? null : _address.text.trim(),
        'city': _city.text.trim().isEmpty ? null : _city.text.trim(),
        'state': _state.text.trim().isEmpty ? null : _state.text.trim().toUpperCase(),
        'zip_code': _zip.text.trim().isEmpty ? null : _zip.text.trim(),
        'notes': _notes.text.trim().isEmpty ? null : _notes.text.trim(),
      };
      if (_staff) {
        body['status'] = _status ?? 'ativo';
        body['pgi_id'] = _pgiId;
        body['role_id'] = _roleId;
        body['department_id'] = _departmentId;
        body['membership_date'] = _membership?.toIso8601String().split('T').first;
      }
      if (widget.isNew) {
        await session.api.createMember(body);
      } else if (widget.memberId != null) {
        await session.api.updateMember(widget.memberId!, body);
      } else {
        final bundle = await session.api.updateMemberProfile(body);
        session.applyMember(bundle.member);
      }
      if (mounted) {
        showSnack(context, widget.isNew ? 'Membro cadastrado.' : 'Dados atualizados.');
        context.pop(true);
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
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.isNew ? 'Novo membro' : 'Editar membro'),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Form(
              key: _form,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
                children: [
                  TextFormField(
                    controller: _name,
                    textCapitalization: TextCapitalization.words,
                    decoration: const InputDecoration(labelText: 'Nome'),
                    validator: (value) {
                      if (value == null || value.trim().isEmpty) {
                        return 'Informe o nome.';
                      }
                      return null;
                    },
                  ),
                  if (_staff) ...[
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      key: ValueKey('status-$_status'),
                      initialValue: _options.statuses.any((item) => item.value == _status)
                          ? _status
                          : 'ativo',
                      decoration: const InputDecoration(labelText: 'Status'),
                      items: [
                        if (_options.statuses.isEmpty)
                          const DropdownMenuItem(value: 'ativo', child: Text('Ativo')),
                        ..._options.statuses.map(
                          (item) => DropdownMenuItem(value: item.value, child: Text(item.label)),
                        ),
                      ],
                      onChanged: (value) => setState(() => _status = value),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
                      key: ValueKey('pgi-$_pgiId'),
                      initialValue: _options.pgis.any((item) => item.id == _pgiId) ? _pgiId : null,
                      decoration: const InputDecoration(labelText: 'PGI'),
                      items: [
                        const DropdownMenuItem(value: null, child: Text('Sem PGI')),
                        ..._options.pgis.map(
                          (item) => DropdownMenuItem(value: item.id, child: Text(item.name)),
                        ),
                      ],
                      onChanged: (value) => setState(() => _pgiId = value),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
                      key: ValueKey('role-$_roleId'),
                      initialValue: _options.roles.any((item) => item.id == _roleId) ? _roleId : null,
                      decoration: const InputDecoration(labelText: 'Cargo'),
                      items: [
                        const DropdownMenuItem(value: null, child: Text('Sem cargo')),
                        ..._options.roles.map(
                          (item) => DropdownMenuItem(value: item.id, child: Text(item.name)),
                        ),
                      ],
                      onChanged: (value) => setState(() => _roleId = value),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
                      key: ValueKey('dept-$_departmentId'),
                      initialValue: _options.departments.any((item) => item.id == _departmentId)
                          ? _departmentId
                          : null,
                      decoration: const InputDecoration(labelText: 'Departamento'),
                      items: [
                        const DropdownMenuItem(value: null, child: Text('Sem departamento')),
                        ..._options.departments.map(
                          (item) => DropdownMenuItem(value: item.id, child: Text(item.name)),
                        ),
                      ],
                      onChanged: (value) => setState(() => _departmentId = value),
                    ),
                  ],
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _email,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(labelText: 'E-mail'),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _phone,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(labelText: 'Telefone'),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String?>(
                    key: ValueKey('gender-${_gender ?? 'none'}'),
                    initialValue: _gender,
                    decoration: const InputDecoration(labelText: 'Gênero'),
                    items: const [
                      DropdownMenuItem(value: null, child: Text('Não informado')),
                      DropdownMenuItem(value: 'M', child: Text('Masculino')),
                      DropdownMenuItem(value: 'F', child: Text('Feminino')),
                    ],
                    onChanged: (value) => setState(() => _gender = value),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String?>(
                    key: ValueKey('marital-${_marital ?? 'none'}'),
                    initialValue: _marital,
                    decoration: const InputDecoration(labelText: 'Estado civil'),
                    items: const [
                      DropdownMenuItem(value: null, child: Text('Não informado')),
                      DropdownMenuItem(value: 'solteiro', child: Text('Solteiro(a)')),
                      DropdownMenuItem(value: 'casado', child: Text('Casado(a)')),
                      DropdownMenuItem(value: 'divorciado', child: Text('Divorciado(a)')),
                      DropdownMenuItem(value: 'viuvo', child: Text('Viúvo(a)')),
                      DropdownMenuItem(value: 'uniao_estavel', child: Text('União estável')),
                    ],
                    onChanged: (value) => setState(() => _marital = value),
                  ),
                  const SizedBox(height: 12),
                  SurfaceCard(
                    onTap: () => _pickDate(birth: true),
                    child: InfoRow(
                      label: 'Nascimento',
                      value: _birth == null ? null : formatIsoDate(_birth),
                    ),
                  ),
                  const SizedBox(height: 10),
                  SurfaceCard(
                    onTap: () => _pickDate(birth: false),
                    child: InfoRow(
                      label: 'Casamento',
                      value: _marriage == null ? null : formatIsoDate(_marriage),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _cpf,
                    decoration: const InputDecoration(labelText: 'CPF'),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _rg,
                    decoration: const InputDecoration(labelText: 'RG'),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _address,
                    decoration: const InputDecoration(labelText: 'Endereço'),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _city,
                    decoration: const InputDecoration(labelText: 'Cidade'),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _state,
                    maxLength: 2,
                    textCapitalization: TextCapitalization.characters,
                    decoration: const InputDecoration(labelText: 'UF', counterText: ''),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _zip,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'CEP'),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _notes,
                    maxLines: 3,
                    decoration: const InputDecoration(labelText: 'Observações'),
                  ),
                  const SizedBox(height: 20),
                  FilledButton(
                    onPressed: _busy ? null : _save,
                    child: Text(_busy ? 'Salvando...' : 'Salvar'),
                  ),
                ],
              ),
            ),
    );
  }
}
