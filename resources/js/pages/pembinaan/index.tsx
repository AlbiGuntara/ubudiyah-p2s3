import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Select } from '@/components/ui/select';
import { Edit2, Trash2, BookOpen, PlusCircle } from 'lucide-react';

export default function PembinaanIndex() {
    const {
        pembinaan,
        daerah,
        asrama,
        filters: initialFilters,
    } = usePage<any>().props;
    const [perPage, setPerPage] = useState(15);
    const [showModal, setShowModal] = useState(false);
    const [showSetorModal, setShowSetorModal] = useState(false);
    const [showPemutihanModal, setShowPemutihanModal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [setorTarget, setSetorTarget] = useState<any>(null);
    const [sortColumn, setSortColumn] = useState('');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>(
        'none',
    );
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);
    const [form, setForm] = useState({ sanksi: 0 });
    const [jumlahSetoran, setJumlahSetoran] = useState('');
    const [multiplier, setMultiplier] = useState('');
    const [search, setSearch] = useState(initialFilters?.search || '');
    const [filterDaerah, setFilterDaerah] = useState(
        initialFilters?.daerah_id || '',
    );
    const [filterAsrama, setFilterAsrama] = useState(
        initialFilters?.asrama_id || '',
    );

    const openEdit = (p: any) => {
        setEditing(p);
        setForm({ sanksi: p.sanksi });
        setShowModal(true);
    };

    const openSetor = (p: any) => {
        setSetorTarget(p);
        setJumlahSetoran('');
        setShowSetorModal(true);
    };

    const openPemutihan = () => {
        setMultiplier('');
        setShowPemutihanModal(true);
    };

    const submit = () => {
        if (editing) {
            router.put(`/pembinaan/${editing.id}`, form, {
                onSuccess: () => setShowModal(false),
            });
        }
    };

    const setorSanksi = () => {
        if (!setorTarget || !jumlahSetoran) return;
        router.post(
            `/pembinaan/${setorTarget.id}/setor-sanksi`,
            { jumlah_setoran: jumlahSetoran },
            {
                onSuccess: () => {
                    setShowSetorModal(false);
                    setSetorTarget(null);
                    setJumlahSetoran('');
                },
            },
        );
    };

    const lakukanPemutihan = () => {
        if (!multiplier) return;
        router.post(
            '/pembinaan/pemutihan',
            { multiplier },
            {
                onSuccess: () => {
                    setShowPemutihanModal(false);
                    setMultiplier('');
                },
            },
        );
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus pembinaan ini?')) {
            router.delete(`/pembinaan/${id}`);
        }
    };

    const bulkDelete = () => {
        if (confirm(`Yakin ingin menghapus ${selectedIds.length} pembinaan?`)) {
            router.post(
                '/pembinaan/bulk-delete',
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
            '/pembinaan',
            {
                sort_column: nextDir === 'none' ? undefined : column,
                sort_direction: nextDir === 'none' ? undefined : nextDir,
                per_page: perPage,
                search: search || undefined,
                daerah_id: filterDaerah || undefined,
                asrama_id: filterAsrama || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const applyFilters = () => {
        router.get(
            '/pembinaan',
            {
                page: 1,
                sort_column: sortColumn || undefined,
                sort_direction:
                    sortDirection === 'none' ? undefined : sortDirection,
                per_page: perPage,
                search: search || undefined,
                daerah_id: filterDaerah || undefined,
                asrama_id: filterAsrama || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    // Helper to get display name
    const getNama = (p: any) => {
        if (p.santri) return p.santri.nama;
        if (!p.santri_id && p.asrama) {
            return `Tanpa Nama`;
        }
        return 'Tanpa Nama';
    };

    // Helper to get daerah name
    const getDaerah = (p: any) => {
        if (p.santri?.asrama?.daerah?.nama_daerah)
            return p.santri.asrama.daerah.nama_daerah;
        if (p.asrama?.daerah?.nama_daerah) return p.asrama.daerah.nama_daerah;
        return '-';
    };

    // Helper to get asrama display
    const getAsrama = (p: any) => {
        const a = p.santri?.asrama || p.asrama;
        if (a?.daerah?.kode) return `${a.daerah.kode}.${a.nomor}`;
        return a?.nomor || '-';
    };

    const filteredAsrama = filterDaerah
        ? asrama.filter((a: any) => a.daerah_id === parseInt(filterDaerah))
        : asrama;

    const columns: Column<any>[] = [
        {
            key: 'no',
            label: '#',
            render: (_p: any, idx: number) => (
                <span>{pembinaan.from + idx}</span>
            ),
            className: 'text-muted-foreground text-xs w-10',
        },
        {
            key: 'santri',
            label: 'Santri',
            sortable: true,
            render: (p) => <span className="font-medium">{getNama(p)}</span>,
        },
        {
            key: 'daerah',
            label: 'Daerah',
            render: (p) => getDaerah(p),
            hideable: true,
        },
        {
            key: 'asrama',
            label: 'Asrama',
            render: (p) => getAsrama(p),
            hideable: true,
        },
        {
            key: 'sanksi',
            label: 'Sanksi',
            sortable: true,
            render: (p) => (
                <span className="font-semibold text-red-600">
                    {p.sanksi.toLocaleString()}
                </span>
            ),
        },
        {
            key: 'shalawat',
            label: 'Sanksi Disetor',
            render: (p) => (
                <span className="text-green-600">
                    {p.shalawat_tertulis.toLocaleString()}
                </span>
            ),
        },
        {
            key: 'sisa_sanksi',
            label: 'Sisa Sanksi',
            sortable: true,
            render: (p) => {
                const sisa = p.sisa_sanksi;
                return (
                    <Badge variant={sisa > 0 ? 'warning' : 'success'}>
                        {sisa.toLocaleString()}
                    </Badge>
                );
            },
        },
        {
            key: 'aksi',
            label: 'Aksi',
            headClassName: 'text-right',
            className: 'text-right',
            render: (p) => (
                <div className="flex justify-end gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => openSetor(p)}
                        title="Setor Sanksi"
                        className="inline-flex items-center gap-1"
                    >
                        <BookOpen className="h-4 w-4" />
                        <span className="hidden sm:inline">Setor</span>
                    </Button>
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
            <Head title="Pembinaan" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Pembinaan
                        </h1>
                        <p className="text-muted-foreground">
                            Kelola pembinaan dan sanksi santri
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="default"
                            size="sm"
                            onClick={openPemutihan}
                            className="inline-flex items-center gap-1"
                        >
                            <PlusCircle className="h-4 w-4" />
                            Lakukan Pemutihan
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={pembinaan.data}
                    meta={pembinaan}
                    keyExtractor={(p) => p.id}
                    onPageChange={(page) =>
                        router.get(
                            '/pembinaan',
                            {
                                page,
                                sort_column: sortColumn || undefined,
                                sort_direction:
                                    sortDirection === 'none'
                                        ? undefined
                                        : sortDirection,
                                per_page: perPage,
                                search: search || undefined,
                                daerah_id: filterDaerah || undefined,
                                asrama_id: filterAsrama || undefined,
                            },
                            { preserveState: true, preserveScroll: true },
                        )
                    }
                    search={search}
                    onSearchChange={(q) => {
                        setSearch(q);
                        router.get(
                            '/pembinaan',
                            {
                                search: q || undefined,
                                page: 1,
                                sort_column: sortColumn || undefined,
                                sort_direction:
                                    sortDirection === 'none'
                                        ? undefined
                                        : sortDirection,
                                daerah_id: filterDaerah || undefined,
                                asrama_id: filterAsrama || undefined,
                                per_page: perPage,
                            },
                            { preserveState: true, preserveScroll: true },
                        );
                    }}
                    searchPlaceholder="Cari nama santri atau kamar..."
                    sortColumn={sortColumn}
                    sortDirection={sortDirection}
                    onSort={handleSort}
                    perPage={perPage}
                    onPerPageChange={(p) => {
                        setPerPage(p);
                        if (p !== perPage) {
                            router.get(
                                '/pembinaan',
                                {
                                    per_page: p,
                                    page: 1,
                                    sort_column: sortColumn || undefined,
                                    sort_direction:
                                        sortDirection === 'none'
                                            ? undefined
                                            : sortDirection,
                                    search: search || undefined,
                                    daerah_id: filterDaerah || undefined,
                                    asrama_id: filterAsrama || undefined,
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
                                options={filteredAsrama.map((a: any) => ({
                                    value: a.id,
                                    label: a.daerah?.kode
                                        ? `${a.daerah.kode}.${a.nomor}`
                                        : `no. ${a.nomor}`,
                                }))}
                                className="min-w-[150px]"
                            />
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={applyFilters}
                            >
                                Filter
                            </Button>
                        </>
                    }
                />
            </div>

            {/* Edit Modal */}
            <Modal
                open={showModal}
                onClose={() => setShowModal(false)}
                title="Edit Pembinaan"
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Sanksi</label>
                        <Input
                            type="number"
                            min={0}
                            value={form.sanksi}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    sanksi: parseInt(e.target.value) || 0,
                                })
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
                        <Button onClick={submit}>Simpan</Button>
                    </div>
                </div>
            </Modal>

            {/* Setor Sanksi Modal */}
            <Modal
                open={showSetorModal}
                onClose={() => setShowSetorModal(false)}
                title="Setor Sanksi"
                description={
                    setorTarget
                        ? `Sanksi: ${setorTarget.sanksi.toLocaleString()} | Sudah disetor: ${setorTarget.shalawat_tertulis.toLocaleString()} | Sisa: ${setorTarget.sisa_sanksi.toLocaleString()}`
                        : ''
                }
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Jumlah Setoran
                        </label>
                        <Input
                            type="number"
                            min={1}
                            max={setorTarget?.sisa_sanksi || 0}
                            value={jumlahSetoran}
                            onChange={(e) => setJumlahSetoran(e.target.value)}
                            placeholder="Masukkan jumlah setoran"
                        />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowSetorModal(false)}
                        >
                            Batal
                        </Button>
                        <Button onClick={setorSanksi}>Setor</Button>
                    </div>
                </div>
            </Modal>

            {/* Pemutihan Modal */}
            <Modal
                open={showPemutihanModal}
                onClose={() => setShowPemutihanModal(false)}
                title="Lakukan Pemutihan"
                description="Masukkan angka pengali untuk melipatgandakan sisa sanksi semua santri."
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Angka Pengali (Multiplier)
                        </label>
                        <Input
                            type="number"
                            min={1}
                            value={multiplier}
                            onChange={(e) => setMultiplier(e.target.value)}
                            placeholder="Contoh: 3"
                        />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowPemutihanModal(false)}
                        >
                            Batal
                        </Button>
                        <Button onClick={lakukanPemutihan}>
                            Konfirmasi Pemutihan
                        </Button>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
