import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Modal } from '@/components/ui/modal';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Edit2, Trash2, Plus, Camera, X } from 'lucide-react';

export default function PetugasIndex() {
    const { petugas, santri, asrama } = usePage<any>().props;
    const [perPage, setPerPage] = useState(10);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [search, setSearch] = useState('');
    const [sortColumn, setSortColumn] = useState('');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>('none');
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);
    const [form, setForm] = useState({
        santri_id: '',
        asrama_id: '',
        jabatan: '',
        tugas: '',
        foto: null as File | null,
    });
    const [fotoPreview, setFotoPreview] = useState<string | null>(null);

    useEffect(() => {
        if (!showModal) {
            setFotoPreview(null);
        }
    }, [showModal]);

    const openCreate = () => {
        setEditing(null);
        setForm({
            santri_id: '',
            asrama_id: '',
            jabatan: '',
            tugas: '',
            foto: null,
        });
        setFotoPreview(null);
        setShowModal(true);
    };

    const openEdit = (p: any) => {
        setEditing(p);
        setForm({
            santri_id: String(p.santri_id || ''),
            asrama_id: String(p.asrama_id || ''),
            jabatan: p.jabatan || '',
            tugas: p.tugas || '',
            foto: null,
        });
        setFotoPreview(p.foto ? `/storage/${p.foto}` : null);
        setShowModal(true);
    };

    const handleSantriChange = (value: string) => {
        const selectedSantri = santri.find((s: any) => String(s.id) === value);
        const asramaId = selectedSantri?.asrama_id ? String(selectedSantri.asrama_id) : '';
        setForm({ ...form, santri_id: value, asrama_id: asramaId });
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
        const data: Record<string, any> = {
            santri_id: form.santri_id,
            asrama_id: form.asrama_id || null,
            jabatan: form.jabatan,
            tugas: form.tugas,
        };

        if (form.foto) {
            data.foto = form.foto;
        }

        const onFinish = () => setShowModal(false);

        if (editing) {
            router.put(`/petugas/${editing.id}`, data, {
                onSuccess: onFinish,
            });
        } else {
            router.post('/petugas', data, {
                onSuccess: onFinish,
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus petugas ini?')) {
            router.delete(`/petugas/${id}`);
        }
    };

    const bulkDelete = () => {
        if (confirm(`Yakin ingin menghapus ${selectedIds.length} petugas?`)) {
            router.post('/petugas/bulk-delete', { ids: selectedIds }, {
                onSuccess: () => setSelectedIds([]),
            });
        }
    };

    const handleSort = (column: string) => {
        const sortMap: Record<string, string> = { petugas: 'nama_petugas' };
        let nextDir: 'asc' | 'desc' | 'none' = 'asc';
        if (sortColumn === column) {
            nextDir = sortDirection === 'none' ? 'asc' : sortDirection === 'asc' ? 'desc' : 'none';
        }
        setSortColumn(nextDir === 'none' ? '' : column);
        setSortDirection(nextDir);
        const serverSort = sortMap[column] || column;
        router.get('/petugas', {
            sort_column: nextDir === 'none' ? undefined : serverSort,
            sort_direction: nextDir === 'none' ? undefined : nextDir,
            search: search || undefined,
            per_page: perPage,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSearchChange = (q: string) => {
        setSearch(q);
        router.get('/petugas', {
            search: q || undefined,
            page: 1,
            per_page: perPage,
            sort_column: sortColumn || undefined,
            sort_direction: sortDirection === 'none' ? undefined : sortDirection,
        }, { preserveState: true, preserveScroll: true });
    };

    const columns: Column<any>[] = [
        { key: 'no', label: '#', render: (_p: any, idx: number) => <span>{petugas.from + idx}</span>, className: 'text-muted-foreground text-xs w-10' },
        {
            key: 'petugas',
            label: 'Petugas',
            sortable: true,
            render: (p) => (
                <div className="flex items-center gap-3">
                    {p.foto ? (
                        <img
                            src={`/storage/${p.foto}`}
                            alt={p.santri?.nama || 'Petugas'}
                            className="h-9 w-9 rounded-full object-cover border"
                        />
                    ) : (
                        <div className="h-9 w-9 rounded-full bg-muted flex items-center justify-center">
                            <Camera className="h-4 w-4 text-muted-foreground" />
                        </div>
                    )}
                    <span className="font-medium">{p.santri?.nama || '-'}</span>
                </div>
            ),
        },
        { key: 'asrama', label: 'Asrama', render: (p) => p.santri?.asrama?.daerah?.kode ? `${p.santri.asrama.daerah.kode}.${p.santri.asrama.nomor}` : p.santri?.asrama?.nomor || p.asrama?.nomor || '-' },
        { key: 'jabatan', label: 'Jabatan', sortable: true },
        { key: 'tugas', label: 'Tugas' },
        {
            key: 'aksi',
            label: 'Aksi',
            headClassName: 'text-right',
            className: 'text-right',
            render: (p) => (
                <div className="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" onClick={() => openEdit(p)}>
                        <Edit2 className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" onClick={() => destroy(p.id)}>
                        <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                </div>
            ),
        },
    ];

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

                <DataTable
                    columns={columns}
                    data={petugas.data}
                    meta={petugas}
                    keyExtractor={(p) => p.id}
                    onPageChange={(page) =>
                        router.get('/petugas', {
                            page,
                            search: search || undefined,
                            sort_column: sortColumn || undefined,
                            sort_direction: sortDirection === 'none' ? undefined : sortDirection,
                            per_page: perPage,
                        }, { preserveState: true, preserveScroll: true })
                    }
                    search={search}
                    onSearchChange={handleSearchChange}
                    searchPlaceholder="Cari nama santri..."
                    sortColumn={sortColumn}
                    sortDirection={sortDirection}
                    onSort={handleSort}
                    perPage={perPage}
                    onPerPageChange={(p) => {
                        setPerPage(p);
                        if (p !== perPage) {
                            router.get('/petugas', {
                                per_page: p,
                                page: 1,
                                search: search || undefined,
                                sort_column: sortColumn || undefined,
                                sort_direction: sortDirection === 'none' ? undefined : sortDirection,
                            }, { preserveState: true, preserveScroll: true });
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
                />
            </div>

            <Modal
                open={showModal}
                onClose={() => setShowModal(false)}
                title={editing ? 'Edit Petugas' : 'Tambah Petugas'}
            >
                <div className="space-y-4">
                    {/* Foto */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Foto Petugas</label>
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

                    {/* Santri (wajib) */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Nama Santri <span className="text-destructive">*</span>
                        </label>
                        <Select
                            value={form.santri_id}
                            onChange={(e) => handleSantriChange(e.target.value)}
                            placeholder="Pilih Santri"
                            options={santri.map((s: any) => ({ value: s.id, label: s.nama }))}
                        />
                        <p className="text-xs text-muted-foreground">Nama petugas akan mengikuti nama santri yang dipilih</p>
                    </div>

                    {/* Asrama (otomatis dari santri, tidak bisa diubah) */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Asrama</label>
                        {form.santri_id && form.asrama_id ? (
                            <div className="flex h-9 w-full items-center rounded-md border border-input bg-muted px-3 text-sm text-muted-foreground">
                                {(() => {
                                    const a = asrama.find((a: any) => String(a.id) === form.asrama_id);
                                    return a ? `${a.daerah?.kode}.${a.nomor}` : 'Otomatis dari santri';
                                })()}
                            </div>
                        ) : (
                            <div className="flex h-9 w-full items-center rounded-md border border-input bg-muted px-3 text-sm text-muted-foreground">
                                Asrama petugas
                            </div>
                        )}
                    </div>

                    {/* Jabatan */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Jabatan <span className="text-destructive">*</span>
                        </label>
                        <Input
                            value={form.jabatan}
                            onChange={(e) => setForm({ ...form, jabatan: e.target.value })}
                            placeholder="Jabatan"
                        />
                    </div>

                    {/* Tugas */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Tugas <span className="text-destructive">*</span>
                        </label>
                        <Input
                            value={form.tugas}
                            onChange={(e) => setForm({ ...form, tugas: e.target.value })}
                            placeholder="Tugas"
                        />
                    </div>

                    <div className="flex justify-end gap-3 pt-2">
                        <Button variant="outline" onClick={() => setShowModal(false)}>Batal</Button>
                        <Button onClick={submit}>{editing ? 'Simpan' : 'Tambah'}</Button>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
