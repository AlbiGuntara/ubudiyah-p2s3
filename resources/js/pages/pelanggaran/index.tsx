import { useState, useMemo, useCallback } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Modal } from '@/components/ui/modal';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Badge } from '@/components/ui/badge';
import { Edit2, Trash2, Plus, X, Printer, RefreshCw } from 'lucide-react';

export default function PelanggaranIndex() {
    const {
        pelanggaran,
        santri,
        asrama,
        daerah,
        daftarPelanggaran,
        filters,
        auth,
    } = usePage<any>().props;
    const userPermissions: string[] = auth?.user?.permissions || [];
    const canCetakSuratPanggilan = userPermissions.includes(
        'cetak_surat_panggilan',
    );
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

    const [showCetakUlang, setShowCetakUlang] = useState(false);
    const [reprintDaerahId, setReprintDaerahId] = useState('');
    const [reprintAsramaId, setReprintAsramaId] = useState('');
    const [reprintRiwayat, setReprintRiwayat] = useState<any[]>([]);
    const [loadingRiwayat, setLoadingRiwayat] = useState(false);

    const filteredReprintAsrama = useMemo(() => {
        if (!reprintDaerahId) return [];
        return asrama.filter(
            (a: any) => String(a.daerah_id) === String(reprintDaerahId),
        );
    }, [asrama, reprintDaerahId]);

    const [form, setForm] = useState({
        asrama_id: '',
        petugas_id: '',
        sumber_pencatatan: 'petugas',
        tanggal: new Date().toISOString().split('T')[0],
        keterangan: '',
    });
    const [daerahId, setDaerahId] = useState('');
    // Santri entries (allow duplicates for multiple violations per santri)
    const [nextSantriId, setNextSantriId] = useState(0);
    const [santriEntries, setSantriEntries] = useState<
        {
            uid: number;
            santri_id: number;
            nama: string;
            daftar_pelanggaran_id: string;
        }[]
    >([]);
    const [pendingsantri_id, setPendingSantriId] = useState('');
    const [pendingPelanggaranId, setPendingPelanggaranId] = useState('');
    // Anonymous entries (row-based like santri)
    const [nextAnonId, setNextAnonId] = useState(0);
    const [anonymousEntries, setAnonymousEntries] = useState<
        { uid: number; jumlah: string; daftar_pelanggaran_id: string }[]
    >([]);
    const [pendingAnonJumlah, setPendingAnonJumlah] = useState('1');
    const [pendingAnonPelanggaranId, setPendingAnonPelanggaranId] =
        useState('');

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

    const openCetakUlang = () => {
        setReprintDaerahId('');
        setReprintAsramaId('');
        setReprintRiwayat([]);
        setShowCetakUlang(true);
    };

    const fetchRiwayat = useCallback(async (asramaId: string) => {
        if (!asramaId) {
            setReprintRiwayat([]);
            return;
        }
        setLoadingRiwayat(true);
        try {
            const res = await fetch(
                `/pelanggaran/surat-panggilan/riwayat?asrama_id=${asramaId}`,
            );
            const data = await res.json();
            setReprintRiwayat(data);
        } catch {
            setReprintRiwayat([]);
        } finally {
            setLoadingRiwayat(false);
        }
    }, []);

    const openCreate = () => {
        setEditing(null);
        setForm({
            asrama_id: '',
            petugas_id: '',
            sumber_pencatatan: 'petugas',
            tanggal: new Date().toISOString().split('T')[0],
            keterangan: '',
        });
        setDaerahId('');
        setSantriEntries([]);
        setAnonymousEntries([]);
        setNextSantriId(0);
        setNextAnonId(0);
        setPendingSantriId('');
        setPendingPelanggaranId('');
        setPendingAnonJumlah('1');
        setPendingAnonPelanggaranId('');
        setShowModal(true);
    };

    const openEdit = (p: any) => {
        setEditing(p);
        setForm({
            asrama_id: p.asrama_id,
            petugas_id: p.petugas_id,
            sumber_pencatatan: p.sumber_pencatatan,
            tanggal: p.tanggal ? p.tanggal.split('T')[0] : '',
            keterangan: p.keterangan || '',
        });
        const a = asrama.find((a: any) => String(a.id) === String(p.asrama_id));
        setDaerahId(a?.daerah_id ? String(a.daerah_id) : '');
        if (p.santri) {
            setNextSantriId(1);
            setSantriEntries([
                {
                    uid: 0,
                    santri_id: p.santri.id,
                    nama: p.santri.nama,
                    daftar_pelanggaran_id: String(p.daftar_pelanggaran_id),
                },
            ]);
        } else {
            setSantriEntries([]);
            // Edit anonymous record
            setNextAnonId(1);
            setAnonymousEntries([
                {
                    uid: 0,
                    jumlah: String(p.jumlah),
                    daftar_pelanggaran_id: String(p.daftar_pelanggaran_id),
                },
            ]);
        }
        setPendingSantriId('');
        setPendingPelanggaranId('');
        setPendingAnonJumlah('1');
        setPendingAnonPelanggaranId('');
        setShowModal(true);
    };

    const addSantri = () => {
        if (!pendingsantri_id || !pendingPelanggaranId) return;
        const s = santri.find((s: any) => String(s.id) === pendingsantri_id);
        if (s) {
            setSantriEntries([
                ...santriEntries,
                {
                    uid: nextSantriId,
                    santri_id: s.id,
                    nama: s.nama,
                    daftar_pelanggaran_id: pendingPelanggaranId,
                },
            ]);
            setNextSantriId(nextSantriId + 1);
        }
        setPendingSantriId('');
        setPendingPelanggaranId('');
    };

    const removeSantri = (uid: number) => {
        setSantriEntries(santriEntries.filter((s) => s.uid !== uid));
    };

    const updateSantriPelanggaran = (
        uid: number,
        daftar_pelanggaran_id: string,
    ) => {
        setSantriEntries(
            santriEntries.map((s) =>
                s.uid === uid ? { ...s, daftar_pelanggaran_id } : s,
            ),
        );
    };

    const addAnonymous = () => {
        if (!pendingAnonJumlah || !pendingAnonPelanggaranId) return;
        const jumlah = parseInt(pendingAnonJumlah);
        if (jumlah < 1) return;
        setAnonymousEntries([
            ...anonymousEntries,
            {
                uid: nextAnonId,
                jumlah: pendingAnonJumlah,
                daftar_pelanggaran_id: pendingAnonPelanggaranId,
            },
        ]);
        setNextAnonId(nextAnonId + 1);
        setPendingAnonJumlah('1');
        setPendingAnonPelanggaranId('');
    };

    const removeAnonymous = (uid: number) => {
        setAnonymousEntries(anonymousEntries.filter((a) => a.uid !== uid));
    };

    const updateAnonymousJumlah = (uid: number, jumlah: string) => {
        setAnonymousEntries(
            anonymousEntries.map((a) => (a.uid === uid ? { ...a, jumlah } : a)),
        );
    };

    const updateAnonymousPelanggaran = (
        uid: number,
        daftar_pelanggaran_id: string,
    ) => {
        setAnonymousEntries(
            anonymousEntries.map((a) =>
                a.uid === uid ? { ...a, daftar_pelanggaran_id } : a,
            ),
        );
    };

    const submit = () => {
        if (editing) {
            const entry = santriEntries[0];
            const anon = anonymousEntries[0];
            const data = {
                santri_id: entry?.santri_id || null,
                asrama_id: form.asrama_id,
                daftar_pelanggaran_id:
                    entry?.daftar_pelanggaran_id || anon?.daftar_pelanggaran_id,
                petugas_id: form.petugas_id || null,
                jumlah: entry ? 1 : parseInt(anon?.jumlah) || 1,
                sumber_pencatatan: form.sumber_pencatatan,
                tanggal: form.tanggal,
                keterangan: form.keterangan,
            };
            router.put(`/pelanggaran/${editing.id}`, data, {
                onSuccess: () => setShowModal(false),
            });
        } else {
            const data: Record<string, any> = {
                santri_pelanggaran: santriEntries.map((s) => ({
                    santri_id: s.santri_id,
                    daftar_pelanggaran_id: s.daftar_pelanggaran_id,
                })),
                anonymous_entries: anonymousEntries.map((a) => ({
                    jumlah: parseInt(a.jumlah),
                    daftar_pelanggaran_id: a.daftar_pelanggaran_id,
                })),
                asrama_id: form.asrama_id,
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
                    <div className="flex flex-wrap justify-end gap-2">
                        {canCetakSuratPanggilan && (
                            <>
                                <Button
                                    variant="outline"
                                    onClick={openCetakUlang}
                                >
                                    <RefreshCw className="h-4 w-4" />
                                    Cetak Ulang
                                </Button>
                                <Button
                                    onClick={() =>
                                        window.open(
                                            '/pelanggaran/surat-panggilan/cetak',
                                            '_blank',
                                        )
                                    }
                                >
                                    <Printer className="h-4 w-4" />
                                    Cetak Surat{' '}
                                    <span className="hidden sm:inline">
                                        Panggilan
                                    </span>
                                </Button>
                            </>
                        )}
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" />
                            Catat{' '}
                            <span className="hidden sm:inline">
                                Pelanggaran
                            </span>
                        </Button>
                    </div>
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
                                    label: a.daerah?.kode
                                        ? `${a.daerah.kode.charAt(0)}.${a.nomor}`
                                        : `Asrama ${a.nomor}`,
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
                open={showCetakUlang}
                onClose={() => setShowCetakUlang(false)}
                title="Cetak Ulang Surat Panggilan"
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Daerah</label>
                        <Select
                            value={reprintDaerahId}
                            onChange={(e) => {
                                setReprintDaerahId(e.target.value);
                                setReprintAsramaId('');
                                setReprintRiwayat([]);
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
                            value={reprintAsramaId}
                            onChange={(e) => {
                                setReprintAsramaId(e.target.value);
                                fetchRiwayat(e.target.value);
                            }}
                            placeholder="Pilih Asrama"
                            disabled={!reprintDaerahId}
                            options={filteredReprintAsrama.map((a: any) => ({
                                value: a.id,
                                label: a.daerah?.kode
                                    ? `${a.daerah.kode.charAt(0)}.${a.nomor}`
                                    : `Asrama ${a.nomor}`,
                            }))}
                        />
                        {!reprintDaerahId && (
                            <p className="text-xs text-muted-foreground">
                                Pilih daerah terlebih dahulu
                            </p>
                        )}
                    </div>

                    {loadingRiwayat && (
                        <div className="flex items-center justify-center py-8">
                            <svg
                                className="h-6 w-6 animate-spin text-green-600"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    className="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    strokeWidth="4"
                                    fill="none"
                                />
                                <path
                                    className="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                />
                            </svg>
                        </div>
                    )}

                    {!loadingRiwayat &&
                        reprintAsramaId &&
                        reprintRiwayat.length === 0 && (
                            <p className="py-4 text-center text-sm text-muted-foreground">
                                Belum ada riwayat cetak untuk asrama ini.
                            </p>
                        )}

                    {!loadingRiwayat && reprintRiwayat.length > 0 && (
                        <div className="max-h-80 space-y-2 overflow-y-auto">
                            {reprintRiwayat.map((item: any) => (
                                <div
                                    key={item.id}
                                    className="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium">
                                            {item.kode_surat}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {item.tanggal_cetak} &middot;{' '}
                                            {item.jumlah_pelanggaran}{' '}
                                            pelanggaran &middot; {item.pencetak}
                                        </p>
                                    </div>
                                    <Button
                                        size="sm"
                                        onClick={() =>
                                            window.open(
                                                `/pelanggaran/surat-panggilan/${item.id}/cetak-ulang`,
                                                '_blank',
                                            )
                                        }
                                    >
                                        <Printer className="h-4 w-4" />
                                        Cetak Ulang
                                    </Button>
                                </div>
                            ))}
                        </div>
                    )}

                    <div className="flex justify-end pt-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowCetakUlang(false)}
                        >
                            Tutup
                        </Button>
                    </div>
                </div>
            </Modal>

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
                                setSantriEntries([]);
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
                                setSantriEntries([]);
                            }}
                            placeholder="Pilih Asrama"
                            options={filteredAsrama.map((a: any) => ({
                                value: a.id,
                                label: a.daerah?.kode
                                    ? `${a.daerah.kode.charAt(0)}.${a.nomor}`
                                    : `Asrama ${a.nomor}`,
                            }))}
                            disabled={!daerahId || !!editing}
                        />
                    </div>

                    {/* Santri (multi-entry, allow duplicates) */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Santri{' '}
                            <span className="text-xs text-muted-foreground">
                                (satu santri bisa ditambah berkali-kali)
                            </span>
                        </label>
                        {santriEntries.length > 0 && (
                            <div className="mb-3 space-y-2">
                                {santriEntries.map((s) => (
                                    <div
                                        key={s.uid}
                                        className="flex items-center gap-2 rounded-lg border p-2"
                                    >
                                        <span className="min-w-0 flex-1 truncate text-sm font-medium">
                                            {s.nama}
                                        </span>
                                        <Select
                                            value={s.daftar_pelanggaran_id}
                                            onChange={(e) =>
                                                updateSantriPelanggaran(
                                                    s.uid,
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Pilih"
                                            options={daftarPelanggaran.map(
                                                (d: any) => ({
                                                    value: d.id,
                                                    label: d.nama_pelanggaran,
                                                }),
                                            )}
                                            disabled={!!editing}
                                            className="min-w-[160px]"
                                        />
                                        {!editing && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    removeSantri(s.uid)
                                                }
                                                className="rounded-md p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                                            >
                                                <X className="h-4 w-4" />
                                            </button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                        {!editing && (
                            <div className="flex flex-col gap-2">
                                <div className="flex gap-2">
                                    <Select
                                        value={pendingsantri_id}
                                        onChange={(e) =>
                                            setPendingSantriId(e.target.value)
                                        }
                                        placeholder="Pilih Santri"
                                        options={filteredSantri.map(
                                            (s: any) => ({
                                                value: s.id,
                                                label: s.nama,
                                            }),
                                        )}
                                        disabled={!form.asrama_id}
                                        className="flex-1"
                                    />
                                    <Select
                                        value={pendingPelanggaranId}
                                        onChange={(e) =>
                                            setPendingPelanggaranId(
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Jenis Pelanggaran"
                                        options={daftarPelanggaran.map(
                                            (d: any) => ({
                                                value: d.id,
                                                label: d.nama_pelanggaran,
                                            }),
                                        )}
                                        className="min-w-[160px]"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={addSantri}
                                        disabled={
                                            !pendingsantri_id ||
                                            !pendingPelanggaranId
                                        }
                                    >
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </div>
                                {!form.asrama_id && (
                                    <p className="text-xs text-muted-foreground">
                                        Pilih daerah dan asrama terlebih dahulu
                                    </p>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Tanpa Nama (row-based, independent) */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Tanpa Nama
                        </label>
                        {anonymousEntries.length > 0 && (
                            <div className="mb-3 space-y-2">
                                {anonymousEntries.map((a) => (
                                    <div
                                        key={a.uid}
                                        className="flex items-center gap-2 rounded-lg border p-2"
                                    >
                                        <Input
                                            type="number"
                                            min={1}
                                            value={a.jumlah}
                                            onChange={(e) =>
                                                updateAnonymousJumlah(
                                                    a.uid,
                                                    e.target.value,
                                                )
                                            }
                                            className="w-20 shrink-0"
                                            disabled={!!editing}
                                        />
                                        <span className="text-xs text-muted-foreground">
                                            orang
                                        </span>
                                        <Select
                                            value={a.daftar_pelanggaran_id}
                                            onChange={(e) =>
                                                updateAnonymousPelanggaran(
                                                    a.uid,
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Jenis Pelanggaran"
                                            options={daftarPelanggaran.map(
                                                (d: any) => ({
                                                    value: d.id,
                                                    label: d.nama_pelanggaran,
                                                }),
                                            )}
                                            disabled={!!editing}
                                            className="min-w-[160px]"
                                        />
                                        {!editing && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    removeAnonymous(a.uid)
                                                }
                                                className="rounded-md p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                                            >
                                                <X className="h-4 w-4" />
                                            </button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                        {!editing && (
                            <div className="flex gap-2">
                                <Input
                                    type="number"
                                    min={1}
                                    value={pendingAnonJumlah}
                                    onChange={(e) =>
                                        setPendingAnonJumlah(e.target.value)
                                    }
                                    placeholder="Jumlah"
                                    className="w-20 shrink-0"
                                />
                                <Select
                                    value={pendingAnonPelanggaranId}
                                    onChange={(e) =>
                                        setPendingAnonPelanggaranId(
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Jenis Pelanggaran"
                                    options={daftarPelanggaran.map(
                                        (d: any) => ({
                                            value: d.id,
                                            label: d.nama_pelanggaran,
                                        }),
                                    )}
                                    className="min-w-[160px]"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={addAnonymous}
                                    disabled={
                                        !pendingAnonJumlah ||
                                        !pendingAnonPelanggaranId
                                    }
                                >
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </div>
                        )}
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
