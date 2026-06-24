import { Head, usePage } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Calendar, Users, AlertTriangle, BookOpen, TrendingUp, MapPin, Home } from 'lucide-react';

interface StatCardProps {
    title: string;
    value: string | number;
    icon: React.ElementType;
    description?: string;
}

function StatCard({ title, value, icon: Icon, description }: StatCardProps) {
    return (
        <Card>
            <CardContent className="p-6">
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <p className="text-sm text-muted-foreground">{title}</p>
                        <p className="text-3xl font-bold">{value}</p>
                        {description && (
                            <p className="text-xs text-muted-foreground">{description}</p>
                        )}
                    </div>
                    <div className="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <Icon className="h-6 w-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

interface ChartProps {
    title: string;
    labels: string[];
    data: number[];
}

function BarChart({ title, labels, data }: ChartProps) {
    const max = Math.max(...data, 1);
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="flex items-end gap-2 h-40">
                    {labels.map((label, i) => (
                        <div key={i} className="flex-1 flex flex-col items-center gap-1">
                            <span className="text-xs font-medium">{data[i]}</span>
                            <div
                                className="w-full rounded-t-md bg-green-500 transition-all hover:bg-green-600"
                                style={{ height: `${(data[i] / max) * 100}%`, minHeight: data[i] > 0 ? '4px' : '0' }}
                            />
                            <span className="text-[10px] text-muted-foreground text-center truncate w-full">
                                {label.length > 8 ? label.slice(0, 8) + '...' : label}
                            </span>
                        </div>
                    ))}
                </div>
                {data.length === 0 && (
                    <p className="text-sm text-muted-foreground text-center py-8">Belum ada data</p>
                )}
            </CardContent>
        </Card>
    );
}

interface TopListProps {
    title: string;
    items: { nama?: string; nama_daerah?: string; nomor?: string; total: number }[];
    valueLabel: string;
}

function TopList({ title, items, valueLabel }: TopListProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                {items.length === 0 ? (
                    <p className="text-sm text-muted-foreground text-center py-4">Belum ada data</p>
                ) : (
                    <div className="space-y-3">
                        {items.map((item, i) => (
                            <div key={i} className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <span className={`w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold ${i < 3 ? 'bg-green-600 text-white' : 'bg-muted text-muted-foreground'}`}>
                                        {i + 1}
                                    </span>
                                    <span className="text-sm">{item.nama || item.nama_daerah || `Asrama ${item.nomor}`}</span>
                                </div>
                                <Badge variant="secondary">{item.total} {valueLabel}</Badge>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

interface PageProps {
    stats: {
        pelanggaran_hari_ini: number;
        santri_melanggar_hari_ini: number;
        pelanggaran_bulan_ini: number;
        santri_melanggar_bulan_ini: number;
    };
    harianChart: { labels: string[]; data: number[] };
    bulananChart: { labels: string[]; data: number[] };
    daerahChart: { labels: string[]; data: number[] };
    asramaChart: { labels: string[]; data: number[] };
    topDaerah: { nama_daerah: string; total: number }[];
    topAsrama: { nomor: string; total: number }[];
    topSantri: { nama: string; nis: string; total: number; total_shalawat: number }[];
}

export default function Dashboard(props: PageProps) {
    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Dashboard</h1>
                    <p className="text-muted-foreground">Overview pelanggaran ubudiyah</p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <StatCard
                        title="Pelanggaran Hari Ini"
                        value={props.stats.pelanggaran_hari_ini}
                        icon={Calendar}
                    />
                    <StatCard
                        title="Santri Melanggar Hari Ini"
                        value={props.stats.santri_melanggar_hari_ini}
                        icon={Users}
                    />
                    <StatCard
                        title="Pelanggaran Bulan Ini"
                        value={props.stats.pelanggaran_bulan_ini}
                        icon={AlertTriangle}
                    />
                    <StatCard
                        title="Santri Melanggar Bulan Ini"
                        value={props.stats.santri_melanggar_bulan_ini}
                        icon={BookOpen}
                    />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <BarChart
                        title="Grafik Pelanggaran Harian (7 Hari)"
                        labels={props.harianChart.labels}
                        data={props.harianChart.data}
                    />
                    <BarChart
                        title="Grafik Pelanggaran Bulanan (6 Bulan)"
                        labels={props.bulananChart.labels}
                        data={props.bulananChart.data}
                    />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <BarChart
                        title="Pelanggaran Berdasarkan Daerah"
                        labels={props.daerahChart.labels}
                        data={props.daerahChart.data}
                    />
                    <BarChart
                        title="Pelanggaran Berdasarkan Asrama"
                        labels={props.asramaChart.labels}
                        data={props.asramaChart.data}
                    />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <TopList
                        title="Top 10 Daerah"
                        items={props.topDaerah}
                        valueLabel="pelanggaran"
                    />
                    <TopList
                        title="Top 10 Asrama"
                        items={props.topAsrama}
                        valueLabel="pelanggaran"
                    />
                    <TopList
                        title="Top 10 Santri"
                        items={props.topSantri}
                        valueLabel="pelanggaran"
                    />
                </div>
            </div>
        </AppLayout>
    );
}
