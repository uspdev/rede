<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RackRequest extends FormRequest
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
        return [
            'nome' => 'required|string|max:255',
            'telecom_room_id' => 'required|exists:telecom_rooms,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do rack é obrigatório',
            'nome.max' => 'O nome não pode ter mais que 255 caracteres',
            'telecom_room_id.required' => 'Escolha a Telecommunications Room do rack',
        ];
    }
}