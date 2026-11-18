import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import EventController from '@/actions/App/Http/Controllers/EventController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
    defaultCommissionRate,
    defaultMultiplier,
    eventStatusLabels,
} from '@/lib/events';
import type { Event, EventStatus } from '@/types';

/**
 * Format a moment as the browser-local "YYYY-MM-DDTHH:mm" value a datetime-local input expects.
 */
function toDateTimeLocalValue(date: Date): string {
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

type EventFormDialogProps = {
    event?: Event;
    trigger: ReactNode;
};

export default function EventFormDialog({
    event,
    trigger,
}: EventFormDialogProps) {
    const [isOpen, setIsOpen] = useState(false);
    const isEditing = event !== undefined;
    const formAction = isEditing
        ? EventController.update.form(event.id)
        : EventController.store.form();

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit event' : 'New event'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update the event details.'
                            : 'Fill in the details to create a new event.'}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...formAction}
                    options={{ preserveScroll: true }}
                    transform={(data) => ({
                        ...data,
                        date: data.date
                            ? new Date(String(data.date)).toISOString()
                            : '',
                    })}
                    onSuccess={() => setIsOpen(false)}
                    resetOnSuccess
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="event-name">Event name</Label>
                                <Input
                                    id="event-name"
                                    name="name"
                                    defaultValue={event?.name}
                                    placeholder="e.g. Grand Derby"
                                    required
                                    autoFocus
                                    aria-invalid={!!errors.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="event-date">
                                    Date and time
                                </Label>
                                <Input
                                    id="event-date"
                                    name="date"
                                    type="datetime-local"
                                    defaultValue={toDateTimeLocalValue(
                                        event
                                            ? new Date(event.date)
                                            : new Date(),
                                    )}
                                    required
                                    aria-invalid={!!errors.date}
                                />
                                <InputError message={errors.date} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="event-commission-rate">
                                        Commission rate (%)
                                    </Label>
                                    <Input
                                        id="event-commission-rate"
                                        name="commission_rate"
                                        type="number"
                                        inputMode="decimal"
                                        min={0}
                                        max={100}
                                        step="0.01"
                                        defaultValue={
                                            event?.commission_rate ??
                                            defaultCommissionRate
                                        }
                                        required
                                        aria-invalid={!!errors.commission_rate}
                                    />
                                    <InputError
                                        message={errors.commission_rate}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="event-multiplier">
                                        Multiplier
                                    </Label>
                                    <Input
                                        id="event-multiplier"
                                        name="multiplier"
                                        type="number"
                                        inputMode="decimal"
                                        min={0}
                                        step="0.01"
                                        defaultValue={
                                            event?.multiplier ??
                                            defaultMultiplier
                                        }
                                        required
                                        aria-invalid={!!errors.multiplier}
                                    />
                                    <InputError message={errors.multiplier} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="event-status">Status</Label>
                                <Select
                                    name="status"
                                    defaultValue={event?.status ?? 'upcoming'}
                                >
                                    <SelectTrigger
                                        id="event-status"
                                        className="w-full"
                                        aria-invalid={!!errors.status}
                                    >
                                        <SelectValue placeholder="Select a status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            {(
                                                Object.keys(
                                                    eventStatusLabels,
                                                ) as EventStatus[]
                                            ).map((status) => (
                                                <SelectItem
                                                    key={status}
                                                    value={status}
                                                >
                                                    {eventStatusLabels[status]}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {isEditing
                                        ? 'Save changes'
                                        : 'Create event'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
