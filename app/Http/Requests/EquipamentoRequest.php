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

    protected function prepareForValidation(): void
    {
        // Verifica se a requisição NÃO é da API (ou seja, é Web)
        // Se for API, o campo 'usuario_id' já precisa vir no JSON enviado pelo cliente.
        if ( !$this->is('api/*') ) {
            // Se for Web, pegamos o usuário logado e injetamos no request
            $this->merge([
                'user_id' => auth()->id(),
            ]);
        }
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
            'ip' => 'required|ip',
            'rack_id' => 'required|exists:racks,id',
            'modelo_switch_id' => 'required|exists:modelo_switches,id',
            'comentario' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'hostname.required' => 'O hostname é obrigatório',
            'hostname.unique' => 'Já existe um equipamento com este hostname',
            'ip.required' => 'O IP é obrigatório',
            'ip.ip' => 'Informe um IP válido',
            'rack_id.required' => 'Selecione um rack',
            'modelo_switch_id.required' => 'Selecione um modelo de switch',
            'tipo.required' => 'Selecione o tipo do equipamento',
            'tipo.in' => 'Tipo inválido',
        ];
    }
}
