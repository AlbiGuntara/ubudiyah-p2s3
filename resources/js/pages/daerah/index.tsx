import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { Dialog } from '@/components/ui/dialog';
import { Edit2, Trash2, Plus, Search, Map } from 'lucide-react';

interface Daerah {
    id: number;
    kode: string;
    nama_daerah: string;
    asrama_count: number;
}

export default function DaerahIndex() {
    const { daerah } = usePage<{ daerah: { data: Daerah[]; current_page: number; last_page: number; total: number; from: number; to: number } }>().props;
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Daerah | null>(null);
    const [search, setSearch] = useState('');

    const [form, setForm] = useState({ kode: '', nama_daerah: '' });

    const openCreate = () => {
        setEditing(null);
        setForm({ kode: '', nama_daerah: '' });
        setShowModal(true);
    };

    const openEdit = (d: Daerah) => {
        setEditing(d);
        setForm({ kode: d.kode, nama_daerah: d.nama_daerah });
        setShowModal(true);
    };

    const submit = () => {
        if (editing) {
            router.put(`/daerah/${editing.id}`, form, {
                onSuccess: () => { setShowModal(false); },
            });
        } else {
            router.post('/daerah', form, {
                onSuccess: () => { setShowModal(false); },
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus daerah ini?')) {
            router.delete(`/daerah/${id}`);
        }
    };

    return (
        <AppLayout>
            <Head title="Daerah" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Daerah</h1>
                        <p className="text-muted-foreground">Kelola data daerah pondok</p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Tambah Daerah
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-center gap-4 mb-4">
                            <div className="relative flex-1 max-w-sm">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Cari daerah..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Kode</TableHead>
                                    <TableHead>Nama Daerah</TableHead>
                                    <TableHead>Jumlah Asrama</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {daerah.data.map((d) => (
                                    <TableRow key={d.id}>
                                        <TableCell className="font-medium">{d.kode}</TableCell>
                                        <TableCell>{d.nama_daerah}</TableCell>
                                        <TableCell>{d.asrama_count}</TableCell>
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
                            currentPage={daerah.current_page}
                            lastPage={daerah.last_page}
                            total={daerah.total}
                            from={daerah.from}
                            to={daerah.to}
                            onPageChange={(page) => router.get('/daerah', { page })}
                        />
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={showModal}
                onClose={() => setShowModal(false)}
                title={editing ? 'Edit Daerah' : 'Tambah Daerah'}
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Kode</label>
                        <Input
                            value={form.kode}
                            onChange={(e) => setForm({ ...form, kode: e.target.value })}
                            placeholder="Contoh: A"
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Nama Daerah</label>
                        <Input
                            value={form.nama_daerah}
                            onChange={(e) => setForm({ ...form, nama_daerah: e.target.value })}
                            placeholder="Contoh: Daerah A"
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
