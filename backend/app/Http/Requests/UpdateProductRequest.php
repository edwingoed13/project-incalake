<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProductRequest extends FormRequest
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
        $productId = $this->route('product');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', 'unique:products,code,' . $productId],
            'nearest_city' => ['nullable', 'string', 'max:255'],
            'nearest_airport' => ['nullable', 'string', 'max:255'],
            'service_id' => ['sometimes', 'required', 'exists:services,id'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'duration' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'attachments' => ['nullable', 'string'],
            'product_code_id' => ['nullable', 'exists:product_codes,id'],
            'status' => ['nullable', 'boolean'],
            'policies' => ['nullable', 'string'],
            'booking_anticipation' => ['nullable', 'string', 'max:255'],
            'data_requirement' => ['nullable', 'integer', 'in:0,1,2,3'],
            'multiple_forms' => ['nullable', 'boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],
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
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede tener más de 255 caracteres.',
            'code.unique' => 'El código ya está en uso.',
            'service_id.required' => 'El servicio es obligatorio.',
            'service_id.exists' => 'El servicio seleccionado no existe.',
            'capacity.integer' => 'La capacidad debe ser un número entero.',
            'capacity.min' => 'La capacidad debe ser al menos 1.',
            'categories.array' => 'Las categorías deben ser un arreglo.',
            'categories.*.exists' => 'Una o más categorías no existen.',
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
