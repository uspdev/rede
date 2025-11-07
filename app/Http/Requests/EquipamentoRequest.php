<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EquipamentoRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $equipamentoId = $this->route('equipamento')?->id;

        $rules = [
            'hostname' => 'required|string|max:255|unique:equipamentos,hostname,' . $equipamentoId,
            'model' => 'required|string|max:255',
            'ip' => 'required|ip',
            'qtde_portas' => 'required|integer|min:1|max:48',
            'rack_id' => 'required|exists:racks,id',
            'poe_type' => 'boolean'
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'hostname.required' => 'O hostname é obrigatório',
            'hostname.max' => 'O hostname não pode ter mais que 255 caracteres',
            'hostname.unique' => 'Já existe um equipamento com este hostname',
            'model.required' => 'O modelo é obrigatório',
            'model.max' => 'O modelo não pode ter mais que 255 caracteres',
            'ip.required' => 'O IP é obrigatório',
            'ip.ip' => 'Informe um IP válido',
            'qtde_portas.required' => 'A quantidade de portas é obrigatória',
            'qtde_portas.integer' => 'A quantidade de portas deve ser um número inteiro',
            'qtde_portas.min' => 'A quantidade de portas deve ser pelo menos 1',
            'qtde_portas.max' => 'A quantidade de portas não pode ser maior que 48',
            'rack_id.required' => 'Selecione um rack',
            'rack_id.exists' => 'Rack selecionado é inválido',
            'poe_type.boolean' => 'O campo PoE deve ser verdadeiro ou falso'
        ];
    }
}
