import { useState, useMemo, useRef } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Modal } from '@/components/ui/modal';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Edit2, Trash2, Plus, Camera } from 'lucide-react';

export default function PetugasIndex() {
    const { petugas, daerah, asrama, search: searchParam, per_page, sort_column, sort_direction } = usePage<any>().props;
    const [perPage, setPerPage] = useState(parseInt(per_page) || 10);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [search, setSearch] = useState(searchParam || '');
    const [sortColumn, setSortColumn] = useState(sort_column || '');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>((sort_direction as any) || 'none');
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);
    const [fotoPreview, setFotoPreview] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [form, setForm] = useState({
        nama: '',
        foto: null as File | null,
        daerah_id: '',
        asrama_id: '',
        jabatan: '',
        tugas: '',
    });

    const filteredAsrama = useMemo(() => {
        return form.daerah_id
            ? asrama.filter((a: any) => String(a.daerah_id) === String(form.daerah_id))
            : [];
    }, [asrama, form.daerah_id]);

    const openCreate = () => {
        setEditing(null);
        setForm({
            nama: '',
            foto: null,
            daerah_id: '',
            asrama_id: '',
            jabatan: '',
            tugas: '',
        });
        setFotoPreview(null);
        setShowModal(true);
    };

    const openEdit = (p: any) => {
        setEditing(p);
        setForm({
            nama: p.nama || '',
            foto: null,
            daerah_id: String(p.daerah_id || ''),
            asrama_id: String(p.asrama_id || ''),
            jabatan: p.jabatan || '',
            tugas: p.tugas || '',
        });
        setFotoPreview(p.foto ? `/storage/${p.foto}` : null);
        setShowModal(true);
    };

    const handleFotoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setForm({ ...form, foto: file });
            setFotoPreview(URL.createObjectURL(file));
        }
    };

    const submit = () => {
        const data = new FormData();
        data.append('nama', form.nama);
        if (form.foto) data.append('foto', form.foto);
        if (form.daerah_id) data.append('daerah_id', form.daerah_id);
        if (form.asrama_id) data.append('asrama_id', form.asrama_id);
        data.append('jabatan', form.jabatan);
        data.append('tugas', form.tugas);

        const onFinish = () => {
            setShowModal(false);
            setFotoPreview(null);
        };

        if (editing) {
            data.append('_method', 'PUT');
            router.post(`/petugas/${editing.id}`, data, {
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
        const sortMap: Record<string, string> = { petugas: 'nama' };
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
                            alt={p.nama || 'Petugas'}
                            className="h-9 w-9 rounded-full object-cover border"
                        />
                    ) : (
                        <div className="h-9 w-9 rounded-full bg-muted flex items-center justify-center">
                            <Camera className="h-4 w-4 text-muted-foreground" />
                        </div>
                    )}
                    <span className="font-medium">{p.nama || '-'}</span>
                </div>
            ),
        },
        { key: 'asrama', label: 'Asrama', render: (p) => p.asrama?.daerah?.kode ? `${p.asrama.daerah.kode.charAt(0)}.${p.asrama.nomor}` : p.asrama?.nomor || '-' },
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
                    searchPlaceholder="Cari nama petugas..."
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
                    {/* Nama */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Nama Petugas <span className="text-destructive">*</span>
                        </label>
                        <Input
                            value={form.nama}
                            onChange={(e) => setForm({ ...form, nama: e.target.value })}
                            placeholder="Nama petugas"
                        />
                    </div>

                    {/* Foto */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Foto</label>
                        <div className="flex items-center gap-4">
                            {fotoPreview ? (
                                <img
                                    src={fotoPreview}
                                    alt="Preview"
                                    className="h-16 w-16 rounded-full object-cover border"
                                />
                            ) : (
                                <div className="h-16 w-16 rounded-full bg-muted flex items-center justify-center">
                                    <Camera className="h-6 w-6 text-muted-foreground" />
                                </div>
                            )}
                            <div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => fileInputRef.current?.click()}
                                >
                                    {fotoPreview ? 'Ganti Foto' : 'Pilih Foto'}
                                </Button>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg"
                                    className="hidden"
                                    onChange={handleFotoChange}
                                />
                                <p className="text-xs text-muted-foreground mt-1">Format: JPEG/PNG, Maks: 2MB</p>
                            </div>
                        </div>
                    </div>

                    {/* Daerah */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Daerah</label>
                        <Select
                            value={form.daerah_id}
                            onChange={(e) =>
                                setForm({ ...form, daerah_id: e.target.value, asrama_id: '' })
                            }
                            placeholder="Pilih Daerah"
                            options={daerah.map((d: any) => ({
                                value: d.id,
                                label: d.nama_daerah,
                            }))}
                        />
                    </div>

                    {/* Asrama (filtered by daerah) */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Asrama</label>
                        <Select
                            value={form.asrama_id}
                            onChange={(e) => setForm({ ...form, asrama_id: e.target.value })}
                            placeholder={
                                form.daerah_id ? 'Pilih Asrama' : 'Pilih daerah terlebih dahulu'
                            }
                            disabled={!form.daerah_id}
                            options={filteredAsrama.map((a: any) => ({
                                value: a.id,
                                label: `${a.daerah?.kode?.charAt(0)}.${a.nomor}`,
                            }))}
                        />
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
