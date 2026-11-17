<?php

namespace App\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Station;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashierRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

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
        /** @var User|null $cashier */
        $cashier = $this->route('cashier');

        $passwordRules = $this->passwordRules();

        if ($cashier !== null) {
            $passwordRules[0] = 'nullable';
        }

        return [
            ...$this->profileRules($cashier?->id),
            'password' => $passwordRules,
            'station_id' => ['nullable', 'integer', Rule::exists(Station::class, 'id')],
        ];
    }
}
