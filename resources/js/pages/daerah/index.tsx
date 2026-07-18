import { useState, FormEvent } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Modal } from '@/components/ui/modal';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Edit2, Trash2, Plus, Map } from 'lucide-react';

interface Daerah {
    id: number;
    kode: string;
    nama_daerah: string;
    asrama_count: number;
}

export default function DaerahIndex() {
    const { daerah, errors, search: searchParam, per_page, sort_column, sort_direction } = usePage<{ daerah: { data: Daerah[]; current_page: number; last_page: number; total: number; from: number; to: number }; errors: Record<string, string>; search?: string; per_page?: string; sort_column?: string; sort_direction?: string }>().props;
    const [perPage, setPerPage] = useState(parseInt(per_page || '') || 10);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Daerah | null>(null);
    const [search, setSearch] = useState(searchParam || '');
    const [sortColumn, setSortColumn] = useState(sort_column || '');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>((sort_direction as any) || 'none');
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);

    const [form, setForm] = useState({ kode: '', nama_daerah: '' });

    const openCreate = () => {
        setEditing(null);
        setForm({ kode: '', nama_daerah: '' });
        setShowModal(true);
    };

    const openEdit = (d: Daerah) => {
        setEditing(d);
        setForm({ kode: d.kode, nama_daerah: d.nama_daerah });
        setShowModal(true);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (editing) {
            router.put(`/daerah/${editing.id}`, form, {
                preserveScroll: true,
                onSuccess: () => { setShowModal(false); },
            });
        } else {
            router.post('/daerah', form, {
                preserveScroll: true,
                onSuccess: () => { setShowModal(false); },
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus daerah ini?')) {
            router.delete(`/daerah/${id}`);
        }
    };

    const bulkDelete = () => {
        if (confirm(`Yakin ingin menghapus ${selectedIds.length} daerah?`)) {
            router.post('/daerah/bulk-delete', { ids: selectedIds }, {
                onSuccess: () => setSelectedIds([]),
            });
        }
    };

    const handleSort = (column: string) => {
        let nextDir: 'asc' | 'desc' | 'none' = 'asc';
        if (sortColumn === column) {
            nextDir = sortDirection === 'none' ? 'asc' : sortDirection === 'asc' ? 'desc' : 'none';
        }
        setSortColumn(nextDir === 'none' ? '' : column);
        setSortDirection(nextDir);
        router.get('/daerah', {
            sort_column: nextDir === 'none' ? undefined : column,
            sort_direction: nextDir === 'none' ? undefined : nextDir,
            search: search || undefined,
            per_page: perPage,
        }, { preserveState: true, preserveScroll: true });
    };

    const columns: Column<Daerah>[] = [
        { key: 'no', label: '#', render: (_item: any, idx: number) => <span>{daerah.from + idx}</span>, className: 'text-muted-foreground text-xs w-10' },
        { key: 'kode', label: 'Kode', sortable: true },
        { key: 'nama_daerah', label: 'Nama Daerah', sortable: true },
        { key: 'asrama_count', label: 'Jumlah Asrama', sortable: true, className: 'text-center' },
        {
            key: 'aksi',
            label: 'Aksi',
            headClassName: 'text-right',
            className: 'text-right',
            render: (d) => (
                <div className="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" onClick={() => openEdit(d)}>
                        <Edit2 className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" onClick={() => destroy(d.id)}>
                        <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Daerah" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Daerah</h1>
                        <p className="text-muted-foreground">Kelola data daerah pondok</p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Tambah Daerah
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={daerah.data}
                    meta={daerah}
                    keyExtractor={(d) => d.id}
                    onPageChange={(page) => router.get('/daerah', { page, per_page: perPage, search: search || undefined, sort_column: sortColumn || undefined, sort_direction: sortDirection === 'none' ? undefined : sortDirection }, { preserveState: true, preserveScroll: true })}
                    search={search}
                    onSearchChange={(q) => {
                        setSearch(q);
                        router.get('/daerah', { search: q || undefined, page: 1, per_page: perPage, sort_column: sortColumn || undefined, sort_direction: sortDirection === 'none' ? undefined : sortDirection }, { preserveState: true, preserveScroll: true });
                    }}
                    searchPlaceholder="Cari daerah..."
                    sortColumn={sortColumn}
                    sortDirection={sortDirection}
                    onSort={handleSort}
                    perPage={perPage}
                    onPerPageChange={(p) => {
                        setPerPage(p);
                        if (p !== perPage) {
                            router.get('/daerah', { per_page: p, page: 1, search: search || undefined, sort_column: sortColumn || undefined, sort_direction: sortDirection === 'none' ? undefined : sortDirection }, { preserveState: true, preserveScroll: true });
                        }
                    }}
                    onSelectionChange={setSelectedIds}
                    bulkActions={
                        selectedIds.length > 0 && (
                            <Button variant="destructive" size="sm" onClick={bulkDelete}>
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
                title={editing ? 'Edit Daerah' : 'Tambah Daerah'}
            >
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Kode</label>
                        <Input
                            value={form.kode}
                            onChange={(e) => setForm({ ...form, kode: e.target.value })}
                            placeholder="Contoh: A"
                        />
                        {errors.kode && <p className="text-sm text-destructive">{errors.kode}</p>}
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Nama Daerah</label>
                        <Input
                            value={form.nama_daerah}
                            onChange={(e) => setForm({ ...form, nama_daerah: e.target.value })}
                            placeholder="Contoh: Daerah A"
                        />
                        {errors.nama_daerah && <p className="text-sm text-destructive">{errors.nama_daerah}</p>}
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button type="button" variant="outline" onClick={() => setShowModal(false)}>Batal</Button>
                        <Button type="submit">{editing ? 'Simpan' : 'Tambah'}</Button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
