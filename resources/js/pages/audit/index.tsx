import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { Badge } from '@/components/ui/badge';
import { Shield } from 'lucide-react';

export default function AuditIndex() {
    const { logs } = usePage<any>().props;

    return (
        <AppLayout>
            <Head title="Audit Log" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Audit Log</h1>
                    <p className="text-muted-foreground">Riwayat aktivitas pengguna</p>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Waktu</TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Aktivitas</TableHead>
                                    <TableHead>Model</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {logs.data.map((log: any) => (
                                    <TableRow key={log.id}>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {new Date(log.created_at).toLocaleString('id')}
                                        </TableCell>
                                        <TableCell className="font-medium">{log.user?.name}</TableCell>
                                        <TableCell>
                                            <Badge variant={
                                                log.aktivitas.startsWith('menambahkan') ? 'success' :
                                                log.aktivitas.startsWith('mengubah') ? 'warning' : 'destructive'
                                            }>
                                                {log.aktivitas}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {log.model_type?.split('\\').pop()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {logs.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-center text-muted-foreground">
                                            Belum ada aktivitas
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        <Pagination
                            currentPage={logs.current_page}
                            lastPage={logs.last_page}
                            total={logs.total}
                            from={logs.from}
                            to={logs.to}
                            onPageChange={(page) => router.get('/audit', { page })}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
