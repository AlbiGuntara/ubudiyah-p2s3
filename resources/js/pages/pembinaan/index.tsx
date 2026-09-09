import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Select } from '@/components/ui/select';
import { BookOpen, PlusCircle, Printer, Clock, Undo2 } from 'lucide-react';

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
    const [showTambahModal, setShowTambahModal] = useState(false);
    const [jumlahTambah, setJumlahTambah] = useState('');
    const [tanggalKoreksi, setTanggalKoreksi] = useState(
        new Date().toISOString().split('T')[0],
    );
    const [multiplier, setMultiplier] = useState('');
    const [showCetakModal, setShowCetakModal] = useState(false);
    const [cetakDaerahId, setCetakDaerahId] = useState('');
    const [search, setSearch] = useState(initialFilters?.search || '');
    const [filterDaerah, setFilterDaerah] = useState(
        initialFilters?.daerah_id || '',
    );
    const [filterAsrama, setFilterAsrama] = useState(
        initialFilters?.asrama_id || '',
    );
    const [pelanggaranOptions, setPelanggaranOptions] = useState<any[]>([]);
    const [selectedPelanggaran, setSelectedPelanggaran] = useState<number[]>(
        [],
    );

    const fetchPelanggaran = async (p: any) => {
        try {
            const res = await fetch(`/pembinaan/${p.id}/pelanggaran`);
            const data = await res.json();
            setPelanggaranOptions(data || []);
        } catch {
            setPelanggaranOptions([]);
        }
    };

    const openSetor = (p: any) => {
        setSetorTarget(p);
        setJumlahSetoran('');
        setTanggalSetor(new Date().toISOString().split('T')[0]);
        setSelectedPelanggaran([]);
        fetchPelanggaran(p);
        setShowSetorModal(true);
    };

    const openPemutihan = () => {
        setMultiplier('');
        setShowPemutihanModal(true);
    };

    const openTambah = (p: any) => {
        setSetorTarget(p);
        setJumlahTambah('');
        setTanggalKoreksi(new Date().toISOString().split('T')[0]);
        setShowTambahModal(true);
    };

    const tambahSanksi = () => {
        if (!setorTarget || !jumlahTambah) return;
        router.post(
            `/pembinaan/${setorTarget.id}/tambah-sanksi`,
            {
                jumlah_tambah: jumlahTambah,
                tanggal_koreksi: tanggalKoreksi,
            },
            {
                onSuccess: () => {
                    setShowTambahModal(false);
                    setSetorTarget(null);
                    setJumlahTambah('');
                },
            },
        );
    };

    const togglePelanggaran = (id: number) => {
        setSelectedPelanggaran((prev) =>
            prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id],
        );
    };

    const selectedCount = selectedPelanggaran.length;
    const selectedOptions = selectedPelanggaran
        .map((id) => pelanggaranOptions.find((pl) => pl.id === id))
        .filter(Boolean);
    const maxSetoran = selectedOptions.reduce(
        (sum, pl: any) => sum + (pl.sisa_sanksi || 0),
        0,
    );
    const minSetoran =
        selectedOptions.length > 0
            ? maxSetoran -
              (selectedOptions[selectedOptions.length - 1].sisa_sanksi || 0)
            : 0;

    const setorSanksi = () => {
        if (!setorTarget || !jumlahSetoran) return;
        if (selectedPelanggaran.length === 0) return;
        router.post(
            `/pembinaan/${setorTarget.id}/setor-sanksi`,
            {
                pelanggaran_ids: selectedPelanggaran,
                jumlah_setoran: jumlahSetoran,
                tanggal_setor: tanggalSetor,
            },
            {
                onSuccess: () => {
                    setShowSetorModal(false);
                    setSetorTarget(null);
                    setJumlahSetoran('');
                    setSelectedPelanggaran([]);
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
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => openTambah(p)}
                        title="Tambah Sanksi"
                        className="inline-flex items-center gap-1"
                    >
                        <Undo2 className="h-4 w-4" />
                        Tambah
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
                onClose={() => {
                    setShowCetakModal(false);
                    setCetakDaerahId('');
                }}
                title="Cetak Pembinaan"
            >
                <div className="space-y-4">
                    <Button
                        className="w-full"
                        onClick={() => {
                            setShowCetakModal(false);
                            setCetakDaerahId('');
                            window.open('/pembinaan/cetak', '_blank');
                        }}
                    >
                        <Printer className="h-4 w-4" />
                        Cetak Semua
                    </Button>

                    <div className="relative">
                        <div className="absolute inset-0 flex items-center">
                            <span className="w-full border-t" />
                        </div>
                        <div className="relative flex justify-center text-xs uppercase">
                            <span className="bg-card px-2 text-muted-foreground">
                                atau
                            </span>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <label className="text-sm font-medium">
                            Cetak Per Daerah
                        </label>
                        <Select
                            value={cetakDaerahId}
                            onChange={(e) => setCetakDaerahId(e.target.value)}
                            placeholder="Pilih Daerah"
                            options={daerah.map((d: any) => ({
                                value: d.id,
                                label: d.nama_daerah,
                            }))}
                        />
                        <Button
                            className="w-full"
                            disabled={!cetakDaerahId}
                            onClick={() => {
                                if (!cetakDaerahId) return;
                                setShowCetakModal(false);
                                const url = `/pembinaan/cetak?daerah_id=${cetakDaerahId}`;
                                window.open(url, '_blank');
                                setCetakDaerahId('');
                            }}
                        >
                            <Printer className="h-4 w-4" />
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
                            Pilih Pelanggaran
                        </label>
                        {pelanggaranOptions.length === 0 ? (
                            <p className="py-2 text-sm text-muted-foreground">
                                Tidak ada pelanggaran yang belum diselesaikan.
                            </p>
                        ) : (
                            <div className="modal-scroll max-h-48 space-y-1 overflow-y-auto rounded-md">
                                {pelanggaranOptions.map((pl) => (
                                    <label
                                        key={pl.id}
                                        className="flex cursor-pointer items-center gap-2 rounded px-2 py-1 text-sm hover:bg-muted/50"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={selectedPelanggaran.includes(
                                                pl.id,
                                            )}
                                            onChange={() =>
                                                togglePelanggaran(pl.id)
                                            }
                                            className="accent-blue-600"
                                        />
                                        <span className="flex-1">
                                            {pl.nama_pelanggaran}
                                            <span className="ml-2 text-muted-foreground">
                                                ({pl.tanggal})
                                            </span>
                                        </span>
                                        <span className="text-xs font-semibold text-amber-600">
                                            Sisa:{' '}
                                            {pl.sisa_sanksi.toLocaleString()}
                                        </span>
                                    </label>
                                ))}
                            </div>
                        )}
                        {selectedCount > 0 && (
                            <p className="text-xs text-muted-foreground">
                                {selectedCount} pelanggaran dipilih. Maksimal
                                setor:{' '}
                                <strong>{maxSetoran.toLocaleString()}</strong> |
                                Minimal setor:{' '}
                                <strong>{minSetoran.toLocaleString()}</strong>
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Jumlah Setoran
                        </label>
                        <Input
                            type="number"
                            min={selectedCount > 0 ? minSetoran : 1}
                            max={selectedCount > 0 ? maxSetoran : 0}
                            value={jumlahSetoran}
                            onChange={(e) => setJumlahSetoran(e.target.value)}
                            placeholder="Masukkan jumlah setoran"
                            disabled={selectedCount === 0}
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
                        <Button
                            onClick={setorSanksi}
                            disabled={
                                selectedCount === 0 ||
                                !jumlahSetoran ||
                                Number(jumlahSetoran) > maxSetoran ||
                                Number(jumlahSetoran) < minSetoran
                            }
                        >
                            Setor
                        </Button>
                    </div>
                </div>
            </Modal>

            {/* Tambah Sanksi Modal */}
            <Modal
                open={showTambahModal}
                onClose={() => setShowTambahModal(false)}
                title="Tambah Sanksi"
                description={
                    setorTarget
                        ? `Sanksi: ${setorTarget.sanksi.toLocaleString()} | Sudah disetor: ${setorTarget.shalawat_tertulis.toLocaleString()} | Sisa: ${setorTarget.sisa_sanksi.toLocaleString()}`
                        : ''
                }
            >
                <div className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Digunakan untuk mengoreksi setoran yang salah. Maksimal{' '}
                        <strong>
                            {(
                                setorTarget?.shalawat_tertulis || 0
                            ).toLocaleString()}
                        </strong>{' '}
                        (total sanksi yang sudah disetor).
                    </p>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Jumlah Sanksi Ditambahkan
                        </label>
                        <Input
                            type="number"
                            min={1}
                            max={setorTarget?.shalawat_tertulis || 0}
                            value={jumlahTambah}
                            onChange={(e) => setJumlahTambah(e.target.value)}
                            placeholder="Masukkan jumlah sanksi"
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Tanggal Koreksi
                        </label>
                        <Input
                            type="date"
                            value={tanggalKoreksi}
                            onChange={(e) => setTanggalKoreksi(e.target.value)}
                        />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowTambahModal(false)}
                        >
                            Batal
                        </Button>
                        <Button onClick={tambahSanksi}>Tambah</Button>
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
                                            new Date(
                                                b.tanggal_setor,
                                            ).getTime() -
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
                                                {formatTanggal(s.tanggal_setor)}
                                            </td>
                                            <td
                                                className={`px-4 py-2 text-right font-medium ${s.jumlah < 0 ? 'text-red-600' : 'text-green-600'}`}
                                            >
                                                {s.jumlah < 0
                                                    ? `-${Math.abs(s.jumlah).toLocaleString()}`
                                                    : s.jumlah.toLocaleString()}
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
