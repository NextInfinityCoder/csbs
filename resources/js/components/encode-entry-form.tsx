import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useRef } from 'react';
import EncodeController from '@/actions/App/Http/Controllers/EncodeController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { calculateOdds, fightResultLabels } from '@/lib/fights';
import { formatAmount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Event, FightResult } from '@/types';

const MINIMUM_BET = 100;
const BETTING_MINUTES = 5;

type EntryData = {
    fight_number: string;
    date: string;
    time_open: string;
    time_close: string;
    meron_amount: string;
    meron_bettors: string;
    wala_amount: string;
    wala_bettors: string;
    result: FightResult | '';
};

const resultOptions: {
    value: Exclude<FightResult, 'pending'>;
    className: string;
}[] = [
    {
        value: 'meron',
        className:
            'data-[state=on]:bg-destructive data-[state=on]:text-white data-[state=on]:border-destructive',
    },
    {
        value: 'wala',
        className:
            'data-[state=on]:bg-info data-[state=on]:text-info-foreground data-[state=on]:border-info',
    },
    {
        value: 'draw',
        className:
            'data-[state=on]:bg-success data-[state=on]:text-success-foreground data-[state=on]:border-success',
    },
    {
        value: 'cancelled',
        className:
            'data-[state=on]:bg-muted-foreground data-[state=on]:text-background data-[state=on]:border-muted-foreground',
    },
];

function addMinutes(time: string, minutes: number): string {
    const [hours, mins] = time.split(':').map(Number);
    const total = (hours * 60 + mins + minutes) % (24 * 60);

    return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
}

function toWholeNumber(value: string): number {
    const number = Number(value);

    return Number.isFinite(number) && number > 0 ? Math.floor(number) : 0;
}

type SidePanelProps = {
    side: 'meron' | 'wala';
    label: string;
    accentClassName: string;
    amount: string;
    bettors: string;
    odds: number;
    amountError?: string;
    bettorsError?: string;
    onAmountChange: (value: string) => void;
    onBettorsChange: (value: string) => void;
    amountInputRef?: React.Ref<HTMLInputElement>;
};

function SidePanel({
    side,
    label,
    accentClassName,
    amount,
    bettors,
    odds,
    amountError,
    bettorsError,
    onAmountChange,
    onBettorsChange,
    amountInputRef,
}: SidePanelProps) {
    const amountValue = toWholeNumber(amount);
    const bettorsValue = toWholeNumber(bettors);
    const minimumAmount = bettorsValue * MINIMUM_BET;
    const isBelowMinimum = bettorsValue > 0 && amountValue < minimumAmount;

    return (
        <div
            className={cn(
                'flex flex-col gap-4 rounded-lg border border-l-4 p-4',
                accentClassName,
            )}
        >
            <div className="flex items-baseline justify-between gap-2">
                <span className="text-sm font-semibold tracking-wider uppercase">
                    {label}
                </span>
                <span className="text-sm text-muted-foreground">
                    Odds{' '}
                    <span className="text-lg font-semibold text-foreground tabular-nums">
                        {odds > 0 ? `${odds.toFixed(1)}%` : '—'}
                    </span>
                </span>
            </div>

            <div className="grid grid-cols-[minmax(0,2fr)_minmax(0,1fr)] gap-3">
                <div className="grid gap-2">
                    <Label htmlFor={`${side}-amount`}>Total Bet</Label>
                    <Input
                        ref={amountInputRef}
                        id={`${side}-amount`}
                        autoFocus={side === 'meron'}
                        type="number"
                        inputMode="numeric"
                        min={0}
                        step={1}
                        placeholder="0"
                        className="h-11 text-lg font-semibold tabular-nums"
                        value={amount}
                        onChange={(event) => onAmountChange(event.target.value)}
                        required
                        aria-invalid={!!amountError || isBelowMinimum}
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor={`${side}-bettors`}>Bettors</Label>
                    <Input
                        id={`${side}-bettors`}
                        type="number"
                        inputMode="numeric"
                        min={0}
                        step={1}
                        placeholder="0"
                        className="h-11 text-lg font-semibold tabular-nums"
                        value={bettors}
                        onChange={(event) =>
                            onBettorsChange(event.target.value)
                        }
                        required
                        aria-invalid={!!bettorsError}
                    />
                </div>
            </div>

            <p
                className={cn(
                    'text-xs',
                    isBelowMinimum
                        ? 'text-destructive-foreground'
                        : 'text-muted-foreground',
                )}
            >
                {bettorsValue > 0
                    ? `Minimum ${formatAmount(minimumAmount)} for ${bettorsValue} ${bettorsValue === 1 ? 'bettor' : 'bettors'}`
                    : `At least ${formatAmount(MINIMUM_BET)} per bettor`}
            </p>
            <InputError message={amountError} />
            <InputError message={bettorsError} />
        </div>
    );
}

