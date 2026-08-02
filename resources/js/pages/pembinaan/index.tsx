import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Select } from '@/components/ui/select';
import { BookOpen, PlusCircle, Printer, Clock } from 'lucide-react';

export default function PembinaanIndex() {
    const {
        pembinaan,
        daerah,
        asrama,
        filters: initialFilters,
        auth,
        per_page,
        sort_column,
        sort_direction,
    } = usePage<any>().props;
    const [perPage, setPerPage] = useState(parseInt(per_page) || 15);
    const [showSetorModal, setShowSetorModal] = useState(false);
    const [showPemutihanModal, setShowPemutihanModal] = useState(false);
    const [setorTarget, setSetorTarget] = useState<any>(null);
    const [sortColumn, setSortColumn] = useState(sort_column || '');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>(
        (sort_direction as 'asc' | 'desc' | 'none') || 'none',
    );
    const [jumlahSetoran, setJumlahSetoran] = useState('');
    const [tanggalSetor, setTanggalSetor] = useState(
        new Date().toISOString().split('T')[0],
    );
    const [showRiwayatModal, setShowRiwayatModal] = useState(false);
    const [multiplier, setMultiplier] = useState('');
    const [showCetakModal, setShowCetakModal] = useState(false);
    const [cetakMode, setCetakMode] = useState<'semua' | 'bulan' | 'rentang'>('semua');
    const [cetakBulan, setCetakBulan] = useState('');
    const [cetakTanggalAwal, setCetakTanggalAwal] = useState('');
    const [cetakTanggalAkhir, setCetakTanggalAkhir] = useState('');
    const [search, setSearch] = useState(initialFilters?.search || '');
    const [filterDaerah, setFilterDaerah] = useState(
        initialFilters?.daerah_id || '',
    );
    const [filterAsrama, setFilterAsrama] = useState(
        initialFilters?.asrama_id || '',
    );

    const openSetor = (p: any) => {
        setSetorTarget(p);
        setJumlahSetoran('');
        setTanggalSetor(new Date().toISOString().split('T')[0]);
        setShowSetorModal(true);
    };

    const openPemutihan = () => {
        setMultiplier('');
        setShowPemutihanModal(true);
    };

    const setorSanksi = () => {
        if (!setorTarget || !jumlahSetoran) return;
        router.post(
            `/pembinaan/${setorTarget.id}/setor-sanksi`,
            {
                jumlah_setoran: jumlahSetoran,
                tanggal_setor: tanggalSetor,
            },
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

    // Helper to get asrama display
    const getAsrama = (p: any) => {
        const a = p.santri?.asrama || p.asrama;
        if (a?.daerah?.kode) return `${a.daerah.kode.charAt(0)}.${a.nomor}`;
        return a?.nomor || '-';
    };

    const formatTanggal = (date: string) => {
        if (!date) return '-';
        const d = new Date(date);
        if (isNaN(d.getTime())) return date;
        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    };

    const openRiwayat = (p: any) => {
        setSetorTarget(p);
        setShowRiwayatModal(true);
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
            key: 'asrama',
            label: 'Asrama',
            render: (p) => getAsrama(p),
            hideable: true,
        },
        {
            key: 'iksass',
            label: 'IKSASS',
            render: (p) => p.santri?.iksass || '-',
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
                        variant="ghost"
                        size="sm"
                        onClick={() => openRiwayat(p)}
                        title="Riwayat Setoran"
                        className="inline-flex items-center gap-1"
                    >
                        <Clock className="h-4 w-4" />
                        Riwayat
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => openSetor(p)}
                        title="Setor Sanksi"
                        className="inline-flex items-center gap-1"
                    >
                        <BookOpen className="h-4 w-4" />
                        Setor
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
                            Kelola data pembinaan santri
                        </p>
                    </div>
                    <div className="flex flex-wrap justify-end gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setShowCetakModal(true)}
                        >
                            <Printer className="h-4 w-4" />
                            Cetak
                        </Button>
                        {auth?.user?.is_pembina ||
                        auth?.user?.is_super_admin ? (
                            <Button
                                variant="default"
                                size="sm"
                                onClick={openPemutihan}
                            >
                                <PlusCircle className="h-4 w-4" />
                                Pemutihan
                            </Button>
                        ) : null}
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
                    searchPlaceholder="Cari nama, panggilan, atau kamar..."
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
                                        ? `${a.daerah.kode.charAt(0)}.${a.nomor}`
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

            {/* Cetak Modal */}
            <Modal
                open={showCetakModal}
                onClose={() => setShowCetakModal(false)}
                title="Cetak Pembinaan"
                description="Pilih periode data pembinaan yang akan dicetak."
            >
                <div className="space-y-4">
                    <div className="space-y-3">
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="radio"
                                name="cetakMode"
                                value="semua"
                                checked={cetakMode === 'semua'}
                                onChange={() => setCetakMode('semua')}
                                className="accent-blue-600"
                            />
                            Semua Data
                        </label>

                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="radio"
                                name="cetakMode"
                                value="bulan"
                                checked={cetakMode === 'bulan'}
                                onChange={() => setCetakMode('bulan')}
                                className="accent-blue-600"
                            />
                            Per Bulan
                        </label>
                        {cetakMode === 'bulan' && (
                            <div className="ml-6">
                                <Input
                                    type="month"
                                    value={cetakBulan}
                                    onChange={(e) =>
                                        setCetakBulan(e.target.value)
                                    }
                                    className="max-w-[200px]"
                                />
                            </div>
                        )}

                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="radio"
                                name="cetakMode"
                                value="rentang"
                                checked={cetakMode === 'rentang'}
                                onChange={() => setCetakMode('rentang')}
                                className="accent-blue-600"
                            />
                            Rentang Tanggal
                        </label>
                        {cetakMode === 'rentang' && (
                            <div className="ml-6 flex items-center gap-2">
                                <Input
                                    type="date"
                                    value={cetakTanggalAwal}
                                    onChange={(e) =>
                                        setCetakTanggalAwal(e.target.value)
                                    }
                                    className="max-w-[180px]"
                                />
                                <span className="text-muted-foreground">
                                    s.d.
                                </span>
                                <Input
                                    type="date"
                                    value={cetakTanggalAkhir}
                                    onChange={(e) =>
                                        setCetakTanggalAkhir(e.target.value)
                                    }
                                    className="max-w-[180px]"
                                />
                            </div>
                        )}
                    </div>

                    <div className="flex justify-end gap-3 pt-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowCetakModal(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            onClick={() => {
                                const params = new URLSearchParams();
                                if (cetakMode === 'bulan' && cetakBulan) {
                                    params.set('bulan', cetakBulan);
                                } else if (cetakMode === 'rentang') {
                                    if (cetakTanggalAwal)
                                        params.set(
                                            'tanggal_awal',
                                            cetakTanggalAwal,
                                        );
                                    if (cetakTanggalAkhir)
                                        params.set(
                                            'tanggal_akhir',
                                            cetakTanggalAkhir,
                                        );
                                }
                                const qs = params.toString();
                                const url = qs
                                    ? `/pembinaan/cetak?${qs}`
                                    : '/pembinaan/cetak';
                                window.open(url, '_blank');
                                setShowCetakModal(false);
                            }}
                        >
                            Cetak
                        </Button>
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
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Tanggal Setor
                        </label>
                        <Input
                            type="date"
                            value={tanggalSetor}
                            onChange={(e) => setTanggalSetor(e.target.value)}
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

            {/* Riwayat Setoran Modal */}
            <Modal
                open={showRiwayatModal}
                onClose={() => setShowRiwayatModal(false)}
                title="Riwayat Setoran"
                description={
                    setorTarget
                        ? `${getNama(setorTarget)} - ${getAsrama(setorTarget)}`
                        : ''
                }
            >
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="px-4 py-2 text-left font-semibold">
                                    #
                                </th>
                                <th className="px-4 py-2 text-left font-semibold">
                                    Tanggal Setor
                                </th>
                                <th className="px-4 py-2 text-right font-semibold">
                                    Jumlah
                                </th>
                                <th className="px-4 py-2 text-left font-semibold">
                                    Oleh
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {(setorTarget?.setoran || []).length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Belum ada riwayat setoran
                                    </td>
                                </tr>
                            ) : (
                                [...(setorTarget?.setoran || [])]
                                    .sort(
                                        (a: any, b: any) =>
                                            new Date(b.tanggal_setor).getTime() -
                                            new Date(a.tanggal_setor).getTime(),
                                    )
                                    .map((s: any, i: number) => (
                                        <tr
                                            key={s.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-4 py-2 text-muted-foreground">
                                                {i + 1}
                                            </td>
                                            <td className="px-4 py-2">
                                                {formatTanggal(
                                                    s.tanggal_setor,
                                                )}
                                            </td>
                                            <td className="px-4 py-2 text-right font-medium text-green-600">
                                                {s.jumlah.toLocaleString()}
                                            </td>
                                            <td className="px-4 py-2">
                                                {s.user_name || '-'}
                                            </td>
                                        </tr>
                                    ))
                            )}
                        </tbody>
                    </table>
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
