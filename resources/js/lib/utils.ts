import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Warna teks nama santri sesuai status, supaya santri yang berhenti dan
 * tidak aktif langsung terlihat berbeda di tabel.
 */
export function statusSantriTextClass(status?: string | null): string {
    if (status === 'berhenti') {
        return 'text-red-600';
    }

    if (status === 'tidak aktif') {
        return 'text-amber-600';
    }

    return '';
}
