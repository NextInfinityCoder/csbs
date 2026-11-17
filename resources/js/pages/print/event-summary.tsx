import CashierTotalsTable from '@/components/cashier-totals-table';
import EventPrintDocument, {
    PrintSection,
} from '@/components/event-print-document';
import EventSummaryList from '@/components/event-summary-list';
import type { Event, EventCashierTotals, EventSummary } from '@/types';

type EventSummaryPrintProps = {
    event: Event;
    summary: EventSummary;
    cashiers: EventCashierTotals[];
    businessName: string;
    generatedAt: string;
};

export default function EventSummaryPrint({
    event,
    summary,
    cashiers,
    businessName,
    generatedAt,
}: EventSummaryPrintProps) {
    return (
        <EventPrintDocument
            event={event}
            pageTitle={`Summary Report · ${event.name}`}
            title="Event Summary Report"
            subtitle="Over-all summary and total bets per cashier"
            businessName={businessName}
            generatedAt={generatedAt}
        >
            <PrintSection title="Over-all Summary">
                <div className="break-inside-avoid rounded-md border px-4">
                    <EventSummaryList event={event} summary={summary} />
                </div>
            </PrintSection>

            <PrintSection title="Total Bets per Cashier">
                {cashiers.length === 0 ? (
                    <p className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                        No bets were placed in this event.
                    </p>
                ) : (
                    <CashierTotalsTable
                        cashiers={cashiers}
                        className="text-[13px]"
                    />
                )}
            </PrintSection>
        </EventPrintDocument>
    );
}
