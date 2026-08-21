<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaFormField extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_NUMBER = 'number';
    public const TYPE_SELECT = 'select';
    public const TYPE_RADIO = 'radio';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_DATE = 'date';
    public const TYPE_EMAIL = 'email';
    public const TYPE_PHONE = 'phone';

    /** Rotulos exibidos no construtor de campos. */
    public const TYPES = [
        self::TYPE_TEXT => 'Texto curto',
        self::TYPE_TEXTAREA => 'Texto longo',
        self::TYPE_NUMBER => 'Numero',
        self::TYPE_SELECT => 'Lista de selecao',
        self::TYPE_RADIO => 'Escolha unica',
        self::TYPE_CHECKBOX => 'Caixas de selecao',
        self::TYPE_DATE => 'Data',
        self::TYPE_EMAIL => 'E-mail',
        self::TYPE_PHONE => 'Telefone',
    ];

    /** Tipos que exigem uma lista de opcoes cadastrada. */
    public const TYPES_WITH_OPTIONS = [
        self::TYPE_SELECT,
        self::TYPE_RADIO,
        self::TYPE_CHECKBOX,
    ];

    protected $fillable = [
        'media_form_id',
        'label',
        'help_text',
        'type',
        'options',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function form()
    {
        return $this->belongsTo(MediaForm::class, 'media_form_id');
    }

    public function answers()
    {
        return $this->hasMany(MediaFormAnswer::class, 'field_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, self::TYPES_WITH_OPTIONS, true);
    }

    /** Somente as caixas de selecao aceitam mais de um valor por resposta. */
    public function acceptsMultipleValues(): bool
    {
        return $this->type === self::TYPE_CHECKBOX;
    }

    /** Campos de escolha podem ser resumidos em contagem por opcao. */
    public function isCountable(): bool
    {
        return $this->hasOptions();
    }

    /**
     * Valor pronto para leitura. Datas são guardadas no formato ISO para
     * ordenarem certo, e só viram dd/mm/aaaa na hora de exibir.
     */
    public function formatValue(?MediaFormAnswer $answer): string
    {
        if (!$answer || $answer->isEmpty()) {
            return '';
        }

        if ($this->type === self::TYPE_DATE) {
            try {
                return \Illuminate\Support\Carbon::parse($answer->value)->format('d/m/Y');
            } catch (\Throwable $e) {
                return (string) $answer->value;
            }
        }

        return (string) $answer->value;
    }

    public function optionList(): array
    {
        return array_values(array_filter(
            array_map('strval', $this->options ?? []),
            fn ($option) => $option !== ''
        ));
    }
}
