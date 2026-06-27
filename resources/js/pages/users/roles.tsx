import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Badge } from '@/components/ui/badge';
import { Edit2, Trash2, Plus, ArrowLeft, Shield } from 'lucide-react';

export default function RolesIndex() {
    const { roles, permissions } = usePage<any>().props;
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [form, setForm] = useState({ name: '', permission_ids: [] as number[] });

    const openCreate = () => {
        setEditing(null);
        setForm({ name: '', permission_ids: [] });
        setShowModal(true);
    };

    const openEdit = (r: any) => {
        setEditing(r);
        setForm({ name: r.name, permission_ids: r.permissions?.map((p: any) => p.id) || [] });
        setShowModal(true);
    };

    const togglePermission = (permId: number) => {
        setForm((prev) => ({
            ...prev,
            permission_ids: prev.permission_ids.includes(permId)
                ? prev.permission_ids.filter((id) => id !== permId)
                : [...prev.permission_ids, permId],
        }));
    };

    const selectAllPermissions = () => {
        const allIds = Object.values(permissions).flat().map((p: any) => p.id);
        setForm((prev) => ({
            ...prev,
            permission_ids: prev.permission_ids.length === allIds.length ? [] : allIds,
        }));
    };

    const submit = () => {
        const data = { name: form.name, permissions: form.permission_ids };
        if (editing) {
            router.put(`/roles/${editing.id}`, data, {
                onSuccess: () => setShowModal(false),
            });
        } else {
            router.post('/roles', data, {
                onSuccess: () => setShowModal(false),
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus role ini?')) {
            router.delete(`/roles/${id}`);
        }
    };

    const columns: Column<any>[] = [
        { key: 'no', label: '#', render: (_r: any, idx: number) => <span>{idx + 1}</span>, className: 'text-muted-foreground text-xs w-10' },
        { key: 'name', label: 'Nama Role', sortable: true, render: (r) => <span className="font-medium capitalize">{r.name}</span> },
        {
            key: 'permissions',
            label: 'Permissions',
            render: (r) => (
                <div className="flex flex-wrap gap-1">
                    {r.permissions?.length > 0
                        ? r.permissions.slice(0, 5).map((p: any) => (
                            <Badge key={p.id} variant="secondary" className="text-xs">{p.name}</Badge>
                        ))
                        : <span className="text-muted-foreground text-sm">-</span>
                    }
                    {(r.permissions?.length || 0) > 5 && (
                        <Badge variant="secondary" className="text-xs">+{r.permissions.length - 5}</Badge>
                    )}
                </div>
            ),
        },
        {
            key: 'aksi',
            label: 'Aksi',
            headClassName: 'text-right',
            className: 'text-right',
            render: (r) => (
                <div className="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" onClick={() => openEdit(r)}>
                        <Edit2 className="h-4 w-4" />
                    </Button>
                    {r.name !== 'super_admin' && (
                        <Button variant="ghost" size="sm" onClick={() => destroy(r.id)}>
                            <Trash2 className="h-4 w-4 text-red-500" />
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Roles" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="sm" onClick={() => router.get('/users')}>
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Roles</h1>
                            <p className="text-muted-foreground">Kelola roles dan hak akses</p>
                        </div>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Tambah Role
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={roles}
                    meta={{ current_page: 1, last_page: 1, total: roles.length, from: 1, to: roles.length }}
                    keyExtractor={(r) => r.id}
                    onPageChange={() => {}}
                />
            </div>

            <Modal
                open={showModal}
                onClose={() => setShowModal(false)}
                title={editing ? 'Edit Role' : 'Tambah Role'}
                maxWidth="2xl"
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Nama Role</label>
                        <Input
                            value={form.name}
                            onChange={(e) => setForm({ ...form, name: e.target.value })}
                            placeholder="Contoh: petugas"
                            required
                        />
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <label className="text-sm font-medium">Permissions</label>
                            <button
                                type="button"
                                onClick={selectAllPermissions}
                                className="text-xs text-primary hover:underline"
                            >
                                {Object.values(permissions).flat().length === form.permission_ids.length
                                    ? 'Unselect All'
                                    : 'Select All'}
                            </button>
                        </div>
                        <div className="max-h-64 overflow-y-auto rounded-md border p-3 space-y-3">
                            {Object.entries(permissions).map(([group, perms]: [string, any]) => (
                                <div key={group}>
                                    <p className="text-xs font-semibold uppercase text-muted-foreground mb-1">{group}</p>
                                    <div className="flex flex-wrap gap-2">
                                        {perms.map((perm: any) => (
                                            <label
                                                key={perm.id}
                                                className={`flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-xs cursor-pointer transition-colors ${
                                                    form.permission_ids.includes(perm.id)
                                                        ? 'bg-primary text-primary-foreground border-primary'
                                                        : 'hover:bg-accent'
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={form.permission_ids.includes(perm.id)}
                                                    onChange={() => togglePermission(perm.id)}
                                                    className="sr-only"
                                                />
                                                {perm.name}
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 pt-2">
                        <Button variant="outline" onClick={() => setShowModal(false)}>Batal</Button>
                        <Button onClick={submit}>{editing ? 'Simpan' : 'Tambah'}</Button>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
