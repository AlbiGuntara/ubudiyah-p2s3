import { useCallback, useEffect, useRef, useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Shield, Search, X } from 'lucide-react';

export default function AuditIndex() {
    const { logs: initialLogs, filters } = usePage<any>().props;

    const [logs, setLogs] = useState(initialLogs.data ?? []);
    const [meta, setMeta] = useState({
        current_page: initialLogs.current_page ?? 1,
        last_page: initialLogs.last_page ?? 1,
        total: initialLogs.total ?? 0,
    });
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState(filters?.search ?? '');
    const sentinelRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        setLogs(initialLogs.data ?? []);
        setMeta({
            current_page: initialLogs.current_page ?? 1,
            last_page: initialLogs.last_page ?? 1,
            total: initialLogs.total ?? 0,
        });
    }, [initialLogs]);

    const hasMore = meta.current_page < meta.last_page;

    const loadMore = useCallback(async () => {
        if (loading || !hasMore) return;
        setLoading(true);

        const nextPage = meta.current_page + 1;
        const params = new URLSearchParams({ page: String(nextPage), per_page: '50' });
        if (search) params.set('search', search);

        try {
            const res = await fetch(`/audit?${params}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();

            setLogs((prev) => [...prev, ...(data.data ?? [])]);
            setMeta({
                current_page: data.current_page,
                last_page: data.last_page,
                total: data.total,
            });
        } finally {
            setLoading(false);
        }
    }, [loading, hasMore, meta.current_page, search, meta.last_page]);

    useEffect(() => {
        const el = sentinelRef.current;
        if (!el) return;

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting) loadMore();
            },
            { rootMargin: '200px' },
        );

        observer.observe(el);
        return () => observer.disconnect();
    }, [loadMore]);

    const handleSearchChange = (value: string) => {
        setSearch(value);
        router.get('/audit', {
            search: value || undefined,
            page: 1,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AppLayout>
            <Head title="Audit Log" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Audit Log</h1>
                        <p className="text-muted-foreground">Riwayat aktivitas pengguna</p>
                    </div>
                    <div className="text-sm text-muted-foreground">
                        {meta.total} total entri
                    </div>
                </div>

                <div className="relative max-w-sm">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        type="text"
                        placeholder="Cari aktivitas, user, model..."
                        value={search}
                        onChange={(e) => handleSearchChange(e.target.value)}
                        className="pl-10 pr-10"
                    />
                    {search && (
                        <button
                            onClick={() => handleSearchChange('')}
                            className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    )}
                </div>

                <div className="rounded-lg border overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3 w-16">#</th>
                                <th className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Waktu</th>
                                <th className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">User</th>
                                <th className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Aktivitas</th>
                                <th className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 py-3">Model</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="text-center text-muted-foreground px-4 py-8">
                                        Tidak ada data
                                    </td>
                                </tr>
                            ) : (
                                logs.map((log: any, idx: number) => (
                                    <tr key={log.id} className="border-b last:border-0 hover:bg-muted/30">
                                        <td className="px-4 py-3 text-xs text-muted-foreground">{idx + 1}</td>
                                        <td className="px-4 py-3 text-sm text-muted-foreground whitespace-nowrap">
                                            {new Date(log.created_at).toLocaleString('id')}
                                        </td>
                                        <td className="px-4 py-3 text-sm font-medium">
                                            {log.user?.name ?? log.user_name ?? <span className="text-muted-foreground italic">User telah dihapus</span>}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant={
                                                log.aktivitas.startsWith('menambahkan') ? 'success' as any :
                                                log.aktivitas.startsWith('mengubah') ? 'warning' as any : 'destructive' as any
                                            }>
                                                {log.aktivitas}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-muted-foreground">
                                            {log.model_type?.split('\\').pop()}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <div ref={sentinelRef} className="flex justify-center py-4">
                    {loading && <p className="text-sm text-muted-foreground">Memuat data...</p>}
                    {!hasMore && logs.length > 0 && (
                        <p className="text-sm text-muted-foreground">Semua data telah dimuat</p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
