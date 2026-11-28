<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\PortasEmUso;
use App\Models\ModeloSwitch;

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
            'hostname.unique' => 'Já existe um switch com este hostname',
            'ip.required' => 'O IP é obrigatório',
            'ip.ip' => 'Informe um IP válido',
            'rack_id.required' => 'Selecione um rack',
            'modelo_switch_id.required' => 'Selecione um modelo de switch',
            'tipo.required' => 'Selecione o tipo do switch',
            'tipo.in' => 'Tipo inválido',
        ];
    }

    /**
     * Ao trocar o modelo, o novo precisa ter portas suficientes para a maior porta com patch cord.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $equipamento = $this->route('equipamento');
            if (!$equipamento || $validator->errors()->has('modelo_switch_id')) {
                return;
            }
            $modelo = ModeloSwitch::find($this->input('modelo_switch_id'));
            $uso = PortasEmUso::switch($equipamento);
            if ($modelo && $uso && $modelo->qtde_portas < $uso['porta']) {
                $validator->errors()->add('modelo_switch_id',
                    "O modelo {$modelo->nome} tem {$modelo->qtde_portas} portas, mas a porta {$uso['porta']} deste switch está {$uso['motivo']}. "
                    . "Escolha um modelo com pelo menos {$uso['porta']} portas (ou desconecte a porta antes).");
            }
        });
    }
}
