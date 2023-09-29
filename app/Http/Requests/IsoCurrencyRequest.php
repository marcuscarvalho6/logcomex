<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IsoCurrencyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'iso_currency' => [
                'required',
                'array', // Certifique-se de que é um array
                Rule::requiredIf(function () {
                    // Verifique se é um número ou um código ISO 4217 válido
                    return is_numeric(request('iso_currency')) || $this->isValidIsoCurrency(request('iso_currency'));
                }),
            ],
        ];
    }

    protected function isValidIsoCurrency($values)
    {
        $isoCurrencies = [
            'USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY',
        ];

        if (is_array($values)) {
            foreach ($values as $value) {
                if (!in_array(strtoupper($value), $isoCurrencies)) {
                    return false;
                }
            }
        } else {
            return in_array(strtoupper($values), $isoCurrencies);
        }

        return true;
    }
}
