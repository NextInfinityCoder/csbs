import { Head, router } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import Heading from '@/components/heading';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { ChartConfig } from '@/components/ui/chart';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { formatAmount } from '@/lib/format';
import { dashboard } from '@/routes';
import type { DailyAnalysis } from '@/types';

type DashboardProps = {
    range: number;
    ranges: number[];
    days: DailyAnalysis[];
};

const betsAndPayoutsChartConfig = {
    total_bets: {
        label: 'Total Bets',
        theme: { light: '#2a78d6', dark: '#3987e5' },
    },
    total_payout: {
        label: 'Payout / Cashout',
        theme: { light: '#eb6834', dark: '#d95926' },
    },
} satisfies ChartConfig;

const commissionChartConfig = {
    total_commission: {
        label: 'Commission',
        theme: { light: '#008300', dark: '#0ca30c' },
    },
} satisfies ChartConfig;

const compactAmountFormatter = new Intl.NumberFormat(undefined, {
    notation: 'compact',
    maximumFractionDigits: 1,
});

const shortDateFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    timeZone: 'UTC',
});

const longDateFormatter = new Intl.DateTimeFormat(undefined, {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'UTC',
});

function formatDay(date: string, formatter: Intl.DateTimeFormat): string {
    return formatter.format(new Date(`${date}T00:00:00Z`));
}

type StatTileProps = {
    label: string;
    rangeTotal: number;
    todayTotal: number;
    rangeLabel: string;
    isTodayOnly: boolean;
};

function StatTile({
    label,
    rangeTotal,
    todayTotal,
    rangeLabel,
    isTodayOnly,
}: StatTileProps) {
    return (
        <Card className="gap-2">
            <CardHeader>
                <CardDescription>{label}</CardDescription>
                <CardTitle className="text-3xl tabular-nums">
                    {formatAmount(rangeTotal)}
                </CardTitle>
            </CardHeader>
            <CardContent className="flex items-baseline justify-between gap-2 text-sm">
                <span className="text-muted-foreground">{rangeLabel}</span>
                {!isTodayOnly && (
                    <span className="tabular-nums">
                        Today{' '}
                        <span className="font-medium">
                            {formatAmount(todayTotal)}
                        </span>
                    </span>
                )}
            </CardContent>
        </Card>
    );
}

export default function Dashboard({ range, ranges, days }: DashboardProps) {
    const today = days[days.length - 1];
    const isTodayOnly = range === 1;
    const rangeLabel = isTodayOnly
        ? `Today, ${formatDay(today.date, longDateFormatter)}`
        : `Last ${range} days`;

    const totals = days.reduce(
        (sum, day) => ({
            bets: sum.bets + day.total_bets,
            commission: sum.commission + day.total_commission,
            payout: sum.payout + day.total_payout,
        }),
        { bets: 0, commission: 0, payout: 0 },
    );

    function changeRange(value: string) {
        if (!value) {
            return;
        }

        router.get(
            dashboard(),
            { range: value },
            { preserveScroll: true, preserveState: true, replace: true },
        );
    }

    const xAxis = (
        <XAxis
            dataKey="date"
            tickLine={false}
            axisLine={false}
            tickMargin={8}
            minTickGap={24}
            tickFormatter={(date: string) =>
                formatDay(date, shortDateFormatter)
            }
        />
    );

    const yAxis = (
        <YAxis
            tickLine={false}
            axisLine={false}
            tickMargin={4}
            width={48}
            tickFormatter={(value: number) =>
                compactAmountFormatter.format(value)
            }
        />
    );

    const tooltip = (
        <ChartTooltip
            cursor={false}
            content={
                <ChartTooltipContent
                    labelFormatter={(date) =>
                        formatDay(String(date), longDateFormatter)
                    }
                />
            }
        />
    );

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Daily Analysis"
                        description="Total bets, commission and payouts per day."
                    />
                    <ToggleGroup
                        type="single"
                        variant="outline"
                        size="sm"
                        value={String(range)}
                        onValueChange={changeRange}
                        aria-label="Date range"
                    >
                        {ranges.map((option) => (
                            <ToggleGroupItem
                                key={option}
                                value={String(option)}
                                className="px-3"
                            >
                                {option === 1 ? 'Today' : `${option}D`}
                            </ToggleGroupItem>
                        ))}
                    </ToggleGroup>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <StatTile
                        label="Total Bets"
                        rangeTotal={totals.bets}
                        todayTotal={today?.total_bets ?? 0}
                        rangeLabel={rangeLabel}
                        isTodayOnly={isTodayOnly}
                    />
                    <StatTile
                        label="Total Commission"
                        rangeTotal={totals.commission}
                        todayTotal={today?.total_commission ?? 0}
                        rangeLabel={rangeLabel}
                        isTodayOnly={isTodayOnly}
                    />
                    <StatTile
                        label="Total Payout / Cashout"
                        rangeTotal={totals.payout}
                        todayTotal={today?.total_payout ?? 0}
                        rangeLabel={rangeLabel}
                        isTodayOnly={isTodayOnly}
                    />
                </div>

                <div className="grid gap-4 xl:grid-cols-3">
                    <Card className="xl:col-span-2">
                        <CardHeader>
                            <CardTitle>Bets vs Payouts</CardTitle>
                            <CardDescription>
                                Money taken in and paid out per day
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer
                                config={betsAndPayoutsChartConfig}
                                className="aspect-auto h-72 w-full"
                            >
                                <BarChart
                                    accessibilityLayer
                                    data={days}
                                    barGap={2}
                                >
                                    <CartesianGrid vertical={false} />
                                    {xAxis}
                                    {yAxis}
                                    {tooltip}
                                    <ChartLegend
                                        content={<ChartLegendContent />}
                                    />
                                    <Bar
                                        dataKey="total_bets"
                                        fill="var(--color-total_bets)"
                                        radius={[4, 4, 0, 0]}
                                    />
                                    <Bar
                                        dataKey="total_payout"
                                        fill="var(--color-total_payout)"
                                        radius={[4, 4, 0, 0]}
                                    />
                                </BarChart>
                            </ChartContainer>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Commission</CardTitle>
                            <CardDescription>
                                Operator commission earned per day
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer
                                config={commissionChartConfig}
                                className="aspect-auto h-72 w-full"
                            >
                                <BarChart accessibilityLayer data={days}>
                                    <CartesianGrid vertical={false} />
                                    {xAxis}
                                    {yAxis}
                                    {tooltip}
                                    <Bar
                                        dataKey="total_commission"
                                        fill="var(--color-total_commission)"
                                        radius={[4, 4, 0, 0]}
                                    />
                                </BarChart>
                            </ChartContainer>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Daily Breakdown</CardTitle>
                        <CardDescription>
                            {isTodayOnly
                                ? rangeLabel
                                : `${rangeLabel}, newest first`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead className="text-right">
                                        Total Bets
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Commission
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Payout / Cashout
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {[...days].reverse().map((day) => (
                                    <TableRow key={day.date}>
                                        <TableCell>
                                            {formatDay(
                                                day.date,
                                                longDateFormatter,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatAmount(day.total_bets)}
                                        </TableCell>
                                        <TableCell className="text-right font-semibold text-success tabular-nums">
                                            {formatAmount(day.total_commission)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatAmount(day.total_payout)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                            <TableFooter>
                                <TableRow>
                                    <TableCell>Total</TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatAmount(totals.bets)}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatAmount(totals.commission)}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatAmount(totals.payout)}
                                    </TableCell>
                                </TableRow>
                            </TableFooter>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
