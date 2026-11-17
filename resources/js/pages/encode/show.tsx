import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft, Swords, TriangleAlert } from 'lucide-react';
import EncodeEntryForm from '@/components/encode-entry-form';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { fightResultBadgeVariants, fightResultLabels } from '@/lib/fights';
import { formatAmount } from '@/lib/format';
import { index as encodeIndex, show as encodeShow } from '@/routes/encode';
import type { Event, Fight } from '@/types';

type EncodeShowProps = {
    event: Event;
    fights: Fight[];
    nextFightNumber: number;
    defaultTimeOpen: string | null;
    today: string;
    hasCashiers: boolean;
};

const timeFormatter = new Intl.DateTimeFormat(undefined, {
    hour: 'numeric',
    minute: '2-digit',
});

function formatTime(dateTime: string | null): string {
    return dateTime ? timeFormatter.format(new Date(dateTime)) : '—';
}

export default function EncodeShow({
    event,
    fights,
    nextFightNumber,
    defaultTimeOpen,
    today,
    hasCashiers,
}: EncodeShowProps) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Encode', href: encodeIndex() },
            { title: event.name, href: encodeShow(event.id) },
        ],
    });

    return (
        <>
            <Head title={`Encode · ${event.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-4">
                    <Button variant="ghost" size="sm" className="w-fit" asChild>
                        <Link href={encodeIndex()}>
                            <ArrowLeft data-icon="inline-start" />
                            Back to events
                        </Link>
                    </Button>
                    <Heading
                        title={event.name}
                        description={`Encoding fights · ${event.commission_rate}% operator commission`}
                    />
                </div>

                {!hasCashiers && (
                    <Alert variant="destructive">
                        <TriangleAlert />
                        <AlertTitle>No active cashiers</AlertTitle>
                        <AlertDescription>
                            Bets are assigned to active cashiers only. Ask an
                            admin to activate at least one cashier before
                            encoding.
                        </AlertDescription>
                    </Alert>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>New Entry</CardTitle>
                        <CardDescription>
                            Bets are generated from these totals and assigned to
                            random cashiers.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <EncodeEntryForm
                            key={nextFightNumber}
                            event={event}
                            nextFightNumber={nextFightNumber}
                            defaultTimeOpen={defaultTimeOpen}
                            today={today}
                            hasCashiers={hasCashiers}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Encoded Fights</CardTitle>
                        <CardDescription>
                            {fights.length}{' '}
                            {fights.length === 1 ? 'fight' : 'fights'} in this
                            event
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {fights.length === 0 ? (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <Swords />
                                    </EmptyMedia>
                                    <EmptyTitle>No fights yet</EmptyTitle>
                                    <EmptyDescription>
                                        Encoded fights will appear here.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Fight #</TableHead>
                                        <TableHead className="text-right">
                                            Meron
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Wala
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Meron Odds
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Wala Odds
                                        </TableHead>
                                        <TableHead>Result</TableHead>
                                        <TableHead>Open – Close</TableHead>
                                        <TableHead className="text-right">
                                            Bets
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {fights.map((fight) => (
                                        <TableRow key={fight.id}>
                                            <TableCell className="font-medium tabular-nums">
                                                {fight.fight_number}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {formatAmount(
                                                    fight.meron_amount,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {formatAmount(
                                                    fight.wala_amount,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {fight.meron_odds}%
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {fight.wala_odds}%
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        fightResultBadgeVariants[
                                                            fight.result
                                                        ]
                                                    }
                                                >
                                                    {
                                                        fightResultLabels[
                                                            fight.result
                                                        ]
                                                    }
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {formatTime(fight.bet_open)} –{' '}
                                                {formatTime(fight.bet_closed)}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {fight.bets_count ?? 0}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
