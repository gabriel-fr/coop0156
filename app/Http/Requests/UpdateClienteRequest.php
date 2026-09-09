<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $clienteId = $this->route('cliente');

        return [
            'nome' => ['required', 'string'],
            'cpf' => ['required', 'digits:11', Rule::unique('clientes', 'cpf')->ignore($clienteId)],
            'email' => ['required', 'email', Rule::unique('clientes', 'email')->ignore($clienteId)],
            'telefone' => ['nullable', 'string'],
            'renda_mensal' => ['required', 'numeric', 'min:0'],
        ];
    }
}