function SummaryItem({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex flex-col gap-0.5">
            <span className="text-xs text-muted-foreground">{label}</span>
            <span className="font-semibold tabular-nums">{value}</span>
        </div>
    );
}

type EncodeEntryFormProps = {
    event: Event;
    nextFightNumber: number;
    defaultTimeOpen: string | null;
    today: string;
    hasCashiers: boolean;
};

export default function EncodeEntryForm({
    event,
    nextFightNumber,
    defaultTimeOpen,
    today,
    hasCashiers,
}: EncodeEntryFormProps) {
    const meronAmountInput = useRef<HTMLInputElement>(null);

    const form = useForm<EntryData>({
        fight_number: String(nextFightNumber),
        date: today,
        time_open: defaultTimeOpen ?? '',
        time_close: defaultTimeOpen
            ? addMinutes(defaultTimeOpen, BETTING_MINUTES)
            : '',
        meron_amount: '',
        meron_bettors: '',
        wala_amount: '',
        wala_bettors: '',
        result: '',
    });

    const commissionRate = Number(event.commission_rate);
    const meronAmount = toWholeNumber(form.data.meron_amount);
    const walaAmount = toWholeNumber(form.data.wala_amount);
    const totalPool = meronAmount + walaAmount;
    const totalBettors =
        toWholeNumber(form.data.meron_bettors) +
        toWholeNumber(form.data.wala_bettors);
    const commission =
        form.data.result === 'draw' || form.data.result === 'cancelled'
            ? 0
            : (totalPool * commissionRate) / 100;

    function changeTimeOpen(value: string) {
        form.setData((data) => ({
            ...data,
            time_open: value,
            time_close:
                value && (!data.time_close || data.time_close <= value)
                    ? addMinutes(value, BETTING_MINUTES)
                    : data.time_close,
        }));
    }

    function submit(submitEvent: FormEvent<HTMLFormElement>) {
        submitEvent.preventDefault();

        form.post(EncodeController.store.url(event.id), {
            preserveScroll: true,
            onError: () => meronAmountInput.current?.focus(),
        });
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-6">
            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div className="grid gap-2">
                    <Label htmlFor="fight-number">Fight #</Label>
                    <Input
                        id="fight-number"
                        type="number"
                        inputMode="numeric"
                        min={1}
                        step={1}
                        className="h-11 text-lg font-semibold tabular-nums"
                        value={form.data.fight_number}
                        onChange={(event) =>
                            form.setData('fight_number', event.target.value)
                        }
                        required
                        aria-invalid={!!form.errors.fight_number}
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="fight-date">Date</Label>
                    <Input
                        id="fight-date"
                        type="date"
                        className="h-11"
                        value={form.data.date}
                        onChange={(event) =>
                            form.setData('date', event.target.value)
                        }
                        required
                        aria-invalid={!!form.errors.date}
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="time-open">Time Open</Label>
                    <Input
                        id="time-open"
                        type="time"
                        className="h-11"
                        value={form.data.time_open}
                        onChange={(event) => changeTimeOpen(event.target.value)}
                        required
                        aria-invalid={!!form.errors.time_open}
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="time-close">Time Close</Label>
                    <Input
                        id="time-close"
                        type="time"
                        className="h-11"
                        value={form.data.time_close}
                        onChange={(event) =>
                            form.setData('time_close', event.target.value)
                        }
                        required
                        aria-invalid={!!form.errors.time_close}
                    />
                </div>
            </div>
            {(form.errors.fight_number ||
                form.errors.date ||
                form.errors.time_open ||
                form.errors.time_close) && (
                <div className="-mt-3 flex flex-col gap-1">
                    <InputError message={form.errors.fight_number} />
                    <InputError message={form.errors.date} />
                    <InputError message={form.errors.time_open} />
                    <InputError message={form.errors.time_close} />
                </div>
            )}

            <div className="grid gap-4 md:grid-cols-2">
                <SidePanel
                    side="meron"
                    label="Meron"
                    accentClassName="border-l-destructive"
                    amount={form.data.meron_amount}
                    bettors={form.data.meron_bettors}
                    odds={calculateOdds(
                        meronAmount,
                        walaAmount,
                        commissionRate,
                    )}
                    amountError={form.errors.meron_amount}
                    bettorsError={form.errors.meron_bettors}
                    onAmountChange={(value) =>
                        form.setData('meron_amount', value)
                    }
                    onBettorsChange={(value) =>
                        form.setData('meron_bettors', value)
                    }
                    amountInputRef={meronAmountInput}
                />
                <SidePanel
                    side="wala"
                    label="Wala"
                    accentClassName="border-l-info"
                    amount={form.data.wala_amount}
                    bettors={form.data.wala_bettors}
                    odds={calculateOdds(
                        walaAmount,
                        meronAmount,
                        commissionRate,
                    )}
                    amountError={form.errors.wala_amount}
                    bettorsError={form.errors.wala_bettors}
                    onAmountChange={(value) =>
                        form.setData('wala_amount', value)
                    }
                    onBettorsChange={(value) =>
                        form.setData('wala_bettors', value)
                    }
                />
            </div>

            <div className="grid gap-2">
                <Label id="fight-result-label">Result</Label>
                <ToggleGroup
                    type="single"
                    variant="outline"
                    className="grid w-full grid-cols-2 sm:grid-cols-4"
                    value={form.data.result}
                    onValueChange={(value) =>
                        form.setData('result', value as FightResult | '')
                    }
                    aria-labelledby="fight-result-label"
                >
                    {resultOptions.map((option) => (
                        <ToggleGroupItem
                            key={option.value}
                            value={option.value}
                            className={cn(
                                'h-11 font-semibold',
                                option.className,
                            )}
                        >
                            {fightResultLabels[option.value]}
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>
                <InputError message={form.errors.result} />
            </div>

            <Separator />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="grid grid-cols-3 gap-6">
                    <SummaryItem
                        label="Total Pool"
                        value={formatAmount(totalPool)}
                    />
                    <SummaryItem
                        label={`Commission (${event.commission_rate}%)`}
                        value={formatAmount(commission)}
                    />
                    <SummaryItem
                        label="Total Bettors"
                        value={String(totalBettors)}
                    />
                </div>
                <div className="flex flex-col items-stretch gap-2 sm:items-end">
                    <Button
                        type="submit"
                        size="lg"
                        className="sm:min-w-48"
                        disabled={
                            form.processing ||
                            !hasCashiers ||
                            form.data.result === ''
                        }
                    >
                        {form.processing && <Spinner />}
                        Encode Fight #{form.data.fight_number || '—'}
                    </Button>
                    <InputError
                        message={
                            (form.errors as Partial<Record<string, string>>)
                                .cashiers
                        }
                    />
                </div>
            </div>
        </form>
    );
}
