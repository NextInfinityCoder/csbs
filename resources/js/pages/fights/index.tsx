import { Head } from '@inertiajs/react';
import { Swords } from 'lucide-react';
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
import { fightResultBadgeVariants, fightResultLabels } from '@/lib/fights';
import { formatAmount, formatDateTime } from '@/lib/format';
import { index as fightsIndex } from '@/routes/fights';
import type { Fight, Paginated } from '@/types';

type FightsIndexProps = {
    fights: Paginated<Fight>;
};

export default function FightsIndex({ fights }: FightsIndexProps) {
    return (
        <>
            <Head title="Fight List" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Fight List"
                    description="Browse all fights, their bet totals, odds and results."
                />

                <Card>
                    <CardHeader>
                        <CardTitle>All fights</CardTitle>
                        <CardDescription>
                            {fights.total}{' '}
                            {fights.total === 1 ? 'fight' : 'fights'} in total
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {fights.data.length === 0 ? (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <Swords />
                                    </EmptyMedia>
                                    <EmptyTitle>No fights yet</EmptyTitle>
                                    <EmptyDescription>
                                        Fights you add to events will appear
                                        here.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Fight #</TableHead>
                                        <TableHead>Event</TableHead>
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
                                            M Odds
                                        </TableHead>
                                        <TableHead className="text-right">
                                            W Odds
                                        </TableHead>
                                        <TableHead className="text-right">
                                            %
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Commission
                                        </TableHead>
                                        <TableHead>Result</TableHead>
                                        <TableHead>Bet Open</TableHead>
                                        <TableHead>Bet Closed</TableHead>
                                        <TableHead>Cancelled At</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {fights.data.map((fight) => (
                                        <TableRow key={fight.id}>
                                            <TableCell className="font-medium tabular-nums">
                                                {fight.fight_number}
                                            </TableCell>
                                            <TableCell>
                                                {fight.event?.name ?? '—'}
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
                                                {formatAmount(
                                                    fight.draw_amount,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {fight.meron_odds}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {fight.wala_odds}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {fight.percentage}%
                                            </TableCell>
                                            <TableCell className="text-right text-base font-semibold text-success tabular-nums">
                                                {formatAmount(fight.commission)}
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
                                            <TableCell>
                                                {formatDateTime(fight.bet_open)}
                                            </TableCell>
                                            <TableCell>
                                                {formatDateTime(
                                                    fight.bet_closed,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatDateTime(
                                                    fight.canceled_at,
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {fights.last_page > 1 && (
                        <CardFooter className="flex flex-col gap-4 sm:flex-row sm:justify-between">
                            <p className="text-sm text-muted-foreground">
                                Showing {fights.from} to {fights.to} of{' '}
                                {fights.total}
                            </p>
                            <DataPagination paginator={fights} />
                        </CardFooter>
                    )}
                </Card>
            </div>
        </>
    );
}

FightsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Fight List',
            href: fightsIndex(),
        },
    ],
};
