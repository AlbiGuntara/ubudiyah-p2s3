import { useState, useEffect } from 'react';
import { Head, usePage, router, Link } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Modal } from '@/components/ui/modal';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Edit2, Trash2, Plus, Eye, Upload, Camera, X } from 'lucide-react';

export default function SantriIndex() {
    const { santri, daerah, asrama, filters } = usePage<any>().props;
    const [perPage, setPerPage] = useState(15);
    const [showModal, setShowModal] = useState(false);
    const [showImport, setShowImport] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [filterDaerah, setFilterDaerah] = useState(filters?.daerah_id || '');
    const [filterAsrama, setFilterAsrama] = useState(filters?.asrama_id || '');
    const [filterIksass, setFilterIksass] = useState(filters?.iksass || '');
    const [search, setSearch] = useState(filters?.search || '');
    const [sortColumn, setSortColumn] = useState('');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>('none');
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);

    const [form, setForm] = useState({ nama: '', nis: '', iksass: '', foto: null as File | null, daerah_id: '', asrama_id: '' });
    const [formDaerah, setFormDaerah] = useState('');
    const [fotoPreview, setFotoPreview] = useState<string | null>(null);

    useEffect(() => {
        if (!showModal) {
            setFotoPreview(null);
        }
    }, [showModal]);

    const filteredAsrama = asrama.filter((a: any) => {
        if (formDaerah) return String(a.daerah_id) === String(formDaerah);
        return true;
    });

    const filteredAsramaFilter = asrama.filter((a: any) => {
        if (filterDaerah) return String(a.daerah_id) === String(filterDaerah);
        return true;
    });

    const openCreate = () => {
        setEditing(null);
        setForm({ nama: '', nis: '', iksass: '', foto: null, daerah_id: '', asrama_id: '' });
        setFotoPreview(null);
        setFormDaerah('');
        setShowModal(true);
    };

    const openEdit = (s: any) => {
        setEditing(s);
        const daerahId = s.asrama?.daerah_id ? String(s.asrama.daerah_id) : '';
        setFormDaerah(daerahId);
        setForm({ nama: s.nama, nis: s.nis || '', iksass: s.iksass || '', foto: null, daerah_id: daerahId, asrama_id: s.asrama_id });
        setFotoPreview(s.foto ? `/storage/${s.foto}` : null);
        setShowModal(true);
    };

    const handleFotoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] || null;
        if (file) {
            setForm({ ...form, foto: file });
            const reader = new FileReader();
            reader.onload = (ev) => {
                setFotoPreview(ev.target?.result as string);
            };
            reader.readAsDataURL(file);
        }
    };

    const removeFoto = () => {
        setForm({ ...form, foto: null });
        setFotoPreview(null);
    };

    const submit = () => {
        const formData = new FormData();
        formData.append('nama', form.nama);
        formData.append('nis', form.nis || '');
        formData.append('iksass', form.iksass || '');
        formData.append('asrama_id', form.asrama_id);
        if (form.foto) {
            formData.append('foto', form.foto);
        }

        if (editing) {
            formData.append('_method', 'PUT');
            router.post(`/santri/${editing.id}`, formData, {
                onSuccess: () => setShowModal(false),
            });
        } else {
            router.post('/santri', formData, {
                onSuccess: () => setShowModal(false),
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus santri ini?')) {
            router.delete(`/santri/${id}`);
        }
    };

    const bulkDelete = () => {
        if (confirm(`Yakin ingin menghapus ${selectedIds.length} santri?`)) {
            router.post('/santri/bulk-delete', { ids: selectedIds }, {
                onSuccess: () => setSelectedIds([]),
            });
        }
    };

    const handleSort = (column: string) => {
        let nextDir: 'asc' | 'desc' | 'none' = 'asc';
        if (sortColumn === column) {
            nextDir = sortDirection === 'none' ? 'asc' : sortDirection === 'asc' ? 'desc' : 'none';
        }
        setSortColumn(nextDir === 'none' ? '' : column);
        setSortDirection(nextDir);
        router.get('/santri', {
            sort_column: nextDir === 'none' ? undefined : column,
            sort_direction: nextDir === 'none' ? undefined : nextDir,
            daerah_id: filterDaerah || undefined,
            asrama_id: filterAsrama || undefined,
            iksass: filterIksass || undefined,
            search: search || undefined,
            per_page: perPage,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleImport = (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData(e.target as HTMLFormElement);
        router.post('/santri/import', formData, {
            onSuccess: () => setShowImport(false),
        });
    };

    const columns: Column<any>[] = [
        { key: 'no', label: '#', render: (_s: any, idx: number) => <span>{santri.from + idx}</span>, className: 'text-muted-foreground text-xs w-10' },
        {
            key: 'foto',
            label: 'Foto',
            render: (s) =>
                s.foto ? (
                    <img
                        src={`/storage/${s.foto}`}
                        alt={s.nama}
                        className="h-8 w-8 rounded-full object-cover"
                    />
                ) : (
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted">
                        <Camera className="h-4 w-4 text-muted-foreground" />
                    </div>
                ),
        },
        { key: 'nama', label: 'Nama', sortable: true, render: (s) => <span className="font-medium">{s.nama}</span> },
        { key: 'nis', label: 'NIS', sortable: true },
        { key: 'iksass', label: 'IKSASS', sortable: true },
        { key: 'daerah', label: 'Daerah', render: (s) => s.asrama?.daerah?.nama_daerah || '-', hideable: true },
        { key: 'asrama', label: 'Nomor', render: (s) => s.asrama?.nomor || '-', hideable: true },
        {
            key: 'aksi',
            label: 'Aksi',
            headClassName: 'text-right',
            className: 'text-right',
            render: (s) => (
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
            ),
        },
    ];

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

                <DataTable
                    columns={columns}
                    data={santri.data}
                    meta={santri}
                    keyExtractor={(s) => s.id}
                    onPageChange={(page) => router.get('/santri', { page, search: search || undefined, daerah_id: filterDaerah || undefined, asrama_id: filterAsrama || undefined, iksass: filterIksass || undefined, sort_column: sortColumn || undefined, sort_direction: sortDirection === 'none' ? undefined : sortDirection, per_page: perPage }, { preserveState: true, preserveScroll: true })}
                    search={search}
                    onSearchChange={(q) => {
                        setSearch(q);
                        router.get('/santri', { search: q || undefined, page: 1, per_page: perPage, sort_column: sortColumn || undefined, sort_direction: sortDirection === 'none' ? undefined : sortDirection }, { preserveState: true, preserveScroll: true });
                    }}
                    searchPlaceholder="Cari nama/NIS..."
                    sortColumn={sortColumn}
                    sortDirection={sortDirection}
                    onSort={handleSort}
                    perPage={perPage}
                    onPerPageChange={(p) => {
                        setPerPage(p);
                        if (p !== perPage) {
                            router.get('/santri', { per_page: p, page: 1, search: search || undefined, daerah_id: filterDaerah || undefined, asrama_id: filterAsrama || undefined, iksass: filterIksass || undefined, sort_column: sortColumn || undefined, sort_direction: sortDirection === 'none' ? undefined : sortDirection }, { preserveState: true, preserveScroll: true });
                        }
                    }}
                    onSelectionChange={setSelectedIds}
                    bulkActions={
                        selectedIds.length > 0 && (
                            <Button variant="destructive" size="sm" onClick={bulkDelete}>
                                <Trash2 className="h-4 w-4" />
                                Hapus ({selectedIds.length})
                            </Button>
                        )
                    }
                    filters={
                        <>
                            <Select
                                value={filterDaerah}
                                onChange={(e) => {
                                    setFilterDaerah(e.target.value);
                                    setFilterAsrama('');
                                }}
                                placeholder="Semua Daerah"
                                options={daerah.map((d: any) => ({ value: d.id, label: d.nama_daerah }))}
                                className="min-w-[150px]"
                            />
                            <Select
                                value={filterAsrama}
                                onChange={(e) => setFilterAsrama(e.target.value)}
                                placeholder="Semua Asrama"
                                options={filteredAsramaFilter.map((a: any) => ({ value: a.id, label: `${a.nomor}` }))}
                                className="min-w-[150px]"
                            />
                            <Input
                                value={filterIksass}
                                onChange={(e) => setFilterIksass(e.target.value)}
                                placeholder="Cari asal..."
                                className="max-w-[130px]"
                            />
                            <Button variant="outline" size="sm" onClick={() => router.get('/santri', { daerah_id: filterDaerah || undefined, asrama_id: filterAsrama || undefined, iksass: filterIksass || undefined, search: search || undefined, per_page: perPage, sort_column: sortColumn || undefined, sort_direction: sortDirection === 'none' ? undefined : sortDirection }, { preserveState: true, preserveScroll: true })}>Filter</Button>
                        </>
                    }
                />
                </div>

            <Modal
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
                        <label className="text-sm font-medium">Asal (IKSASS)</label>
                        <Input
                            value={form.iksass}
                            onChange={(e) => setForm({ ...form, iksass: e.target.value })}
                            placeholder="Contoh: Situbondo, Bondowoso"
                        />
                    </div>

                    {/* Foto */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Foto Santri</label>
                        <div className="flex items-center gap-4">
                            {fotoPreview ? (
                                <div className="relative">
                                    <img
                                        src={fotoPreview}
                                        alt="Preview"
                                        className="h-20 w-20 rounded-lg object-cover border"
                                    />
                                    <button
                                        type="button"
                                        onClick={removeFoto}
                                        className="absolute -top-2 -right-2 rounded-full bg-destructive text-destructive-foreground p-0.5"
                                    >
                                        <X className="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            ) : (
                                <div className="h-20 w-20 rounded-lg border-2 border-dashed border-muted-foreground/30 flex items-center justify-center bg-muted/30">
                                    <Camera className="h-6 w-6 text-muted-foreground/50" />
                                </div>
                            )}
                            <label className="cursor-pointer">
                                <span className="inline-flex items-center gap-2 text-sm text-primary hover:underline">
                                    <Camera className="h-4 w-4" />
                                    {fotoPreview ? 'Ganti Foto' : 'Upload Foto'}
                                </span>
                                <input
                                    type="file"
                                    accept="image/jpg,image/jpeg,image/png"
                                    onChange={handleFotoChange}
                                    className="hidden"
                                />
                            </label>
                        </div>
                        <p className="text-xs text-muted-foreground">Format: JPG/PNG, maks. 2MB</p>
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium">Daerah</label>
                        <Select
                            value={formDaerah}
                            onChange={(e) => {
                                const val = e.target.value;
                                setFormDaerah(val);
                                setForm({ ...form, daerah_id: val, asrama_id: '' });
                            }}
                            placeholder="Pilih Daerah"
                            options={daerah.map((d: any) => ({ value: d.id, label: d.nama_daerah }))}
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Asrama</label>
                        <Select
                            value={form.asrama_id}
                            onChange={(e) => setForm({ ...form, asrama_id: e.target.value })}
                            placeholder="Pilih Asrama"
                            options={filteredAsrama.map((a: any) => ({ value: a.id, label: `${a.nomor}` }))}
                        />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button variant="outline" onClick={() => setShowModal(false)}>Batal</Button>
                        <Button onClick={submit}>{editing ? 'Simpan' : 'Tambah'}</Button>
                    </div>
                </div>
            </Modal>

            <Modal open={showImport} onClose={() => setShowImport(false)} title="Import Santri" description="Upload file Excel">
                <form onSubmit={handleImport} className="space-y-4">
                    <input type="file" name="file" accept=".xlsx,.xls" required className="block w-full text-sm" />
                    <div className="flex justify-end gap-3 pt-2">
                        <Button variant="outline" type="button" onClick={() => setShowImport(false)}>Batal</Button>
                        <Button type="submit">Import</Button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
