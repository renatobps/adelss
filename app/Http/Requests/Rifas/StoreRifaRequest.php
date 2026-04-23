<?php

namespace App\Http\Requests\Rifas;

use Illuminate\Foundation\Http\FormRequest;

class StoreRifaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Rifa::class) ?? false;
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
            'quantidade_numeros' => ['required', 'integer', 'min:1', 'max:100000'],
            'valor_numero' => ['required', 'numeric', 'min:0.01'],
            'numeros_por_cartela' => ['required', 'integer', 'min:1', 'lte:quantidade_numeros'],
            'data_sorteio' => ['nullable', 'date'],
            'status' => ['required', 'in:ativa,finalizada,cancelada'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome da rifa.',
            'quantidade_numeros.required' => 'Informe a quantidade de números.',
            'valor_numero.required' => 'Informe o valor de cada número.',
            'numeros_por_cartela.required' => 'Informe a quantidade de números por cartela.',
            'status.in' => 'Status inválido para a rifa.',
        ];
    }
}
