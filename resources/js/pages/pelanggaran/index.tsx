import { useState, useMemo } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Modal } from '@/components/ui/modal';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Badge } from '@/components/ui/badge';
import { Edit2, Trash2, Plus, X } from 'lucide-react';

export default function PelanggaranIndex() {
    const { pelanggaran, santri, asrama, daerah, daftarPelanggaran, filters } =
        usePage<any>().props;
    const [perPage, setPerPage] = useState(15);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [filterSumber, setFilterSumber] = useState(filters?.sumber || '');
    const [filterDaerah, setFilterDaerah] = useState(filters?.daerah_id || '');
    const [filterAsrama, setFilterAsrama] = useState(filters?.asrama_id || '');
    const [filterTanggalMulai, setFilterTanggalMulai] = useState('');
    const [filterTanggalSelesai, setFilterTanggalSelesai] = useState('');
    const [sortColumn, setSortColumn] = useState('');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>(
        'none',
    );
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);

    const [form, setForm] = useState({
        asrama_id: '',
        daftar_pelanggaran_id: '',
        petugas_id: '',
        tanpa_nama: '0',
        sumber_pencatatan: 'petugas',
        tanggal: new Date().toISOString().split('T')[0],
        keterangan: '',
    });
    const [daerahId, setDaerahId] = useState('');
    const [selectedSantri, setSelectedSantri] = useState<any[]>([]);
    const [pendingsantri_id, setPendingSantriId] = useState('');

    const daerahList = useMemo(() => {
        const map: Record<string, any> = {};
        asrama.forEach((a: any) => {
            if (a.daerah) map[a.daerah.id] = a.daerah;
        });
        return Object.values(map);
    }, [asrama]);

    const filteredAsrama = useMemo(() => {
        if (!daerahId) return asrama;
        return asrama.filter(
            (a: any) => String(a.daerah_id) === String(daerahId),
        );
    }, [asrama, daerahId]);

    const filteredSantri = useMemo(() => {
        if (!form.asrama_id) return [];
        return santri.filter(
            (s: any) => String(s.asrama_id) === String(form.asrama_id),
        );
    }, [santri, form.asrama_id]);

    const openCreate = () => {
        setEditing(null);
        setForm({
            asrama_id: '',
            daftar_pelanggaran_id: '',
            petugas_id: '',
            tanpa_nama: '0',
            sumber_pencatatan: 'petugas',
            tanggal: new Date().toISOString().split('T')[0],
            keterangan: '',
        });
        setDaerahId('');
        setSelectedSantri([]);
        setPendingSantriId('');
        setShowModal(true);
    };

    const openEdit = (p: any) => {
        setEditing(p);
        setForm({
            asrama_id: p.asrama_id,
            daftar_pelanggaran_id: p.daftar_pelanggaran_id,
            petugas_id: p.petugas_id,
            tanpa_nama: p.santri_id ? '0' : String(p.jumlah),
            sumber_pencatatan: p.sumber_pencatatan,
            tanggal: p.tanggal ? p.tanggal.split('T')[0] : '',
            keterangan: p.keterangan || '',
        });
        const a = asrama.find((a: any) => String(a.id) === String(p.asrama_id));
        setDaerahId(a?.daerah_id ? String(a.daerah_id) : '');
        setSelectedSantri(p.santri ? [p.santri] : []);
        setPendingSantriId('');
        setShowModal(true);
    };

    const addSantri = (santriId: string) => {
        if (!santriId) return;
        const s = santri.find((s: any) => String(s.id) === santriId);
        if (
            s &&
            !selectedSantri.find((sel: any) => String(sel.id) === santriId)
        ) {
            setSelectedSantri([...selectedSantri, s]);
        }
        setPendingSantriId('');
    };

    const removeSantri = (santriId: number | string) => {
        setSelectedSantri(
            selectedSantri.filter((s: any) => String(s.id) !== String(santriId)),
        );
    };

    const submit = () => {
        if (editing) {
            const data = {
                santri_id: selectedSantri[0]?.id || null,
                asrama_id: form.asrama_id,
                daftar_pelanggaran_id: form.daftar_pelanggaran_id,
                petugas_id: form.petugas_id || null,
                jumlah:
                    selectedSantri.length > 0
                        ? 1
                        : parseInt(form.tanpa_nama) || 1,
                sumber_pencatatan: form.sumber_pencatatan,
                tanggal: form.tanggal,
                keterangan: form.keterangan,
            };
            router.put(`/pelanggaran/${editing.id}`, data, {
                onSuccess: () => setShowModal(false),
            });
        } else {
            const data: Record<string, any> = {
                santri_ids: selectedSantri.map((s: any) => s.id),
                tanpa_nama: parseInt(form.tanpa_nama) || 0,
                asrama_id: form.asrama_id,
                daftar_pelanggaran_id: form.daftar_pelanggaran_id,
                petugas_id: form.petugas_id || null,
                sumber_pencatatan: form.sumber_pencatatan,
                tanggal: form.tanggal,
                keterangan: form.keterangan,
            };
            router.post('/pelanggaran', data, {
                onSuccess: () => setShowModal(false),
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus pelanggaran ini?')) {
            router.delete(`/pelanggaran/${id}`);
        }
    };

    const bulkDelete = () => {
        if (
            confirm(`Yakin ingin menghapus ${selectedIds.length} pelanggaran?`)
        ) {
            router.post(
                '/pelanggaran/bulk-delete',
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
            '/pelanggaran',
            {
                sort_column: nextDir === 'none' ? undefined : column,
                sort_direction: nextDir === 'none' ? undefined : nextDir,
                sumber: filterSumber || undefined,
                daerah_id: filterDaerah || undefined,
                asrama_id: filterAsrama || undefined,
                tanggal_mulai: filterTanggalMulai || undefined,
                tanggal_selesai: filterTanggalSelesai || undefined,
                per_page: perPage,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const formatDate = (dateString: string) => {
        if (!dateString) return '-';
        const [y, m, d] = dateString.split('T')[0].split('-');
        return `${d}-${m}-${y}`;
    };

    const columns: Column<any>[] = [
        {
            key: 'no',
            label: '#',
            render: (_p: any, idx: number) => (
                <span>{pelanggaran.from + idx}</span>
            ),
            className: 'text-muted-foreground text-xs w-10',
        },
        {
            key: 'tanggal',
            label: 'Tanggal',
            sortable: true,
            render: (p) => <span>{formatDate(p.tanggal)}</span>,
        },
        {
            key: 'santri',
            label: 'Santri',
            sortable: true,
            render: (p) => (
                <span className="font-medium">
                    {p.santri?.nama || `${p.jumlah} Orang`}
                </span>
            ),
        },
        {
            key: 'asrama',
            label: 'Asrama',
            sortable: true,
            render: (p) =>
                p.asrama?.daerah?.kode
                    ? `${p.asrama.daerah.kode.charAt(0)}.${p.asrama.nomor}`
                    : p.asrama?.nomor || '-',
            hideable: true,
        },
        {
            key: 'pelanggaran',
            label: 'Pelanggaran',
            sortable: true,
            render: (p) => p.daftar_pelanggaran?.nama_pelanggaran || '-',
        },
        {
            key: 'sumber',
            label: 'Sumber',
            render: (p) => (
                <Badge
                    variant={
                        p.sumber_pencatatan === 'petugas'
                            ? 'success'
                            : 'warning'
                    }
                >
                    {p.sumber_pencatatan}
                </Badge>
            ),
            hideable: true,
        },
        {
            key: 'aksi',
            label: 'Aksi',
            headClassName: 'text-right',
            className: 'text-right',
            render: (p) => (
                <div className="flex justify-end gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => openEdit(p)}
                    >
                        <Edit2 className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => destroy(p.id)}
                    >
                        <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Pelanggaran" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Pelanggaran
                        </h1>
                        <p className="text-muted-foreground">
                            Catat dan kelola pelanggaran santri
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Catat Pelanggaran
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={pelanggaran.data}
                    meta={pelanggaran}
                    keyExtractor={(p) => p.id}
                    onPageChange={(page) =>
                        router.get(
                            '/pelanggaran',
                            {
                                page,
                                sort_column: sortColumn || undefined,
                                sort_direction:
                                    sortDirection === 'none'
                                        ? undefined
                                        : sortDirection,
                                sumber: filterSumber || undefined,
                                daerah_id: filterDaerah || undefined,
                                asrama_id: filterAsrama || undefined,
                                tanggal_mulai: filterTanggalMulai || undefined,
                                tanggal_selesai:
                                    filterTanggalSelesai || undefined,
                                per_page: perPage,
                            },
                            { preserveState: true, preserveScroll: true },
                        )
                    }
                    sortColumn={sortColumn}
                    sortDirection={sortDirection}
                    onSort={handleSort}
                    perPage={perPage}
                    onPerPageChange={(p) => {
                        setPerPage(p);
                        if (p !== perPage) {
                            router.get(
                                '/pelanggaran',
                                {
                                    per_page: p,
                                    page: 1,
                                    sort_column: sortColumn || undefined,
                                    sort_direction:
                                        sortDirection === 'none'
                                            ? undefined
                                            : sortDirection,
                                    sumber: filterSumber || undefined,
                                    daerah_id: filterDaerah || undefined,
                                    asrama_id: filterAsrama || undefined,
                                    tanggal_mulai:
                                        filterTanggalMulai || undefined,
                                    tanggal_selesai:
                                        filterTanggalSelesai || undefined,
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
                                value={filterSumber}
                                onChange={(e) => {
                                    setFilterSumber(e.target.value);
                                    setFilterDaerah('');
                                    setFilterAsrama('');
                                }}
                                placeholder="Semua Sumber"
                                options={[
                                    { value: 'petugas', label: 'Petugas' },
                                    {
                                        value: 'ketua_kamar',
                                        label: 'Ketua Kamar',
                                    },
                                ]}
                                className="min-w-[150px]"
                            />
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
                                options={(filterDaerah
                                    ? asrama.filter(
                                          (a: any) =>
                                              String(a.daerah_id) ===
                                              String(filterDaerah),
                                      )
                                    : asrama
                                ).map((a: any) => ({
                                    value: a.id,
                                    label: `No. ${a.nomor}`,
                                }))}
                                className="min-w-[150px]"
                            />
                            <Input
                                type="date"
                                value={filterTanggalMulai}
                                onChange={(e) =>
                                    setFilterTanggalMulai(e.target.value)
                                }
                                placeholder="Tanggal Mulai"
                                className="max-w-[150px]"
                            />
                            <Input
                                type="date"
                                value={filterTanggalSelesai}
                                onChange={(e) =>
                                    setFilterTanggalSelesai(e.target.value)
                                }
                                placeholder="Tanggal Selesai"
                                className="max-w-[150px]"
                            />
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    router.get(
                                        '/pelanggaran',
                                        {
                                            sumber: filterSumber || undefined,
                                            daerah_id:
                                                filterDaerah || undefined,
                                            asrama_id:
                                                filterAsrama || undefined,
                                            tanggal_mulai:
                                                filterTanggalMulai || undefined,
                                            tanggal_selesai:
                                                filterTanggalSelesai ||
                                                undefined,
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
                title={editing ? 'Edit Pelanggaran' : 'Catat Pelanggaran'}
            >
                <div className="space-y-4">
                    {/* Daerah */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Daerah</label>
                        <Select
                            value={daerahId}
                            onChange={(e) => {
                                setDaerahId(e.target.value);
                                setForm({ ...form, asrama_id: '' });
                                setSelectedSantri([]);
                            }}
                            placeholder="Pilih Daerah"
                            options={daerahList.map((d: any) => ({
                                value: d.id,
                                label: d.nama_daerah,
                            }))}
                            disabled={!!editing}
                        />
                    </div>

                    {/* Asrama (setelah daerah) */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Asrama</label>
                        <Select
                            value={form.asrama_id}
                            onChange={(e) => {
                                setForm({ ...form, asrama_id: e.target.value });
                                setSelectedSantri([]);
                            }}
                            placeholder="Pilih Asrama"
                            options={filteredAsrama.map((a: any) => ({
                                value: a.id,
                                label: `Asrama ${a.nomor}`,
                            }))}
                            disabled={!daerahId || !!editing}
                        />
                    </div>

                    {/* Santri (multi-select) */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Santri</label>
                        {selectedSantri.length > 0 && (
                            <div className="mb-2 flex flex-wrap gap-2">
                                {selectedSantri.map((s: any) => (
                                    <div
                                        key={s.id}
                                        className="flex items-center gap-1.5 rounded-full border bg-accent px-2.5 py-1 text-xs"
                                    >
                                        <span>{s.nama}</span>
                                        {!editing && (
                                            <button
                                                type="button"
                                                onClick={() => removeSantri(s.id)}
                                                className="text-muted-foreground hover:text-foreground"
                                            >
                                                <X className="h-3 w-3" />
                                            </button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                        {!editing && (
                            <div className="flex gap-2">
                                <Select
                                    value={pendingsantri_id}
                                    onChange={(e) =>
                                        setPendingSantriId(e.target.value)
                                    }
                                    placeholder="Pilih Santri"
                                    options={filteredSantri
                                        .filter(
                                            (s: any) =>
                                                !selectedSantri.find(
                                                    (sel: any) =>
                                                        String(sel.id) ===
                                                        String(s.id),
                                                ),
                                        )
                                        .map((s: any) => ({
                                            value: s.id,
                                            label: s.nama,
                                        }))}
                                    disabled={!form.asrama_id}
                                    className="flex-1"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => addSantri(pendingsantri_id)}
                                    disabled={!pendingsantri_id}
                                >
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </div>
                        )}
                        {!form.asrama_id && !editing && (
                            <p className="text-xs text-muted-foreground">
                                Pilih daerah dan asrama terlebih dahulu
                            </p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Tanpa Nama
                            </label>
                            <Input
                                type="number"
                                min={0}
                                value={form.tanpa_nama}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        tanpa_nama: e.target.value,
                                    })
                                }
                                placeholder="Jumlah santri tidak dikenal"
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Jenis Pelanggaran
                            </label>
                            <Select
                                value={form.daftar_pelanggaran_id}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        daftar_pelanggaran_id: e.target.value,
                                    })
                                }
                                placeholder="Pilih"
                                options={daftarPelanggaran.map((d: any) => ({
                                    value: d.id,
                                    label: d.nama_pelanggaran,
                                }))}
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Tanggal
                            </label>
                            <Input
                                type="date"
                                value={form.tanggal}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        tanggal: e.target.value,
                                    })
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Sumber
                            </label>
                            <Select
                                value={form.sumber_pencatatan}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        sumber_pencatatan: e.target.value,
                                    })
                                }
                                options={[
                                    { value: 'petugas', label: 'Petugas' },
                                    {
                                        value: 'ketua_kamar',
                                        label: 'Ketua Kamar',
                                    },
                                ]}
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Keterangan
                        </label>
                        <Input
                            value={form.keterangan}
                            onChange={(e) =>
                                setForm({ ...form, keterangan: e.target.value })
                            }
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
                            {editing ? 'Simpan' : 'Catat'}
                        </Button>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
