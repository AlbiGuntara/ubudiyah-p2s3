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
import { Badge } from '@/components/ui/badge';
import { Edit2, Trash2, Plus } from 'lucide-react';

export default function PembinaanIndex() {
    const { pembinaan, santri } = usePage<any>().props;
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [form, setForm] = useState({ santri_id: '', panggilan: 'I', tanggal_panggilan: new Date().toISOString().split('T')[0], sanksi: '' });

    const openCreate = () => {
        setEditing(null);
        setForm({ santri_id: '', panggilan: 'I', tanggal_panggilan: new Date().toISOString().split('T')[0], sanksi: '' });
        setShowModal(true);
    };

    const openEdit = (p: any) => {
        setEditing(p);
        setForm({ santri_id: p.santri_id, panggilan: p.panggilan, tanggal_panggilan: p.tanggal_panggilan, sanksi: p.sanksi || '' });
        setShowModal(true);
    };

    const submit = () => {
        if (editing) {
            router.put(`/pembinaan/${editing.id}`, form, {
                onSuccess: () => setShowModal(false),
            });
        } else {
            router.post('/pembinaan', form, {
                onSuccess: () => setShowModal(false),
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus pembinaan ini?')) {
            router.delete(`/pembinaan/${id}`);
        }
    };

    return (
        <AppLayout>
            <Head title="Pembinaan" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Pembinaan</h1>
                        <p className="text-muted-foreground">Kelola pembinaan dan sanksi santri</p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Tambah Pembinaan
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Santri</TableHead>
                                    <TableHead>Daerah</TableHead>
                                    <TableHead>Asrama</TableHead>
                                    <TableHead>Panggilan</TableHead>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Sanksi</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pembinaan.data.map((p: any) => (
                                    <TableRow key={p.id}>
                                        <TableCell className="font-medium">{p.santri?.nama}</TableCell>
                                        <TableCell>{p.santri?.asrama?.daerah?.nama_daerah}</TableCell>
                                        <TableCell>{p.santri?.asrama?.nomor}</TableCell>
                                        <TableCell>
                                            <Badge variant="warning">Panggilan {p.panggilan}</Badge>
                                        </TableCell>
                                        <TableCell>{p.tanggal_panggilan}</TableCell>
                                        <TableCell className="max-w-[200px] truncate">{p.sanksi}</TableCell>
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
                            currentPage={pembinaan.current_page}
                            lastPage={pembinaan.last_page}
                            total={pembinaan.total}
                            from={pembinaan.from}
                            to={pembinaan.to}
                            onPageChange={(page) => router.get('/pembinaan', { page })}
                        />
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={showModal}
                onClose={() => setShowModal(false)}
                title={editing ? 'Edit Pembinaan' : 'Tambah Pembinaan'}
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Santri</label>
                        <Select
                            value={form.santri_id}
                            onChange={(e) => setForm({ ...form, santri_id: e.target.value })}
                            placeholder="Pilih Santri"
                            options={santri.map((s: any) => ({ value: s.id, label: s.nama }))}
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Panggilan</label>
                            <Select
                                value={form.panggilan}
                                onChange={(e) => setForm({ ...form, panggilan: e.target.value })}
                                options={[
                                    { value: 'I', label: 'Panggilan I' },
                                    { value: 'II', label: 'Panggilan II' },
                                    { value: 'III', label: 'Panggilan III' },
                                ]}
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Tanggal Panggilan</label>
                            <Input type="date" value={form.tanggal_panggilan} onChange={(e) => setForm({ ...form, tanggal_panggilan: e.target.value })} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Sanksi</label>
                        <textarea
                            value={form.sanksi}
                            onChange={(e) => setForm({ ...form, sanksi: e.target.value })}
                            className="flex h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs"
                            placeholder="Deskripsi sanksi"
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
