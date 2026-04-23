<?php

namespace App\Http\Requests\Rifas;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRifaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $rifa = $this->route('rifa');
        return $rifa ? ($this->user()?->can('update', $rifa) ?? false) : false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'valor_numero' => ['required', 'numeric', 'min:0.01'],
            'data_sorteio' => ['nullable', 'date'],
            'status' => ['required', 'in:ativa,finalizada,cancelada'],
        ];
    }
}
