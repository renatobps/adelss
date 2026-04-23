<?php

namespace App\Http\Requests\Rifas;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarVendaRapidaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $rifa = $this->route('rifa');
        return $rifa ? ($this->user()?->can('sell', $rifa) ?? false) : false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vendedor_id' => ['required', 'exists:members,id'],
            'comprador_nome' => ['required', 'string', 'max:255'],
            'comprador_telefone' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'in:reservado,vendido'],
            'numero_ids' => ['required', 'array', 'min:1'],
            'numero_ids.*' => ['integer', 'exists:numeros_rifa,id'],
        ];
    }
}
