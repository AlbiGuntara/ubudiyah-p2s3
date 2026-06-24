import { useState } from 'react';
import { Head, usePage, router, Link } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { Dialog } from '@/components/ui/dialog';
import { Edit2, Trash2, Plus, Search, Eye, Upload } from 'lucide-react';

export default function SantriIndex() {
    const { santri, daerah, asrama, filters } = usePage<any>().props;
    const [showModal, setShowModal] = useState(false);
    const [showImport, setShowImport] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [filterDaerah, setFilterDaerah] = useState(filters?.daerah_id || '');
    const [filterAsrama, setFilterAsrama] = useState(filters?.asrama_id || '');
    const [filterIksass, setFilterIksass] = useState(filters?.iksass || '');
    const [search, setSearch] = useState(filters?.search || '');

    const [form, setForm] = useState({ nama: '', nis: '', iksass: '', asrama_id: '' });

    const openCreate = () => {
        setEditing(null);
        setForm({ nama: '', nis: '', iksass: '', asrama_id: '' });
        setShowModal(true);
    };

    const openEdit = (s: any) => {
        setEditing(s);
        setForm({ nama: s.nama, nis: s.nis || '', iksass: s.iksass || '', asrama_id: s.asrama_id });
        setShowModal(true);
    };

    const submit = () => {
        if (editing) {
            router.put(`/santri/${editing.id}`, form, {
                onSuccess: () => setShowModal(false),
            });
        } else {
            router.post('/santri', form, {
                onSuccess: () => setShowModal(false),
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus santri ini?')) {
            router.delete(`/santri/${id}`);
        }
    };

    const applyFilter = () => {
        router.get('/santri', {
            daerah_id: filterDaerah || undefined,
            asrama_id: filterAsrama || undefined,
            iksass: filterIksass || undefined,
            search: search || undefined,
        });
    };

    const handleImport = (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData(e.target as HTMLFormElement);
        router.post('/santri/import', formData, {
            onSuccess: () => setShowImport(false),
        });
    };

    return (
        <AppLayout>
            <Head title="Santri" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Santri</h1>
                        <p className="text-muted-foreground">Kelola data santri pondok</p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={() => setShowImport(true)}>
                            <Upload className="h-4 w-4" />
                            Import
                        </Button>
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" />
                            Tambah Santri
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <div className="flex flex-wrap items-center gap-4 mb-4">
                            <div className="relative flex-1 min-w-[200px]">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Cari nama/NIS..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                            <Select
                                value={filterDaerah}
                                onChange={(e) => setFilterDaerah(e.target.value)}
                                placeholder="Semua Daerah"
                                options={daerah.map((d: any) => ({ value: d.id, label: d.nama_daerah }))}
                                className="min-w-[150px]"
                            />
                            <Select
                                value={filterAsrama}
                                onChange={(e) => setFilterAsrama(e.target.value)}
                                placeholder="Semua Asrama"
                                options={asrama.map((a: any) => ({ value: a.id, label: a.nomor }))}
                                className="min-w-[150px]"
                            />
                            <Input
                                value={filterIksass}
                                onChange={(e) => setFilterIksass(e.target.value)}
                                placeholder="IKSASS"
                                className="max-w-[100px]"
                            />
                            <Button variant="outline" onClick={applyFilter}>Filter</Button>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>NIS</TableHead>
                                    <TableHead>IKSASS</TableHead>
                                    <TableHead>Daerah</TableHead>
                                    <TableHead>Asrama</TableHead>
                                    <TableHead className="text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {santri.data.map((s: any) => (
                                    <TableRow key={s.id}>
                                        <TableCell className="font-medium">{s.nama}</TableCell>
                                        <TableCell>{s.nis}</TableCell>
                                        <TableCell>{s.iksass}</TableCell>
                                        <TableCell>{s.asrama?.daerah?.nama_daerah}</TableCell>
                                        <TableCell>{s.asrama?.nomor}</TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Link href={`/santri/${s.id}`}>
                                                    <Button variant="ghost" size="sm">
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                                <Button variant="ghost" size="sm" onClick={() => openEdit(s)}>
                                                    <Edit2 className="h-4 w-4" />
                                                </Button>
                                                <Button variant="ghost" size="sm" onClick={() => destroy(s.id)}>
                                                    <Trash2 className="h-4 w-4 text-red-500" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        <Pagination
                            currentPage={santri.current_page}
                            lastPage={santri.last_page}
                            total={santri.total}
                            from={santri.from}
                            to={santri.to}
                            onPageChange={(page) => router.get('/santri', { page })}
                        />
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={showModal}
                onClose={() => setShowModal(false)}
                title={editing ? 'Edit Santri' : 'Tambah Santri'}
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Nama</label>
                        <Input
                            value={form.nama}
                            onChange={(e) => setForm({ ...form, nama: e.target.value })}
                            placeholder="Nama santri"
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">NIS</label>
                        <Input
                            value={form.nis}
                            onChange={(e) => setForm({ ...form, nis: e.target.value })}
                            placeholder="Nomor induk santri"
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">IKSASS</label>
                        <Input
                            value={form.iksass}
                            onChange={(e) => setForm({ ...form, iksass: e.target.value })}
                            placeholder="Tahun masuk"
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Asrama</label>
                        <Select
                            value={form.asrama_id}
                            onChange={(e) => setForm({ ...form, asrama_id: e.target.value })}
                            placeholder="Pilih Asrama"
                            options={asrama.map((a: any) => ({ value: a.id, label: a.nomor }))}
                        />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button variant="outline" onClick={() => setShowModal(false)}>Batal</Button>
                        <Button onClick={submit}>{editing ? 'Simpan' : 'Tambah'}</Button>
                    </div>
                </div>
            </Dialog>

            <Dialog open={showImport} onClose={() => setShowImport(false)} title="Import Santri" description="Upload file Excel">
                <form onSubmit={handleImport} className="space-y-4">
                    <input type="file" name="file" accept=".xlsx,.xls" required className="block w-full text-sm" />
                    <div className="flex justify-end gap-3 pt-2">
                        <Button variant="outline" type="button" onClick={() => setShowImport(false)}>Batal</Button>
                        <Button type="submit">Import</Button>
                    </div>
                </form>
            </Dialog>
        </AppLayout>
    );
}
