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
import { Edit2, Trash2, Plus, Users } from 'lucide-react';

export default function PelanggaranIndex() {
    const { pelanggaran, santri, asrama, daftarPelanggaran, filters } = usePage<any>().props;
    const [showModal, setShowModal] = useState(false);
    const [showMassal, setShowMassal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [filterSumber, setFilterSumber] = useState(filters?.sumber || '');
    const [filterAsrama, setFilterAsrama] = useState(filters?.asrama_id || '');
    const [filterBulan, setFilterBulan] = useState(filters?.bulan || '');
    const [filterTahun, setFilterTahun] = useState(filters?.tahun || '');

    const [form, setForm] = useState({
        santri_id: '', asrama_id: '', daftar_pelanggaran_id: '',
        petugas_id: '', jumlah: '1', sumber_pencatatan: 'petugas',
        tanggal: new Date().toISOString().split('T')[0], keterangan: '',
    });

    const [massalForm, setMassalForm] = useState({
        asrama_id: '', daftar_pelanggaran_id: '',
        petugas_id: '', jumlah: '2', tanggal: new Date().toISOString().split('T')[0], keterangan: '',
    });

    const openCreate = () => {
        setEditing(null);
        setForm({
            santri_id: '', asrama_id: '', daftar_pelanggaran_id: '',
            petugas_id: '', jumlah: '1', sumber_pencatatan: 'petugas',
            tanggal: new Date().toISOString().split('T')[0], keterangan: '',
        });
        setShowModal(true);
    };

    const openEdit = (p: any) => {
        setEditing(p);
        setForm({
            santri_id: p.santri_id || '',
            asrama_id: p.asrama_id,
            daftar_pelanggaran_id: p.daftar_pelanggaran_id,
            petugas_id: p.petugas_id,
            jumlah: String(p.jumlah),
            sumber_pencatatan: p.sumber_pencatatan,
            tanggal: p.tanggal,
            keterangan: p.keterangan || '',
        });
        setShowModal(true);
    };

    const submit = () => {
        const data = {
            ...form,
            santri_id: form.santri_id || null,
            jumlah: parseInt(form.jumlah),
        };
        if (editing) {
            router.put(`/pelanggaran/${editing.id}`, data, {
                onSuccess: () => setShowModal(false),
            });
        } else {
            router.post('/pelanggaran', data, {
                onSuccess: () => setShowModal(false),
            });
        }
    };

    const submitMassal = () => {
        router.post('/pelanggaran/massal', {
            ...massalForm,
            jumlah: parseInt(massalForm.jumlah),
        }, {
            onSuccess: () => setShowMassal(false),
        });
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus pelanggaran ini?')) {
            router.delete(`/pelanggaran/${id}`);
        }
    };

    const applyFilter = () => {
        router.get('/pelanggaran', {
            sumber: filterSumber || undefined,
            asrama_id: filterAsrama || undefined,
            bulan: filterBulan || undefined,
            tahun: filterTahun || undefined,
        });
    };

    return (
        <AppLayout>
            <Head title="Pelanggaran" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Pelanggaran</h1>
                        <p className="text-muted-foreground">Catat dan kelola pelanggaran santri</p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={() => setShowMassal(true)}>
                            <Users className="h-4 w-4" />
                            Massal
                        </Button>
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" />
                            Catat Pelanggaran
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <div className="flex flex-wrap items-center gap-4 mb-4">
                            <Select
                                value={filterSumber}
                                onChange={(e) => setFilterSumber(e.target.value)}
                                placeholder="Semua Sumber"
                                options={[
                                    { value: 'petugas', label: 'Petugas' },
                                    { value: 'ketua_kamar', label: 'Ketua Kamar' },
                                ]}
                                className="min-w-[150px]"
                            />
                            <Select
                                value={filterAsrama}
                                onChange={(e) => setFilterAsrama(e.target.value)}
                                placeholder="Semua Asrama"
                                options={asrama.map((a: any) => ({ value: a.id, label: `Asrama ${a.nomor}` }))}
                                className="min-w-[150px]"
                            />
                            <Select
                                value={filterBulan}
                                onChange={(e) => setFilterBulan(e.target.value)}
                                placeholder="Semua Bulan"
                                options={Array.from({ length: 12 }, (_, i) => ({ value: i + 1, label: `Bulan ${i + 1}` }))}
                                className="min-w-[120px]"
                            />
                            <Input
                                type="number"
                                value={filterTahun}
                                onChange={(e) => setFilterTahun(e.target.value)}
                                placeholder="Tahun"
                                className="max-w-[100px]"
                            />
                            <Button variant="outline" onClick={applyFilter}>Filter</Button>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Santri</TableHead>
                                    <TableHead>Asrama</TableHead>
                                    <TableHead>Pelanggaran</TableHead>
                                    <TableHead>Jumlah</TableHead>
                                    <TableHead>Sumber</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pelanggaran.data.map((p: any) => (
                                    <TableRow key={p.id}>
                                        <TableCell>{p.tanggal}</TableCell>
                                        <TableCell className="font-medium">{p.santri?.nama || 'Massal'}</TableCell>
                                        <TableCell>{p.asrama?.nomor}</TableCell>
                                        <TableCell>{p.daftar_pelanggaran?.nama_pelanggaran}</TableCell>
                                        <TableCell>{p.jumlah}x</TableCell>
                                        <TableCell>
                                            <Badge variant={p.sumber_pencatatan === 'petugas' ? 'success' : 'warning'}>
                                                {p.sumber_pencatatan}
                                            </Badge>
                                        </TableCell>
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
                            currentPage={pelanggaran.current_page}
                            lastPage={pelanggaran.last_page}
                            total={pelanggaran.total}
                            from={pelanggaran.from}
                            to={pelanggaran.to}
                            onPageChange={(page) => router.get('/pelanggaran', { page })}
                        />
                    </CardContent>
                </Card>
            </div>

            <Dialog open={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit Pelanggaran' : 'Catat Pelanggaran'}>
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Santri (kosongkan untuk massal)</label>
                        <Select
                            value={form.santri_id}
                            onChange={(e) => setForm({ ...form, santri_id: e.target.value })}
                            placeholder="Pilih Santri"
                            options={santri.map((s: any) => ({ value: s.id, label: s.nama }))}
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Asrama</label>
                            <Select
                                value={form.asrama_id}
                                onChange={(e) => setForm({ ...form, asrama_id: e.target.value })}
                                placeholder="Pilih Asrama"
                                options={asrama.map((a: any) => ({ value: a.id, label: `Asrama ${a.nomor}` }))}
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Jenis Pelanggaran</label>
                            <Select
                                value={form.daftar_pelanggaran_id}
                                onChange={(e) => setForm({ ...form, daftar_pelanggaran_id: e.target.value })}
                                placeholder="Pilih"
                                options={daftarPelanggaran.map((d: any) => ({ value: d.id, label: d.nama_pelanggaran }))}
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Jumlah</label>
                            <Input type="number" min={1} value={form.jumlah} onChange={(e) => setForm({ ...form, jumlah: e.target.value })} />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Tanggal</label>
                            <Input type="date" value={form.tanggal} onChange={(e) => setForm({ ...form, tanggal: e.target.value })} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Sumber</label>
                        <Select
                            value={form.sumber_pencatatan}
                            onChange={(e) => setForm({ ...form, sumber_pencatatan: e.target.value })}
                            options={[
                                { value: 'petugas', label: 'Petugas' },
                                { value: 'ketua_kamar', label: 'Ketua Kamar' },
                            ]}
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Keterangan</label>
                        <Input value={form.keterangan} onChange={(e) => setForm({ ...form, keterangan: e.target.value })} />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button variant="outline" onClick={() => setShowModal(false)}>Batal</Button>
                        <Button onClick={submit}>{editing ? 'Simpan' : 'Catat'}</Button>
                    </div>
                </div>
            </Dialog>

            <Dialog open={showMassal} onClose={() => setShowMassal(false)} title="Input Pelanggaran Massal">
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Asrama</label>
                        <Select
                            value={massalForm.asrama_id}
                            onChange={(e) => setMassalForm({ ...massalForm, asrama_id: e.target.value })}
                            placeholder="Pilih Asrama"
                            options={asrama.map((a: any) => ({ value: a.id, label: `Asrama ${a.nomor}` }))}
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Jenis Pelanggaran</label>
                        <Select
                            value={massalForm.daftar_pelanggaran_id}
                            onChange={(e) => setMassalForm({ ...massalForm, daftar_pelanggaran_id: e.target.value })}
                            placeholder="Pilih"
                            options={daftarPelanggaran.map((d: any) => ({ value: d.id, label: d.nama_pelanggaran }))}
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Jumlah Santri</label>
                            <Input type="number" min={2} value={massalForm.jumlah} onChange={(e) => setMassalForm({ ...massalForm, jumlah: e.target.value })} />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Tanggal</label>
                            <Input type="date" value={massalForm.tanggal} onChange={(e) => setMassalForm({ ...massalForm, tanggal: e.target.value })} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Keterangan</label>
                        <Input value={massalForm.keterangan} onChange={(e) => setMassalForm({ ...massalForm, keterangan: e.target.value })} />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button variant="outline" onClick={() => setShowMassal(false)}>Batal</Button>
                        <Button onClick={submitMassal}>Simpan Massal</Button>
                    </div>
                </div>
            </Dialog>
        </AppLayout>
    );
}
