<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CurrencyRequest extends FormRequest
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
            'code' => [
                'nullable',
                'string',
                Rule::requiredIf(function () {
                    return !$this->input('code_list') && !$this->input('number') && !$this->input('number_list');
                }),
                Rule::prohibitedIf(function() {
                    return $this->input('code_list') || $this->input('number') || $this->input('number_list');
                }),
                'exists:currencies,code'
            ],
            'code_list' => [
                'nullable',
                'array',
                Rule::requiredIf(function () {
                    return !$this->input('code') && !$this->input('number') && !$this->input('number_list');
                }),
                Rule::prohibitedIf(function() {
                    return $this->input('code') || $this->input('number') || $this->input('number_list');
                }),
                'exists:currencies,code'
            ],
            'number' => [
                'nullable',
                'integer',
                Rule::requiredIf(function () {
                    return !$this->input('code') && !$this->input('code_list') && !$this->input('number_list');
                }),
                Rule::prohibitedIf(function() {
                    return $this->input('code') || $this->input('code_list') || $this->input('number_list');
                }),
            ],
            'number_list' => [
                'nullable',
                'array',
                'exists:currencies,number',
                Rule::requiredIf(function () {
                    return !$this->input('code') && !$this->input('code_list') && !$this->input('number');
                }),
                Rule::prohibitedIf(function() {
                    return $this->input('code') || $this->input('code_list') || $this->input('number');
                }),
            ],
            'exchange' => 'string|min:3|max:3|exists:currencies,code'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.*
     * @return array
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => implode('', collect($validator->errors())->first()),
            'status' => true
        ], 422));
    }

    public function messages()
    {
        return [
            'required_without_all' => 'Apenas um dos campos deve ser preenchido: code, code_list, number ou number_list.',
        ];
    }
}
