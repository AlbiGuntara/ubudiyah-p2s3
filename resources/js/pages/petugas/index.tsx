import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { Dialog } from '@/components/ui/dialog';
import { Edit2, Trash2, Plus } from 'lucide-react';

export default function PetugasIndex() {
    const { petugas, santri, asrama } = usePage<any>().props;
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [form, setForm] = useState({ santri_id: '', asrama_id: '', jabatan: '', tugas: '' });

    const openCreate = () => {
        setEditing(null);
        setForm({ santri_id: '', asrama_id: '', jabatan: '', tugas: '' });
        setShowModal(true);
    };

    const openEdit = (p: any) => {
        setEditing(p);
        setForm({
            santri_id: p.santri_id || '',
            asrama_id: p.asrama_id || '',
            jabatan: p.jabatan,
            tugas: p.tugas,
        });
        setShowModal(true);
    };

    const submit = () => {
        const data = {
            ...form,
            santri_id: form.santri_id || null,
            asrama_id: form.asrama_id || null,
        };
        if (editing) {
            router.put(`/petugas/${editing.id}`, data, {
                onSuccess: () => setShowModal(false),
            });
        } else {
            router.post('/petugas', data, {
                onSuccess: () => setShowModal(false),
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus petugas ini?')) {
            router.delete(`/petugas/${id}`);
        }
    };

    return (
        <AppLayout>
            <Head title="Petugas" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Petugas</h1>
                        <p className="text-muted-foreground">Kelola data petugas ubudiyah</p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Tambah Petugas
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama Santri</TableHead>
                                    <TableHead>Asrama</TableHead>
                                    <TableHead>Jabatan</TableHead>
                                    <TableHead>Tugas</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {petugas.data.map((p: any) => (
                                    <TableRow key={p.id}>
                                        <TableCell className="font-medium">{p.santri?.nama || '-'}</TableCell>
                                        <TableCell>{p.asrama?.nomor || '-'}</TableCell>
                                        <TableCell>{p.jabatan}</TableCell>
                                        <TableCell>{p.tugas}</TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button variant="ghost" size="sm" onClick={() => openEdit(p)}>
                                                    <Edit2 className="h-4 w-4" />
                                                </Button>
                                                <Button variant="ghost" size="sm" onClick={() => destroy(p.id)}>
                                                    <Trash2 className="h-4 w-4 text-red-500" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        <Pagination
                            currentPage={petugas.current_page}
                            lastPage={petugas.last_page}
                            total={petugas.total}
                            from={petugas.from}
                            to={petugas.to}
                            onPageChange={(page) => router.get('/petugas', { page })}
                        />
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={showModal}
                onClose={() => setShowModal(false)}
                title={editing ? 'Edit Petugas' : 'Tambah Petugas'}
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Santri (opsional)</label>
                        <Select
                            value={form.santri_id}
                            onChange={(e) => setForm({ ...form, santri_id: e.target.value })}
                            placeholder="Pilih Santri"
                            options={santri.map((s: any) => ({ value: s.id, label: s.nama }))}
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Asrama (opsional)</label>
                        <Select
                            value={form.asrama_id}
                            onChange={(e) => setForm({ ...form, asrama_id: e.target.value })}
                            placeholder="Pilih Asrama"
                            options={asrama.map((a: any) => ({ value: a.id, label: a.nomor }))}
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Jabatan</label>
                        <Input value={form.jabatan} onChange={(e) => setForm({ ...form, jabatan: e.target.value })} placeholder="Jabatan" />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Tugas</label>
                        <Input value={form.tugas} onChange={(e) => setForm({ ...form, tugas: e.target.value })} placeholder="Tugas" />
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
