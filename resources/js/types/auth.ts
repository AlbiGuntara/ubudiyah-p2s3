export interface User {
    id: number;
    name: string;
    username: string;
    email: string | null;
    role: 'super_admin' | 'petugas' | 'pembina';
    is_super_admin: boolean;
    is_petugas: boolean;
    is_pembina: boolean;
    permissions: string[];
}

export interface Petugas {
    id: number;
    santri_id: number | null;
    asrama_id: number | null;
    jabatan: string;
    tugas: string;
}

export interface Auth {
    user: User | null;
    petugas: Petugas | null;
}
