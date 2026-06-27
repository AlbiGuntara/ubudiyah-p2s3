import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Column } from '@/components/shared/data-table';

export default function LaporanPerAsrama() {
    const { data, asrama, filters } = usePage<any>().props;
    const [asramaId, setAsramaId] = useState(filters?.asrama_id || '');

    const filter = () => {
        router.get('/laporan/per-asrama', {
            asrama_id: asramaId || undefined,
        });
    };

    const dataWithRank = data.map((s: any, i: number) => ({ ...s, _rank: i + 1 }));

    const columns: Column<any>[] = [
        {
            key: '_rank',
            label: 'Ranking',
            render: (s) => <Badge variant={s._rank <= 3 ? 'default' : 'secondary'}>{s._rank}</Badge>,
        },
        { key: 'santri_nama', label: 'Nama Santri', sortable: true, render: (s) => <span className="font-medium">{s.santri_nama}</span> },
        { key: 'santri_nis', label: 'NIS' },
        { key: 'jumlah_pelanggaran', label: 'Jumlah Pelanggaran', sortable: true },
        { key: 'jumlah_shalawat', label: 'Jumlah Shalawat', sortable: true },
    ];

    return (
        <AppLayout>
            <Head title="Laporan Per Asrama" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Laporan Per Asrama</h1>
                    <p className="text-muted-foreground">Ranking santri per asrama</p>
                </div>

                <DataTable
                    columns={columns}
                    data={dataWithRank}
                    meta={{ current_page: 1, last_page: 1, total: data.length, from: 1, to: data.length }}
                    keyExtractor={(s) => s.santri_id || Math.random()}
                    onPageChange={() => {}}
                    filters={
                        <>
                            <Select value={asramaId} onChange={(e) => setAsramaId(e.target.value)} placeholder="Semua Asrama" options={asrama.map((a: any) => ({ value: a.id, label: a.nomor }))} />
                            <Button variant="outline" size="sm" onClick={filter}>Tampilkan</Button>
                        </>
                    }
                />
            </div>
        </AppLayout>
    );
}
