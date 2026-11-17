import EventPrintDocument, {
    PrintSection,
} from '@/components/event-print-document';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { fightResultLabels } from '@/lib/fights';
import { formatAmount } from '@/lib/format';
import type { Event, Fight, FightResult } from '@/types';

type EventFightsPrintProps = {
    event: Event;
    fights: Fight[];
    businessName: string;
    generatedAt: string;
};

const timeFormatter = new Intl.DateTimeFormat(undefined, {
    hour: 'numeric',
    minute: '2-digit',
});

function formatTime(dateTime: string | null): string {
    return dateTime ? timeFormatter.format(new Date(dateTime)) : '—';
}

const resultOrder: FightResult[] = [
    'meron',
    'wala',
    'draw',
    'cancelled',
    'pending',
];

export default function EventFightsPrint({
    event,
    fights,
    businessName,
    generatedAt,
}: EventFightsPrintProps) {
    const totals = fights.reduce(
        (sum, fight) => ({
            meron: sum.meron + Number(fight.meron_amount),
            wala: sum.wala + Number(fight.wala_amount),
            draw: sum.draw + Number(fight.draw_amount),
            commission: sum.commission + Number(fight.commission),
        }),
        { meron: 0, wala: 0, draw: 0, commission: 0 },
    );

    const resultCounts = resultOrder
        .map((result) => ({
            result,
            count: fights.filter((fight) => fight.result === result).length,
        }))
        .filter(({ count }) => count > 0);

    return (
        <EventPrintDocument
            event={event}
            pageTitle={`Fight List · ${event.name}`}
            title="Fight List"
            subtitle="Every fight in the event with its bets, odds and result"
            businessName={businessName}
            generatedAt={generatedAt}
        >
            <PrintSection title={`Fights (${fights.length})`}>
                {fights.length === 0 ? (
                    <p className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                        No fights were encoded in this event.
                    </p>
                ) : (
                    <>
                        <Table className="text-xs">
                            <TableHeader>
                                <TableRow className="border-foreground/40">
                                    <TableHead className="pl-0">#</TableHead>
                                    <TableHead>Result</TableHead>
                                    <TableHead className="text-right">
                                        Meron
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Wala
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Draw
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Meron Odds
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Wala Odds
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Commission
                                    </TableHead>
                                    <TableHead className="pr-0 text-right">
                                        Open – Close
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {fights.map((fight) => (
                                    <TableRow
                                        key={fight.id}
                                        className="break-inside-avoid"
                                    >
                                        <TableCell className="pl-0 font-medium tabular-nums">
                                            {fight.fight_number}
                                        </TableCell>
                                        <TableCell className="font-semibold uppercase">
                                            {fightResultLabels[fight.result]}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatAmount(fight.meron_amount)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatAmount(fight.wala_amount)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatAmount(fight.draw_amount)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {fight.meron_odds}%
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {fight.wala_odds}%
                                        </TableCell>
                                        <TableCell className="text-right font-medium tabular-nums">
                                            {formatAmount(fight.commission)}
                                        </TableCell>
                                        <TableCell className="pr-0 text-right tabular-nums">
                                            {formatTime(fight.bet_open)} –{' '}
                                            {formatTime(fight.bet_closed)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                            <TableFooter className="border-t-2 border-foreground/60 bg-transparent">
                                <TableRow className="hover:bg-transparent">
                                    <TableCell
                                        colSpan={2}
                                        className="pl-0 font-semibold uppercase"
                                    >
                                        Total
                                    </TableCell>
                                    <TableCell className="text-right font-semibold tabular-nums">
                                        {formatAmount(totals.meron)}
                                    </TableCell>
                                    <TableCell className="text-right font-semibold tabular-nums">
                                        {formatAmount(totals.wala)}
                                    </TableCell>
                                    <TableCell className="text-right font-semibold tabular-nums">
                                        {formatAmount(totals.draw)}
                                    </TableCell>
                                    <TableCell colSpan={2} />
                                    <TableCell className="text-right font-bold tabular-nums">
                                        {formatAmount(totals.commission)}
                                    </TableCell>
                                    <TableCell className="pr-0" />
                                </TableRow>
                            </TableFooter>
                        </Table>

                        <dl className="flex flex-wrap gap-x-6 gap-y-1 text-xs">
                            {resultCounts.map(({ result, count }) => (
                                <div key={result} className="flex gap-1.5">
                                    <dt className="text-muted-foreground">
                                        {fightResultLabels[result]}
                                    </dt>
                                    <dd className="font-semibold tabular-nums">
                                        {count}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </>
                )}
            </PrintSection>
        </EventPrintDocument>
    );
}
