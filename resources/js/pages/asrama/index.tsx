import { useState, FormEvent } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Modal } from '@/components/ui/modal';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Edit2, Trash2, Plus, Building2 } from 'lucide-react';

export default function AsramaIndex() {
    const { asrama, daerah, filters, errors, per_page, sort_column, sort_direction } = usePage<any>().props;
    const [perPage, setPerPage] = useState(parseInt(per_page) || 10);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [filterDaerah, setFilterDaerah] = useState(filters?.daerah_id || '');
    const [sortColumn, setSortColumn] = useState(sort_column || '');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>((sort_direction as any) || 'none');
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);

    const [form, setForm] = useState({ daerah_id: '', nomor: '' });

    const openCreate = () => {
        setEditing(null);
        setForm({ daerah_id: '', nomor: '' });
        setShowModal(true);
    };

    const openEdit = (a: any) => {
        setEditing(a);
        setForm({ daerah_id: a.daerah_id, nomor: a.nomor });
        setShowModal(true);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (editing) {
            router.put(`/asrama/${editing.id}`, form, {
                preserveScroll: true,
                onSuccess: () => setShowModal(false),
            });
        } else {
            router.post('/asrama', form, {
                preserveScroll: true,
                onSuccess: () => setShowModal(false),
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus asrama ini?')) {
            router.delete(`/asrama/${id}`);
        }
    };

    const bulkDelete = () => {
        if (confirm(`Yakin ingin menghapus ${selectedIds.length} asrama?`)) {
            router.post('/asrama/bulk-delete', { ids: selectedIds }, {
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
        router.get('/asrama', {
            sort_column: nextDir === 'none' ? undefined : column,
            sort_direction: nextDir === 'none' ? undefined : nextDir,
            daerah_id: filterDaerah || undefined,
            per_page: perPage,
        }, { preserveState: true, preserveScroll: true });
    };

    const columns: Column<any>[] = [
        { key: 'no', label: '#', render: (_a: any, idx: number) => <span>{asrama.from + idx}</span>, className: 'text-muted-foreground text-xs w-10' },
        { key: 'daerah', label: 'Daerah', sortable: true, render: (a) => a.daerah?.nama_daerah || '-' },
        { key: 'nomor', label: 'Nomor Asrama', sortable: true },
        { key: 'santri_count', label: 'Jumlah Santri', sortable: true, className: 'text-center' },
        {
            key: 'aksi',
            label: 'Aksi',
            headClassName: 'text-right',
            className: 'text-right',
            render: (a) => (
                <div className="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" onClick={() => openEdit(a)}>
                        <Edit2 className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" onClick={() => destroy(a.id)}>
                        <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Asrama" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Asrama</h1>
                        <p className="text-muted-foreground">Kelola data asrama pondok</p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Tambah Asrama
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={asrama.data}
                    meta={asrama}
                    keyExtractor={(a) => a.id}
                    onPageChange={(page) => router.get('/asrama', { page, daerah_id: filterDaerah || undefined, sort_column: sortColumn || undefined, sort_direction: sortDirection === 'none' ? undefined : sortDirection, per_page: perPage }, { preserveState: true, preserveScroll: true })}
                    sortColumn={sortColumn}
                    sortDirection={sortDirection}
                    onSort={handleSort}
                    perPage={perPage}
                    onPerPageChange={(p) => {
                        setPerPage(p);
                        if (p !== perPage) {
                            router.get('/asrama', { per_page: p, page: 1, daerah_id: filterDaerah || undefined, sort_column: sortColumn || undefined, sort_direction: sortDirection === 'none' ? undefined : sortDirection }, { preserveState: true, preserveScroll: true });
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
                    filters={
                        <>
                            <Select
                                value={filterDaerah}
                                onChange={(e) => setFilterDaerah(e.target.value)}
                                placeholder="Semua Daerah"
                                options={daerah.map((d: any) => ({ value: d.id, label: d.nama_daerah }))}
                                className="max-w-xs"
                            />
                            <Button variant="outline" size="sm" onClick={() => router.get('/asrama', { daerah_id: filterDaerah || undefined, per_page: perPage, sort_column: sortColumn || undefined, sort_direction: sortDirection === 'none' ? undefined : sortDirection }, { preserveState: true, preserveScroll: true })}>Filter</Button>
                        </>
                    }
                />

                </div>

            <Modal
                open={showModal}
                onClose={() => setShowModal(false)}
                title={editing ? 'Edit Asrama' : 'Tambah Asrama'}
            >
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Daerah</label>
                        <Select
                            value={form.daerah_id}
                            onChange={(e) => setForm({ ...form, daerah_id: e.target.value })}
                            placeholder="Pilih Daerah"
                            options={daerah.map((d: any) => ({ value: d.id, label: d.nama_daerah }))}
                        />
                        {errors.daerah_id && <p className="text-sm text-destructive">{errors.daerah_id}</p>}
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Nomor Asrama</label>
                        <Input
                            value={form.nomor}
                            onChange={(e) => setForm({ ...form, nomor: e.target.value })}
                            placeholder="Contoh: 01"
                        />
                        {errors.nomor && <p className="text-sm text-destructive">{errors.nomor}</p>}
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
