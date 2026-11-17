import { Head } from '@inertiajs/react';
import { Ticket } from 'lucide-react';
import DataPagination from '@/components/data-pagination';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
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
import { betSideBadgeVariants, betSideLabels } from '@/lib/bets';
import { formatAmount, formatDateTime } from '@/lib/format';
import { index as betsIndex } from '@/routes/bets';
import type { Bet, Paginated } from '@/types';

type BetsIndexProps = {
    bets: Paginated<Bet>;
};

export default function BetsIndex({ bets }: BetsIndexProps) {
    return (
        <>
            <Head title="Bet List" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Bet List"
                    description="Browse all bets placed by cashiers and their claim status."
                />

                <Card>
                    <CardHeader>
                        <CardTitle>All bets</CardTitle>
                        <CardDescription>
                            {bets.total} {bets.total === 1 ? 'bet' : 'bets'} in
                            total
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {bets.data.length === 0 ? (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <Ticket />
                                    </EmptyMedia>
                                    <EmptyTitle>No bets yet</EmptyTitle>
                                    <EmptyDescription>
                                        Bets placed by cashiers will appear
                                        here.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Barcode</TableHead>
                                        <TableHead>Event</TableHead>
                                        <TableHead>Fight #</TableHead>
                                        <TableHead>Side</TableHead>
                                        <TableHead className="text-right">
                                            Amount
                                        </TableHead>
                                        <TableHead>Cashier</TableHead>
                                        <TableHead>Station</TableHead>
                                        <TableHead>Placed At</TableHead>
                                        <TableHead className="text-right">
                                            Claim Amount
                                        </TableHead>
                                        <TableHead>Claimed From</TableHead>
                                        <TableHead>Claimed At</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {bets.data.map((bet) => (
                                        <TableRow key={bet.id}>
                                            <TableCell className="font-mono font-medium tabular-nums">
                                                {bet.barcode}
                                            </TableCell>
                                            <TableCell>
                                                {bet.event?.name ?? '—'}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {bet.fight?.fight_number ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        betSideBadgeVariants[
                                                            bet.bet_side
                                                        ]
                                                    }
                                                >
                                                    {
                                                        betSideLabels[
                                                            bet.bet_side
                                                        ]
                                                    }
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right font-semibold tabular-nums">
                                                {formatAmount(bet.amount)}
                                            </TableCell>
                                            <TableCell>
                                                {bet.user?.name ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {bet.station?.name ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {formatDateTime(bet.created_at)}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {bet.claim_amount ? (
                                                    <span className="text-base font-semibold text-success">
                                                        {formatAmount(
                                                            bet.claim_amount,
                                                        )}
                                                    </span>
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {bet.claimed_from?.name ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {formatDateTime(bet.claimed_at)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {bets.last_page > 1 && (
                        <CardFooter className="flex flex-col gap-4 sm:flex-row sm:justify-between">
                            <p className="text-sm text-muted-foreground">
                                Showing {bets.from} to {bets.to} of {bets.total}
                            </p>
                            <DataPagination paginator={bets} />
                        </CardFooter>
                    )}
                </Card>
            </div>
        </>
    );
}

BetsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Bet List',
            href: betsIndex(),
        },
    ],
};
