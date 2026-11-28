<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TelecomRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tr = $this->route('telecomRoom');
        $predioId = $tr?->predio_id ?? $this->input('predio_id');

        return [
            'predio_id' => $tr ? 'prohibited' : 'required|exists:predios,id',
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('telecom_rooms')->where('predio_id', $predioId)->ignore($tr),
            ],
            'descricao' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da Telecommunications Room é obrigatório',
            'nome.unique' => 'Já existe uma Telecommunications Room com este nome neste prédio',
            'predio_id.required' => 'O prédio é obrigatório',
        ];
    }
}
