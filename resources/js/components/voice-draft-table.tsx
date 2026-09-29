import { AlertCircle, Plus, X } from 'lucide-react';
import { useMemo, useState } from 'react';

import { cn } from '@/lib/utils';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Select } from './ui/select';

/**
 * Satu baris hasil pencatatan voice.
 *
 * Asrama, sumber pencatatan, dan keterangan ikut dibawa per baris, bukan
 * tingkat form, karena satu rekaman bisa memuat beberapa asrama.
 */
export interface VoiceEntry {
    uid: number;
    anonymous: boolean;
    /** Berapa orang, hanya dipakai baris tanpa nama. */
    jumlah: string;
    /** Berisi kalau baris ini menunjuk Santri tertentu. */
    santri_id: string;
    /** Nama yang diucapkan atau diketik petugas. */
    nama: string;
    asrama_id: string;
    daftar_pelanggaran_id: string;
    tanggal: string;
    sumber_pencatatan: string;
    keterangan: string;
    /**
     * Nama mirip dari server, muncul ketika nama yang diucapkan tidak ada
     * di database. Kosong bila namanya sudah cocok persis.
     */
    kandidat: KandidatSantri[];
}

export interface KandidatSantri {
    id: number;
    nama: string;
    nis: string;
    asrama_id: number | null;
}

export interface AsramaOpsi {
    id: number;
    nomor: string;
    daerah_id: number;
    daerah?: { kode: string } | null;
}

export interface SantriOpsi {
    id: number;
    nama: string;
    nis: string;
    asrama_id: number | null;
}

export interface VoiceDraftTableProps {
    entries: VoiceEntry[];
    /** Kode daerah dan nomor asrama, dipakai sebagai label kolom asrama. */
    asrama: AsramaOpsi[];
    /** Seluruh Santri, dipakai untuk mencari nama yang mirip. */
    santri: SantriOpsi[];
    daftarPelanggaran: { id: number; nama_pelanggaran: string }[];
    tanggalDefault: string;
    onUbah: (uid: number, perubahan: Partial<VoiceEntry>) => void;
    /** Dipanggil saat asrama diganti, supaya Santri yang tidak cocok dibuang. */
    onUbahAsrama: (uid: number, asramaId: string) => void;
    /** Dipanggil saat Santri dipilih, supaya asramanya ikut terisi. */
    onPilihSantri: (uid: number, Santri: SantriOpsi | undefined) => void;
    onTambah: () => void;
    onHapus: (uid: number) => void;
}

/**
 * Label asrama ditulis sebagai kode daerah dan nomor asrama, misalnya "A-03".
 * Kolom daerah dihilangkan di mode voice, jadi kode daerah ikut ditampilkan
 * di dalam label asrama.
 */
export function labelAsrama(asrama: AsramaOpsi): string {
    return `${asrama.daerah?.kode ?? '?'}-${asrama.nomor}`;
}

