<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
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
        return [
            'nome' => ['required', 'string'],
            'cpf' => ['required', 'digits:11', 'unique:clientes,cpf'],
            'email' => ['required', 'email', 'unique:clientes,email'],
            'telefone' => ['nullable', 'string'],
            'renda_mensal' => ['required', 'numeric', 'min:0'],
        ];
    }
}
