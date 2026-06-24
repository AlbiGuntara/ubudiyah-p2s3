import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { BarChart3 } from 'lucide-react';

export default function LaporanTotal() {
    const { data, daerah, asrama, filters } = usePage<any>().props;
    const [daerahId, setDaerahId] = useState(filters?.daerah_id || '');
    const [asramaId, setAsramaId] = useState(filters?.asrama_id || '');

    const filter = () => {
        router.get('/laporan/total', {
            daerah_id: daerahId || undefined,
            asrama_id: asramaId || undefined,
        });
    };

    return (
        <AppLayout>
            <Head title="Laporan Total Pelanggaran" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Laporan Total Pelanggaran</h1>
                    <p className="text-muted-foreground">Seluruh pelanggaran termasuk ketua kamar</p>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <div className="flex flex-wrap items-end gap-4 mb-6">
                            <Select value={daerahId} onChange={(e) => setDaerahId(e.target.value)} placeholder="Semua Daerah" options={daerah.map((d: any) => ({ value: d.id, label: d.nama_daerah }))} />
                            <Select value={asramaId} onChange={(e) => setAsramaId(e.target.value)} placeholder="Semua Asrama" options={asrama.map((a: any) => ({ value: a.id, label: a.nomor }))} />
                            <Button onClick={filter}>Tampilkan</Button>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="p-8 rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/50 dark:to-purple-900/30 text-center">
                                <BarChart3 className="h-10 w-10 mx-auto mb-3 text-purple-600" />
                                <p className="text-sm text-muted-foreground mb-1">Total Pelanggaran</p>
                                <p className="text-4xl font-bold text-purple-700 dark:text-purple-400">{data.total_pelanggaran}</p>
                            </div>
                            <div className="p-8 rounded-xl bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/30 text-center">
                                <BarChart3 className="h-10 w-10 mx-auto mb-3 text-green-600" />
                                <p className="text-sm text-muted-foreground mb-1">Sumber Petugas</p>
                                <p className="text-4xl font-bold text-green-700 dark:text-green-400">{data.sumber_petugas}</p>
                            </div>
                            <div className="p-8 rounded-xl bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-950/50 dark:to-yellow-900/30 text-center">
                                <BarChart3 className="h-10 w-10 mx-auto mb-3 text-yellow-600" />
                                <p className="text-sm text-muted-foreground mb-1">Sumber Ketua Kamar</p>
                                <p className="text-4xl font-bold text-yellow-700 dark:text-yellow-400">{data.sumber_ketua_kamar}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
