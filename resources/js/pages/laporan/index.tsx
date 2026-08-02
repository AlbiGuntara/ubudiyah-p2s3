import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Pagination } from '@/components/ui/pagination';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import {
    FileDown,
    Filter,
    Map,
    Building2,
    BookOpen,
    Users,
} from 'lucide-react';

const bulanList = Array.from({ length: 12 }, (_, i) => ({
    value: i + 1,
    label: new Date(2024, i).toLocaleString('id', { month: 'long' }),
}));

function buildExportUrl(section: string, filters: any): string {
    const p = new URLSearchParams();
    if (filters.bulan || filters.tahun) {
        if (filters.bulan) p.set('bulan', filters.bulan);
        if (filters.tahun) p.set('tahun', filters.tahun);
    }
    if (filters.tanggal_mulai) p.set('tanggal_mulai', filters.tanggal_mulai);
    if (filters.tanggal_selesai)
        p.set('tanggal_selesai', filters.tanggal_selesai);
    if (filters.daerah_id) p.set('daerah_id', filters.daerah_id);
    if (filters.asrama_id) p.set('asrama_id', filters.asrama_id);
    if (filters.iksass) p.set('iksass', filters.iksass);
    if (filters.sumber_pencatatan)
        p.set('sumber_pencatatan', filters.sumber_pencatatan);
    return `/export/excel/${section}?${p}`;
}

