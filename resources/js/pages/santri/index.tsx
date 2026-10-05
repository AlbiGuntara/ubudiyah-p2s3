import { useState, useEffect } from 'react';
import { Head, usePage, router, Link } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Modal } from '@/components/ui/modal';
import { DataTable, type Column } from '@/components/shared/data-table';
import {
    Edit2,
    Trash2,
    Plus,
    Eye,
    Upload,
    Camera,
    X,
    FileText,
    FileDown,
    Printer,
    ZoomIn,
} from 'lucide-react';

export default function SantriIndex() {
    const {
        santri,
        daerah,
        asrama,
        filters,
        per_page,
        sort_column,
        sort_direction,
    } = usePage<any>().props;
    const [perPage, setPerPage] = useState(parseInt(per_page) || 15);
    const [showModal, setShowModal] = useState(false);
    const [showImport, setShowImport] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [filterDaerah, setFilterDaerah] = useState(filters?.daerah_id || '');
    const [showExportModal, setShowExportModal] = useState(false);
    const [exportDaerah, setExportDaerah] = useState('');
    const [filterAsrama, setFilterAsrama] = useState(filters?.asrama_id || '');
    const [filterIksass, setFilterIksass] = useState(filters?.iksass || '');
    const [filterStatus, setFilterStatus] = useState(filters?.status || '');
    const [search, setSearch] = useState(filters?.search || '');
    const [sortColumn, setSortColumn] = useState(sort_column || '');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>(
        (sort_direction as 'asc' | 'desc' | 'none') || 'none',
    );
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);
    const [importFile, setImportFile] = useState<File | null>(null);

    const [form, setForm] = useState({
        nama: '',
        nis: '',
        iksass: '',
        nama_panggilan: '',
        status: 'aktif',
        foto: null as File | null,
        daerah_id: '',
        asrama_id: '',
    });
    const [formDaerah, setFormDaerah] = useState('');
    const [fotoPreview, setFotoPreview] = useState<string | null>(null);
    const [showMergeConfirm, setShowMergeConfirm] = useState(false);
    const [mergeExisting, setMergeExisting] = useState<any>(null);
    const [showFotoModal, setShowFotoModal] = useState(false);
    const [fotoModalSrc, setFotoModalSrc] = useState<string>('');
    const [fotoModalNama, setFotoModalNama] = useState<string>('');

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
        setForm({
            nama: '',
            nis: '',
            iksass: '',
            nama_panggilan: '',
            status: 'aktif',
            foto: null,
            daerah_id: '',
            asrama_id: '',
        });
        setFotoPreview(null);
        setFormDaerah('');
        setShowModal(true);
    };

    const openEdit = (s: any) => {
        setEditing(s);
        const daerahId = s.asrama?.daerah_id ? String(s.asrama.daerah_id) : '';
        setFormDaerah(daerahId);
        setForm({
            nama: s.nama,
            nis: s.nis || '',
            iksass: s.iksass || '',
            nama_panggilan: s.nama_panggilan || '',
            status: s.status || 'aktif',
            foto: null,
            daerah_id: daerahId,
            asrama_id: s.asrama_id,
        });
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

    const submit = async () => {
        if (form.nis) {
            try {
                const excludeId = editing ? editing.id : null;
                const res = await fetch(
                    `/santri/cek-nis?nis=${encodeURIComponent(form.nis)}&exclude_id=${excludeId || ''}`,
                );
                const data = await res.json();
                if (data.found) {
                    setMergeExisting(data.santri);
                    setShowMergeConfirm(true);
                    return;
                }
            } catch {
                // proceed normally if check fails
            }
        }
        doSubmit();
    };

    const doSubmit = (mergeAction?: string, mergeTargetId?: number) => {
        const formData = new FormData();
        formData.append('nama', form.nama);
        formData.append('nis', form.nis || '');
        formData.append('iksass', form.iksass || '');
        formData.append('nama_panggilan', form.nama_panggilan || '');
        formData.append('status', form.status);
        formData.append('asrama_id', form.asrama_id);
        if (form.foto) {
            formData.append('foto', form.foto);
        }
        if (mergeAction && mergeTargetId) {
            formData.append('merge_action', mergeAction);
            formData.append('merge_target_id', String(mergeTargetId));
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
            router.post(
                '/santri/bulk-delete',
                { ids: selectedIds },
                {
                    onSuccess: () => setSelectedIds([]),
                },
            );
        }
    };

    const handleSort = (column: string) => {
        let nextDir: 'asc' | 'desc' | 'none' = 'asc';
        if (sortColumn === column) {
            nextDir =
                sortDirection === 'none'
                    ? 'asc'
                    : sortDirection === 'asc'
                      ? 'desc'
                      : 'none';
        }
        setSortColumn(nextDir === 'none' ? '' : column);
        setSortDirection(nextDir);
        router.get(
            '/santri',
            {
                sort_column: nextDir === 'none' ? undefined : column,
                sort_direction: nextDir === 'none' ? undefined : nextDir,
                daerah_id: filterDaerah || undefined,
                asrama_id: filterAsrama || undefined,
                iksass: filterIksass || undefined,
                status: filterStatus || undefined,
                search: search || undefined,
                per_page: perPage,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleImport = (e: React.FormEvent) => {
        e.preventDefault();
        if (!importFile) return;
        const formData = new FormData();
        formData.append('file', importFile);
        router.post('/santri/import', formData, {
            onSuccess: () => {
                setShowImport(false);
                setImportFile(null);
            },
        });
    };

    const openFotoModal = (src: string, nama: string) => {
        setFotoModalSrc(src);
        setFotoModalNama(nama);
        setShowFotoModal(true);
    };

    const columns: Column<any>[] = [
        {
            key: 'no',
            label: '#',
            render: (_s: any, idx: number) => <span>{santri.from + idx}</span>,
            className: 'text-muted-foreground text-xs w-10',
        },
        {
            key: 'foto',
            label: 'Foto',
            render: (s) =>
                s.foto ? (
                    <button
                        type="button"
                        onClick={() =>
                            openFotoModal(`/storage/${s.foto}`, s.nama)
                        }
                        className="group relative"
                    >
                        <img
                            src={`/storage/${s.foto}`}
                            alt={s.nama}
                            className="h-8 w-8 rounded-full object-cover transition-transform hover:scale-110 hover:shadow-md"
                        />
                        <div className="absolute inset-0 flex items-center justify-center rounded-full bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                            <ZoomIn className="h-3.5 w-3.5 text-white" />
                        </div>
                    </button>
                ) : (
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted">
                        <Camera className="h-4 w-4 text-muted-foreground" />
                    </div>
                ),
        },
        {
            key: 'nama',
            label: 'Nama',
            sortable: true,
            render: (s) => (
                <span className="font-medium">
                    {s.nama}
                    {s.nama_panggilan && (
                        <span className="ml-1.5 text-xs text-muted-foreground">
                            ({s.nama_panggilan})
                        </span>
                    )}
                </span>
            ),
        },
        { key: 'nis', label: 'NIS', sortable: true },
        { key: 'iksass', label: 'IKSASS', sortable: true },
        {
            key: 'daerah',
            label: 'Daerah',
            render: (s) => s.asrama?.daerah?.nama_daerah || '-',
            hideable: true,
        },
        {
            key: 'asrama',
            label: 'Nomor',
            render: (s) => s.asrama?.nomor || '-',
            hideable: true,
        },
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
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => openEdit(s)}
                    >
                        <Edit2 className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => destroy(s.id)}
                    >
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
                        <h1 className="text-2xl font-bold tracking-tight">
                            Santri
                        </h1>
                        <p className="text-muted-foreground">
                            Kelola data santri pondok
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowExportModal(true)}
                            title="Export PDF data Santri per daerah untuk divalidasi asrama"
                        >
                            <FileDown className="h-4 w-4" />
                            Export
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => {
                                setShowImport(true);
                                setImportFile(null);
                            }}
                        >
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
                    onPageChange={(page) =>
                        router.get(
                            '/santri',
                            {
                                page,
                                search: search || undefined,
                                daerah_id: filterDaerah || undefined,
                                asrama_id: filterAsrama || undefined,
                                iksass: filterIksass || undefined,
                                status: filterStatus || undefined,
                                sort_column: sortColumn || undefined,
                                sort_direction:
                                    sortDirection === 'none'
                                        ? undefined
                                        : sortDirection,
                                per_page: perPage,
                            },
                            { preserveState: true, preserveScroll: true },
                        )
                    }
                    search={search}
                    onSearchChange={(q) => {
                        setSearch(q);
                        router.get(
                            '/santri',
                            {
                                search: q || undefined,
                                page: 1,
                                per_page: perPage,
                                sort_column: sortColumn || undefined,
                                sort_direction:
                                    sortDirection === 'none'
                                        ? undefined
                                        : sortDirection,
                            },
                            { preserveState: true, preserveScroll: true },
                        );
                    }}
                    searchPlaceholder="Cari nama, panggilan/NIS..."
                    sortColumn={sortColumn}
                    sortDirection={sortDirection}
                    onSort={handleSort}
                    perPage={perPage}
                    onPerPageChange={(p) => {
                        setPerPage(p);
                        if (p !== perPage) {
                            router.get(
                                '/santri',
                                {
                                    per_page: p,
                                    page: 1,
                                    search: search || undefined,
                                    daerah_id: filterDaerah || undefined,
                                    asrama_id: filterAsrama || undefined,
                                    iksass: filterIksass || undefined,
                                    status: filterStatus || undefined,
                                    sort_column: sortColumn || undefined,
                                    sort_direction:
                                        sortDirection === 'none'
                                            ? undefined
                                            : sortDirection,
                                },
                                { preserveState: true, preserveScroll: true },
                            );
                        }
                    }}
                    onSelectionChange={setSelectedIds}
                    bulkActions={
                        selectedIds.length > 0 && (
                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={bulkDelete}
                            >
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
                                options={daerah.map((d: any) => ({
                                    value: d.id,
                                    label: d.nama_daerah,
                                }))}
                                className="min-w-[150px]"
                            />
                            <Select
                                value={filterAsrama}
                                onChange={(e) =>
                                    setFilterAsrama(e.target.value)
                                }
                                placeholder="Semua Asrama"
                                options={filteredAsramaFilter.map((a: any) => ({
                                    value: a.id,
                                    label: `No. ${a.nomor}`,
                                }))}
                                className="min-w-[150px]"
                            />
                            <Input
                                value={filterIksass}
                                onChange={(e) =>
                                    setFilterIksass(e.target.value)
                                }
                                placeholder="Cari asal..."
                                className="max-w-[130px]"
                            />
                            <Select
                                value={filterStatus}
                                onChange={(e) =>
                                    setFilterStatus(e.target.value)
                                }
                                placeholder="Semua Status"
                                options={[
                                    { value: 'aktif', label: 'Aktif' },
                                    {
                                        value: 'tidak aktif',
                                        label: 'Tidak Aktif',
                                    },
                                    { value: 'berhenti', label: 'Berhenti' },
                                ]}
                                className="min-w-[140px]"
                            />
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    router.get(
                                        '/santri',
                                        {
                                            daerah_id:
                                                filterDaerah || undefined,
                                            asrama_id:
                                                filterAsrama || undefined,
                                            iksass: filterIksass || undefined,
                                            status: filterStatus || undefined,
                                            search: search || undefined,
                                            per_page: perPage,
                                            sort_column:
                                                sortColumn || undefined,
                                            sort_direction:
                                                sortDirection === 'none'
                                                    ? undefined
                                                    : sortDirection,
                                        },
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                        },
                                    )
                                }
                            >
                                Filter
                            </Button>
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
                            onChange={(e) =>
                                setForm({ ...form, nama: e.target.value })
                            }
                            placeholder="Nama santri"
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Nama Panggilan
                        </label>
                        <Input
                            value={form.nama_panggilan}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    nama_panggilan: e.target.value,
                                })
                            }
                            placeholder="Nama panggilan santri"
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Status</label>
                        <Select
                            value={form.status}
                            onChange={(e) =>
                                setForm({ ...form, status: e.target.value })
                            }
                            options={[
                                { value: 'aktif', label: 'Aktif' },
                                { value: 'tidak aktif', label: 'Tidak Aktif' },
                                { value: 'berhenti', label: 'Berhenti' },
                            ]}
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">NIS</label>
                        <Input
                            value={form.nis}
                            onChange={(e) =>
                                setForm({ ...form, nis: e.target.value })
                            }
                            placeholder="Nomor induk santri"
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Asal (IKSASS)
                        </label>
                        <Input
                            value={form.iksass}
                            onChange={(e) =>
                                setForm({ ...form, iksass: e.target.value })
                            }
                            placeholder="Contoh: Situbondo, Bondowoso"
                        />
                    </div>

                    {/* Foto */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Foto Santri
                        </label>
                        <div className="flex items-center gap-4">
                            {fotoPreview ? (
                                <div className="relative">
                                    <img
                                        src={fotoPreview}
                                        alt="Preview"
                                        className="h-20 w-20 rounded-lg border object-cover"
                                    />
                                    <button
                                        type="button"
                                        onClick={removeFoto}
                                        className="absolute -top-2 -right-2 rounded-full bg-destructive p-0.5 text-destructive-foreground"
                                    >
                                        <X className="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            ) : (
                                <div className="flex h-20 w-20 items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/30 bg-muted/30">
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
                        <p className="text-xs text-muted-foreground">
                            Format: JPG/PNG, maks. 2MB
                        </p>
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium">Daerah</label>
                        <Select
                            value={formDaerah}
                            onChange={(e) => {
                                const val = e.target.value;
                                setFormDaerah(val);
                                setForm({
                                    ...form,
                                    daerah_id: val,
                                    asrama_id: '',
                                });
                            }}
                            placeholder="Pilih Daerah"
                            options={daerah.map((d: any) => ({
                                value: d.id,
                                label: d.nama_daerah,
                            }))}
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Asrama</label>
                        <Select
                            value={form.asrama_id}
                            onChange={(e) =>
                                setForm({ ...form, asrama_id: e.target.value })
                            }
                            placeholder="Pilih Asrama"
                            options={filteredAsrama.map((a: any) => ({
                                value: a.id,
                                label: `${a.nomor}`,
                            }))}
                        />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowModal(false)}
                        >
                            Batal
                        </Button>
                        <Button onClick={submit}>
                            {editing ? 'Simpan' : 'Tambah'}
                        </Button>
                    </div>
                </div>
            </Modal>

            <Modal
                open={showMergeConfirm}
                onClose={() => setShowMergeConfirm(false)}
                title="NIS Sudah Terdaftar"
            >
                <div className="space-y-4">
                    <p className="text-sm">
                        NIS <strong>{form.nis}</strong> sudah terdaftar atas
                        nama <strong>{mergeExisting?.nama}</strong>.
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Pilih tindakan penggabungan data:
                    </p>
                    <div className="flex flex-col gap-3">
                        <Button
                            className="w-full"
                            onClick={() => {
                                setShowMergeConfirm(false);
                                doSubmit(
                                    editing ? 'keep_other' : 'keep_old',
                                    mergeExisting.id,
                                );
                            }}
                        >
                            <span className="flex min-w-0 items-center gap-0">
                                <span>Gunakan data </span>
                                <span className="min-w-0 truncate">
                                    {mergeExisting?.nama}
                                </span>
                                <span className="shrink-0">
                                    {' '}
                                    & gabung riwayat
                                </span>
                            </span>
                        </Button>
                        <Button
                            variant="outline"
                            className="w-full"
                            onClick={() => {
                                setShowMergeConfirm(false);
                                doSubmit(
                                    editing ? undefined : 'keep_new',
                                    editing ? undefined : mergeExisting.id,
                                );
                            }}
                        >
                            <span className="flex min-w-0 items-center gap-0">
                                <span>Gunakan data </span>
                                <span className="min-w-0 truncate">
                                    {editing
                                        ? editing?.nama || 'Santri Saat Ini'
                                        : form.nama || 'Data Baru'}
                                </span>
                                <span className="shrink-0">
                                    {' '}
                                    & gabung riwayat
                                </span>
                            </span>
                        </Button>
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button
                            variant="ghost"
                            onClick={() => setShowMergeConfirm(false)}
                        >
                            Batal
                        </Button>
                    </div>
                </div>
            </Modal>

            {/* Foto View Modal */}
            <Modal
                open={showFotoModal}
                onClose={() => setShowFotoModal(false)}
                title={fotoModalNama}
                description="Foto Santri"
                maxWidth="2xl"
            >
                <div className="flex items-center justify-center">
                    <img
                        src={fotoModalSrc}
                        alt={fotoModalNama}
                        className="max-h-[70vh] w-auto rounded-lg object-contain shadow-lg"
                    />
                </div>
            </Modal>

            <Modal
                open={showImport}
                onClose={() => {
                    setShowImport(false);
                    setImportFile(null);
                }}
                title="Import Santri"
                description="Upload file Excel"
            >
                <form onSubmit={handleImport} className="space-y-4">
                    {importFile ? (
                        <label className="flex cursor-pointer items-center gap-4 rounded-lg border-2 border-solid border-primary/40 bg-primary/5 px-5 py-4 transition hover:bg-primary/10">
                            <FileText className="h-8 w-8 shrink-0 text-primary" />
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-medium">
                                    {importFile.name}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {(importFile.size / 1024).toFixed(1)} KB —
                                    Klik untuk ganti file
                                </p>
                            </div>
                            <input
                                type="file"
                                accept=".xlsx,.xls"
                                className="hidden"
                                onChange={(e) =>
                                    setImportFile(e.target.files?.[0] ?? null)
                                }
                            />
                        </label>
                    ) : (
                        <label className="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/30 bg-muted/30 px-6 py-10 transition hover:border-primary/50 hover:bg-muted/50">
                            <Upload className="mb-2 h-8 w-8 text-muted-foreground/50" />
                            <span className="text-sm font-medium text-muted-foreground">
                                Pilih file Excel
                            </span>
                            <span className="mt-1 text-xs text-muted-foreground/60">
                                Format: .xlsx atau .xls
                            </span>
                            <input
                                type="file"
                                accept=".xlsx,.xls"
                                required
                                className="hidden"
                                onChange={(e) =>
                                    setImportFile(e.target.files?.[0] ?? null)
                                }
                            />
                        </label>
                    )}
                    <div className="flex justify-end gap-3 pt-2">
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => {
                                setShowImport(false);
                                setImportFile(null);
                            }}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={!importFile}>
                            Import
                        </Button>
                    </div>
                </form>
            </Modal>

            <Modal
                open={showExportModal}
                onClose={() => {
                    setShowExportModal(false);
                    setExportDaerah('');
                }}
                title="Export Validasi Data Santri"
            >
                <div className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Data tiap asrama dipisahkan per halaman dengan kop surat
                        agar bisa langsung diserahkan ke tiap asrama.
                    </p>

                    <div className="space-y-3">
                        <label className="text-sm font-medium">
                            Export Per Daerah
                        </label>
                        <Select
                            value={exportDaerah}
                            onChange={(e) => setExportDaerah(e.target.value)}
                            placeholder="Pilih Daerah"
                            options={daerah.map((d: any) => ({
                                value: d.id,
                                label: d.nama_daerah,
                            }))}
                        />
                        <Button
                            className="w-full"
                            disabled={!exportDaerah}
                            onClick={() => {
                                if (!exportDaerah) return;
                                setShowExportModal(false);
                                const url = `/export/santri-validasi/pdf?daerah_id=${exportDaerah}`;
                                window.open(url, '_blank');
                                setExportDaerah('');
                            }}
                        >
                            <Printer className="h-4 w-4" />
                            Export
                        </Button>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
