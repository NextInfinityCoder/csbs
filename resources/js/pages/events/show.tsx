import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft, Printer, Receipt, Users } from 'lucide-react';
import CashierTotalsTable from '@/components/cashier-totals-table';
import EventSummaryList from '@/components/event-summary-list';
import Heading from '@/components/heading';
import StationZReportCard from '@/components/station-z-report-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
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
import { eventStatusBadgeVariants, eventStatusLabels } from '@/lib/events';
import { formatAmount, formatDateTime } from '@/lib/format';
import {
    fightsReport as eventFightsReport,
    index as eventsIndex,
    show as eventsShow,
    summaryReport as eventSummaryReport,
    zReport as eventZReport,
} from '@/routes/events';
import type {
    Event,
    EventCashierTotals,
    EventSummary,
    StationZReport,
} from '@/types';

type EventsShowProps = {
    event: Event;
    summary: EventSummary;
    cashiers: EventCashierTotals[];
    zReport: StationZReport[];
};

export default function EventsShow({
    event,
    summary,
    cashiers,
    zReport,
}: EventsShowProps) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Events', href: eventsIndex() },
            { title: event.name, href: eventsShow(event.id) },
        ],
    });

    const totalZReportSales = zReport.reduce(
        (total, report) => total + report.sales,
        0,
    );

    return (
        <>
            <Head title={event.name} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-4">
                    <Button variant="ghost" size="sm" className="w-fit" asChild>
                        <Link href={eventsIndex()}>
                            <ArrowLeft data-icon="inline-start" />
                            Back to events
                        </Link>
                    </Button>
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <Heading
                            title={event.name}
                            description={`${formatDateTime(event.date)} · ${event.commission_rate}% operator commission`}
                        />
                        <div className="flex items-center gap-3">
                            <Badge
                                variant={eventStatusBadgeVariants[event.status]}
                            >
                                {eventStatusLabels[event.status]}
                            </Badge>
                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href={eventFightsReport.url(event.id)}
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <Printer data-icon="inline-start" />
                                    Print Fights
                                </a>
                            </Button>
                        </div>
                    </div>
                </div>

                <Card>
                    <CardHeader className="flex flex-row items-start justify-between gap-4">
                        <CardTitle>Over-all Summary Report</CardTitle>
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={eventSummaryReport.url(event.id)}
                                target="_blank"
                                rel="noopener"
                            >
                                <Printer data-icon="inline-start" />
                                Print
                            </a>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <EventSummaryList event={event} summary={summary} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-start justify-between gap-4">
                        <div className="flex flex-col gap-1.5">
                            <CardTitle>Total Bets per Cashier</CardTitle>
                            <CardDescription>
                                Bets taken by each cashier in this event.
                            </CardDescription>
                        </div>
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={eventSummaryReport.url(event.id)}
                                target="_blank"
                                rel="noopener"
                            >
                                <Printer data-icon="inline-start" />
                                Print
                            </a>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {cashiers.length === 0 ? (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <Users />
                                    </EmptyMedia>
                                    <EmptyTitle>No bets yet</EmptyTitle>
                                    <EmptyDescription>
                                        Cashier totals will appear once bets are
                                        placed in this event.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        ) : (
                            <CashierTotalsTable cashiers={cashiers} />
                        )}
                    </CardContent>
                </Card>

                <section className="flex flex-col gap-4">
                    <div className="flex flex-wrap items-end justify-between gap-2">
                        <div className="flex flex-col gap-0.5">
                            <h3 className="text-lg font-semibold tracking-tight">
                                Z-Report (BIR)
                            </h3>
                            <p className="text-sm text-muted-foreground">
                                End-of-event reading per station. Sales are the
                                operator commission.
                            </p>
                        </div>
                        {zReport.length > 0 && (
                            <div className="flex items-center gap-4">
                                <p className="text-sm text-muted-foreground">
                                    Total sales:{' '}
                                    <span className="font-semibold text-foreground tabular-nums">
                                        {formatAmount(totalZReportSales)}
                                    </span>
                                </p>
                                <Button variant="outline" size="sm" asChild>
                                    <a
                                        href={eventZReport.url(event.id)}
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <Printer data-icon="inline-start" />
                                        Print Z-Report
                                    </a>
                                </Button>
                            </div>
                        )}
                    </div>

                    {zReport.length === 0 ? (
                        <Card>
                            <CardContent>
                                <Empty>
                                    <EmptyHeader>
                                        <EmptyMedia variant="icon">
                                            <Receipt />
                                        </EmptyMedia>
                                        <EmptyTitle>No Z-report yet</EmptyTitle>
                                        <EmptyDescription>
                                            The Z-report appears once bets are
                                            placed in this event.
                                        </EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {zReport.map((report) => (
                                <StationZReportCard
                                    key={report.station_id ?? 'unassigned'}
                                    report={report}
                                    reportDate={formatDateTime(event.date)}
                                />
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}
