import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { eventStatusLabels } from '@/lib/events';
import { formatDateTime } from '@/lib/format';
import { show as eventsShow } from '@/routes/events';
import type { Event } from '@/types';

function MetaItem({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex flex-col gap-0.5">
            <dt className="text-[11px] font-medium tracking-wider text-muted-foreground uppercase">
                {label}
            </dt>
            <dd className="text-sm font-medium">{value}</dd>
        </div>
    );
}

export function PrintSection({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <section className="flex flex-col gap-3">
            <h2 className="text-xs font-semibold tracking-wider uppercase">
                {title}
            </h2>
            {children}
        </section>
    );
}

type EventPrintDocumentProps = {
    event: Event;
    pageTitle: string;
    title: string;
    subtitle: string;
    businessName: string;
    generatedAt: string;
    children: ReactNode;
};

/**
 * An A4 printable document about an event: toolbar, letterhead, event
 * details, the report body and a footer.
 */
export default function EventPrintDocument({
    event,
    pageTitle,
    title,
    subtitle,
    businessName,
    generatedAt,
    children,
}: EventPrintDocumentProps) {
    return (
        <>
            <Head title={pageTitle} />

            <div className="min-h-screen bg-muted print:min-h-0 print:bg-white">
                <div className="sticky top-0 z-10 border-b bg-background/95 backdrop-blur print:hidden">
                    <div className="mx-auto flex max-w-[210mm] items-center justify-between gap-4 px-4 py-3">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={eventsShow(event.id)}>
                                <ArrowLeft data-icon="inline-start" />
                                Back to event
                            </Link>
                        </Button>
                        <Button size="sm" onClick={() => window.print()}>
                            <Printer data-icon="inline-start" />
                            Print
                        </Button>
                    </div>
                </div>

                <article className="paper mx-auto my-8 flex w-full max-w-[210mm] flex-col gap-8 p-6 shadow-sm ring-1 ring-border sm:p-12 print:m-0 print:max-w-none print:p-0 print:shadow-none print:ring-0">
                    <header className="flex flex-col gap-6">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div className="flex flex-col gap-1">
                                <span className="text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase">
                                    {businessName}
                                </span>
                                <h1 className="text-2xl font-bold tracking-tight">
                                    {title}
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    {subtitle}
                                </p>
                            </div>
                            <div className="flex flex-col items-end gap-0.5 text-right">
                                <span className="text-[11px] font-medium tracking-wider text-muted-foreground uppercase">
                                    Generated
                                </span>
                                <span className="text-sm font-medium tabular-nums">
                                    {formatDateTime(generatedAt)}
                                </span>
                            </div>
                        </div>

                        <Separator className="bg-foreground" />

                        <dl className="grid grid-cols-2 gap-x-8 gap-y-4 sm:grid-cols-4">
                            <MetaItem label="Event" value={event.name} />
                            <MetaItem
                                label="Event Date"
                                value={formatDateTime(event.date)}
                            />
                            <MetaItem
                                label="Status"
                                value={eventStatusLabels[event.status]}
                            />
                            <MetaItem
                                label="Operator Commission"
                                value={`${event.commission_rate}%`}
                            />
                        </dl>
                    </header>

                    {children}

                    <footer className="flex flex-wrap items-center justify-between gap-2 border-t pt-4 text-[11px] text-muted-foreground">
                        <span>System-generated report · {businessName}</span>
                        <span className="tabular-nums">
                            Printed {formatDateTime(generatedAt)}
                        </span>
                    </footer>
                </article>
            </div>
        </>
    );
}
