import { Head } from '@inertiajs/react';
import { Pencil, Plus, Store } from 'lucide-react';
import DataPagination from '@/components/data-pagination';
import Heading from '@/components/heading';
import StationFormDialog from '@/components/station-form-dialog';
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
import { index as stationsIndex } from '@/routes/stations';
import type { Paginated, Station } from '@/types';

type StationsIndexProps = {
    stations: Paginated<Station>;
};

export default function StationsIndex({ stations }: StationsIndexProps) {
    return (
        <>
            <Head title="Station Management" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Station Management"
                        description="Manage the stations cashiers can be assigned to."
                    />
                    <StationFormDialog
                        trigger={
                            <Button>
                                <Plus data-icon="inline-start" />
                                New station
                            </Button>
                        }
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All stations</CardTitle>
                        <CardDescription>
                            {stations.total}{' '}
                            {stations.total === 1 ? 'station' : 'stations'} in
                            total
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {stations.data.length === 0 ? (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <Store />
                                    </EmptyMedia>
                                    <EmptyTitle>No stations yet</EmptyTitle>
                                    <EmptyDescription>
                                        Stations you create will appear here.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Cashiers</TableHead>
                                        <TableHead className="w-12">
                                            <span className="sr-only">
                                                Actions
                                            </span>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {stations.data.map((station) => (
                                        <TableRow key={station.id}>
                                            <TableCell className="font-medium">
                                                {station.name}
                                            </TableCell>
                                            <TableCell className="max-w-md truncate text-muted-foreground">
                                                {station.description ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {station.cashiers_count ??
                                                        0}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <StationFormDialog
                                                    station={station}
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label={`Edit ${station.name}`}
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
                    {stations.last_page > 1 && (
                        <CardFooter className="flex flex-col gap-4 sm:flex-row sm:justify-between">
                            <p className="text-sm text-muted-foreground">
                                Showing {stations.from} to {stations.to} of{' '}
                                {stations.total}
                            </p>
                            <DataPagination paginator={stations} />
                        </CardFooter>
                    )}
                </Card>
            </div>
        </>
    );
}

StationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Station Management',
            href: stationsIndex(),
        },
    ],
};
