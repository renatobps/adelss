<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleNotificationSetting extends Model
{
    public const WEEKDAYS = [
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    public const VARIABLES = [
        '{nome}' => 'Nome do voluntário',
        '{mes}' => 'Mês/ano da escala (ex.: setembro/2026)',
        '{escalas}' => 'Lista das escalas do período',
        '{culto}' => 'Nome do culto',
        '{dia_culto}' => 'Data do culto',
        '{hora_culto}' => 'Horário do culto',
        '{area_servico}' => 'Área em que vai servir',
        '{local_servico}' => 'Local do culto',
        '{responsavel_area}' => 'Responsável pela área',
    ];

    public const DEFAULT_IMMEDIATE_INDIVIDUAL_TEMPLATE = "✨ Olá {nome}, paz do Senhor! 🙏\n\nVocê está escalado para servir no culto e será uma bênção nesse dia!\n\n⛪ Culto: {culto}\n📅 Dia: {dia_culto}\n⏰ Hora: {hora_culto}\n📍 Área de Serviço: {area_servico}\n📌 Local: {local_servico}\n\nObs: Caso você não consiga ir, avise com pelo menos 1 dia de antecedência o responsável pela área: {responsavel_area}\n\nContamos com você 💙";

    public const DEFAULT_IMMEDIATE_GROUP_TEMPLATE = "Confira a escala do culto, chegue 20 minutos antes do culto iniciar contamos com vc!!\n\n⛪ Culto: {culto}\n📅 Dia: {dia_culto}\n⏰ Hora: {hora_culto}\n\nObs: Caso vc não consiga ir avise com pelo menos 1 dia de antecedencia ao responsavel pela areas: {responsavel_area}";

    protected $fillable = [
        'month_enabled',
        'month_day',
        'month_time',
        'month_template',
        'week_enabled',
        'week_weekday',
        'week_time',
        'week_template',
        'day_enabled',
        'day_time',
        'day_template',
        'immediate_individual_template',
        'immediate_group_template',
    ];

    protected $casts = [
        'month_enabled' => 'boolean',
        'week_enabled' => 'boolean',
        'day_enabled' => 'boolean',
        'month_day' => 'integer',
        'week_weekday' => 'integer',
    ];

    public static function current(): self
    {
        $defaults = [
            'month_enabled' => false,
            'month_day' => 1,
            'month_time' => '09:00',
            'month_template' => "Olá, {nome}! 🙏\n\nSegue sua escala de *{mes}*:\n{escalas}\n\nDeus abençoe seu serviço!",
            'week_enabled' => false,
            'week_weekday' => 1,
            'week_time' => '09:00',
            'week_template' => "Olá, {nome}! 🙏\n\nNesta semana você está escalado(a) em:\n{escalas}\n\nDeus abençoe!",
            'day_enabled' => false,
            'day_time' => '09:00',
            'day_template' => "Olá, {nome}! 🙏\n\nLembrete: hoje ({dia_culto}) você serve no *{culto}* às {hora_culto} na área *{area_servico}*.\n\nDeus abençoe!",
            'immediate_individual_template' => self::DEFAULT_IMMEDIATE_INDIVIDUAL_TEMPLATE,
            'immediate_group_template' => self::DEFAULT_IMMEDIATE_GROUP_TEMPLATE,
        ];

        return static::firstOrCreate([], $defaults);
    }

    public function resolvedImmediateIndividualTemplate(): string
    {
        $template = trim((string) $this->immediate_individual_template);

        return $template !== '' ? $template : self::DEFAULT_IMMEDIATE_INDIVIDUAL_TEMPLATE;
    }

    public function resolvedImmediateGroupTemplate(): string
    {
        $template = trim((string) $this->immediate_group_template);

        return $template !== '' ? $template : self::DEFAULT_IMMEDIATE_GROUP_TEMPLATE;
    }

    public function timeHour(string $field): int
    {
        return (int) explode(':', (string) ($this->{$field} ?: '09:00'))[0];
    }

    public function timeMinute(string $field): int
    {
        return (int) (explode(':', (string) ($this->{$field} ?: '09:00'))[1] ?? 0);
    }

    public function reachedSendTime(string $timeField): bool
    {
        $target = now()->copy()->setTime($this->timeHour($timeField), $this->timeMinute($timeField));

        return now()->gte($target);
    }
}
