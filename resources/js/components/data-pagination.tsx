import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

type DataPaginationProps<T> = {
    paginator: Paginated<T>;
};

export default function DataPagination<T>({
    paginator,
}: DataPaginationProps<T>) {
    const pageLinks = paginator.links.slice(1, -1);

    return (
        <Pagination className="mx-0 w-auto">
            <PaginationContent>
                <PaginationItem>
                    <PaginationPrevious
                        href={paginator.prev_page_url ?? paginator.path}
                        aria-disabled={!paginator.prev_page_url}
                        tabIndex={paginator.prev_page_url ? undefined : -1}
                        className={cn(
                            !paginator.prev_page_url &&
                                'pointer-events-none opacity-50',
                        )}
                        preserveScroll
                    />
                </PaginationItem>
                {pageLinks.map((link, index) => (
                    <PaginationItem key={`${link.label}-${index}`}>
                        {link.url ? (
                            <PaginationLink
                                href={link.url}
                                isActive={link.active}
                                preserveScroll
                            >
                                {link.label}
                            </PaginationLink>
                        ) : (
                            <PaginationEllipsis />
                        )}
                    </PaginationItem>
                ))}
                <PaginationItem>
                    <PaginationNext
                        href={paginator.next_page_url ?? paginator.path}
                        aria-disabled={!paginator.next_page_url}
                        tabIndex={paginator.next_page_url ? undefined : -1}
                        className={cn(
                            !paginator.next_page_url &&
                                'pointer-events-none opacity-50',
                        )}
                        preserveScroll
                    />
                </PaginationItem>
            </PaginationContent>
        </Pagination>
    );
}
