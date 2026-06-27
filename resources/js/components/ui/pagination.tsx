import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from './button';
import { cn } from '@/lib/utils';

interface PaginationProps {
    currentPage: number;
    lastPage: number;
    total: number;
    from: number;
    to: number;
    onPageChange: (page: number) => void;
    perPage?: number;
    onPerPageChange?: (perPage: number) => void;
}

const perPageOptions = [10, 25, 50, 100];

export function Pagination({
    currentPage,
    lastPage,
    total,
    from,
    to,
    onPageChange,
    perPage = 10,
    onPerPageChange,
}: PaginationProps) {
    if (lastPage <= 1 && !onPerPageChange) return null;

    const pages: (number | 'dots')[] = [];
    if (lastPage <= 7) {
        for (let i = 1; i <= lastPage; i++) pages.push(i);
    } else if (currentPage <= 4) {
        for (let i = 1; i <= 5; i++) pages.push(i);
        pages.push('dots');
        pages.push(lastPage);
    } else if (currentPage >= lastPage - 3) {
        pages.push(1);
        pages.push('dots');
        for (let i = lastPage - 4; i <= lastPage; i++) pages.push(i);
    } else {
        pages.push(1);
        pages.push('dots');
        for (let i = currentPage - 1; i <= currentPage + 1; i++) pages.push(i);
        pages.push('dots');
        pages.push(lastPage);
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-4">
            <div className="flex items-center gap-3">
                {onPerPageChange && (
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <span>Show</span>
                        <select
                            value={perPage}
                            onChange={(e) => onPerPageChange(Number(e.target.value))}
                            className="h-8 rounded-md border border-input bg-background px-2 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        >
                            {perPageOptions.map((opt) => (
                                <option key={opt} value={opt}>{opt}</option>
                            ))}
                        </select>
                    </div>
                )}
                <span className="text-sm text-muted-foreground">
                    {from}–{to} of {total}
                </span>
            </div>

            {lastPage > 1 && (
                <div className="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => onPageChange(currentPage - 1)}
                        disabled={currentPage === 1}
                        className="h-8 w-8 p-0"
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    {pages.map((page, i) =>
                        page === 'dots' ? (
                            <span key={`dots-${i}`} className="px-1 text-muted-foreground text-sm">
                                ...
                            </span>
                        ) : (
                            <Button
                                key={page}
                                variant={page === currentPage ? 'default' : 'outline'}
                                size="sm"
                                className={cn(
                                    'h-8 min-w-[32px] p-0 text-xs',
                                    page === currentPage && 'pointer-events-none',
                                )}
                                onClick={() => onPageChange(page)}
                            >
                                {page}
                            </Button>
                        ),
                    )}
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => onPageChange(currentPage + 1)}
                        disabled={currentPage === lastPage}
                        className="h-8 w-8 p-0"
                    >
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                </div>
            )}
        </div>
    );
}
