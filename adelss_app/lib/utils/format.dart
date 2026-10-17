import 'package:intl/intl.dart';

final _dateTime = DateFormat("EEE, d MMM 'às' HH:mm", 'pt_BR');
final _date = DateFormat('EEE, d MMM y', 'pt_BR');
final _longDate = DateFormat("EEEE, d 'de' MMMM", 'pt_BR');
final _day = DateFormat('d', 'pt_BR');
final _weekday = DateFormat('EEE', 'pt_BR');
final _month = DateFormat('MMM', 'pt_BR');
final _time = DateFormat('HH:mm', 'pt_BR');
final _money = NumberFormat.currency(locale: 'pt_BR', symbol: r'R$');

String formatDateTime(DateTime? value, {bool allDay = false}) {
  if (value == null) {
    return 'Data não informada';
  }
  if (allDay) {
    return _date.format(value);
  }
  return _dateTime.format(value);
}

String formatDate(DateTime? value) {
  if (value == null) {
    return 'Data não informada';
  }
  return _date.format(value);
}

String formatLongDate(DateTime value) => capitalize(_longDate.format(value));

String formatDay(DateTime? value) => value == null ? '--' : _day.format(value);

String formatWeekday(DateTime? value) =>
    value == null ? '' : capitalize(_weekday.format(value).replaceAll('.', ''));

String formatMonth(DateTime? value) =>
    value == null ? '' : capitalize(_month.format(value).replaceAll('.', ''));

String formatTime(DateTime? value, {bool allDay = false}) {
  if (value == null) {
    return '';
  }
  return allDay ? 'Dia inteiro' : _time.format(value);
}

String formatMoney(double value) => _money.format(value);

String formatIsoDate(DateTime? value) {
  if (value == null) {
    return 'Não informado';
  }
  return DateFormat('dd/MM/yyyy', 'pt_BR').format(value);
}

String greetingFor(DateTime now) {
  final hour = now.hour;
  if (hour < 12) {
    return 'Bom dia';
  }
  if (hour < 18) {
    return 'Boa tarde';
  }
  return 'Boa noite';
}

String firstName(String value) {
  final parts = value.trim().split(RegExp(r'\s+'));
  return parts.isEmpty ? value : parts.first;
}

String initials(String value) {
  final parts = value.trim().split(RegExp(r'\s+')).where((part) => part.isNotEmpty);
  if (parts.isEmpty) {
    return 'A';
  }
  final letters = parts.take(2).map((part) => part[0].toUpperCase());
  return letters.join();
}

String capitalize(String value) {
  if (value.isEmpty) {
    return value;
  }
  return value[0].toUpperCase() + value.substring(1);
}
