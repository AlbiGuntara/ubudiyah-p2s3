import { useState, useMemo, useEffect, useRef } from 'react';
import {
    Search,
    ChevronUp,
    ChevronDown,
    ChevronsUpDown,
    Eye,
    EyeOff,
    Check,
    Trash2,
    Columns,
} from 'lucide-react';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export interface Column<T> {
    key: string;
    label: string;
    sortable?: boolean;
    render?: (item: T, index: number) => React.ReactNode;
    className?: string;
    headClassName?: string;
    hideable?: boolean;
    defaultVisible?: boolean;
}

interface PaginationMeta {
    current_page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
}

interface DataTableProps<T> {
    columns: Column<T>[];
    data: T[];
    meta: PaginationMeta;
    keyExtractor: (item: T) => string | number;
    onPageChange: (page: number) => void;
    search?: string;
    onSearchChange?: (search: string) => void;
    searchPlaceholder?: string;
    sortColumn?: string;
    sortDirection?: 'asc' | 'desc' | 'none';
    onSort?: (column: string) => void;
    filters?: React.ReactNode;
    bulkActions?: React.ReactNode;
    onSelectionChange?: (selectedIds: (string | number)[]) => void;
    toolbar?: React.ReactNode;
    title?: string;
    description?: string;
    perPage?: number;
    onPerPageChange?: (perPage: number) => void;
}

