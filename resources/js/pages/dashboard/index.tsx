import { Head, usePage } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Calendar, Users, AlertTriangle, BookOpen,
    TrendingUp, MapPin, Home,
} from 'lucide-react';
import {
    BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip,
    ResponsiveContainer, PieChart, Pie, Cell, Legend,
} from 'recharts';

const CHART_COLORS = ['#16a34a', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899'];

function StatCard({ title, value, icon: Icon, description }: { title: string; value: string | number; icon: React.ElementType; description?: string }) {
    return (
        <Card>
            <CardContent className="p-6">
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <p className="text-sm text-muted-foreground">{title}</p>
                        <p className="text-3xl font-bold">{value}</p>
                        {description && <p className="text-xs text-muted-foreground">{description}</p>}
                    </div>
                    <div className="w-12 h-12 rounded-full bg-green-600/20 flex items-center justify-center">
                        <Icon className="h-6 w-6 text-green-600" />
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function BarChartCard({ title, labels, data, color = '#16a34a' }: { title: string; labels: string[]; data: number[]; color?: string }) {
    const chartData = labels.map((label, i) => ({ name: label, value: data[i] }));
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                {data.every((v) => v === 0) ? (
                    <p className="text-sm text-muted-foreground text-center py-8">Belum ada data</p>
                ) : (
                    <ResponsiveContainer width="100%" height={250}>
                        <BarChart data={chartData} barCategoryGap="20%">
                            <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" vertical={false} />
                            <XAxis dataKey="name" tick={{ fontSize: 12, fill: '#6b7280' }} axisLine={{ stroke: '#e5e7eb' }} tickLine={false} />
                            <YAxis tick={{ fontSize: 12, fill: '#6b7280' }} axisLine={false} tickLine={false} allowDecimals={false} />
                            <Tooltip contentStyle={{ backgroundColor: '#fff', border: '1px solid #e5e7eb', borderRadius: '8px', boxShadow: '0 2px 8px rgba(0,0,0,0.08)' }} cursor={{ fill: 'rgba(22, 163, 74, 0.08)' }} />
                            <Bar dataKey="value" fill={color} radius={[4, 4, 0, 0]} maxBarSize={48} />
                        </BarChart>
                    </ResponsiveContainer>
                )}
            </CardContent>
        </Card>
    );
}

function PieChartCard({ title, labels, data }: { title: string; labels: string[]; data: number[] }) {
    const chartData = labels
        .map((label, i) => ({ name: label, value: data[i] }))
        .filter((d) => d.value > 0);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                {chartData.length === 0 ? (
                    <p className="text-sm text-muted-foreground text-center py-8">Belum ada data</p>
                ) : (
                    <ResponsiveContainer width="100%" height={280}>
                        <PieChart>
                            <Pie
                                data={chartData}
                                cx="50%"
                                cy="50%"
                                innerRadius={60}
                                outerRadius={100}
                                paddingAngle={3}
                                dataKey="value"
                                label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                            >
                                {chartData.map((_, i) => (
                                    <Cell key={i} fill={CHART_COLORS[i % CHART_COLORS.length]} />
                                ))}
                            </Pie>
                            <Tooltip
                                contentStyle={{
                                    backgroundColor: '#fff',
                                    border: '1px solid #e5e7eb',
                                    borderRadius: '8px',
                                    boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
                                }}
                            />
                            <Legend />
                        </PieChart>
                    </ResponsiveContainer>
                )}
            </CardContent>
        </Card>
    );
}

function SimpleBarCard({ title, labels, data, color = '#16a34a' }: { title: string; labels: string[]; data: number[]; color?: string }) {
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
                                className="w-full rounded-t-md transition-all"
                                style={{ height: `${(data[i] / max) * 100}%`, minHeight: data[i] > 0 ? '4px' : '0', backgroundColor: color }}
                            />
                            <span className="text-[10px] text-muted-foreground text-center truncate w-full">
                                {label.length > 8 ? label.slice(0, 8) + '...' : label}
                            </span>
                        </div>
                    ))}
                </div>
                {data.length === 0 && <p className="text-sm text-muted-foreground text-center py-8">Belum ada data</p>}
            </CardContent>
        </Card>
    );
}

function TopList({ title, items, valueLabel }: { title: string; items: { nama?: string; nama_daerah?: string; label?: string; asrama_label?: string; total: number }[]; valueLabel: string }) {
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
                                    <div>
                                        <span className="text-sm">{item.label || item.nama_daerah || item.nama}</span>
                                        {item.asrama_label && <span className="text-xs text-muted-foreground ml-1">{item.asrama_label}</span>}
                                    </div>
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
    topDaerah: { nama_daerah: string; total: number }[];
    topAsrama: { label: string; total: number }[];
    topSantri: { nama: string; total: number; asrama_label: string }[];
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
                    <StatCard title="Pelanggaran Hari Ini" value={props.stats.pelanggaran_hari_ini} icon={Calendar} />
                    <StatCard title="Santri Melanggar Hari Ini" value={props.stats.santri_melanggar_hari_ini} icon={Users} />
                    <StatCard title="Pelanggaran Bulan Ini" value={props.stats.pelanggaran_bulan_ini} icon={AlertTriangle} />
                    <StatCard title="Santri Melanggar Bulan Ini" value={props.stats.santri_melanggar_bulan_ini} icon={BookOpen} />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <BarChartCard title="Grafik Pelanggaran Harian (7 Hari)" labels={props.harianChart.labels} data={props.harianChart.data} />
                    <PieChartCard title="Grafik Pelanggaran Bulanan (6 Bulan)" labels={props.bulananChart.labels} data={props.bulananChart.data} />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div className="lg:col-span-2">
                        <SimpleBarCard title="Pelanggaran Berdasarkan Daerah" labels={props.daerahChart.labels} data={props.daerahChart.data} color="#16a34a" />
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <TopList title="Top 10 Daerah" items={props.topDaerah} valueLabel="pelanggaran" />
                    <TopList title="Top 10 Asrama" items={props.topAsrama} valueLabel="pelanggaran" />
                    <TopList title="Top 10 Santri" items={props.topSantri} valueLabel="pelanggaran" />
                </div>
            </div>
        </AppLayout>
    );
}
