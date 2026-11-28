<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoPortaSwitchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tipo_portas_switch')->ignore($this->route('tipoPortaSwitch')),
            ],
            'cor' => ['nullable', 'string', 'hex_color'],
            'vlan' => ['nullable', 'integer', 'between:1,4094'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do tipo de porta é obrigatório',
            'nome.max' => 'O nome não pode ter mais que 255 caracteres',
            'nome.unique' => 'Já existe um tipo de porta de switch com este nome',
            'vlan.integer' => 'A VLAN deve ser um número',
            'vlan.between' => 'A VLAN deve estar entre 1 e 4094',
        ];
    }
}
