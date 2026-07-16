import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Modal } from '@/components/ui/modal';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Badge } from '@/components/ui/badge';
import { Edit2, Trash2, Plus, Shield, UserPlus } from 'lucide-react';

export default function UsersIndex() {
    const { users, roles } = usePage<any>().props;
    const [perPage, setPerPage] = useState(10);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [sortColumn, setSortColumn] = useState('');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>(
        'none',
    );
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);

    const [form, setForm] = useState({
        name: '',
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'petugas',
    });

    const openCreate = () => {
        setEditing(null);
        setForm({
            name: '',
            username: '',
            email: '',
            password: '',
            password_confirmation: '',
            role: 'petugas',
        });
        setShowModal(true);
    };

    const openEdit = (u: any) => {
        setEditing(u);
        setForm({
            name: u.name,
            username: u.username,
            email: u.email || '',
            password: '',
            password_confirmation: '',
            role: u.role,
        });
        setShowModal(true);
    };

    const submit = () => {
        const data = { ...form };
        if (editing && !data.password) {
            delete (data as any).password;
            delete (data as any).password_confirmation;
        }
        if (editing) {
            router.put(`/users/${editing.id}`, data, {
                onSuccess: () => setShowModal(false),
            });
        } else {
            router.post('/users', data, {
                onSuccess: () => setShowModal(false),
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus user ini?')) {
            router.delete(`/users/${id}`);
        }
    };

    const bulkDelete = () => {
        if (confirm(`Yakin ingin menghapus ${selectedIds.length} user?`)) {
            router.post(
                '/users/bulk-delete',
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
            '/users',
            {
                sort_column: nextDir === 'none' ? undefined : column,
                sort_direction: nextDir === 'none' ? undefined : nextDir,
                per_page: perPage,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const roleBadge = (role: string) => {
        const variants: Record<string, string> = {
            super_admin: 'destructive',
        };
        return (
            <Badge variant={(variants[role] || 'default') as any}>
                {role.replace('_', ' ')}
            </Badge>
        );
    };

    const columns: Column<any>[] = [
        {
            key: 'no',
            label: '#',
            render: (_u: any, idx: number) => <span>{users.from + idx}</span>,
            className: 'text-muted-foreground text-xs w-10',
        },
        {
            key: 'name',
            label: 'Nama',
            sortable: true,
            render: (u) => <span className="font-medium">{u.name}</span>,
        },
        { key: 'username', label: 'Username', sortable: true },
        { key: 'email', label: 'Email', render: (u) => u.email || '-' },
        {
            key: 'role',
            label: 'Role',
            sortable: true,
            render: (u) => roleBadge(u.role),
        },
        {
            key: 'aksi',
            label: 'Aksi',
            headClassName: 'text-right',
            className: 'text-right',
            render: (u) => (
                <div className="flex justify-end gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => openEdit(u)}
                    >
                        <Edit2 className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => destroy(u.id)}
                    >
                        <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Pengguna" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Pengguna
                        </h1>
                        <p className="text-muted-foreground">
                            Kelola pengguna sistem
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            onClick={() => router.get('/roles')}
                        >
                            <Shield className="h-4 w-4" />
                            Roles
                        </Button>
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" />
                            Tambah User
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={users.data}
                    meta={users}
                    keyExtractor={(u) => u.id}
                    onPageChange={(page) =>
                        router.get(
                            '/users',
                            {
                                page,
                                sort_column: sortColumn || undefined,
                                sort_direction:
                                    sortDirection === 'none'
                                        ? undefined
                                        : sortDirection,
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
                                '/users',
                                {
                                    per_page: p,
                                    page: 1,
                                    sort_column: sortColumn || undefined,
                                    sort_direction:
                                        sortDirection === 'none'
                                            ? undefined
                                            : sortDirection,
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
                />
            </div>

            <Modal
                open={showModal}
                onClose={() => setShowModal(false)}
                title={editing ? 'Edit User' : 'Tambah User'}
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Nama</label>
                        <Input
                            value={form.name}
                            onChange={(e) =>
                                setForm({ ...form, name: e.target.value })
                            }
                            placeholder="Nama lengkap"
                            required
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Username
                            </label>
                            <Input
                                value={form.username}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        username: e.target.value,
                                    })
                                }
                                placeholder="Username"
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Email</label>
                            <Input
                                type="email"
                                value={form.email}
                                onChange={(e) =>
                                    setForm({ ...form, email: e.target.value })
                                }
                                placeholder="Email (opsional)"
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Password{' '}
                                {editing && '(kosongkan jika tidak diubah)'}
                            </label>
                            <Input
                                type="password"
                                value={form.password}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        password: e.target.value,
                                    })
                                }
                                placeholder={
                                    editing ? 'Biarkan kosong' : 'Password'
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Konfirmasi Password
                            </label>
                            <Input
                                type="password"
                                value={form.password_confirmation}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        password_confirmation: e.target.value,
                                    })
                                }
                                placeholder="Konfirmasi password"
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Role</label>
                        <Select
                            value={form.role}
                            onChange={(e) =>
                                setForm({ ...form, role: e.target.value })
                            }
                        >
                            {roles.map((r: any) => (
                                <option key={r.id} value={r.name}>
                                    {r.name
                                        .replace('_', ' ')
                                        .replace(/\b\w/g, (c: string) =>
                                            c.toUpperCase(),
                                        )}
                                </option>
                            ))}
                        </Select>
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowModal(false)}
                        >
                            Batal
                        </Button>
                        <Button onClick={submit}>
                            {editing ? 'Simpan' : 'Tambah'}
                        </Button>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
