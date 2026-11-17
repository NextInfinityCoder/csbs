import { Fragment } from 'react';
import type { ReactNode } from 'react';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

export type ReportRow = {
    label: string;
    value: ReactNode;
    hint?: string;
    isEmphasized?: boolean;
};

export default function ReportList({ rows }: { rows: ReportRow[] }) {
    return (
        <dl className="flex flex-col">
            {rows.map((row, index) => (
                <Fragment key={row.label}>
                    {index > 0 && <Separator />}
                    <div className="flex items-baseline justify-between gap-4 py-3">
                        <dt className="flex flex-col">
                            <span
                                className={cn(
                                    'text-sm',
                                    row.isEmphasized && 'font-semibold',
                                )}
                            >
                                {row.label}
                            </span>
                            {row.hint && (
                                <span className="text-xs text-muted-foreground">
                                    {row.hint}
                                </span>
                            )}
                        </dt>
                        <dd
                            className={cn(
                                'text-right font-medium tabular-nums',
                                row.isEmphasized && 'text-lg font-semibold',
                            )}
                        >
                            {row.value}
                        </dd>
                    </div>
                </Fragment>
            ))}
        </dl>
    );
}
