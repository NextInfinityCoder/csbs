<?php

namespace App\Http\Requests;

use App\Actions\EncodeFight;
use App\EventStatus;
use App\FightResult;
use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EncodeFightRequest extends FormRequest
{
    public const int MAX_BETTORS_PER_SIDE = 1000;

    /**
     * Only ongoing events can be encoded.
     */
    public function authorize(): bool
    {
        /** @var Event $event */
        $event = $this->route('event');

        return $event->status === EventStatus::Ongoing;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'fight_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique(Fight::class)->where('event_id', $event->id),
            ],
            'meron_amount' => ['required', 'integer', 'min:0'],
            'meron_bettors' => ['required', 'integer', 'min:0', 'max:'.self::MAX_BETTORS_PER_SIDE],
            'wala_amount' => ['required', 'integer', 'min:0'],
            'wala_bettors' => ['required', 'integer', 'min:0', 'max:'.self::MAX_BETTORS_PER_SIDE],
            'date' => ['required', 'date_format:Y-m-d'],
            'time_open' => ['required', 'date_format:H:i'],
            'time_close' => ['required', 'date_format:H:i', 'after:time_open'],
            'result' => ['required', Rule::enum(FightResult::class)->except(FightResult::Pending)],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach (['meron' => 'Meron', 'wala' => 'Wala'] as $side => $label) {
                    $amount = $this->integer("{$side}_amount");
                    $bettors = $this->integer("{$side}_bettors");

                    if ($bettors === 0 && $amount > 0) {
                        $validator->errors()->add("{$side}_bettors", __('Add at least one :side bettor for the :side bet.', ['side' => $label]));
                    }

                    if ($bettors > 0 && $amount < $bettors * EncodeFight::MINIMUM_BET) {
                        $validator->errors()->add("{$side}_amount", __('The :side bet must be at least :minimum for :bettors bettors.', [
                            'side' => $label,
                            'minimum' => number_format($bettors * EncodeFight::MINIMUM_BET),
                            'bettors' => $bettors,
                        ]));
                    }
                }

                if ($this->integer('meron_bettors') + $this->integer('wala_bettors') === 0) {
                    $validator->errors()->add('meron_bettors', __('Add at least one bettor.'));
                }

                if (! User::query()->cashiers()->exists()) {
                    $validator->errors()->add('cashiers', __('Create at least one cashier before encoding.'));
                }
            },
        ];
    }
}
