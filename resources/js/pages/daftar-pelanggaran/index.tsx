import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { Dialog } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Edit2, Trash2, Plus } from 'lucide-react';

export default function DaftarPelanggaranIndex() {
    const { daftarPelanggaran } = usePage<any>().props;
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [form, setForm] = useState({ nama_pelanggaran: '', poin: '1' });

    const openCreate = () => {
        setEditing(null);
        setForm({ nama_pelanggaran: '', poin: '1' });
        setShowModal(true);
    };

    const openEdit = (d: any) => {
        setEditing(d);
        setForm({ nama_pelanggaran: d.nama_pelanggaran, poin: String(d.poin) });
        setShowModal(true);
    };

    const submit = () => {
        const data = { ...form, poin: parseInt(form.poin) };
        if (editing) {
            router.put(`/daftar-pelanggaran/${editing.id}`, data, {
                onSuccess: () => setShowModal(false),
            });
        } else {
            router.post('/daftar-pelanggaran', data, {
                onSuccess: () => setShowModal(false),
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus jenis pelanggaran ini?')) {
            router.delete(`/daftar-pelanggaran/${id}`);
        }
    };

    return (
        <AppLayout>
            <Head title="Jenis Pelanggaran" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Jenis Pelanggaran</h1>
                        <p className="text-muted-foreground">Kelola daftar jenis pelanggaran ubudiyah</p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Tambah Pelanggaran
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama Pelanggaran</TableHead>
                                    <TableHead>Poin</TableHead>
                                    <TableHead>Digunakan</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {daftarPelanggaran.data.map((d: any) => (
                                    <TableRow key={d.id}>
                                        <TableCell className="font-medium">{d.nama_pelanggaran}</TableCell>
                                        <TableCell><Badge>{d.poin} Poin</Badge></TableCell>
                                        <TableCell>{d.pelanggaran_count} kali</TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button variant="ghost" size="sm" onClick={() => openEdit(d)}>
                                                    <Edit2 className="h-4 w-4" />
                                                </Button>
                                                <Button variant="ghost" size="sm" onClick={() => destroy(d.id)}>
                                                    <Trash2 className="h-4 w-4 text-red-500" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        <Pagination
                            currentPage={daftarPelanggaran.current_page}
                            lastPage={daftarPelanggaran.last_page}
                            total={daftarPelanggaran.total}
                            from={daftarPelanggaran.from}
                            to={daftarPelanggaran.to}
                            onPageChange={(page) => router.get('/daftar-pelanggaran', { page })}
                        />
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={showModal}
                onClose={() => setShowModal(false)}
                title={editing ? 'Edit Jenis Pelanggaran' : 'Tambah Jenis Pelanggaran'}
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Nama Pelanggaran</label>
                        <Input
                            value={form.nama_pelanggaran}
                            onChange={(e) => setForm({ ...form, nama_pelanggaran: e.target.value })}
                            placeholder="Contoh: Tidak Jamaah"
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Poin</label>
                        <Input
                            type="number"
                            min={1}
                            value={form.poin}
                            onChange={(e) => setForm({ ...form, poin: e.target.value })}
                        />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button variant="outline" onClick={() => setShowModal(false)}>Batal</Button>
                        <Button onClick={submit}>{editing ? 'Simpan' : 'Tambah'}</Button>
                    </div>
                </div>
            </Dialog>
        </AppLayout>
    );
}
