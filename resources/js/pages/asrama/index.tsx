import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { Dialog } from '@/components/ui/dialog';
import { Edit2, Trash2, Plus, Search, Building2 } from 'lucide-react';

export default function AsramaIndex() {
    const { asrama, daerah, filters } = usePage<any>().props;
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [filterDaerah, setFilterDaerah] = useState(filters?.daerah_id || '');

    const [form, setForm] = useState({ daerah_id: '', nomor: '' });

    const openCreate = () => {
        setEditing(null);
        setForm({ daerah_id: '', nomor: '' });
        setShowModal(true);
    };

    const openEdit = (a: any) => {
        setEditing(a);
        setForm({ daerah_id: a.daerah_id, nomor: a.nomor });
        setShowModal(true);
    };

    const submit = () => {
        if (editing) {
            router.put(`/asrama/${editing.id}`, form, {
                onSuccess: () => setShowModal(false),
            });
        } else {
            router.post('/asrama', form, {
                onSuccess: () => setShowModal(false),
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus asrama ini?')) {
            router.delete(`/asrama/${id}`);
        }
    };

    const applyFilter = () => {
        router.get('/asrama', { daerah_id: filterDaerah || undefined });
    };

    return (
        <AppLayout>
            <Head title="Asrama" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Asrama</h1>
                        <p className="text-muted-foreground">Kelola data asrama pondok</p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Tambah Asrama
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-center gap-4 mb-4">
                            <Select
                                value={filterDaerah}
                                onChange={(e) => setFilterDaerah(e.target.value)}
                                placeholder="Semua Daerah"
                                options={daerah.map((d: any) => ({ value: d.id, label: d.nama_daerah }))}
                                className="max-w-xs"
                            />
                            <Button variant="outline" onClick={applyFilter}>Filter</Button>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Daerah</TableHead>
                                    <TableHead>Nomor Asrama</TableHead>
                                    <TableHead>Jumlah Santri</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {asrama.data.map((a: any) => (
                                    <TableRow key={a.id}>
                                        <TableCell className="font-medium">{a.daerah?.nama_daerah}</TableCell>
                                        <TableCell>{a.nomor}</TableCell>
                                        <TableCell>{a.santri_count}</TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button variant="ghost" size="sm" onClick={() => openEdit(a)}>
                                                    <Edit2 className="h-4 w-4" />
                                                </Button>
                                                <Button variant="ghost" size="sm" onClick={() => destroy(a.id)}>
                                                    <Trash2 className="h-4 w-4 text-red-500" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        <Pagination
                            currentPage={asrama.current_page}
                            lastPage={asrama.last_page}
                            total={asrama.total}
                            from={asrama.from}
                            to={asrama.to}
                            onPageChange={(page) => router.get('/asrama', { page, daerah_id: filterDaerah || undefined })}
                        />
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={showModal}
                onClose={() => setShowModal(false)}
                title={editing ? 'Edit Asrama' : 'Tambah Asrama'}
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Daerah</label>
                        <Select
                            value={form.daerah_id}
                            onChange={(e) => setForm({ ...form, daerah_id: e.target.value })}
                            placeholder="Pilih Daerah"
                            options={daerah.map((d: any) => ({ value: d.id, label: d.nama_daerah }))}
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Nomor Asrama</label>
                        <Input
                            value={form.nomor}
                            onChange={(e) => setForm({ ...form, nomor: e.target.value })}
                            placeholder="Contoh: 01"
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
