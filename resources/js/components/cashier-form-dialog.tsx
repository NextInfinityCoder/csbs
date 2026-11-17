import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import CashierController from '@/actions/App/Http/Controllers/CashierController';
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
import type { Cashier, Station } from '@/types';

const noStationValue = 'none';

type CashierFormDialogProps = {
    cashier?: Cashier;
    stations: Pick<Station, 'id' | 'name'>[];
    trigger: ReactNode;
};

export default function CashierFormDialog({
    cashier,
    stations,
    trigger,
}: CashierFormDialogProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [stationId, setStationId] = useState(
        cashier?.station_id ? String(cashier.station_id) : noStationValue,
    );
    const isEditing = cashier !== undefined;
    const formAction = isEditing
        ? CashierController.update.form(cashier.id)
        : CashierController.store.form();

    function handleOpenChange(open: boolean) {
        setIsOpen(open);

        if (open) {
            setStationId(
                cashier?.station_id
                    ? String(cashier.station_id)
                    : noStationValue,
            );
        }
    }

    return (
        <Dialog open={isOpen} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit cashier' : 'New cashier'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update the cashier details. Leave the password blank to keep the current one.'
                            : 'Create a cashier account and optionally assign it to a station.'}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...formAction}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setIsOpen(false)}
                    resetOnSuccess={['password', 'password_confirmation']}
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="cashier-name">Name</Label>
                                <Input
                                    id="cashier-name"
                                    name="name"
                                    defaultValue={cashier?.name}
                                    placeholder="Full name"
                                    autoComplete="off"
                                    required
                                    autoFocus
                                    aria-invalid={!!errors.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="cashier-email">
                                    Email address
                                </Label>
                                <Input
                                    id="cashier-email"
                                    name="email"
                                    type="email"
                                    defaultValue={cashier?.email}
                                    placeholder="cashier@example.com"
                                    autoComplete="off"
                                    required
                                    aria-invalid={!!errors.email}
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="cashier-station">
                                    Station{' '}
                                    <span className="text-muted-foreground">
                                        (optional)
                                    </span>
                                </Label>
                                <Select
                                    value={stationId}
                                    onValueChange={setStationId}
                                >
                                    <SelectTrigger
                                        id="cashier-station"
                                        className="w-full"
                                        aria-invalid={!!errors.station_id}
                                    >
                                        <SelectValue placeholder="Select a station" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value={noStationValue}>
                                                No station
                                            </SelectItem>
                                            {stations.map((station) => (
                                                <SelectItem
                                                    key={station.id}
                                                    value={String(station.id)}
                                                >
                                                    {station.name}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <input
                                    type="hidden"
                                    name="station_id"
                                    value={
                                        stationId === noStationValue
                                            ? ''
                                            : stationId
                                    }
                                />
                                <InputError message={errors.station_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="cashier-password">
                                    Password
                                    {isEditing && (
                                        <span className="text-muted-foreground">
                                            {' '}
                                            (optional)
                                        </span>
                                    )}
                                </Label>
                                <Input
                                    id="cashier-password"
                                    name="password"
                                    type="password"
                                    autoComplete="new-password"
                                    required={!isEditing}
                                    aria-invalid={!!errors.password}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="cashier-password-confirmation">
                                    Confirm password
                                </Label>
                                <Input
                                    id="cashier-password-confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    autoComplete="new-password"
                                    required={!isEditing}
                                />
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
                                        : 'Create cashier'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
