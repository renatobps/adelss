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
            'atualizar_vendedores' => ['sometimes', 'boolean'],
            'vendedor_ids' => ['sometimes', 'array', 'max:10000'],
            'vendedor_ids.*' => ['integer', 'distinct', \Illuminate\Validation\Rule::exists('members', 'id')->whereNull('deleted_at')],
            'department_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'nome' => ['required', 'string', 'max:255'],
            'pix_chave' => ['nullable', 'required_with:pix_recebedor', 'string', 'max:255'],
            'pix_recebedor' => ['nullable', 'required_with:pix_chave', 'string', 'max:120'],
            'valor_numero' => ['required', 'numeric', 'min:0.01'],
            'data_sorteio' => ['nullable', 'date'],
            'status' => ['required', 'in:ativa,finalizada,cancelada'],
        ];
    }

    public function messages(): array
    {
        return [
            'pix_chave.required_with' => 'Informe a chave PIX do recebedor.',
            'pix_recebedor.required_with' => 'Informe o nome do recebedor da chave PIX.',
        ];
    }
}
