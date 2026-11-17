<?php

namespace App\Http\Requests;

use App\EventStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'commission_rate' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'date' => ['required', 'date'],
        ];
    }

    /**
     * Get the validated event attributes with the date in the application timezone.
     *
     * The form sends the date as an exact moment (with its UTC offset) so it is
     * stored correctly whatever timezone the browser is in.
     *
     * @return array{name: string, commission_rate: string, status: string, date: Carbon}
     */
    public function eventAttributes(): array
    {
        return [
            ...$this->safe()->only(['name', 'commission_rate', 'status']),
            'date' => Carbon::parse($this->validated('date'))->setTimezone(config('app.timezone')),
        ];
    }
}
