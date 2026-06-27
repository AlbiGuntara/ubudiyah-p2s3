import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Badge } from '@/components/ui/badge';
import { Trash2, Plus, ArrowLeft } from 'lucide-react';

export default function PermissionsIndex() {
    const { permissions } = usePage<any>().props;
    const [showModal, setShowModal] = useState(false);
    const [formName, setFormName] = useState('');

    const submit = () => {
        router.post('/permissions', { name: formName }, {
            onSuccess: () => {
                setShowModal(false);
                setFormName('');
            },
        });
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus permission ini?')) {
            router.delete(`/permissions/${id}`);
        }
    };

    const allPermissions = Object.values(permissions).flat() as any[];

    const columns: Column<any>[] = [
        { key: 'no', label: '#', render: (_p: any, idx: number) => <span>{idx + 1}</span>, className: 'text-muted-foreground text-xs w-10' },
        { key: 'name', label: 'Permission', sortable: true, render: (p) => <span className="font-medium">{p.name}</span> },
        {
            key: 'guard_name',
            label: 'Guard',
            render: (p) => <Badge variant="secondary">{p.guard_name}</Badge>,
        },
        {
            key: 'aksi',
            label: 'Aksi',
            headClassName: 'text-right',
            className: 'text-right',
            render: (p) => (
                <div className="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" onClick={() => destroy(p.id)}>
                        <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Permissions" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="sm" onClick={() => router.get('/roles')}>
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Permissions</h1>
                            <p className="text-muted-foreground">Kelola permissions sistem</p>
                        </div>
                    </div>
                    <Button onClick={() => setShowModal(true)}>
                        <Plus className="h-4 w-4" />
                        Tambah Permission
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={allPermissions}
                    meta={{ current_page: 1, last_page: 1, total: allPermissions.length, from: 1, to: allPermissions.length }}
                    keyExtractor={(p) => p.id}
                    onPageChange={() => {}}
                />
            </div>

            <Modal
                open={showModal}
                onClose={() => setShowModal(false)}
                title="Tambah Permission"
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Nama Permission</label>
                        <Input
                            value={formName}
                            onChange={(e) => setFormName(e.target.value)}
                            placeholder="Contoh: daerah.create"
                        />
                        <p className="text-xs text-muted-foreground">
                            Gunakan format: resource.action (contoh: daerah.create, daerah.edit, daerah.delete)
                        </p>
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button variant="outline" onClick={() => setShowModal(false)}>Batal</Button>
                        <Button onClick={submit}>Tambah</Button>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
