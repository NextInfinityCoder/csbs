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
import { cn } from '@/lib/utils';
import type { EventCashierTotals } from '@/types';

type CashierTotalsTableProps = {
    cashiers: EventCashierTotals[];
    className?: string;
};

export default function CashierTotalsTable({
    cashiers,
    className,
}: CashierTotalsTableProps) {
    const totals = cashiers.reduce(
        (sum, cashier) => ({
            meron: sum.meron + cashier.total_meron_bets,
            wala: sum.wala + cashier.total_wala_bets,
            draw: sum.draw + cashier.total_draw_bets,
            count: sum.count + cashier.number_of_bets,
        }),
        { meron: 0, wala: 0, draw: 0, count: 0 },
    );

    return (
        <Table className={className}>
            <TableHeader>
                <TableRow>
                    <TableHead>Cashier</TableHead>
                    <TableHead>Station</TableHead>
                    <TableHead className="text-right">
                        Total Meron Bet
                    </TableHead>
                    <TableHead className="text-right">Total Wala Bet</TableHead>
                    <TableHead className="text-right">Total Draw Bet</TableHead>
                    <TableHead className="text-right">No. of Bets</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {cashiers.map((cashier) => (
                    <TableRow key={cashier.id}>
                        <TableCell className="font-medium">
                            {cashier.name}
                        </TableCell>
                        <TableCell className="text-muted-foreground">
                            {cashier.station ?? '—'}
                        </TableCell>
                        <TableCell className="text-right tabular-nums">
                            {formatAmount(cashier.total_meron_bets)}
                        </TableCell>
                        <TableCell className="text-right tabular-nums">
                            {formatAmount(cashier.total_wala_bets)}
                        </TableCell>
                        <TableCell className="text-right tabular-nums">
                            {formatAmount(cashier.total_draw_bets)}
                        </TableCell>
                        <TableCell className="text-right tabular-nums">
                            {cashier.number_of_bets}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
            <TableFooter className={cn('font-semibold')}>
                <TableRow>
                    <TableCell colSpan={2}>Total</TableCell>
                    <TableCell className="text-right tabular-nums">
                        {formatAmount(totals.meron)}
                    </TableCell>
                    <TableCell className="text-right tabular-nums">
                        {formatAmount(totals.wala)}
                    </TableCell>
                    <TableCell className="text-right tabular-nums">
                        {formatAmount(totals.draw)}
                    </TableCell>
                    <TableCell className="text-right tabular-nums">
                        {totals.count}
                    </TableCell>
                </TableRow>
            </TableFooter>
        </Table>
    );
}
