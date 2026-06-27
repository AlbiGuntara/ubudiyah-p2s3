import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Shield } from 'lucide-react';

export default function AuditIndex() {
    const { logs } = usePage<any>().props;
    const [sortColumn, setSortColumn] = useState('');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

    const handleSort = (column: string) => {
        const dir = sortColumn === column && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortColumn(column);
        setSortDirection(dir);
        router.get('/audit', { sort_column: column, sort_direction: dir });
    };

    const columns: Column<any>[] = [
        { key: 'no', label: '#', render: (_log: any, idx: number) => <span>{logs.from + idx}</span>, className: 'text-muted-foreground text-xs w-10' },
        {
            key: 'created_at',
            label: 'Waktu',
            sortable: true,
            render: (log) => <span className="text-sm text-muted-foreground">{new Date(log.created_at).toLocaleString('id')}</span>,
        },
        { key: 'user', label: 'User', sortable: true, render: (log) => <span className="font-medium">{log.user?.name}</span> },
        {
            key: 'aktivitas',
            label: 'Aktivitas',
            render: (log) => (
                <Badge variant={
                    log.aktivitas.startsWith('menambahkan') ? 'success' :
                    log.aktivitas.startsWith('mengubah') ? 'warning' : 'destructive'
                }>
                    {log.aktivitas}
                </Badge>
            ),
        },
        {
            key: 'model',
            label: 'Model',
            render: (log) => <span className="text-muted-foreground">{log.model_type?.split('\\').pop()}</span>,
        },
    ];

    return (
        <AppLayout>
            <Head title="Audit Log" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Audit Log</h1>
                    <p className="text-muted-foreground">Riwayat aktivitas pengguna</p>
                </div>

                <DataTable
                    columns={columns}
                    data={logs.data}
                    meta={logs}
                    keyExtractor={(log) => log.id}
                    onPageChange={(page) => router.get('/audit', { page, sort_column: sortColumn || undefined, sort_direction: sortDirection || undefined })}
                    sortColumn={sortColumn}
                    sortDirection={sortDirection}
                    onSort={handleSort}
                />
            </div>
        </AppLayout>
    );
}
