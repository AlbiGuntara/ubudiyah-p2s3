export interface User {
    id: number;
    name: string;
    username: string;
    email: string | null;
    role: 'super_admin' | 'petugas' | 'pembina';
    foto: string | null;
    jabatan: string | null;
    tugas: string | null;
    is_super_admin: boolean;
    is_petugas: boolean;
    is_pembina: boolean;
    permissions: string[];
}

export interface Auth {
    user: User | null;
}
