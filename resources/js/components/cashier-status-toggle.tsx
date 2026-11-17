import { router } from '@inertiajs/react';
import { Power, PowerOff } from 'lucide-react';
import { useState } from 'react';
import CashierStatusController from '@/actions/App/Http/Controllers/CashierStatusController';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { Cashier } from '@/types';

export default function CashierStatusToggle({ cashier }: { cashier: Cashier }) {
    const [isSaving, setIsSaving] = useState(false);
    const Icon = cashier.is_active ? PowerOff : Power;

    function toggle() {
        router.patch(
            CashierStatusController.url(cashier.id),
            { is_active: !cashier.is_active },
            {
                preserveScroll: true,
                onStart: () => setIsSaving(true),
                onFinish: () => setIsSaving(false),
            },
        );
    }

    return (
        <Button
            variant="outline"
            size="sm"
            className="w-28"
            onClick={toggle}
            disabled={isSaving}
            aria-label={`${cashier.is_active ? 'Deactivate' : 'Activate'} ${cashier.name}`}
        >
            {isSaving ? <Spinner /> : <Icon data-icon="inline-start" />}
            {cashier.is_active ? 'Deactivate' : 'Activate'}
        </Button>
    );
}