export default function VoiceDraftTable({
    entries,
    asrama,
    santri,
    daftarPelanggaran,
    tanggalDefault,
    onUbah,
    onUbahAsrama,
    onPilihSantri,
    onTambah,
    onHapus,
}: VoiceDraftTableProps) {
    // Teks pencarian disimpan di komponen, bukan di data baris, karena hanya
    // dipakai saat menampilkan saran dan tidak perlu ikut dikirim.
    const [cariPerBaris, setCariPerBaris] = useState<Record<number, string>>(
        {},
    );
    const [saranTerbuka, setSaranTerbuka] = useState<Record<number, boolean>>(
        {},
    );

    const asramaTerpilih = useMemo(() => {
        const map: Record<string, AsramaOpsi> = {};

        asrama.forEach((a) => {
            map[String(a.id)] = a;
        });

        return map;
    }, [asrama]);

    const pilihanAsrama = useMemo(
        () =>
            [...asrama]
                .sort(
                    (a, b) =>
                        (a.daerah?.kode ?? '').localeCompare(
                            b.daerah?.kode ?? '',
                        ) || a.nomor.localeCompare(b.nomor),
                )
                .map((a) => ({ value: String(a.id), label: labelAsrama(a) })),
        [asrama],
    );

    const pilihanPelanggaran = useMemo(
        () =>
            daftarPelanggaran.map((d) => ({
                value: String(d.id),
                label: d.nama_pelanggaran,
            })),
        [daftarPelanggaran],
    );

    const pilihanSumber = useMemo(
        () => [
            { value: 'petugas', label: 'Petugas' },
            { value: 'ketua_kamar', label: 'Ketua Kamar' },
        ],
        [],
    );

    /**
     * Kemiripan sederhana untuk jaring pengaman.
     *
     * Pencocokan ketat sudah dikerjakan server, jadi ini hanya perlu
     * menjangkau nama yang tetap luput ketika ejaan pengucapan dan ejaan
     * database berbeda, misalnya "Jimmie" untuk "Jimmy".
     */
    const mirip = (a: string, b: string): boolean => {
        if (a.length < 3 || b.length < 3) {
            return false;
        }

        let sama = 0;

        while (sama < a.length && sama < b.length && a[sama] === b[sama]) {
            sama++;
        }

        return sama >= 3;
    };

    /**
     * Nama mirip untuk satu baris. Sumbernya dua: kandidat dari server
     * untuk nama yang tidak ditemukan, dan hasil pencarian langsung di
     * daftar Santri untuk nama yang diketik petugas secara manual.
     */
    const saranUntuk = (entry: VoiceEntry): KandidatSantri[] => {
        const cari = (cariPerBaris[entry.uid] ?? '').trim().toLowerCase();

        // Nama yang diketik di baris ini hanya boleh mendapat saran dari
        // asrama yang sama, supaya daftar tidak menawarkan Santri yang jelas
        // tidak mungkin dipilih.
        const diAsrama = (s: SantriOpsi) =>
            !entry.asrama_id || String(s.asrama_id) === String(entry.asrama_id);

        const dariPencarian = cari
            ? santri
                  .filter(
                      (s) =>
                          diAsrama(s) &&
                          (s.nama.toLowerCase().includes(cari) ||
                              String(s.nis ?? '')
                                  .toLowerCase()
                                  .includes(cari) ||
                              mirip(s.nama.toLowerCase(), cari)),
                  )
                  .map((s) => ({
                      id: s.id,
                      nama: s.nama,
                      nis: s.nis,
                      asrama_id: s.asrama_id,
                  }))
            : [];

        const gabungan = [...dariPencarian, ...entry.kandidat];
        const unik: KandidatSantri[] = [];

        gabungan.forEach((k) => {
            if (!unik.some((x) => x.id === k.id)) {
                unik.push(k);
            }
        });

        return unik.slice(0, 6);
    };

    return (
        <div className="space-y-2">
            <div className="overflow-x-auto rounded-md border">
                <table className="w-full">
                    <thead>
                        <tr className="border-b bg-muted/50">
                            <th className="px-2 py-2 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Santri
                            </th>
                            <th className="px-2 py-2 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Asrama
                            </th>
                            <th className="px-2 py-2 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Pelanggaran
                            </th>
                            <th className="px-2 py-2 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Tanggal
                            </th>
                            <th className="px-2 py-2 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Sumber
                            </th>
                            <th className="px-2 py-2 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Keterangan
                            </th>
                            <th className="w-8" />
                        </tr>
                    </thead>
                    <tbody>
                        {entries.map((entry) => {
                            // Nama yang diucapkan menjadi isi awal kotak
                            // pencarian. Tanpa ini, kolom Santri tampak
                            // kosong padahal namanya sudah terdeteksi.
                            const cari = cariPerBaris[entry.uid] ?? entry.nama;
                            const saran = saranUntuk(entry);

                            // Kandidat dari server langsung ditampilkan,
                            // tanpa menunggu klik, supaya petugas cepat
                            // melihat nama mirip lalu memilihnya.
                            const tampilSaran =
                                (saranTerbuka[entry.uid] ??
                                    entry.kandidat.length > 0) &&
                                saran.length > 0;
                            const asramaBaris = entry.asrama_id
                                ? asramaTerpilih[entry.asrama_id]
                                : undefined;
                            const namaSantriTerpilih = entry.santri_id
                                ? santri.find(
                                      (s) =>
                                          String(s.id) ===
                                          String(entry.santri_id),
                                  )?.nama
                                : undefined;

                            return (
                                <tr
                                    key={entry.uid}
                                    className="border-b align-top last:border-0"
                                >
                                    {/* Santri: nama atau jumlah orang */}
                                    <td className="px-2 py-2">
                                        <div className="flex gap-1">
                                            <div className="flex shrink-0 overflow-hidden rounded-md border">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        onUbah(entry.uid, {
                                                            anonymous: false,
                                                        })
                                                    }
                                                    className={cn(
                                                        'px-1.5 py-1 text-[11px] transition-colors',
                                                        !entry.anonymous
                                                            ? 'bg-muted font-medium text-foreground'
                                                            : 'text-muted-foreground hover:bg-accent',
                                                    )}
                                                >
                                                    Nama
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        onUbah(entry.uid, {
                                                            anonymous: true,
                                                            santri_id: '',
                                                        })
                                                    }
                                                    className={cn(
                                                        'px-1.5 py-1 text-[11px] transition-colors',
                                                        entry.anonymous
                                                            ? 'bg-muted font-medium text-foreground'
                                                            : 'text-muted-foreground hover:bg-accent',
                                                    )}
                                                >
                                                    Angka
                                                </button>
                                            </div>

                                            {entry.anonymous ? (
                                                <Input
                                                    type="number"
                                                    min={1}
                                                    value={entry.jumlah}
                                                    onChange={(e) =>
                                                        onUbah(entry.uid, {
                                                            jumlah: e.target
                                                                .value,
                                                        })
                                                    }
                                                    placeholder="Jumlah"
                                                    aria-label="Jumlah orang"
                                                    className="h-8 w-20"
                                                />
                                            ) : (
                                                <div className="min-w-[180px] flex-1">
                                                    <Input
                                                        value={cari}
                                                        placeholder={
                                                            namaSantriTerpilih ??
                                                            'Nama Santri'
                                                        }
                                                        aria-label="Nama Santri"
                                                        onFocus={() =>
                                                            setSaranTerbuka(
                                                                (s) => ({
                                                                    ...s,
                                                                    [entry.uid]: true,
                                                                }),
                                                            )
                                                        }
                                                        onBlur={() =>
                                                            setSaranTerbuka(
                                                                (s) => ({
                                                                    ...s,
                                                                    [entry.uid]: false,
                                                                }),
                                                            )
                                                        }
                                                        onChange={(e) => {
                                                            const value =
                                                                e.target.value;
                                                            setCariPerBaris(
                                                                (c) => ({
                                                                    ...c,
                                                                    [entry.uid]:
                                                                        value,
                                                                }),
                                                            );
                                                            setSaranTerbuka(
                                                                (s) => ({
                                                                    ...s,
                                                                    [entry.uid]: true,
                                                                }),
                                                            );
                                                            onUbah(entry.uid, {
                                                                nama: value,
                                                            });
                                                        }}
                                                        className="h-8"
                                                    />

                                                    {tampilSaran && (
                                                        <ul className="mt-1 max-h-40 overflow-y-auto rounded-md border bg-popover shadow-xs">
                                                            {saran.map((k) => (
                                                                <li key={k.id}>
                                                                    <button
                                                                        type="button"
                                                                        // Tekanannya membuat input kehilangan
                                                                        // fokus sebelum klik
                                                                        // selesai, jadi event ini
                                                                        // ditahan agar pilihan tetap
                                                                        // tercatat.
                                                                        onMouseDown={(
                                                                            e,
                                                                        ) =>
                                                                            e.preventDefault()
                                                                        }
                                                                        onClick={() => {
                                                                            setCariPerBaris(
                                                                                (
                                                                                    c,
                                                                                ) => ({
                                                                                    ...c,
                                                                                    [entry.uid]:
                                                                                        k.nama,
                                                                                }),
                                                                            );
                                                                            setSaranTerbuka(
                                                                                (
                                                                                    s,
                                                                                ) => ({
                                                                                    ...s,
                                                                                    [entry.uid]: false,
                                                                                }),
                                                                            );
                                                                            onPilihSantri(
                                                                                entry.uid,
                                                                                santri.find(
                                                                                    (
                                                                                        s,
                                                                                    ) =>
                                                                                        s.id ===
                                                                                        k.id,
                                                                                ),
                                                                            );
                                                                        }}
                                                                        className="flex w-full items-center justify-between gap-2 px-2 py-1.5 text-left text-xs hover:bg-accent"
                                                                    >
                                                                        <span className="truncate">
                                                                            {
                                                                                k.nama
                                                                            }
                                                                        </span>
                                                                        <span className="shrink-0 text-muted-foreground">
                                                                            {asramaTerpilih[
                                                                                String(
                                                                                    k.asrama_id,
                                                                                )
                                                                            ]
                                                                                ? labelAsrama(
                                                                                      asramaTerpilih[
                                                                                          String(
                                                                                              k.asrama_id,
                                                                                          )
                                                                                      ],
                                                                                  )
                                                                                : 'Tanpa asrama'}{' '}
                                                                            &middot;
                                                                            NIS{' '}
                                                                            {
                                                                                k.nis
                                                                            }
                                                                        </span>
                                                                    </button>
                                                                </li>
                                                            ))}
                                                        </ul>
                                                    )}
                                                </div>
                                            )}
                                        </div>

                                        {!entry.anonymous &&
                                            !entry.santri_id && (
                                                <p className="mt-1 flex items-start gap-1 text-[11px] text-amber-700">
                                                    <AlertCircle className="mt-px h-3 w-3 shrink-0" />
                                                    Nama belum dipilih dari
                                                    daftar.
                                                </p>
                                            )}
                                    </td>

                                    {/* Asrama: kode daerah dan nomor asrama */}
                                    <td className="px-2 py-2">
                                        <Select
                                            value={entry.asrama_id}
                                            onChange={(e) =>
                                                onUbahAsrama(
                                                    entry.uid,
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Pilih Asrama"
                                            options={pilihanAsrama}
                                            aria-label="Asrama"
                                            className="h-8 min-w-[110px]"
                                        />
                                        {entry.santri_id &&
                                            asramaBaris &&
                                            String(
                                                santri.find(
                                                    (s) =>
                                                        s.id ===
                                                        Number(entry.santri_id),
                                                )?.asrama_id,
                                            ) !== String(entry.asrama_id) && (
                                                <p className="mt-1 text-[11px] text-amber-700">
                                                    Santri ini bukan dari asrama
                                                    tersebut.
                                                </p>
                                            )}
                                    </td>

                                    <td className="px-2 py-2">
                                        <Select
                                            value={entry.daftar_pelanggaran_id}
                                            onChange={(e) =>
                                                onUbah(entry.uid, {
                                                    daftar_pelanggaran_id:
                                                        e.target.value,
                                                })
                                            }
                                            placeholder="Jenis"
                                            options={pilihanPelanggaran}
                                            aria-label="Jenis pelanggaran"
                                            className="h-8 min-w-[150px]"
                                        />
                                    </td>

                                    <td className="px-2 py-2">
                                        <Input
                                            type="date"
                                            value={
                                                entry.tanggal || tanggalDefault
                                            }
                                            onChange={(e) =>
                                                onUbah(entry.uid, {
                                                    tanggal: e.target.value,
                                                })
                                            }
                                            aria-label="Tanggal"
                                            className="h-8 w-[135px]"
                                        />
                                    </td>

                                    <td className="px-2 py-2">
                                        <Select
                                            value={entry.sumber_pencatatan}
                                            onChange={(e) =>
                                                onUbah(entry.uid, {
                                                    sumber_pencatatan:
                                                        e.target.value,
                                                })
                                            }
                                            options={pilihanSumber}
                                            aria-label="Sumber pencatatan"
                                            className="h-8 min-w-[110px]"
                                        />
                                    </td>

                                    <td className="px-2 py-2">
                                        <Input
                                            value={entry.keterangan}
                                            onChange={(e) =>
                                                onUbah(entry.uid, {
                                                    keterangan: e.target.value,
                                                })
                                            }
                                            placeholder="Keterangan"
                                            aria-label="Keterangan"
                                            className="h-8 min-w-[160px]"
                                        />
                                    </td>

                                    <td className="px-2 py-2">
                                        <button
                                            type="button"
                                            onClick={() => onHapus(entry.uid)}
                                            aria-label="Hapus baris"
                                            className="rounded p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                                        >
                                            <X className="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            <div className="flex items-center justify-between gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={onTambah}
                >
                    <Plus className="h-4 w-4" />
                    Tambah baris
                </Button>
                <p className="text-xs text-muted-foreground">
                    {entries.length === 0
                        ? 'Belum ada data. Rekam suara atau tambah baris.'
                        : `${entries.length} baris siap disimpan.`}
                </p>
            </div>
        </div>
    );
}
