import ReportList from '@/components/report-list';
import type { ReportRow } from '@/components/report-list';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatAmount } from '@/lib/format';
import type { StationZReport } from '@/types';

type StationZReportCardProps = {
    report: StationZReport;
    reportDate: string;
};

export default function StationZReportCard({
    report,
    reportDate,
}: StationZReportCardProps) {
    const isStation = report.station_id !== null;

    const rows: ReportRow[] = [
        { label: 'No. of Transactions', value: report.transaction_count },
        {
            label: 'Gross Bets (Meron/Wala)',
            value: formatAmount(report.gross_bets),
        },
        { label: 'Draw Bets', value: formatAmount(report.draw_bets) },
        {
            label: 'Cancelled Bets',
            value: formatAmount(report.cancelled_bets),
        },
        {
            label: 'Sales (Commission)',
            hint: 'Gross Bets × Operator %',
            value: formatAmount(report.sales),
            isEmphasized: true,
        },
    ];

    return (
        <Card className="gap-2">
            <CardHeader>
                <CardTitle>{report.station}</CardTitle>
                <CardDescription>
                    {isStation
                        ? `Z-Reading · ${reportDate}`
                        : 'Bets taken by cashiers without a station'}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ReportList rows={rows} />
            </CardContent>
        </Card>
    );
}
