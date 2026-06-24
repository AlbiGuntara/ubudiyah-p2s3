import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Card, CardContent } from '@/components/ui/card';
import { FileDown, FileText, BarChart3 } from 'lucide-react';

export default function LaporanTahunan() {
    const { data, daerah, asrama, filters } = usePage<any>().props;
    const [tahun, setTahun] = useState(filters?.tahun || new Date().getFullYear());
    const [daerahId, setDaerahId] = useState(filters?.daerah_id || '');
    const [asramaId, setAsramaId] = useState(filters?.asrama_id || '');
    const [iksass, setIksass] = useState(filters?.iksass || '');

    const filter = () => {
        router.get('/laporan/tahunan', {
            tahun,
            daerah_id: daerahId || undefined,
            asrama_id: asramaId || undefined,
            iksass: iksass || undefined,
        });
    };

    const exportExcel = () => {
        const params = new URLSearchParams({ tahun });
        if (daerahId) params.set('daerah_id', daerahId);
        if (asramaId) params.set('asrama_id', asramaId);
        if (iksass) params.set('iksass', iksass);
        window.open(`/export/tahunan/excel?${params}`, '_blank');
    };

    const exportPdf = () => {
        const params = new URLSearchParams({ tahun });
        if (daerahId) params.set('daerah_id', daerahId);
        if (asramaId) params.set('asrama_id', asramaId);
        if (iksass) params.set('iksass', iksass);
        window.open(`/export/tahunan/pdf?${params}`, '_blank');
    };

    return (
        <AppLayout>
            <Head title="Laporan Tahunan" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Laporan Tahunan</h1>
                    <p className="text-muted-foreground">Laporan pelanggaran tahunan (sumber: petugas)</p>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <div className="flex flex-wrap items-end gap-4 mb-6">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Tahun</label>
                                <Input type="number" value={tahun} onChange={(e) => setTahun(e.target.value)} className="w-24" />
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Daerah</label>
                                <Select value={daerahId} onChange={(e) => setDaerahId(e.target.value)} placeholder="Semua" options={daerah.map((d: any) => ({ value: d.id, label: d.nama_daerah }))} />
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Asrama</label>
                                <Select value={asramaId} onChange={(e) => setAsramaId(e.target.value)} placeholder="Semua" options={asrama.map((a: any) => ({ value: a.id, label: a.nomor }))} />
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium">IKSASS</label>
                                <Input value={iksass} onChange={(e) => setIksass(e.target.value)} placeholder="Tahun" className="w-24" />
                            </div>
                            <Button onClick={filter}>Tampilkan</Button>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="p-8 rounded-xl bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/30 text-center">
                                <BarChart3 className="h-10 w-10 mx-auto mb-3 text-green-600" />
                                <p className="text-sm text-muted-foreground mb-1">Jumlah Pelanggaran</p>
                                <p className="text-4xl font-bold text-green-700 dark:text-green-400">{data.jumlah_pelanggaran}</p>
                            </div>
                            <div className="p-8 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/30 text-center">
                                <BarChart3 className="h-10 w-10 mx-auto mb-3 text-blue-600" />
                                <p className="text-sm text-muted-foreground mb-1">Jumlah Santri Melanggar</p>
                                <p className="text-4xl font-bold text-blue-700 dark:text-blue-400">{data.jumlah_santri}</p>
                            </div>
                        </div>

                        <div className="flex gap-3 mt-6 pt-6 border-t">
                            <Button onClick={exportExcel}>
                                <FileDown className="h-4 w-4" />
                                Export Excel
                            </Button>
                            <Button variant="outline" onClick={exportPdf}>
                                <FileText className="h-4 w-4" />
                                Export PDF
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
