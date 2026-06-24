import { Head, usePage } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';

export default function LaporanPerDaerah() {
    const { data } = usePage<any>().props;
    const entries = Object.entries(data || {});

    return (
        <AppLayout>
            <Head title="Laporan Per Daerah" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Laporan Per Daerah</h1>
                    <p className="text-muted-foreground">Ranking asrama per daerah</p>
                </div>

                {entries.length === 0 ? (
                    <Card>
                        <CardContent className="p-6 text-center text-muted-foreground">Belum ada data</CardContent>
                    </Card>
                ) : (
                    entries.map(([daerahId, daerah]: [string, any]) => (
                        <Card key={daerahId}>
                            <CardHeader>
                                <CardTitle className="text-lg text-green-700 dark:text-green-400">{daerah.nama_daerah}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Ranking</TableHead>
                                            <TableHead>Asrama</TableHead>
                                            <TableHead>Jumlah Pelanggaran</TableHead>
                                            <TableHead>Jumlah Santri</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {daerah.asrama.map((a: any, i: number) => (
                                            <TableRow key={a.asrama_id}>
                                                <TableCell>
                                                    <Badge variant={i < 3 ? 'default' : 'secondary'}>{i + 1}</Badge>
                                                </TableCell>
                                                <TableCell className="font-medium">Asrama {a.asrama_nomor}</TableCell>
                                                <TableCell>{a.jumlah_pelanggaran}</TableCell>
                                                <TableCell>{a.jumlah_santri}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    ))
                )}
            </div>
        </AppLayout>
    );
}
