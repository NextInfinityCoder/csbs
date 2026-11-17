import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import StationController from '@/actions/App/Http/Controllers/StationController';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Station } from '@/types';

type StationFormDialogProps = {
    station?: Station;
    trigger: ReactNode;
};

export default function StationFormDialog({
    station,
    trigger,
}: StationFormDialogProps) {
    const [isOpen, setIsOpen] = useState(false);
    const isEditing = station !== undefined;
    const formAction = isEditing
        ? StationController.update.form(station.id)
        : StationController.store.form();

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Edit station' : 'New station'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Update the station details.'
                            : 'Fill in the details to create a new station.'}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...formAction}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setIsOpen(false)}
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="station-name">Name</Label>
                                <Input
                                    id="station-name"
                                    name="name"
                                    defaultValue={station?.name}
                                    placeholder="e.g. Station 1"
                                    required
                                    autoFocus
                                    aria-invalid={!!errors.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="station-description">
                                    Description{' '}
                                    <span className="text-muted-foreground">
                                        (optional)
                                    </span>
                                </Label>
                                <Textarea
                                    id="station-description"
                                    name="description"
                                    defaultValue={station?.description ?? ''}
                                    placeholder="e.g. Ground floor, near the main entrance"
                                    aria-invalid={!!errors.description}
                                />
                                <InputError message={errors.description} />
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
                                        : 'Create station'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
