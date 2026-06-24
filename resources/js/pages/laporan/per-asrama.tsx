import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';

export default function LaporanPerAsrama() {
    const { data, asrama, filters } = usePage<any>().props;
    const [asramaId, setAsramaId] = useState(filters?.asrama_id || '');

    const filter = () => {
        router.get('/laporan/per-asrama', {
            asrama_id: asramaId || undefined,
        });
    };

    return (
        <AppLayout>
            <Head title="Laporan Per Asrama" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Laporan Per Asrama</h1>
                    <p className="text-muted-foreground">Ranking santri per asrama</p>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-end gap-4 mb-6">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Asrama</label>
                                <Select value={asramaId} onChange={(e) => setAsramaId(e.target.value)} placeholder="Semua Asrama" options={asrama.map((a: any) => ({ value: a.id, label: a.nomor }))} />
                            </div>
                            <Button onClick={filter}>Tampilkan</Button>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Ranking</TableHead>
                                    <TableHead>Nama Santri</TableHead>
                                    <TableHead>NIS</TableHead>
                                    <TableHead>Jumlah Pelanggaran</TableHead>
                                    <TableHead>Jumlah Shalawat</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.map((s: any, i: number) => (
                                    <TableRow key={s.santri_id}>
                                        <TableCell>
                                            <Badge variant={i < 3 ? 'default' : 'secondary'}>{i + 1}</Badge>
                                        </TableCell>
                                        <TableCell className="font-medium">{s.santri_nama}</TableCell>
                                        <TableCell>{s.santri_nis}</TableCell>
                                        <TableCell>{s.jumlah_pelanggaran}</TableCell>
                                        <TableCell>{s.jumlah_shalawat}</TableCell>
                                    </TableRow>
                                ))}
                                {data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center text-muted-foreground">
                                            Belum ada data
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
