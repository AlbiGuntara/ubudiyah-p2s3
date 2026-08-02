import { Head, usePage, Link } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable, type Column } from '@/components/shared/data-table';
import { ArrowLeft, AlertTriangle, BookOpen } from 'lucide-react';

export default function SantriShow() {
    const { santri, statistik } = usePage<any>().props;

    const formatDate = (date: string) => {
        if (!date) return '-';
        const [y, m, d] = date.split('T')[0].split('-');
        return `${d}-${m}-${y}`;
    };

    const setoranRows = (santri.pembinaan || [])
        .flatMap((p: any) => {
            const setoran = [...(p.setoran || [])].sort(
                (a: any, b: any) =>
                    new Date(a.tanggal_setor).getTime() -
                    new Date(b.tanggal_setor).getTime(),
            );
            let sisa = p.sisa_sanksi || 0;
            const rows: any[] = [];
            for (let i = setoran.length - 1; i >= 0; i--) {
                const s = setoran[i];
                rows.push({ ...s, _sisa_sanksi: sisa });
                sisa += s.jumlah || 0;
            }
            return rows;
        })
        .sort(
            (a: any, b: any) =>
                new Date(b.tanggal_setor).getTime() -
                new Date(a.tanggal_setor).getTime(),
        );

    return (
        <AppLayout>
            <Head title={santri.nama} />
            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/santri">
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{santri.nama}</h1>
                        <p className="text-muted-foreground">Detail santri</p>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Informasi Santri</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-col items-center mb-4">
                                {santri.foto ? (
                                    <img
                                        src={`/storage/${santri.foto}`}
                                        alt={santri.nama}
                                        className="h-24 w-24 rounded-full object-cover"
                                    />
                                ) : (
                                    <div className="flex h-24 w-24 items-center justify-center rounded-full bg-muted">
                                        <span className="text-2xl font-bold text-muted-foreground">
                                            {santri.nama.charAt(0).toUpperCase()}
                                        </span>
                                    </div>
                                )}
                            </div>
                            <dl className="space-y-3">
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Nama</dt>
                                    <dd className="font-medium">{santri.nama}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">NIS</dt>
                                    <dd className="font-medium">{santri.nis || '-'}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Asal (IKSASS)</dt>
                                    <dd className="font-medium">{santri.iksass || '-'}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Nama Panggilan</dt>
                                    <dd className="font-medium">{santri.nama_panggilan || '-'}</dd>
                                </div>
                                <div className="flex justify-between items-center">
                                    <dt className="text-muted-foreground">Status</dt>
                                    <dd>
                                        <Badge variant={
                                            santri.status === 'aktif'
                                                ? 'success'
                                                : santri.status === 'tidak aktif'
                                                  ? 'warning'
                                                  : 'destructive'
                                        }>
                                            {santri.status || 'aktif'}
                                        </Badge>
                                    </dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Daerah</dt>
                                    <dd className="font-medium">{santri.asrama?.daerah?.nama_daerah}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Asrama</dt>
                                    <dd className="font-medium">{santri.asrama?.nomor}</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Statistik</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="text-center p-4 rounded-lg bg-red-600/20">
                                    <AlertTriangle className="h-6 w-6 mx-auto mb-2 text-red-500" />
                                    <p className="text-2xl font-bold">{statistik.total_pelanggaran}</p>
                                    <p className="text-xs text-muted-foreground">Pelanggaran</p>
                                </div>
                                <div className="text-center p-4 rounded-lg bg-yellow-600/20">
                                    <BookOpen className="h-6 w-6 mx-auto mb-2 text-yellow-500" />
                                    <p className="text-2xl font-bold">{statistik.total_shalawat}</p>
                                    <p className="text-xs text-muted-foreground">Shalawat</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Riwayat Pelanggaran</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <DataTable
                            columns={[
                                { key: 'no', label: '#', render: (_p: any, idx: number) => <span>{idx + 1}</span>, className: 'text-muted-foreground text-xs w-10' },
                                { key: 'tanggal', label: 'Tanggal', render: (p: any) => <span>{formatDate(p.tanggal)}</span> },
                                { key: 'pelanggaran', label: 'Jenis Pelanggaran', render: (p: any) => p.daftar_pelanggaran?.nama_pelanggaran },
                                { key: 'sanksi', label: 'Sanksi', render: (p: any) => <span className="font-semibold text-red-600">{(p.jumlah || 1) * 100}</span> },
                                { key: 'sumber', label: 'Sumber', render: (p: any) => <Badge variant={p.sumber_pencatatan === 'petugas' ? 'success' : 'warning'}>{p.sumber_pencatatan}</Badge> },
                            ]}
                            data={santri.pelanggaran}
                            meta={{ current_page: 1, last_page: 1, total: santri.pelanggaran.length, from: 1, to: santri.pelanggaran.length }}
                            keyExtractor={(p: any) => p.id}
                            onPageChange={() => {}}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Riwayat Setoran</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <DataTable
                            columns={[
                                { key: 'no', label: '#', render: (_p: any, idx: number) => <span>{idx + 1}</span>, className: 'text-muted-foreground text-xs w-10' },
                                { key: 'tanggal_setor', label: 'Tanggal Setor', render: (s: any) => <span>{formatDate(s.tanggal_setor)}</span> },
                                { key: 'jumlah', label: 'Jumlah Setoran', render: (s: any) => <span className="font-medium text-green-600">{s.jumlah.toLocaleString()}</span> },
                                { key: 'sisa_sanksi', label: 'Sisa Sanksi', render: (s: any) => {
                                    const sisa = s._sisa_sanksi;
                                    return <Badge variant={sisa > 0 ? 'warning' : 'success'}>{sisa.toLocaleString()}</Badge>;
                                }},
                            ]}
                            data={setoranRows}
                            meta={{ current_page: 1, last_page: 1, total: setoranRows.length, from: 1, to: setoranRows.length }}
                            keyExtractor={(s: any) => s.id}
                            onPageChange={() => {}}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}