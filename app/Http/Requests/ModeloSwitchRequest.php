<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Support\PortasEmUso;

class ModeloSwitchRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:255',
            'fabricante' => 'required|string|max:255',
            'qtde_portas' => 'required|integer|min:1|max:96',
            'poe' => 'nullable|boolean',
            'numeracao_portas' => 'required|in:horizontal,vertical',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do modelo é obrigatório',
            'fabricante.required' => 'O fabricante é obrigatório',
            'qtde_portas.required' => 'A quantidade de portas é obrigatória',
            'poe' => 'O PoE dever ser true ou false',
        ];
    }

    /**
     * Não deixa diminuir as portas do modelo abaixo da maior porta com patch cord
     * em algum switch desse modelo.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $modelo = $this->route('modeloSwitch');
            $nova = (int) $this->input('qtde_portas');
            if (!$modelo || $validator->errors()->has('qtde_portas') || !$nova) {
                return;
            }
            $uso = PortasEmUso::modelo($modelo);
            if ($uso && $nova < $uso['porta']) {
                $validator->errors()->add('qtde_portas',
                    "Não é possível reduzir para {$nova} portas: a porta {$uso['porta']} {$uso['motivo']}. "
                    . "O mínimo para este modelo é {$uso['porta']} portas.");
            }
        });
    }
}