function DataTable<T>({
    columns,
    data,
    meta,
    keyExtractor,
    onPageChange,
    search: externalSearch,
    onSearchChange,
    searchPlaceholder = 'Cari...',
    sortColumn,
    sortDirection,
    onSort,
    filters,
    bulkActions,
    onSelectionChange,
    toolbar,
    title,
    description,
    perPage,
    onPerPageChange,
}: DataTableProps<T>) {
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);
    const [showColumnConfig, setShowColumnConfig] = useState(false);
    const columnRef = useRef<HTMLDivElement>(null);
    const [searchInput, setSearchInput] = useState(externalSearch || '');

    useEffect(() => {
        setSearchInput(externalSearch || '');
    }, [externalSearch]);

    const handleSearchChange = (value: string) => {
        setSearchInput(value);
        onSearchChange?.(value);
    };

    useEffect(() => {
        const handleClick = (e: MouseEvent) => {
            if (columnRef.current && !columnRef.current.contains(e.target as Node)) {
                setShowColumnConfig(false);
            }
        };
        if (showColumnConfig) {
            document.addEventListener('mousedown', handleClick);
        }
        return () => document.removeEventListener('mousedown', handleClick);
    }, [showColumnConfig]);

    const defaultVisibility = useMemo(() => {
        const vis: Record<string, boolean> = {};
        columns.forEach((col) => {
            vis[col.key] = col.hideable ? (col.defaultVisible ?? true) : true;
        });
        return vis;
    }, [columns]);

    const [columnVisibility, setColumnVisibility] = useState<Record<string, boolean>>(defaultVisibility);

    const visibleColumns = columns.filter((col) => columnVisibility[col.key] !== false);

    const allSelected = data.length > 0 && selectedIds.length === data.length;
    const someSelected = selectedIds.length > 0 && !allSelected;

    const toggleSelectAll = () => {
        if (allSelected) {
            setSelectedIds([]);
            onSelectionChange?.([]);
        } else {
            const ids = data.map((item) => keyExtractor(item));
            setSelectedIds(ids);
            onSelectionChange?.(ids);
        }
    };

    const toggleSelect = (id: string | number) => {
        const next = selectedIds.includes(id)
            ? selectedIds.filter((sid) => sid !== id)
            : [...selectedIds, id];
        setSelectedIds(next);
        onSelectionChange?.(next);
    };

    const toggleColumn = (key: string) => {
        setColumnVisibility((prev) => ({
            ...prev,
            [key]: !(prev[key] ?? true),
        }));
    };

    const resetColumns = () => {
        setColumnVisibility(defaultVisibility);
    };

    const renderSortIcon = (column: Column<T>) => {
        if (!column.sortable) return null;
        if (sortColumn !== column.key) {
            return <ChevronsUpDown className="ml-1 h-3.5 w-3.5 shrink-0 opacity-30 group-hover:opacity-60 transition-opacity" />;
        }
        if (sortDirection === 'none') return null;
        return sortDirection === 'asc'
            ? <ChevronUp className="ml-1 h-3.5 w-3.5 shrink-0 text-primary" />
            : <ChevronDown className="ml-1 h-3.5 w-3.5 shrink-0 text-primary" />;
    };

    return (
        <div className="rounded-xl border bg-card text-card-foreground shadow-sm">
            {/* Header */}
            {(title || description || toolbar) && (
                <div className="flex items-center justify-between px-6 pt-6 pb-2">
                    <div>
                        {title && <h2 className="text-lg font-semibold text-foreground">{title}</h2>}
                        {description && <p className="text-sm text-muted-foreground mt-0.5">{description}</p>}
                    </div>
                    {toolbar && <div className="flex items-center gap-2">{toolbar}</div>}
                </div>
            )}

            {/* Toolbar */}
            <div className="px-6 py-4">
                <div className="flex flex-wrap items-center gap-3">
                    {onSearchChange && (
                        <div className="relative flex-1 min-w-[200px] max-w-sm">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder={searchPlaceholder}
                                value={searchInput}
                                onChange={(e) => handleSearchChange(e.target.value)}
                                className="pl-9 h-9"
                            />
                        </div>
                    )}

                    {filters}

                    <div className="flex-1" />

                    <div ref={columnRef} className="relative">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setShowColumnConfig(!showColumnConfig)}
                            className="h-9"
                        >
                            <Columns className="h-4 w-4" />
                            Kolom
                        </Button>
                        {showColumnConfig && (
                            <div className="absolute right-0 top-full mt-1 z-50 w-56 rounded-lg border bg-card shadow-lg p-2 space-y-1">
                                <div className="flex items-center justify-between px-2 py-1.5 border-b mb-1">
                                    <span className="text-xs font-medium text-muted-foreground">Tampilkan Kolom</span>
                                    <button
                                        onClick={resetColumns}
                                        className="text-xs text-primary hover:underline"
                                    >
                                        Reset
                                    </button>
                                </div>
                                {columns.filter((c) => c.hideable).map((col) => (
                                    <label
                                        key={col.key}
                                        onClick={() => toggleColumn(col.key)}
                                        className="flex items-center gap-2.5 px-2 py-1.5 rounded-md hover:bg-accent cursor-pointer text-sm"
                                    >
                                        <div
                                            className={cn(
                                                'flex h-4 w-4 shrink-0 items-center justify-center rounded border transition-colors',
                                                columnVisibility[col.key] !== false
                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                    : 'border-input',
                                            )}
                                        >
                                            {columnVisibility[col.key] !== false && <Check className="h-3 w-3" />}
                                        </div>
                                        {col.label}
                                    </label>
                                ))}
                                {columns.filter((c) => c.hideable).length === 0 && (
                                    <p className="text-xs text-muted-foreground px-2 py-1">Tidak ada kolom yang dapat disembunyikan</p>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Bulk actions bar */}
            {selectedIds.length > 0 && bulkActions && (
                <div className="mx-6 mb-2 flex items-center gap-3 px-4 py-2.5 rounded-lg bg-primary/10 border border-primary/20">
                    <span className="text-sm font-medium text-primary">{selectedIds.length} dipilih</span>
                    <div className="flex items-center gap-2 ml-auto">
                        {bulkActions}
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => { setSelectedIds([]); onSelectionChange?.([]); }}
                            className="text-muted-foreground h-8"
                        >
                            Batal
                        </Button>
                    </div>
                </div>
            )}

            {/* Table */}
            <div className="px-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {onSelectionChange && (
                                <TableHead className="w-10 pl-6">
                                    <input
                                        type="checkbox"
                                        checked={allSelected}
                                        ref={(el) => {
                                            if (el) el.indeterminate = someSelected;
                                        }}
                                        onChange={toggleSelectAll}
                                        className="rounded border-gray-300 accent-primary"
                                    />
                                </TableHead>
                            )}
                            {visibleColumns.map((col) => (
                                <TableHead
                                    key={col.key}
                                    className={cn(
                                        col.sortable && 'cursor-pointer select-none group',
                                        col.headClassName,
                                    )}
                                    onClick={() => {
                                        if (col.sortable && onSort) {
                                            onSort(col.key);
                                        }
                                    }}
                                >
                                    <div className="flex items-center gap-0.5">
                                        {col.label}
                                        {renderSortIcon(col)}
                                    </div>
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {data.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={(onSelectionChange ? 1 : 0) + visibleColumns.length}
                                    className="text-center text-muted-foreground py-16"
                                >
                                    <div className="flex flex-col items-center gap-1">
                                        <Search className="h-8 w-8 text-muted-foreground/40" />
                                        <p>Tidak ada data</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ) : (
                            data.map((item, idx) => {
                                const id = keyExtractor(item);
                                return (
                                    <TableRow key={id} className={cn(idx % 2 === 1 && 'bg-muted/20')}>
                                        {onSelectionChange && (
                                            <TableCell className="pl-6">
                                                <input
                                                    type="checkbox"
                                                    checked={selectedIds.includes(id)}
                                                    onChange={() => toggleSelect(id)}
                                                    className="rounded border-gray-300 accent-primary"
                                                />
                                            </TableCell>
                                        )}
                                        {visibleColumns.map((col) => (
                                            <TableCell key={col.key} className={col.className}>
                                                {col.render
                                                    ? col.render(item, idx)
                                                    : (item as any)[col.key] ?? '-'}
                                            </TableCell>
                                        ))}
                                    </TableRow>
                                );
                            })
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Footer */}
            <div className="px-6 py-4 border-t">
                <Pagination
                    currentPage={meta.current_page}
                    lastPage={meta.last_page}
                    total={meta.total}
                    from={meta.from}
                    to={meta.to}
                    onPageChange={onPageChange}
                    perPage={perPage}
                    onPerPageChange={onPerPageChange}
                />
            </div>
        </div>
    );
}

export { DataTable };
