import { Head, Link } from '@inertiajs/react';
import { CalendarDays, ClipboardPen } from 'lucide-react';
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
import { eventStatusBadgeVariants, eventStatusLabels } from '@/lib/events';
import { formatDateTime } from '@/lib/format';
import { index as encodeIndex, show as encodeShow } from '@/routes/encode';
import type { Event, Paginated } from '@/types';

type EncodeIndexProps = {
    events: Paginated<Event>;
};

export default function EncodeIndex({ events }: EncodeIndexProps) {
    return (
        <>
            <Head title="Encode" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Encode"
                    description="Pick an ongoing event to start encoding its fights."
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Ongoing Events</CardTitle>
                        <CardDescription>
                            {events.total}{' '}
                            {events.total === 1 ? 'event' : 'events'} ongoing
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {events.data.length === 0 ? (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <CalendarDays />
                                    </EmptyMedia>
                                    <EmptyTitle>No ongoing events</EmptyTitle>
                                    <EmptyDescription>
                                        Ask an admin to set an event to Ongoing
                                        to start encoding.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Event</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Fights Encoded
                                        </TableHead>
                                        <TableHead className="w-32">
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
                                                {event.name}
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
                                            <TableCell className="text-right tabular-nums">
                                                {event.fights_count ?? 0}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button size="sm" asChild>
                                                    <Link
                                                        href={encodeShow(
                                                            event.id,
                                                        )}
                                                        prefetch
                                                    >
                                                        <ClipboardPen data-icon="inline-start" />
                                                        Encode
                                                    </Link>
                                                </Button>
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

EncodeIndex.layout = {
    breadcrumbs: [{ title: 'Encode', href: encodeIndex() }],
};