export default function LaporanIndex() {
    const { data, tahunTersedia, filters } = usePage<any>().props;

    const [modeTanggal, setModeTanggal] = useState<'bulanan' | 'rentang'>(
        filters?.tanggal_mulai ? 'rentang' : 'bulanan',
    );
    const [bulan, setBulan] = useState(filters?.bulan || '');
    const [tahun, setTahun] = useState(filters?.tahun || '');
    const [tanggalMulai, setTanggalMulai] = useState(
        filters?.tanggal_mulai || '',
    );
    const [tanggalSelesai, setTanggalSelesai] = useState(
        filters?.tanggal_selesai || '',
    );
    const [sumber, setSumber] = useState(filters?.sumber_pencatatan || '');
    const [activeTab, setActiveTab] = useState('per_daerah');

    const filter = () => {
        const params: any = {};
        if (modeTanggal === 'bulanan') {
            if (bulan) params.bulan = bulan;
            if (tahun) params.tahun = tahun;
        } else {
            if (tanggalMulai) params.tanggal_mulai = tanggalMulai;
            if (tanggalSelesai) params.tanggal_selesai = tanggalSelesai;
        }
        if (sumber) params.sumber_pencatatan = sumber;
        router.get('/laporan', params);
    };

    const currentFilters = {
        bulan,
        tahun,
        tanggal_mulai: tanggalMulai,
        tanggal_selesai: tanggalSelesai,
        sumber_pencatatan: sumber,
    };

    const tabs = [
        { key: 'per_daerah', label: 'Per Daerah', icon: Map },
        { key: 'per_asrama', label: 'Per Asrama', icon: Building2 },
        { key: 'per_jenis', label: 'Per Jenis', icon: BookOpen },
        { key: 'per_iksass', label: 'Per IKSASS', icon: Users },
    ];

    return (
        <AppLayout>
            <Head title="Laporan" />
            <div className="space-y-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Laporan
                        </h1>
                        <p className="text-muted-foreground">
                            Laporan pelanggaran lengkap
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        onClick={() =>
                            window.open(
                                '/export/pelanggaran-full/pdf',
                                '_blank',
                            )
                        }
                    >
                        <FileDown className="h-4 w-4" />
                        Export Full
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <div className="flex flex-wrap items-end gap-4">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Mode Tanggal
                                </label>
                                <Select
                                    value={modeTanggal}
                                    onChange={(e) =>
                                        setModeTanggal(e.target.value as any)
                                    }
                                    options={[
                                        {
                                            value: 'bulanan',
                                            label: 'Bulan/Tahun',
                                        },
                                        {
                                            value: 'rentang',
                                            label: 'Rentang Tanggal',
                                        },
                                    ]}
                                />
                            </div>

                            {modeTanggal === 'bulanan' ? (
                                <>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">
                                            Bulan
                                        </label>
                                        <Select
                                            value={bulan}
                                            onChange={(e) =>
                                                setBulan(e.target.value)
                                            }
                                            placeholder="Semua Bulan"
                                            options={bulanList}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">
                                            Tahun
                                        </label>
                                        <Select
                                            value={tahun}
                                            onChange={(e) =>
                                                setTahun(e.target.value)
                                            }
                                            placeholder="Semua Tahun"
                                            options={(tahunTersedia || []).map(
                                                (t: any) => ({
                                                    value: t,
                                                    label: String(t),
                                                }),
                                            )}
                                        />
                                    </div>
                                </>
                            ) : (
                                <>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">
                                            Tanggal Mulai
                                        </label>
                                        <Input
                                            type="date"
                                            value={tanggalMulai}
                                            onChange={(e) =>
                                                setTanggalMulai(e.target.value)
                                            }
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">
                                            Tanggal Selesai
                                        </label>
                                        <Input
                                            type="date"
                                            value={tanggalSelesai}
                                            onChange={(e) =>
                                                setTanggalSelesai(
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                </>
                            )}

                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Sumber
                                </label>
                                <Select
                                    value={sumber}
                                    onChange={(e) => setSumber(e.target.value)}
                                    options={[
                                        { value: '', label: 'Semua' },
                                        { value: 'petugas', label: 'Petugas' },
                                        {
                                            value: 'ketua_kamar',
                                            label: 'Ketua Kamar',
                                        },
                                    ]}
                                />
                            </div>
                            <Button onClick={filter}>
                                <Filter className="h-4 w-4" />
                                Tampilkan
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div className="border-b">
                    <div className="flex gap-1 overflow-x-auto">
                        {tabs.map((tab) => {
                            const Icon = tab.icon;
                            return (
                                <button
                                    key={tab.key}
                                    onClick={() => setActiveTab(tab.key)}
                                    className={`flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors ${
                                        activeTab === tab.key
                                            ? 'border-primary text-primary'
                                            : 'border-transparent text-muted-foreground hover:text-foreground'
                                    }`}
                                >
                                    <Icon className="h-4 w-4" />
                                    {tab.label}
                                </button>
                            );
                        })}
                    </div>
                </div>

                {activeTab === 'per_daerah' && (
                    <SectionTable
                        title="Pelanggaran Per Daerah"
                        headers={[
                            'No',
                            'Kode',
                            'Daerah',
                            'Jumlah Pelanggaran',
                            'Jumlah Pelanggar',
                            'Total Santri',
                        ]}
                        rows={(data?.per_daerah || []).map(
                            (d: any, i: number) => [
                                i + 1,
                                d.kode || '-',
                                d.nama_daerah,
                                <Badge key="jp" variant="default">
                                    {d.jumlah_pelanggaran}
                                </Badge>,
                                d.jumlah_santri,
                                d.total_santri,
                            ],
                        )}
                        exportSection="per_daerah"
                        filters={currentFilters}
                    />
                )}

                {activeTab === 'per_asrama' && (
                    <SectionTable
                        title="Pelanggaran Per Asrama"
                        headers={[
                            'No',
                            'Daerah',
                            'Asrama',
                            'Jumlah Pelanggaran',
                            'Jumlah Pelanggar',
                            'Total Santri',
                        ]}
                        rows={(data?.per_asrama || []).map(
                            (d: any, i: number) => [
                                i + 1,
                                d.nama_daerah,
                                d.daerah_kode
                                    ? `${d.daerah_kode.charAt(0)}.${d.nomor}`
                                    : d.nomor,
                                <Badge key="jp" variant="default">
                                    {d.jumlah_pelanggaran}
                                </Badge>,
                                d.jumlah_santri,
                                d.total_santri,
                            ],
                        )}
                        exportSection="per_asrama"
                        filters={currentFilters}
                    />
                )}

                {activeTab === 'per_jenis' && (
                    <SectionTable
                        title="Pelanggaran Per Jenis"
                        headers={[
                            'No',
                            'Jenis Pelanggaran',
                            'Jumlah Pelanggaran',
                            'Jumlah Santri',
                        ]}
                        rows={(data?.per_jenis_pelanggaran || []).map(
                            (d: any, i: number) => [
                                i + 1,
                                d.nama_pelanggaran,
                                <Badge key="jp" variant="default">
                                    {d.jumlah_pelanggaran}
                                </Badge>,
                                d.jumlah_santri,
                            ],
                        )}
                        exportSection="per_jenis_pelanggaran"
                        filters={currentFilters}
                    />
                )}

                {activeTab === 'per_iksass' && (
                    <SectionTable
                        title="Pelanggaran Per IKSASS"
                        headers={[
                            'No',
                            'IKSASS',
                            'Jumlah Pelanggaran',
                            'Jumlah Pelanggar',
                            'Total Santri',
                        ]}
                        rows={(data?.per_iksass || []).map(
                            (d: any, i: number) => [
                                i + 1,
                                d.iksass || '-',
                                <Badge key="jp" variant="default">
                                    {d.jumlah_pelanggaran}
                                </Badge>,
                                d.jumlah_santri,
                                d.total_santri,
                            ],
                        )}
                        exportSection="per_iksass"
                        filters={currentFilters}
                    />
                )}
            </div>
        </AppLayout>
    );
}

function SectionTable({
    title,
    headers,
    rows,
    exportSection,
    filters,
}: {
    title: string;
    headers: string[];
    rows: React.ReactNode[][];
    exportSection: string;
    filters: any;
}) {
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(10);

    const total = rows.length;
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const safePage = Math.min(page, lastPage);
    const from = total === 0 ? 0 : (safePage - 1) * perPage + 1;
    const to = Math.min(safePage * perPage, total);
    const pagedRows = rows.slice((safePage - 1) * perPage, safePage * perPage);

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle className="text-base">{title}</CardTitle>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() =>
                        window.open(
                            buildExportUrl(exportSection, filters),
                            '_blank',
                        )
                    }
                >
                    <FileDown className="h-4 w-4" />
                    Export Excel
                </Button>
            </CardHeader>
            <CardContent className="p-0">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                {headers.map((h, i) => (
                                    <th
                                        key={i}
                                        className="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    >
                                        {h}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {pagedRows.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={headers.length}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Tidak ada data
                                    </td>
                                </tr>
                            ) : (
                                pagedRows.map((row, ri) => (
                                    <tr
                                        key={ri}
                                        className="border-b last:border-0 hover:bg-muted/30"
                                    >
                                        {row.map((cell, ci) => (
                                            <td
                                                key={ci}
                                                className="px-4 py-3 text-sm"
                                            >
                                                {cell}
                                            </td>
                                        ))}
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="border-t px-4 py-3">
                    <Pagination
                        currentPage={safePage}
                        lastPage={lastPage}
                        total={total}
                        from={from}
                        to={to}
                        onPageChange={setPage}
                        perPage={perPage}
                        onPerPageChange={(v) => {
                            setPerPage(v);
                            setPage(1);
                        }}
                    />
                </div>
            </CardContent>
        </Card>
    );
}
