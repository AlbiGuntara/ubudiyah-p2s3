import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Modal } from '@/components/ui/modal';
import { Badge } from '@/components/ui/badge';
import { DataTable, type Column } from '@/components/shared/data-table';
import { Edit2, Trash2, Plus } from 'lucide-react';

export default function DaftarPelanggaranIndex() {
    const { daftarPelanggaran } = usePage<any>().props;
    const [perPage, setPerPage] = useState(10);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [search, setSearch] = useState('');
    const [sortColumn, setSortColumn] = useState('');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>('none');
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);
    const [form, setForm] = useState({ nama_pelanggaran: '', poin: '1' });

    const openCreate = () => {
        setEditing(null);
        setForm({ nama_pelanggaran: '', poin: '1' });
        setShowModal(true);
    };

    const openEdit = (d: any) => {
        setEditing(d);
        setForm({ nama_pelanggaran: d.nama_pelanggaran, poin: String(d.poin) });
        setShowModal(true);
    };

    const submit = () => {
        const data = { ...form, poin: parseInt(form.poin) };
        if (editing) {
            router.put(`/daftar-pelanggaran/${editing.id}`, data, {
                onSuccess: () => setShowModal(false),
            });
        } else {
            router.post('/daftar-pelanggaran', data, {
                onSuccess: () => setShowModal(false),
            });
        }
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus jenis pelanggaran ini?')) {
            router.delete(`/daftar-pelanggaran/${id}`);
        }
    };

    const bulkDelete = () => {
        if (confirm(`Yakin ingin menghapus ${selectedIds.length} jenis pelanggaran?`)) {
            router.post('/daftar-pelanggaran/bulk-delete', { ids: selectedIds }, {
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
        router.get('/daftar-pelanggaran', {
            sort_column: nextDir === 'none' ? undefined : column,
            sort_direction: nextDir === 'none' ? undefined : nextDir,
            search: search || undefined,
            per_page: perPage,
        }, { preserveState: true, preserveScroll: true });
    };

    const columns: Column<any>[] = [
        { key: 'no', label: '#', render: (_d: any, idx: number) => <span>{daftarPelanggaran.from + idx}</span>, className: 'text-muted-foreground text-xs w-10' },
        { key: 'nama_pelanggaran', label: 'Nama Pelanggaran', sortable: true },
        { key: 'poin', label: 'Poin', sortable: true, render: (d) => <Badge>{d.poin} Poin</Badge> },
        { key: 'pelanggaran_count', label: 'Digunakan', sortable: true, render: (d) => `${d.pelanggaran_count} kali` },
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
            <Head title="Jenis Pelanggaran" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Jenis Pelanggaran</h1>
                        <p className="text-muted-foreground">Kelola daftar jenis pelanggaran ubudiyah</p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="h-4 w-4" />
                        Tambah Pelanggaran
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={daftarPelanggaran.data}
                    meta={daftarPelanggaran}
                    keyExtractor={(d) => d.id}
                    onPageChange={(page) => router.get('/daftar-pelanggaran', { page, search: search || undefined, sort_column: sortColumn || undefined, sort_direction: sortDirection === 'none' ? undefined : sortDirection, per_page: perPage }, { preserveState: true, preserveScroll: true })}
                    search={search}
                    onSearchChange={(q) => {
                        setSearch(q);
                        router.get('/daftar-pelanggaran', { search: q || undefined, page: 1, per_page: perPage, sort_column: sortColumn || undefined, sort_direction: sortDirection === 'none' ? undefined : sortDirection }, { preserveState: true, preserveScroll: true });
                    }}
                    searchPlaceholder="Cari jenis pelanggaran..."
                    sortColumn={sortColumn}
                    sortDirection={sortDirection}
                    onSort={handleSort}
                    perPage={perPage}
                    onPerPageChange={(p) => {
                        setPerPage(p);
                        if (p !== perPage) {
                            router.get('/daftar-pelanggaran', { per_page: p, page: 1, search: search || undefined, sort_column: sortColumn || undefined, sort_direction: sortDirection === 'none' ? undefined : sortDirection }, { preserveState: true, preserveScroll: true });
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
                title={editing ? 'Edit Jenis Pelanggaran' : 'Tambah Jenis Pelanggaran'}
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Nama Pelanggaran</label>
                        <Input
                            value={form.nama_pelanggaran}
                            onChange={(e) => setForm({ ...form, nama_pelanggaran: e.target.value })}
                            placeholder="Contoh: Tidak Jamaah"
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Poin</label>
                        <Input
                            type="number"
                            min={1}
                            value={form.poin}
                            onChange={(e) => setForm({ ...form, poin: e.target.value })}
                        />
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
