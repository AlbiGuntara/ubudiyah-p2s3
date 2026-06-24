import { Head, usePage, Link } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { ArrowLeft, AlertTriangle, BookOpen, Phone } from 'lucide-react';

export default function SantriShow() {
    const { santri, statistik } = usePage<any>().props;

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
                                    <dt className="text-muted-foreground">IKSASS</dt>
                                    <dd className="font-medium">{santri.iksass || '-'}</dd>
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
                            <div className="grid grid-cols-3 gap-4">
                                <div className="text-center p-4 rounded-lg bg-red-50 dark:bg-red-900/20">
                                    <AlertTriangle className="h-6 w-6 mx-auto mb-2 text-red-500" />
                                    <p className="text-2xl font-bold">{statistik.total_pelanggaran}</p>
                                    <p className="text-xs text-muted-foreground">Pelanggaran</p>
                                </div>
                                <div className="text-center p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
                                    <BookOpen className="h-6 w-6 mx-auto mb-2 text-yellow-500" />
                                    <p className="text-2xl font-bold">{statistik.total_shalawat}</p>
                                    <p className="text-xs text-muted-foreground">Shalawat</p>
                                </div>
                                <div className="text-center p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                    <Phone className="h-6 w-6 mx-auto mb-2 text-blue-500" />
                                    <p className="text-2xl font-bold">{statistik.jumlah_panggilan}</p>
                                    <p className="text-xs text-muted-foreground">Panggilan</p>
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
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Jenis Pelanggaran</TableHead>
                                    <TableHead>Jumlah</TableHead>
                                    <TableHead>Sumber</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {santri.pelanggaran.map((p: any) => (
                                    <TableRow key={p.id}>
                                        <TableCell>{p.tanggal}</TableCell>
                                        <TableCell>{p.daftar_pelanggaran?.nama_pelanggaran}</TableCell>
                                        <TableCell>{p.jumlah}</TableCell>
                                        <TableCell>
                                            <Badge variant={p.sumber_pencatatan === 'petugas' ? 'success' : 'warning'}>
                                                {p.sumber_pencatatan}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {santri.pelanggaran.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-center text-muted-foreground">
                                            Tidak ada pelanggaran
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Riwayat Pembinaan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Panggilan</TableHead>
                                    <TableHead>Sanksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {santri.pembinaan.map((p: any) => (
                                    <TableRow key={p.id}>
                                        <TableCell>{p.tanggal_panggilan}</TableCell>
                                        <TableCell>
                                            <Badge variant="warning">Panggilan {p.panggilan}</Badge>
                                        </TableCell>
                                        <TableCell>{p.sanksi}</TableCell>
                                    </TableRow>
                                ))}
                                {santri.pembinaan.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={3} className="text-center text-muted-foreground">
                                            Tidak ada pembinaan
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
