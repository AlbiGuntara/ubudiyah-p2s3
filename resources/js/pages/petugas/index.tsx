import { useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Camera, Edit2, Plus, Shield, Trash2 } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { DataTable } from '@/components/shared/data-table';
import type { Column } from '@/components/shared/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { Select } from '@/components/ui/select';

interface Petugas {
    id: number;
    name: string;
    username: string;
    email: string | null;
    role: string;
    foto: string | null;
    jabatan: string | null;
    tugas: string | null;
}

interface PageProps extends Record<string, unknown> {
    users: {
        data: Petugas[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
    };
    roles: { id: number; name: string }[];
    per_page?: string;
    sort_column?: string;
    sort_direction?: string;
}

const kosong = {
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'petugas',
    jabatan: '',
    tugas: '',
};

export default function PetugasIndex() {
    const { users, roles, per_page, sort_column, sort_direction } =
        usePage<PageProps>().props;
    const [perPage, setPerPage] = useState(parseInt(per_page || '') || 10);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Petugas | null>(null);
    const [sortColumn, setSortColumn] = useState(sort_column || '');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>(
        (sort_direction as 'asc' | 'desc' | 'none') || 'none',
    );
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);
    const [foto, setFoto] = useState<File | null>(null);
    const [fotoPreview, setFotoPreview] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const [form, setForm] = useState(kosong);

    const openCreate = () => {
        setEditing(null);
        setForm(kosong);
        setFoto(null);
        setFotoPreview(null);
        setShowModal(true);
    };

    const openEdit = (u: Petugas) => {
        setEditing(u);
        setForm({
            name: u.name,
            username: u.username,
            email: u.email || '',
            password: '',
            password_confirmation: '',
            role: u.role,
            jabatan: u.jabatan || '',
            tugas: u.tugas || '',
        });
        setFoto(null);
        setFotoPreview(u.foto ? `/storage/${u.foto}` : null);
        setShowModal(true);
    };

    const handleFotoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setFoto(file);
            setFotoPreview(URL.createObjectURL(file));
        }
    };

    const submit = () => {
        const data = new FormData();
        data.append('name', form.name);
        data.append('username', form.username);
        data.append('email', form.email);
        data.append('role', form.role);
        data.append('jabatan', form.jabatan);
        data.append('tugas', form.tugas);
        if (form.password) {
            data.append('password', form.password);
            data.append('password_confirmation', form.password_confirmation);
        }
        if (foto) {
            data.append('foto', foto);
        }

        const onSuccess = () => {
            setShowModal(false);
            setFoto(null);
            setFotoPreview(null);
        };

        if (editing) {
            data.append('_method', 'PUT');
            router.post(`/petugas/${editing.id}`, data, { onSuccess });
        } else {
            router.post('/petugas', data, { onSuccess });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus petugas ini?')) {
            router.delete(`/petugas/${id}`);
        }
    };

    const bulkDelete = () => {
        if (confirm(`Yakin ingin menghapus ${selectedIds.length} petugas?`)) {
            router.post(
                '/petugas/bulk-delete',
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
            '/petugas',
            {
                sort_column: nextDir === 'none' ? undefined : column,
                sort_direction: nextDir === 'none' ? undefined : nextDir,
                per_page: perPage,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const roleBadge = (role: string) => (
        <Badge
            variant={
                (role === 'super_admin' ? 'destructive' : 'default') as any
            }
        >
            {role.replace('_', ' ')}
        </Badge>
    );

    const columns: Column<Petugas>[] = [
        {
            key: 'no',
            label: '#',
            render: (_u: Petugas, idx: number) => (
                <span>{users.from + idx}</span>
            ),
            className: 'text-muted-foreground text-xs w-10',
        },
        {
            key: 'name',
            label: 'Petugas',
            sortable: true,
            render: (u) => (
                <div className="flex items-center gap-3">
                    {u.foto ? (
                        <img
                            src={`/storage/${u.foto}`}
                            alt={u.name}
                            className="h-9 w-9 rounded-full border object-cover"
                        />
                    ) : (
                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-muted">
                            <Camera className="h-4 w-4 text-muted-foreground" />
                        </div>
                    )}
                    <span className="font-medium">{u.name}</span>
                </div>
            ),
        },
        { key: 'username', label: 'Username', sortable: true },
        {
            key: 'jabatan',
            label: 'Jabatan',
            sortable: true,
            render: (u) => u.jabatan || '-',
        },
        {
            key: 'tugas',
            label: 'Tugas',
            render: (u) => u.tugas || '-',
        },
        {
            key: 'email',
            label: 'Email',
            render: (u) => u.email || '-',
        },
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
            <Head title="Petugas" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Petugas
                        </h1>
                        <p className="text-muted-foreground">
                            Kelola data petugas ubudiyah
                        </p>
                    </div>
                    <div className="flex flex-wrap justify-end gap-2">
                        <Button
                            variant="outline"
                            onClick={() => router.get('/roles')}
                        >
                            <Shield className="h-4 w-4" />
                            Roles
                        </Button>
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" />
                            Tambah Petugas
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
                            '/petugas',
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
                                '/petugas',
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
                title={editing ? 'Edit Petugas' : 'Tambah Petugas'}
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Foto</label>
                        <div className="flex items-center gap-4">
                            {fotoPreview ? (
                                <img
                                    src={fotoPreview}
                                    alt="Preview"
                                    className="h-16 w-16 rounded-full border object-cover"
                                />
                            ) : (
                                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                                    <Camera className="h-6 w-6 text-muted-foreground" />
                                </div>
                            )}
                            <div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        fileInputRef.current?.click()
                                    }
                                >
                                    {fotoPreview ? 'Ganti Foto' : 'Pilih Foto'}
                                </Button>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg"
                                    className="hidden"
                                    onChange={handleFotoChange}
                                />
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Format: JPEG/PNG, Maks: 2MB
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Jabatan
                            </label>
                            <Input
                                value={form.jabatan}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        jabatan: e.target.value,
                                    })
                                }
                                placeholder="Jabatan"
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">Tugas</label>
                            <Input
                                value={form.tugas}
                                onChange={(e) =>
                                    setForm({ ...form, tugas: e.target.value })
                                }
                                placeholder="Tugas"
                            />
                        </div>
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

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                            {roles.map((r) => (
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
