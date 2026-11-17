import ReportList from '@/components/report-list';
import type { ReportRow } from '@/components/report-list';
import { formatAmount } from '@/lib/format';
import type { Event, EventSummary } from '@/types';

type EventSummaryListProps = {
    event: Event;
    summary: EventSummary;
};

export default function EventSummaryList({
    event,
    summary,
}: EventSummaryListProps) {
    const isNetWin = summary.net_win_lose >= 0;

    const rows: ReportRow[] = [
        {
            label: 'Total Commission',
            hint: `Total Bets (Kiosk) × Operator ${event.commission_rate}%`,
            value: formatAmount(summary.total_commission),
        },
        { label: 'Total Bets', value: formatAmount(summary.total_bets) },
        {
            label: 'Total Draw Bets',
            value: formatAmount(summary.total_draw_bets),
        },
        {
            label: 'Total Cancelled Bets',
            value: formatAmount(summary.total_cancelled_bets),
        },
        {
            label: 'Total Payout Paid',
            value: formatAmount(summary.total_payout_paid),
        },
        {
            label: 'Total Cancelled/Draw Paid',
            value: formatAmount(summary.total_cancelled_draw_paid),
        },
        {
            label: 'Total Draw Paid',
            value: formatAmount(summary.total_draw_paid),
        },
        {
            label: 'Net Win/Lose',
            value: (
                <>
                    {isNetWin ? '+' : '−'}
                    {formatAmount(Math.abs(summary.net_win_lose))}{' '}
                    <span className="text-sm font-normal text-muted-foreground">
                        ({isNetWin ? 'Win' : 'Lose'})
                    </span>
                </>
            ),
            isEmphasized: true,
        },
    ];

    return <ReportList rows={rows} />;
}
