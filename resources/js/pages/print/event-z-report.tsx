import EventPrintDocument from '@/components/event-print-document';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatAmount } from '@/lib/format';
import type { Event, StationZReport } from '@/types';

type EventZReportPrintProps = {
    event: Event;
    zReport: StationZReport[];
    businessName: string;
    generatedAt: string;
};

type ReadingLine = {
    label: string;
    value: string;
    isTotal?: boolean;
};

function readingLines(report: StationZReport): ReadingLine[] {
    return [
        {
            label: 'No. of Transactions',
            value: String(report.transaction_count),
        },
        {
            label: 'Gross Bets (Meron/Wala)',
            value: formatAmount(report.gross_bets),
        },
        { label: 'Draw Bets', value: formatAmount(report.draw_bets) },
        { label: 'Cancelled Bets', value: formatAmount(report.cancelled_bets) },
        {
            label: 'Sales (Commission)',
            value: formatAmount(report.sales),
            isTotal: true,
        },
    ];
}

export default function EventZReportPrint({
    event,
    zReport,
    businessName,
    generatedAt,
}: EventZReportPrintProps) {
    const totals = zReport.reduce(
        (sum, report) => ({
            transactions: sum.transactions + report.transaction_count,
            grossBets: sum.grossBets + report.gross_bets,
            drawBets: sum.drawBets + report.draw_bets,
            cancelledBets: sum.cancelledBets + report.cancelled_bets,
            sales: sum.sales + report.sales,
        }),
        {
            transactions: 0,
            grossBets: 0,
            drawBets: 0,
            cancelledBets: 0,
            sales: 0,
        },
    );

    return (
        <EventPrintDocument
            event={event}
            pageTitle={`Z-Report · ${event.name}`}
            title="Z-Reading Report"
            subtitle="End-of-event sales reading per station"
            businessName={businessName}
            generatedAt={generatedAt}
        >
            <section className="flex flex-col gap-3">
                <h2 className="text-xs font-semibold tracking-wider uppercase">
                    Summary by Station
                </h2>
                {zReport.length === 0 ? (
                    <p className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                        No bets were placed in this event.
                    </p>
                ) : (
                    <Table className="text-[13px]">
                        <TableHeader>
                            <TableRow className="border-foreground/40">
                                <TableHead className="pl-0">Station</TableHead>
                                <TableHead className="text-right">
                                    Txns
                                </TableHead>
                                <TableHead className="text-right">
                                    Gross Bets
                                </TableHead>
                                <TableHead className="text-right">
                                    Draw Bets
                                </TableHead>
                                <TableHead className="text-right">
                                    Cancelled
                                </TableHead>
                                <TableHead className="pr-0 text-right">
                                    Sales
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {zReport.map((report) => (
                                <TableRow
                                    key={report.station_id ?? 'unassigned'}
                                >
                                    <TableCell className="pl-0 font-medium">
                                        {report.station}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {report.transaction_count}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatAmount(report.gross_bets)}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatAmount(report.draw_bets)}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatAmount(report.cancelled_bets)}
                                    </TableCell>
                                    <TableCell className="pr-0 text-right font-medium tabular-nums">
                                        {formatAmount(report.sales)}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                        <TableFooter className="border-t-2 border-foreground/60 bg-transparent">
                            <TableRow className="hover:bg-transparent">
                                <TableCell className="pl-0 font-semibold uppercase">
                                    Total
                                </TableCell>
                                <TableCell className="text-right font-semibold tabular-nums">
                                    {totals.transactions}
                                </TableCell>
                                <TableCell className="text-right font-semibold tabular-nums">
                                    {formatAmount(totals.grossBets)}
                                </TableCell>
                                <TableCell className="text-right font-semibold tabular-nums">
                                    {formatAmount(totals.drawBets)}
                                </TableCell>
                                <TableCell className="text-right font-semibold tabular-nums">
                                    {formatAmount(totals.cancelledBets)}
                                </TableCell>
                                <TableCell className="pr-0 text-right text-sm font-bold tabular-nums">
                                    {formatAmount(totals.sales)}
                                </TableCell>
                            </TableRow>
                        </TableFooter>
                    </Table>
                )}
            </section>

            {zReport.length > 0 && (
                <section className="flex flex-col gap-3">
                    <h2 className="text-xs font-semibold tracking-wider uppercase">
                        Station Readings
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-2 print:grid-cols-2">
                        {zReport.map((report) => (
                            <div
                                key={report.station_id ?? 'unassigned'}
                                className="flex break-inside-avoid flex-col rounded-md border"
                            >
                                <div className="flex items-baseline justify-between gap-2 border-b bg-muted px-4 py-2.5">
                                    <span className="text-sm font-semibold">
                                        {report.station}
                                    </span>
                                    <span className="text-[11px] font-medium tracking-wider text-muted-foreground uppercase">
                                        Z-Reading
                                    </span>
                                </div>
                                <dl className="flex flex-col px-4 py-2">
                                    {readingLines(report).map((line) => (
                                        <div
                                            key={line.label}
                                            className={
                                                line.isTotal
                                                    ? 'mt-1 flex items-baseline justify-between gap-4 border-t border-foreground/40 pt-2 pb-1'
                                                    : 'flex items-baseline justify-between gap-4 py-1'
                                            }
                                        >
                                            <dt
                                                className={
                                                    line.isTotal
                                                        ? 'text-[13px] font-semibold'
                                                        : 'text-[13px] text-muted-foreground'
                                                }
                                            >
                                                {line.label}
                                            </dt>
                                            <dd
                                                className={
                                                    line.isTotal
                                                        ? 'text-sm font-bold tabular-nums'
                                                        : 'text-[13px] font-medium tabular-nums'
                                                }
                                            >
                                                {line.value}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            </div>
                        ))}
                    </div>
                </section>
            )}

            <p className="text-xs leading-relaxed text-muted-foreground">
                Sales represent the operator commission: Meron and Wala bets on
                fights that were not cancelled, multiplied by the{' '}
                {event.commission_rate}% operator commission. Draw and cancelled
                bets are shown for reference only.
            </p>
        </EventPrintDocument>
    );
}
