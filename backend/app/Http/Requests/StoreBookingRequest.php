<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBookingRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:100', 'unique:bookings,code'],
            'created_at_booking' => ['nullable', 'date'],
            'booking_details' => ['required', 'array', 'min:1'],
            'booking_details.*.email' => ['required', 'email'],
            'booking_details.*.phone' => ['required', 'string', 'max:50'],
            'booking_details.*.leader_name' => ['required', 'string', 'max:255'],
            'booking_details.*.information_group_id' => ['nullable', 'exists:information_groups,id'],
            'booking_details.*.services' => ['required', 'array', 'min:1'],
            'booking_details.*.services.*' => ['exists:services,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'El código de reserva ya está en uso.',
            'booking_details.required' => 'Debe proporcionar al menos un detalle de reserva.',
            'booking_details.array' => 'Los detalles de reserva deben ser un arreglo.',
            'booking_details.*.email.required' => 'El correo electrónico es obligatorio.',
            'booking_details.*.email.email' => 'Debe proporcionar un correo electrónico válido.',
            'booking_details.*.phone.required' => 'El teléfono es obligatorio.',
            'booking_details.*.leader_name.required' => 'El nombre del líder es obligatorio.',
            'booking_details.*.services.required' => 'Debe seleccionar al menos un servicio.',
            'booking_details.*.services.*.exists' => 'Uno o más servicios no existen.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Errores de validación.',
            'errors' => $validator->errors()
        ], 422));
    }
}
