<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaFormAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'field_id',
        'value',
        'value_list',
    ];

    protected $casts = [
        'value_list' => 'array',
    ];

    public function submission()
    {
        return $this->belongsTo(MediaFormSubmission::class, 'submission_id');
    }

    /**
     * Inclui campos removidos do construtor, porque a resposta antiga continua
     * valendo e precisa do rotulo original para ser exibida.
     */
    public function field()
    {
        return $this->belongsTo(MediaFormField::class, 'field_id')->withTrashed();
    }

    public function isEmpty(): bool
    {
        return blank($this->value) && blank($this->value_list);
    }

    /** Lista de valores escolhidos, para campos de multipla escolha. */
    public function selectedValues(): array
    {
        if (filled($this->value_list)) {
            return array_values($this->value_list);
        }

        return blank($this->value) ? [] : [$this->value];
    }
}
