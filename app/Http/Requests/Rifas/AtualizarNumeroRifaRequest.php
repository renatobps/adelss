<?php

namespace App\Http\Requests\Rifas;

use Illuminate\Foundation\Http\FormRequest;

class AtualizarNumeroRifaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $numero = $this->route('numero');
        if (!$numero) {
            return false;
        }

        return $this->user()?->can('sell', $numero->rifa) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comprador_nome' => ['required', 'string', 'max:255'],
            'comprador_telefone' => ['nullable', 'string', 'max:30'],
            'vendedor_id' => ['nullable', 'exists:members,id'],
        ];
    }
}
