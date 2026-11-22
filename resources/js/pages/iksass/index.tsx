import { Head, router, usePage } from '@inertiajs/react';
import { Edit2, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { DataTable } from '@/components/shared/data-table';
import type { Column } from '@/components/shared/data-table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';

interface Iksass {
    id: number;
    kode: string;
    nama_iksass: string;
    santri_count: number;
}

export default function IksassIndex() {
    const {
        iksass,
        errors,
        search: searchParam,
        per_page,
        sort_column,
        sort_direction,
    } = usePage<{
        iksass: {
            data: Iksass[];
            current_page: number;
            last_page: number;
            total: number;
            from: number;
            to: number;
        };
        errors: Record<string, string>;
        search?: string;
        per_page?: string;
        sort_column?: string;
        sort_direction?: string;
    }>().props;
    const [perPage, setPerPage] = useState(parseInt(per_page || '') || 10);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Iksass | null>(null);
    const [search, setSearch] = useState(searchParam || '');
    const [sortColumn, setSortColumn] = useState(sort_column || '');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>(
        (sort_direction as any) || 'none',
    );
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);

    const [form, setForm] = useState({ kode: '', nama_iksass: '' });

    const openCreate = () => {
        setEditing(null);
        setForm({ kode: '', nama_iksass: '' });
        setShowModal(true);
    };

    const openEdit = (d: Iksass) => {
        setEditing(d);
        setForm({ kode: d.kode, nama_iksass: d.nama_iksass });
        setShowModal(true);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();

        if (editing) {
            router.put(`/iksass/${editing.id}`, form, {
                preserveScroll: true,
                onSuccess: () => {
                    setShowModal(false);
                },
            });
        } else {
            router.post('/iksass', form, {
                preserveScroll: true,
                onSuccess: () => {
                    setShowModal(false);
                },
            });
        }
    };

    const destroy = (id: number) => {
        if (
            confirm(
                'Yakin ingin menghapus IKSASS ini? Santri yang terkait tidak akan dihapus.',
            )
        ) {
            router.delete(`/iksass/${id}`);
        }
    };

    const bulkDelete = () => {
        if (
            confirm(
                `Yakin ingin menghapus ${selectedIds.length} IKSASS? Santri yang terkait tidak akan dihapus.`,
            )
        ) {
            router.post(
                '/iksass/bulk-delete',
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
            '/iksass',
            {
                sort_column: nextDir === 'none' ? undefined : column,
                sort_direction: nextDir === 'none' ? undefined : nextDir,
                search: search || undefined,
                per_page: perPage,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const columns: Column<Iksass>[] = [
        {
            key: 'no',
            label: '#',
            render: (_item: any, idx: number) => (
                <span>{iksass.from + idx}</span>
            ),
            className: 'text-muted-foreground text-xs w-10',
        },
        { key: 'kode', label: 'Kode', sortable: true },
        { key: 'nama_iksass', label: 'Nama IKSASS', sortable: true },
        {
            key: 'santri_count',
            label: 'Jumlah Santri',
            sortable: true,
            className: 'text-center',
        },
        {
            key: 'aksi',
            label: 'Aksi',
            headClassName: 'text-right',
            className: 'text-right',
            render: (d) => (
                <div className="flex justify-end gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => openEdit(d)}
                    >
                        <Edit2 className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => destroy(d.id)}
                    >
                        <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="IKSASS" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            IKSASS
                        </h1>
                        <p className="text-muted-foreground">
                            Kelola data asal daerah santri (IKSASS)
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Tambah IKSASS
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={iksass.data}
                    meta={iksass}
                    keyExtractor={(d) => d.id}
                    onPageChange={(page) =>
                        router.get(
                            '/iksass',
                            {
                                page,
                                per_page: perPage,
                                search: search || undefined,
                                sort_column: sortColumn || undefined,
                                sort_direction:
                                    sortDirection === 'none'
                                        ? undefined
                                        : sortDirection,
                            },
                            { preserveState: true, preserveScroll: true },
                        )
                    }
                    search={search}
                    onSearchChange={(q) => {
                        setSearch(q);
                        router.get(
                            '/iksass',
                            {
                                search: q || undefined,
                                page: 1,
                                per_page: perPage,
                                sort_column: sortColumn || undefined,
                                sort_direction:
                                    sortDirection === 'none'
                                        ? undefined
                                        : sortDirection,
                            },
                            { preserveState: true, preserveScroll: true },
                        );
                    }}
                    searchPlaceholder="Cari kode atau nama IKSASS..."
                    sortColumn={sortColumn}
                    sortDirection={sortDirection}
                    onSort={handleSort}
                    perPage={perPage}
                    onPerPageChange={(p) => {
                        setPerPage(p);

                        if (p !== perPage) {
                            router.get(
                                '/iksass',
                                {
                                    per_page: p,
                                    page: 1,
                                    search: search || undefined,
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
                title={editing ? 'Edit IKSASS' : 'Tambah IKSASS'}
            >
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Kode</label>
                        <Input
                            value={form.kode}
                            onChange={(e) =>
                                setForm({ ...form, kode: e.target.value })
                            }
                            placeholder="Contoh: SIT"
                        />
                        {errors.kode && (
                            <p className="text-sm text-destructive">
                                {errors.kode}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Nama IKSASS
                        </label>
                        <Input
                            value={form.nama_iksass}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    nama_iksass: e.target.value,
                                })
                            }
                            placeholder="Contoh: Situbondo"
                        />
                        {errors.nama_iksass && (
                            <p className="text-sm text-destructive">
                                {errors.nama_iksass}
                            </p>
                        )}
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setShowModal(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit">
                            {editing ? 'Simpan' : 'Tambah'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
