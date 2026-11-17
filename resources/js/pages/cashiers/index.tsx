import { Head } from '@inertiajs/react';
import { Pencil, Plus, Users } from 'lucide-react';
import CashierFormDialog from '@/components/cashier-form-dialog';
import CashierStatusToggle from '@/components/cashier-status-toggle';
import DataPagination from '@/components/data-pagination';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import { index as cashiersIndex } from '@/routes/cashiers';
import type { Cashier, Paginated, Station } from '@/types';

type CashiersIndexProps = {
    cashiers: Paginated<Cashier>;
    stations: Pick<Station, 'id' | 'name'>[];
};

export default function CashiersIndex({
    cashiers,
    stations,
}: CashiersIndexProps) {
    return (
        <>
            <Head title="Cashier Management" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Cashier Management"
                        description="Manage cashier accounts and their station assignments."
                    />
                    <CashierFormDialog
                        stations={stations}
                        trigger={
                            <Button>
                                <Plus data-icon="inline-start" />
                                New cashier
                            </Button>
                        }
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Cashiers</CardTitle>
                        <CardDescription>
                            {cashiers.total}{' '}
                            {cashiers.total === 1 ? 'cashier' : 'cashiers'} in
                            total
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {cashiers.data.length === 0 ? (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <Users />
                                    </EmptyMedia>
                                    <EmptyTitle>No cashiers yet</EmptyTitle>
                                    <EmptyDescription>
                                        Cashiers you create will appear here.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Station</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Created</TableHead>
                                        <TableHead className="w-44">
                                            <span className="sr-only">
                                                Actions
                                            </span>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {cashiers.data.map((cashier) => (
                                        <TableRow key={cashier.id}>
                                            <TableCell className="font-medium">
                                                {cashier.name}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {cashier.email}
                                            </TableCell>
                                            <TableCell>
                                                {cashier.station ? (
                                                    <Badge variant="secondary">
                                                        {cashier.station.name}
                                                    </Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        Unassigned
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        cashier.is_active
                                                            ? 'success'
                                                            : 'muted'
                                                    }
                                                >
                                                    {cashier.is_active
                                                        ? 'Active'
                                                        : 'Inactive'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {formatDateTime(
                                                    cashier.created_at,
                                                )}
                                            </TableCell>
                                            <TableCell className="flex items-center justify-end gap-1">
                                                <CashierStatusToggle
                                                    cashier={cashier}
                                                />
                                                <CashierFormDialog
                                                    cashier={cashier}
                                                    stations={stations}
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label={`Edit ${cashier.name}`}
                                                        >
                                                            <Pencil />
                                                        </Button>
                                                    }
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {cashiers.last_page > 1 && (
                        <CardFooter className="flex flex-col gap-4 sm:flex-row sm:justify-between">
                            <p className="text-sm text-muted-foreground">
                                Showing {cashiers.from} to {cashiers.to} of{' '}
                                {cashiers.total}
                            </p>
                            <DataPagination paginator={cashiers} />
                        </CardFooter>
                    )}
                </Card>
            </div>
        </>
    );
}

CashiersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Cashier Management',
            href: cashiersIndex(),
        },
    ],
};
