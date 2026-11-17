import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Pencil, Plus } from 'lucide-react';
import DataPagination from '@/components/data-pagination';
import EventFormDialog from '@/components/event-form-dialog';
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
import { formatAmount, formatDateTime } from '@/lib/format';
import { eventStatusBadgeVariants, eventStatusLabels } from '@/lib/events';
import { index as eventsIndex, show as eventsShow } from '@/routes/events';
import type { Event, Paginated } from '@/types';

type EventsIndexProps = {
    events: Paginated<Event>;
};

export default function EventsIndex({ events }: EventsIndexProps) {
    return (
        <>
            <Head title="Events" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Events"
                        description="Browse all events and their commission details."
                    />
                    <EventFormDialog
                        trigger={
                            <Button>
                                <Plus data-icon="inline-start" />
                                New event
                            </Button>
                        }
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All events</CardTitle>
                        <CardDescription>
                            {events.total}{' '}
                            {events.total === 1 ? 'event' : 'events'} in total
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {events.data.length === 0 ? (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <CalendarDays />
                                    </EmptyMedia>
                                    <EmptyTitle>No events yet</EmptyTitle>
                                    <EmptyDescription>
                                        Events you create will appear here.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead className="text-right">
                                            Com%
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Commission
                                        </TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="w-12">
                                            <span className="sr-only">
                                                Actions
                                            </span>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {events.data.map((event) => (
                                        <TableRow key={event.id}>
                                            <TableCell className="font-medium">
                                                <Link
                                                    href={eventsShow(event.id)}
                                                    className="underline-offset-4 hover:underline"
                                                    prefetch
                                                >
                                                    {event.name}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {event.commission_rate}%
                                            </TableCell>
                                            <TableCell className="text-right text-base font-semibold text-success tabular-nums">
                                                {formatAmount(
                                                    event.commission_amount,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatDateTime(event.date)}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        eventStatusBadgeVariants[
                                                            event.status
                                                        ]
                                                    }
                                                >
                                                    {
                                                        eventStatusLabels[
                                                            event.status
                                                        ]
                                                    }
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <EventFormDialog
                                                    event={event}
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label={`Edit ${event.name}`}
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
                    {events.last_page > 1 && (
                        <CardFooter className="flex flex-col gap-4 sm:flex-row sm:justify-between">
                            <p className="text-sm text-muted-foreground">
                                Showing {events.from} to {events.to} of{' '}
                                {events.total}
                            </p>
                            <DataPagination paginator={events} />
                        </CardFooter>
                    )}
                </Card>
            </div>
        </>
    );
}

EventsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Events',
            href: eventsIndex(),
        },
    ],
};
