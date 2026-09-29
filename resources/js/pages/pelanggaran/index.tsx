import { useState, useMemo, useCallback, useEffect, useRef } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Modal } from '@/components/ui/modal';
import { DataTable, type Column } from '@/components/shared/data-table';
import VoiceDraftTable from '@/components/voice-draft-table';
import type {
    KandidatSantri,
    VoiceEntry,
} from '@/components/voice-draft-table';
import { cn } from '@/lib/utils';
import {
    Edit2,
    Trash2,
    Plus,
    X,
    Printer,
    RefreshCw,
    ChevronDown,
    Mic,
    Square,
    Loader2,
    AlertCircle,
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu';

const LABEL_PERIKSA: Record<string, string> = {
    jenis: 'jenis pelanggaran',
    asrama: 'asrama',
    santri: 'santri',
};

export default function PelanggaranIndex() {
    const {
        pelanggaran,
        santri,
        asrama,
        daerah,
        daftarPelanggaran,
        filters,
        auth,
        per_page,
        sort_column,
        sort_direction,
        voice,
    } = usePage<any>().props;
    const voiceEnabled = voice?.enabled === true;
    const voiceMaxDuration = voice?.max_duration ?? 30;
    const userPermissions: string[] = auth?.user?.permissions || [];
    const canCetakSuratPanggilan = userPermissions.includes(
        'cetak_surat_panggilan',
    );
    const [perPage, setPerPage] = useState(parseInt(per_page) || 15);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<any>(null);
    const [search, setSearch] = useState(filters?.search || '');
    const [filterDaerah, setFilterDaerah] = useState(filters?.daerah_id || '');
    const [filterAsrama, setFilterAsrama] = useState(filters?.asrama_id || '');
    const [filterTanggalMulai, setFilterTanggalMulai] = useState(
        filters?.tanggal_mulai || '',
    );
    const [filterTanggalSelesai, setFilterTanggalSelesai] = useState(
        filters?.tanggal_selesai || '',
    );
    const [sortColumn, setSortColumn] = useState(sort_column || '');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | 'none'>(
        (sort_direction as 'asc' | 'desc' | 'none') || 'none',
    );
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);
    const [showEditModal, setShowEditModal] = useState(false);
    const [editForm, setEditForm] = useState({
        daftar_pelanggaran_id: '',
        tanggal: '',
        sumber_pencatatan: 'petugas',
        keterangan: '',
    });
    const [showBulkEditModal, setShowBulkEditModal] = useState(false);
    const [bulkForm, setBulkForm] = useState({
        sumber_pencatatan: 'petugas',
        keterangan: '',
    });

    const [showCetakUlang, setShowCetakUlang] = useState(false);
    const [reprintDaerahId, setReprintDaerahId] = useState('');
    const [reprintAsramaId, setReprintAsramaId] = useState('');
    const [reprintRiwayat, setReprintRiwayat] = useState<any[]>([]);
    const [loadingRiwayat, setLoadingRiwayat] = useState(false);

    const [showRiwayatGlobal, setShowRiwayatGlobal] = useState(false);
    const [globalRiwayat, setGlobalRiwayat] = useState<any[]>([]);
    const [loadingGlobalRiwayat, setLoadingGlobalRiwayat] = useState(false);

    const [showPrintModal, setShowPrintModal] = useState(false);
    const [printDaerahId, setPrintDaerahId] = useState('');

    const openRiwayatGlobal = useCallback(async () => {
        setShowRiwayatGlobal(true);
        setLoadingGlobalRiwayat(true);
        try {
            const res = await fetch(
                '/pelanggaran/surat-panggilan/riwayat-global',
            );
            const data = await res.json();
            setGlobalRiwayat(data);
        } catch {
            setGlobalRiwayat([]);
        } finally {
            setLoadingGlobalRiwayat(false);
        }
    }, []);

    const hapusRiwayat = useCallback(async (printedAt: string) => {
        if (
            !confirm(
                'Yakin ingin menghapus sesi cetak ini? Semua surat dalam sesi ini akan dihapus dan pelanggaran akan kembali ke daftar cetak.',
            )
        )
            return;
        try {
            const res = await fetch(
                '/pelanggaran/surat-panggilan/delete-session',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (window as any).csrfToken || '',
                    },
                    body: JSON.stringify({ printed_at: printedAt }),
                },
            );
            if (res.ok) {
                setGlobalRiwayat((prev) =>
                    prev.filter((r) => r.printed_at !== printedAt),
                );
            }
        } catch {}
    }, []);

    const filteredReprintAsrama = useMemo(() => {
        if (!reprintDaerahId) return [];
        return asrama.filter(
            (a: any) => String(a.daerah_id) === String(reprintDaerahId),
        );
    }, [asrama, reprintDaerahId]);

    const [form, setForm] = useState({
        asrama_id: '',
        petugas_id: '',
        sumber_pencatatan: 'petugas',
        tanggal: new Date().toISOString().split('T')[0],
        keterangan: '',
    });
    const [daerahId, setDaerahId] = useState('');
    // Santri entries (allow duplicates for multiple violations per santri)
    const [nextSantriId, setNextSantriId] = useState(0);
    const [santriEntries, setSantriEntries] = useState<
        {
            uid: number;
            santri_id: number;
            nama: string;
            daftar_pelanggaran_id: string;
            tanggal: string;
        }[]
    >([]);
    const [pendingsantri_id, setPendingSantriId] = useState('');
    const [pendingSantriSearch, setPendingSantriSearch] = useState('');
    const [showSantriDropdown, setShowSantriDropdown] = useState(false);
    const [pendingPelanggaranId, setPendingPelanggaranId] = useState('');
    // Anonymous entries (row-based like santri)
    const [nextAnonId, setNextAnonId] = useState(0);
    const [anonymousEntries, setAnonymousEntries] = useState<
        {
            uid: number;
            jumlah: string;
            daftar_pelanggaran_id: string;
            tanggal: string;
        }[]
    >([]);
    const [pendingAnonJumlah, setPendingAnonJumlah] = useState('1');
    const [pendingAnonPelanggaranId, setPendingAnonPelanggaranId] =
        useState('');

    // Pencatatan dengan voice memakai tabelnya sendiri, terpisah dari form
    // manual di bawah. Asrama, sumber, dan keterangan dibawa per baris
    // supaya satu rekaman bisa memuat beberapa asrama.
    const [modeCatat, setModeCatat] = useState<'manual' | 'voice'>('manual');
    const [voiceEntries, setVoiceEntries] = useState<VoiceEntry[]>([]);
    const [nextVoiceId, setNextVoiceId] = useState(0);
    const [voiceKesalahan, setVoiceKesalahan] = useState<string[]>([]);
    const [merekam, setMerekam] = useState(false);
    const [memproses, setMemproses] = useState(false);
    const [voiceError, setVoiceError] = useState<string | null>(null);
    const [draft, setDraft] = useState<any>(null);
    const [tanggalDefault, setTanggalDefault] = useState(
        new Date().toISOString().split('T')[0],
    );
    const [detik, setDetik] = useState(0);
    const recorderRef = useRef<MediaRecorder | null>(null);
    const chunksRef = useRef<Blob[]>([]);
    const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);
    const batalRef = useRef(false);

    const daerahList = useMemo(() => {
        const map: Record<string, any> = {};
        asrama.forEach((a: any) => {
            if (a.daerah) map[a.daerah.id] = a.daerah;
        });
        return Object.values(map);
    }, [asrama]);

    const filteredAsrama = useMemo(() => {
        if (!daerahId) return asrama;
        return asrama.filter(
            (a: any) => String(a.daerah_id) === String(daerahId),
        );
    }, [asrama, daerahId]);

    const filteredSantri = useMemo(() => {
        if (!form.asrama_id) return [];
        return santri.filter(
            (s: any) => String(s.asrama_id) === String(form.asrama_id),
        );
    }, [santri, form.asrama_id]);

    const filteredSantriBySearch = useMemo(() => {
        if (!pendingSantriSearch) return filteredSantri;
        const q = pendingSantriSearch.toLowerCase();
        return filteredSantri.filter((s: any) =>
            s.nama.toLowerCase().includes(q),
        );
    }, [filteredSantri, pendingSantriSearch]);

    const openCetakUlang = () => {
        setReprintDaerahId('');
        setReprintAsramaId('');
        setReprintRiwayat([]);
        setShowCetakUlang(true);
    };

    const fetchRiwayat = useCallback(async (asramaId: string) => {
        if (!asramaId) {
            setReprintRiwayat([]);
            return;
        }
        setLoadingRiwayat(true);
        try {
            const res = await fetch(
                `/pelanggaran/surat-panggilan/riwayat?asrama_id=${asramaId}`,
            );
            const data = await res.json();
            setReprintRiwayat(data);
        } catch {
            setReprintRiwayat([]);
        } finally {
            setLoadingRiwayat(false);
        }
    }, []);

    const openCreate = () => {
        setEditing(null);
        setForm({
            asrama_id: '',
            petugas_id: '',
            sumber_pencatatan: 'petugas',
            tanggal: new Date().toISOString().split('T')[0],
            keterangan: '',
        });
        setDaerahId('');
        setSantriEntries([]);
        setAnonymousEntries([]);
        setNextSantriId(0);
        setNextAnonId(0);
        setPendingSantriId('');
        setPendingSantriSearch('');
        setShowSantriDropdown(false);
        setPendingPelanggaranId('');
        setPendingAnonJumlah('1');
        setPendingAnonPelanggaranId('');
        setModeCatat('manual');
        setDraft(null);
        setVoiceError(null);
        setMerekam(false);
        setDetik(0);
        setVoiceEntries([]);
        setNextVoiceId(0);
        setVoiceKesalahan([]);
        setTanggalDefault(new Date().toISOString().split('T')[0]);
        stopTimer();
        setShowModal(true);
    };

    const openEdit = (p: any) => {
        setEditing(p);
        setEditForm({
            daftar_pelanggaran_id: String(p.daftar_pelanggaran_id || ''),
            tanggal: p.tanggal ? p.tanggal.split('T')[0] : '',
            sumber_pencatatan: p.sumber_pencatatan || 'petugas',
            keterangan: p.keterangan || '',
        });
        setShowEditModal(true);
    };

    const addSantri = () => {
        if (!pendingsantri_id || !pendingPelanggaranId) return;
        const s = santri.find((s: any) => String(s.id) === pendingsantri_id);
        if (s) {
            setSantriEntries([
                ...santriEntries,
                {
                    uid: nextSantriId,
                    santri_id: s.id,
                    nama: s.nama,
                    daftar_pelanggaran_id: pendingPelanggaranId,
                    tanggal: new Date().toISOString().split('T')[0],
                },
            ]);
            setNextSantriId(nextSantriId + 1);
        }
        setPendingSantriId('');
        setPendingSantriSearch('');
        setShowSantriDropdown(false);
        setPendingPelanggaranId('');
    };

    const removeSantri = (uid: number) => {
        setSantriEntries(santriEntries.filter((s) => s.uid !== uid));
    };

    const updateSantriPelanggaran = (
        uid: number,
        daftar_pelanggaran_id: string,
    ) => {
        setSantriEntries(
            santriEntries.map((s) =>
                s.uid === uid ? { ...s, daftar_pelanggaran_id } : s,
            ),
        );
    };

    const updateSantriTanggal = (uid: number, tanggal: string) => {
        setSantriEntries(
            santriEntries.map((s) => (s.uid === uid ? { ...s, tanggal } : s)),
        );
    };

    const addAnonymous = () => {
        if (!pendingAnonJumlah || !pendingAnonPelanggaranId) return;
        const jumlah = parseInt(pendingAnonJumlah);
        if (jumlah < 1) return;
        setAnonymousEntries([
            ...anonymousEntries,
            {
                uid: nextAnonId,
                jumlah: pendingAnonJumlah,
                daftar_pelanggaran_id: pendingAnonPelanggaranId,
                tanggal: new Date().toISOString().split('T')[0],
            },
        ]);
        setNextAnonId(nextAnonId + 1);
        setPendingAnonJumlah('1');
        setPendingAnonPelanggaranId('');
    };

    const removeAnonymous = (uid: number) => {
        setAnonymousEntries(anonymousEntries.filter((a) => a.uid !== uid));
    };

    const updateAnonymousJumlah = (uid: number, jumlah: string) => {
        setAnonymousEntries(
            anonymousEntries.map((a) => (a.uid === uid ? { ...a, jumlah } : a)),
        );
    };

    const updateAnonymousPelanggaran = (
        uid: number,
        daftar_pelanggaran_id: string,
    ) => {
        setAnonymousEntries(
            anonymousEntries.map((a) =>
                a.uid === uid ? { ...a, daftar_pelanggaran_id } : a,
            ),
        );
    };

    const updateAnonymousTanggal = (uid: number, tanggal: string) => {
        setAnonymousEntries(
            anonymousEntries.map((a) =>
                a.uid === uid ? { ...a, tanggal } : a,
            ),
        );
    };

    const submit = () => {
        const data: Record<string, any> = {
            santri_pelanggaran: santriEntries.map((s) => ({
                santri_id: s.santri_id,
                daftar_pelanggaran_id: s.daftar_pelanggaran_id,
                tanggal: s.tanggal,
            })),
            anonymous_entries: anonymousEntries.map((a) => ({
                jumlah: parseInt(a.jumlah),
                daftar_pelanggaran_id: a.daftar_pelanggaran_id,
                tanggal: a.tanggal,
            })),
            asrama_id: form.asrama_id,
            petugas_id: form.petugas_id || null,
            sumber_pencatatan: form.sumber_pencatatan,
            keterangan: form.keterangan,
        };
        router.post('/pelanggaran', data, {
            onSuccess: () => setShowModal(false),
        });
    };

    /**
     * Validasi tabel rekaman lalu kirim. Tiap baris membawa asrama, sumber,
     * dan keterangannya sendiri supaya beberapa Santri dari asrama berbeda
     * bisa dicatat dalam satu kali simpan.
     */
    const submitVoice = () => {
        const masalah: string[] = [];
        const santriPelanggaran: Record<string, any>[] = [];
        const anonymousEntries: Record<string, any>[] = [];

        voiceEntries.forEach((b, index) => {
            const nomor = index + 1;

            if (!b.asrama_id) {
                masalah.push(`Baris ${nomor}: asrama belum dipilih.`);
            }

            if (!b.daftar_pelanggaran_id) {
                masalah.push(
                    `Baris ${nomor}: jenis pelanggaran belum dipilih.`,
                );
            }

            if (b.anonymous) {
                const jumlah = parseInt(b.jumlah || '0');

                if (!jumlah || jumlah < 1) {
                    masalah.push(`Baris ${nomor}: jumlah orang tidak valid.`);
                }
            } else if (!b.santri_id) {
                masalah.push(`Baris ${nomor}: Santri belum dipilih.`);
            }
        });

        if (voiceEntries.length === 0) {
            masalah.push('Belum ada baris untuk disimpan.');
        }

        setVoiceKesalahan(masalah);

        if (masalah.length > 0) {
            return;
        }

        voiceEntries.forEach((b) => {
            const bersama = {
                daftar_pelanggaran_id: b.daftar_pelanggaran_id,
                tanggal: b.tanggal || tanggalDefault,
                asrama_id: b.asrama_id,
                sumber_pencatatan: b.sumber_pencatatan || 'petugas',
                keterangan: b.keterangan || null,
            };

            if (b.anonymous) {
                anonymousEntries.push({
                    ...bersama,
                    jumlah: parseInt(b.jumlah || '1'),
                });
            } else {
                santriPelanggaran.push({
                    ...bersama,
                    santri_id: b.santri_id,
                });
            }
        });

        // Asrama tingkat form tetap diwajibkan oleh validasi server, walau
        // setiap entri sudah membawa asramanya sendiri. Baris pertama dipakai
        // sebagai cadangan, termasuk ketika semua baris tanpa nama.
        const data = {
            santri_pelanggaran: santriPelanggaran,
            anonymous_entries: anonymousEntries,
            asrama_id: voiceEntries[0]?.asrama_id || null,
            sumber_pencatatan: 'petugas',
            keterangan: null,
        };

        router.post('/pelanggaran', data, {
            onSuccess: () => setShowModal(false),
        });
    };

    const stopTimer = () => {
        if (timerRef.current !== null) {
            clearInterval(timerRef.current);
            timerRef.current = null;
        }
    };

    // Mikrofon dan pengatur waktu harus berhenti saat modal ditutup supaya
    // perangkat tidak merekam di belakang layar.
    useEffect(() => {
        return () => {
            stopTimer();
        };
    }, []);

    const barisKosong = (uid: number): VoiceEntry => ({
        uid,
        anonymous: false,
        jumlah: '1',
        santri_id: '',
        nama: '',
        asrama_id: '',
        daftar_pelanggaran_id: '',
        tanggal: tanggalDefault,
        sumber_pencatatan: 'petugas',
        keterangan: '',
        kandidat: [],
    });

    const kandidatKeBaris = (kandidat: any): KandidatSantri[] => {
        if (!Array.isArray(kandidat)) {
            return [];
        }

        return kandidat.map((c: any) => {
            const dariMaster = (santri as any[]).find((s) => s.id === c.id);

            return {
                id: c.id,
                nama: c.nama,
                nis: c.nis ?? dariMaster?.nis ?? '',
                asrama_id: c.asrama_id ?? dariMaster?.asrama_id ?? null,
            };
        });
    };

    /**
     * Satu rekaman menjadi satu baris. Asrama diambil dari Santri yang
     * terpilih, karena suara petugas sering menyebut nama saja.
     */
    const hasilkanBaris = (d: any, uid: number): VoiceEntry => {
        const terpilih = d?.santri?.selected ?? null;
        const asramaId = terpilih?.asrama_id
            ? String(terpilih.asrama_id)
            : d?.asrama_id
              ? String(d.asrama_id)
              : '';

        return {
            ...barisKosong(uid),
            anonymous: !!d?.anonymous,
            jumlah: String(d?.jumlah || 1),
            // Nama yang diucapkan tidak selalu ada di database. Kandidat dari
            // server ikut disimpan supaya nama yang mirip bisa dipilih tanpa
            // mengetik ulang.
            kandidat: kandidatKeBaris(d?.santri?.candidates),
            nama: terpilih?.nama ?? '',
            santri_id: terpilih?.id ? String(terpilih.id) : '',
            asrama_id: asramaId,
            daftar_pelanggaran_id: d?.daftar_pelanggaran_ids?.[0]
                ? String(d.daftar_pelanggaran_ids[0])
                : '',
            tanggal: d?.tanggal || tanggalDefault,
            sumber_pencatatan: d?.sumber_pencatatan || 'petugas',
            keterangan: d?.keterangan ?? '',
        };
    };

    /**
     * Isi tabel dari hasil rekaman tanpa menyentuh form manual sama sekali.
     *
     * Baris rekaman ditambahkan ke baris yang sudah ada, jadi petugas bisa
     * menambah beberapa Santri lewat beberapa kali rekaman.
     */
    const isiDraftKeTabel = (d: any, tanggalDariServer?: string) => {
        setDraft(d);

        if (tanggalDariServer) {
            setTanggalDefault(tanggalDariServer);
        }

        const baris = hasilkanBaris(d, nextVoiceId);

        setVoiceEntries((lama) => [...lama, baris]);
        setNextVoiceId(nextVoiceId + 1);
    };

    const ubahBaris = (uid: number, perubahan: Partial<VoiceEntry>) => {
        setVoiceEntries((lama) =>
            lama.map((b) => (b.uid === uid ? { ...b, ...perubahan } : b)),
        );
    };

    /**
     * Ganti asrama sebuah baris. Kalau Santri yang sedang dipilih bukan dari
     * asrama baru itu, pilihannya dibuang supaya tidak ada pasangan yang
     * tidak mungkin.
     */
    const ubahAsramaBaris = (uid: number, asramaId: string) => {
        setVoiceEntries((lama) =>
            lama.map((b) => {
                if (b.uid !== uid) {
                    return b;
                }

                const s = (santri as any[]).find(
                    (x) => String(x.id) === String(b.santri_id),
                );
                const tetapCocok =
                    !s || !asramaId || String(s.asrama_id) === String(asramaId);

                return {
                    ...b,
                    asrama_id: asramaId,
                    santri_id: tetapCocok ? b.santri_id : '',
                    nama: tetapCocok ? b.nama : '',
                };
            }),
        );
    };

    /**
     * Pilih Santri untuk sebuah baris. Asrama baris ikut menyesuaikan dengan
     * asrama Santri tersebut, dan daftar nama mirip ditutup.
     */
    const pilihSantriBaris = (uid: number, s: any) => {
        if (!s) {
            return;
        }

        setVoiceEntries((lama) =>
            lama.map((b) =>
                b.uid === uid
                    ? {
                          ...b,
                          santri_id: String(s.id),
                          nama: s.nama,
                          asrama_id: s.asrama_id
                              ? String(s.asrama_id)
                              : b.asrama_id,
                          kandidat: [],
                      }
                    : b,
            ),
        );
    };

    const tambahBaris = () => {
        // Nomor baris dalam pesan galat ikut bergeser, jadi pesan lama
        // tidak lagi relevan.
        setVoiceKesalahan([]);
        setVoiceEntries((lama) => [...lama, barisKosong(nextVoiceId)]);
        setNextVoiceId(nextVoiceId + 1);
    };

    const hapusBaris = (uid: number) => {
        setVoiceKesalahan([]);
        setVoiceEntries((lama) => lama.filter((b) => b.uid !== uid));
    };

    const kirimRekaman = async (blob: Blob) => {
        setMemproses(true);
        setVoiceError(null);

        try {
            const ext = blob.type.includes('mp4')
                ? 'mp4'
                : blob.type.includes('ogg')
                  ? 'ogg'
                  : 'webm';
            const data = new FormData();
            data.append('audio', blob, `rekaman.${ext}`);

            const res = await fetch('/pelanggaran/voice', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
                body: data,
            });
            const body = await res.json().catch(() => ({}));

            if (!res.ok) {
                setVoiceError(
                    body?.message || 'Rekaman gagal diproses. Coba lagi.',
                );

                return;
            }

            isiDraftKeTabel(body.draft, body.tanggal_default);
        } catch {
            setVoiceError('Tidak bisa menghubungi server. Coba lagi.');
        } finally {
            setMemproses(false);
        }
    };

    const mulaiRekam = async () => {
        setVoiceError(null);
        setDraft(null);
        setVoiceKesalahan([]);

        if (typeof MediaRecorder === 'undefined') {
            setVoiceError('Peramban ini tidak mendukung rekaman suara.');

            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true,
            });
            // Chrome mengirim "audio/webm;codecs=opus". Server memvalidasi
            // berkas lewat magic bytes, jadi tipe yang di sini hanya petunjuk
            // untuk perekam dan nama berkas.
            const tipe = [
                'audio/webm;codecs=opus',
                'audio/webm',
                'audio/mp4',
                'audio/ogg;codecs=opus',
            ].find((t) => MediaRecorder.isTypeSupported(t));
            const recorder = tipe
                ? new MediaRecorder(stream, { mimeType: tipe })
                : new MediaRecorder(stream);

            batalRef.current = false;
            chunksRef.current = [];
            recorder.ondataavailable = (e) => {
                if (e.data && e.data.size > 0) {
                    chunksRef.current.push(e.data);
                }
            };
            recorder.onstop = () => {
                stream.getTracks().forEach((t) => t.stop());

                if (batalRef.current) {
                    return;
                }

                kirimRekaman(
                    new Blob(chunksRef.current, {
                        type: recorder.mimeType || 'audio/webm',
                    }),
                );
            };

            recorderRef.current = recorder;
            recorder.start();
            setMerekam(true);

            // Rekaman dihentikan sendiri agar berkas tidak melebihi batas
            // ukuran yang diizinkan server.
            let tick = 0;
            timerRef.current = setInterval(() => {
                tick += 1;
                setDetik(tick);

                if (tick >= voiceMaxDuration) {
                    stopRekam();
                }
            }, 1000);
        } catch {
            setVoiceError(
                'Tidak bisa mengakses mikrofon. Periksa izin peramban.',
            );
        }
    };

    const stopRekam = () => {
        recorderRef.current?.stop();
        setMerekam(false);
        stopTimer();
    };

    const closeCatat = () => {
        batalRef.current = true;

        if (recorderRef.current && recorderRef.current.state !== 'inactive') {
            recorderRef.current.stop();
        }

        setMerekam(false);
        stopTimer();
        setShowModal(false);
    };

    const submitEdit = () => {
        if (!editing) return;
        router.put(`/pelanggaran/${editing.id}`, editForm, {
            onSuccess: () => setShowEditModal(false),
        });
    };

    const submitBulkEdit = () => {
        router.post(
            '/pelanggaran/bulk-update',
            {
                ids: selectedIds,
                sumber_pencatatan: bulkForm.sumber_pencatatan,
                keterangan: bulkForm.keterangan,
            },
            {
                onSuccess: () => {
                    setShowBulkEditModal(false);
                    setSelectedIds([]);
                },
            },
        );
    };

    const destroy = (id: number) => {
        if (confirm('Yakin ingin menghapus pelanggaran ini?')) {
            router.delete(`/pelanggaran/${id}`);
        }
    };

    const bulkDelete = () => {
        if (
            confirm(`Yakin ingin menghapus ${selectedIds.length} pelanggaran?`)
        ) {
            router.post(
                '/pelanggaran/bulk-delete',
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
            '/pelanggaran',
            {
                sort_column: nextDir === 'none' ? undefined : column,
                sort_direction: nextDir === 'none' ? undefined : nextDir,
                daerah_id: filterDaerah || undefined,
                asrama_id: filterAsrama || undefined,
                tanggal_mulai: filterTanggalMulai || undefined,
                tanggal_selesai: filterTanggalSelesai || undefined,
                search: search || undefined,
                per_page: perPage,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const formatDate = (dateString: string) => {
        if (!dateString) return '-';
        const [y, m, d] = dateString.split('T')[0].split('-');
        return `${d}-${m}-${y}`;
    };

    const columns: Column<any>[] = [
        {
            key: 'no',
            label: '#',
            render: (_p: any, idx: number) => (
                <span>{pelanggaran.from + idx}</span>
            ),
            className: 'text-muted-foreground text-xs w-10',
        },
        {
            key: 'tanggal',
            label: 'Tanggal',
            sortable: true,
            render: (p) => <span>{formatDate(p.tanggal)}</span>,
        },
        {
            key: 'santri',
            label: 'Santri',
            sortable: true,
            render: (p) => (
                <span className="font-medium">
                    {p.santri?.nama || `${p.jumlah} Orang`}
                </span>
            ),
        },
        {
            key: 'asrama',
            label: 'Asrama',
            sortable: true,
            render: (p) =>
                p.asrama?.daerah?.kode
                    ? `${p.asrama.daerah.kode.charAt(0)}.${p.asrama.nomor}`
                    : p.asrama?.nomor || '-',
            hideable: true,
        },
        {
            key: 'pelanggaran',
            label: 'Pelanggaran',
            sortable: true,
            render: (p) => p.daftar_pelanggaran?.nama_pelanggaran || '-',
        },
        {
            key: 'aksi',
            label: 'Aksi',
            headClassName: 'text-right',
            className: 'text-right',
            render: (p) => (
                <div className="flex justify-end gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => openEdit(p)}
                    >
                        <Edit2 className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => destroy(p.id)}
                    >
                        <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Pelanggaran" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Pelanggaran
                        </h1>
                        <p className="text-muted-foreground">
                            Catat dan kelola pelanggaran santri
                        </p>
                    </div>
                    <div className="flex flex-wrap justify-end gap-2">
                        {canCetakSuratPanggilan && (
                            <>
                                <div className="hidden sm:flex sm:gap-2">
                                    <Button
                                        variant="outline"
                                        onClick={openRiwayatGlobal}
                                    >
                                        <RefreshCw className="h-4 w-4" />
                                        Riwayat Cetak
                                    </Button>
                                    <Button
                                        variant="outline"
                                        onClick={openCetakUlang}
                                    >
                                        <Printer className="h-4 w-4" />
                                        Cetak Ulang
                                    </Button>
                                    <Button
                                        onClick={() => setShowPrintModal(true)}
                                    >
                                        <Printer className="h-4 w-4" />
                                        Cetak Surat{' '}
                                        <span className="hidden sm:inline">
                                            Panggilan
                                        </span>
                                    </Button>
                                </div>
                                <div className="sm:hidden">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="outline">
                                                <Printer className="h-4 w-4" />
                                                Cetak
                                                <ChevronDown className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    setShowPrintModal(true)
                                                }
                                            >
                                                <Printer className="h-4 w-4" />
                                                Cetak Surat Panggilan
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                onClick={openCetakUlang}
                                            >
                                                <Printer className="h-4 w-4" />
                                                Cetak Ulang
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                onClick={openRiwayatGlobal}
                                            >
                                                <RefreshCw className="h-4 w-4" />
                                                Riwayat Cetak
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </>
                        )}
                        <Button onClick={openCreate}>
                            <Plus className="h-4 w-4" />
                            Catat{' '}
                            <span className="hidden sm:inline">
                                Pelanggaran
                            </span>
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    data={pelanggaran.data}
                    meta={pelanggaran}
                    keyExtractor={(p) => p.id}
                    onPageChange={(page) =>
                        router.get(
                            '/pelanggaran',
                            {
                                page,
                                sort_column: sortColumn || undefined,
                                sort_direction:
                                    sortDirection === 'none'
                                        ? undefined
                                        : sortDirection,
                                daerah_id: filterDaerah || undefined,
                                asrama_id: filterAsrama || undefined,
                                tanggal_mulai: filterTanggalMulai || undefined,
                                tanggal_selesai:
                                    filterTanggalSelesai || undefined,
                                search: search || undefined,
                                per_page: perPage,
                            },
                            { preserveState: true, preserveScroll: true },
                        )
                    }
                    search={search}
                    onSearchChange={(q) => {
                        setSearch(q);
                        router.get(
                            '/pelanggaran',
                            {
                                search: q || undefined,
                                page: 1,
                                sort_column: sortColumn || undefined,
                                sort_direction:
                                    sortDirection === 'none'
                                        ? undefined
                                        : sortDirection,
                                daerah_id: filterDaerah || undefined,
                                asrama_id: filterAsrama || undefined,
                                tanggal_mulai: filterTanggalMulai || undefined,
                                tanggal_selesai:
                                    filterTanggalSelesai || undefined,
                                per_page: perPage,
                            },
                            { preserveState: true, preserveScroll: true },
                        );
                    }}
                    searchPlaceholder="Cari nama, panggilan, IKSASS, asrama, atau pelanggaran..."
                    sortColumn={sortColumn}
                    sortDirection={sortDirection}
                    onSort={handleSort}
                    perPage={perPage}
                    onPerPageChange={(p) => {
                        setPerPage(p);
                        if (p !== perPage) {
                            router.get(
                                '/pelanggaran',
                                {
                                    per_page: p,
                                    page: 1,
                                    sort_column: sortColumn || undefined,
                                    sort_direction:
                                        sortDirection === 'none'
                                            ? undefined
                                            : sortDirection,
                                    daerah_id: filterDaerah || undefined,
                                    asrama_id: filterAsrama || undefined,
                                    tanggal_mulai:
                                        filterTanggalMulai || undefined,
                                    tanggal_selesai:
                                        filterTanggalSelesai || undefined,
                                    search: search || undefined,
                                },
                                { preserveState: true, preserveScroll: true },
                            );
                        }
                    }}
                    onSelectionChange={setSelectedIds}
                    bulkActions={
                        selectedIds.length > 0 && (
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setShowBulkEditModal(true)}
                                >
                                    <Edit2 className="h-4 w-4" />
                                    Edit
                                </Button>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    onClick={bulkDelete}
                                >
                                    <Trash2 className="h-4 w-4" />
                                    Hapus ({selectedIds.length})
                                </Button>
                            </div>
                        )
                    }
                    filters={
                        <>
                            <Select
                                value={filterDaerah}
                                onChange={(e) => {
                                    setFilterDaerah(e.target.value);
                                    setFilterAsrama('');
                                }}
                                placeholder="Semua Daerah"
                                options={daerah.map((d: any) => ({
                                    value: d.id,
                                    label: d.nama_daerah,
                                }))}
                                className="min-w-[150px]"
                            />
                            <Select
                                value={filterAsrama}
                                onChange={(e) =>
                                    setFilterAsrama(e.target.value)
                                }
                                placeholder="Semua Asrama"
                                options={(filterDaerah
                                    ? asrama.filter(
                                          (a: any) =>
                                              String(a.daerah_id) ===
                                              String(filterDaerah),
                                      )
                                    : asrama
                                ).map((a: any) => ({
                                    value: a.id,
                                    label: a.daerah?.kode
                                        ? `${a.daerah.kode.charAt(0)}.${a.nomor}`
                                        : `Asrama ${a.nomor}`,
                                }))}
                                className="min-w-[150px]"
                            />
                            <Input
                                type="date"
                                value={filterTanggalMulai}
                                onChange={(e) =>
                                    setFilterTanggalMulai(e.target.value)
                                }
                                placeholder="Tanggal Mulai"
                                className="max-w-[150px]"
                            />
                            <Input
                                type="date"
                                value={filterTanggalSelesai}
                                onChange={(e) =>
                                    setFilterTanggalSelesai(e.target.value)
                                }
                                placeholder="Tanggal Selesai"
                                className="max-w-[150px]"
                            />
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    router.get(
                                        '/pelanggaran',
                                        {
                                            daerah_id:
                                                filterDaerah || undefined,
                                            asrama_id:
                                                filterAsrama || undefined,
                                            tanggal_mulai:
                                                filterTanggalMulai || undefined,
                                            tanggal_selesai:
                                                filterTanggalSelesai ||
                                                undefined,
                                            search: search || undefined,
                                            per_page: perPage,
                                            sort_column:
                                                sortColumn || undefined,
                                            sort_direction:
                                                sortDirection === 'none'
                                                    ? undefined
                                                    : sortDirection,
                                        },
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                        },
                                    )
                                }
                            >
                                Filter
                            </Button>
                        </>
                    }
                />
            </div>

            <Modal
                open={showCetakUlang}
                onClose={() => setShowCetakUlang(false)}
                title="Cetak Ulang Surat Panggilan"
            >
                <div className="space-y-4">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Daerah</label>
                        <Select
                            value={reprintDaerahId}
                            onChange={(e) => {
                                setReprintDaerahId(e.target.value);
                                setReprintAsramaId('');
                                setReprintRiwayat([]);
                            }}
                            placeholder="Pilih Daerah"
                            options={daerah.map((d: any) => ({
                                value: d.id,
                                label: d.nama_daerah,
                            }))}
                        />
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium">Asrama</label>
                        <Select
                            value={reprintAsramaId}
                            onChange={(e) => {
                                setReprintAsramaId(e.target.value);
                                fetchRiwayat(e.target.value);
                            }}
                            placeholder="Pilih Asrama"
                            disabled={!reprintDaerahId}
                            options={filteredReprintAsrama.map((a: any) => ({
                                value: a.id,
                                label: a.daerah?.kode
                                    ? `${a.daerah.kode.charAt(0)}.${a.nomor}`
                                    : `Asrama ${a.nomor}`,
                            }))}
                        />
                        {!reprintDaerahId && (
                            <p className="text-xs text-muted-foreground">
                                Pilih daerah terlebih dahulu
                            </p>
                        )}
                    </div>

                    {loadingRiwayat && (
                        <div className="flex items-center justify-center py-8">
                            <svg
                                className="h-6 w-6 animate-spin text-green-600"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    className="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    strokeWidth="4"
                                    fill="none"
                                />
                                <path
                                    className="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                />
                            </svg>
                        </div>
                    )}

                    {!loadingRiwayat &&
                        reprintAsramaId &&
                        reprintRiwayat.length === 0 && (
                            <p className="py-4 text-center text-sm text-muted-foreground">
                                Belum ada riwayat cetak untuk asrama ini.
                            </p>
                        )}

                    {!loadingRiwayat && reprintRiwayat.length > 0 && (
                        <div className="modal-scroll max-h-80 space-y-2 overflow-y-auto">
                            {reprintRiwayat.map((item: any) => (
                                <div
                                    key={item.id}
                                    className="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium">
                                            {item.tanggal_cetak}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {item.jumlah_pelanggaran}{' '}
                                            pelanggaran &middot; {item.pencetak}
                                        </p>
                                    </div>
                                    <Button
                                        size="sm"
                                        onClick={() =>
                                            window.open(
                                                `/pelanggaran/surat-panggilan/${item.id}/cetak-ulang`,
                                                '_blank',
                                            )
                                        }
                                    >
                                        <Printer className="h-4 w-4" />
                                        Cetak Ulang
                                    </Button>
                                </div>
                            ))}
                        </div>
                    )}

                    <div className="flex justify-end pt-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowCetakUlang(false)}
                        >
                            Tutup
                        </Button>
                    </div>
                </div>
            </Modal>

            <Modal
                open={showRiwayatGlobal}
                onClose={() => setShowRiwayatGlobal(false)}
                title="Riwayat Cetak Surat Panggilan"
            >
                <div className="space-y-4">
                    {loadingGlobalRiwayat && (
                        <div className="flex items-center justify-center py-8">
                            <svg
                                className="h-6 w-6 animate-spin text-green-600"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    className="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    strokeWidth="4"
                                    fill="none"
                                />
                                <path
                                    className="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                />
                            </svg>
                        </div>
                    )}

                    {!loadingGlobalRiwayat && globalRiwayat.length === 0 && (
                        <p className="py-4 text-center text-sm text-muted-foreground">
                            Belum ada riwayat cetak.
                        </p>
                    )}

                    {!loadingGlobalRiwayat && globalRiwayat.length > 0 && (
                        <div className="modal-scroll max-h-96 space-y-3 overflow-y-auto">
                            {globalRiwayat.map((item: any) => (
                                <div
                                    key={item.printed_at}
                                    className="rounded-lg border p-3"
                                >
                                    <div className="flex items-start justify-between">
                                        <div className="min-w-0 flex-1 space-y-1">
                                            <p className="text-sm font-medium">
                                                {item.tanggal_display}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {item.jumlah_surat} surat
                                                &middot;{' '}
                                                {item.jumlah_pelanggaran}{' '}
                                                pelanggaran &middot;{' '}
                                                {item.pencetak}
                                            </p>
                                        </div>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            className="ml-2 shrink-0"
                                            onClick={() =>
                                                hapusRiwayat(item.printed_at)
                                            }
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    <div className="flex justify-end pt-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowRiwayatGlobal(false)}
                        >
                            Tutup
                        </Button>
                    </div>
                </div>
            </Modal>

            <Modal
                open={showPrintModal}
                onClose={() => {
                    setShowPrintModal(false);
                    setPrintDaerahId('');
                }}
                title="Cetak Surat Panggilan"
            >
                <div className="space-y-4">
                    <Button
                        className="w-full"
                        onClick={() => {
                            setShowPrintModal(false);
                            setPrintDaerahId('');
                            window.open(
                                '/pelanggaran/surat-panggilan/cetak',
                                '_blank',
                            );
                        }}
                    >
                        <Printer className="h-4 w-4" />
                        Cetak Semua
                    </Button>

                    <div className="relative">
                        <div className="absolute inset-0 flex items-center">
                            <span className="w-full border-t" />
                        </div>
                        <div className="relative flex justify-center text-xs uppercase">
                            <span className="bg-card px-2 text-muted-foreground">
                                atau
                            </span>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <label className="text-sm font-medium">
                            Cetak Per Daerah
                        </label>
                        <Select
                            value={printDaerahId}
                            onChange={(e) => setPrintDaerahId(e.target.value)}
                            placeholder="Pilih Daerah"
                            options={daerah.map((d: any) => ({
                                value: d.id,
                                label: d.nama_daerah,
                            }))}
                        />
                        <Button
                            className="w-full"
                            disabled={!printDaerahId}
                            onClick={() => {
                                if (!printDaerahId) return;
                                setShowPrintModal(false);
                                const url = `/pelanggaran/surat-panggilan/cetak?daerah_id=${printDaerahId}`;
                                window.open(url, '_blank');
                                setPrintDaerahId('');
                            }}
                        >
                            <Printer className="h-4 w-4" />
                            Cetak
                        </Button>
                    </div>
                </div>
            </Modal>

            <Modal
                open={showBulkEditModal}
                onClose={() => setShowBulkEditModal(false)}
                title={`Edit ${selectedIds.length} Pelanggaran`}
            >
                <div className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Perubahan sumber dan keterangan akan diterapkan ke{' '}
                        {selectedIds.length} pelanggaran yang dipilih.
                    </p>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Sumber</label>
                        <Select
                            value={bulkForm.sumber_pencatatan}
                            onChange={(e) =>
                                setBulkForm({
                                    ...bulkForm,
                                    sumber_pencatatan: e.target.value,
                                })
                            }
                            options={[
                                { value: 'petugas', label: 'Petugas' },
                                {
                                    value: 'ketua_kamar',
                                    label: 'Ketua Kamar',
                                },
                            ]}
                        />
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Keterangan
                        </label>
                        <Input
                            value={bulkForm.keterangan}
                            onChange={(e) =>
                                setBulkForm({
                                    ...bulkForm,
                                    keterangan: e.target.value,
                                })
                            }
                        />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowBulkEditModal(false)}
                        >
                            Batal
                        </Button>
                        <Button onClick={submitBulkEdit}>Simpan</Button>
                    </div>
                </div>
            </Modal>

            <Modal
                open={showEditModal}
                onClose={() => setShowEditModal(false)}
                title="Edit Pelanggaran"
            >
                <div className="space-y-4">
                    <div className="rounded-lg border bg-muted/30 p-3 text-sm">
                        <div className="flex items-center justify-between">
                            <span className="font-medium">
                                {editing?.santri?.nama ||
                                    `${editing?.jumlah ?? ''} Orang`}
                            </span>
                            <span className="text-muted-foreground">
                                {editing?.asrama?.daerah?.kode
                                    ? `${editing.asrama.daerah.kode.charAt(0)}.${editing.asrama.nomor}`
                                    : editing?.asrama?.nomor || '-'}
                            </span>
                        </div>
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Jenis Pelanggaran
                        </label>
                        <Select
                            value={editForm.daftar_pelanggaran_id}
                            onChange={(e) =>
                                setEditForm({
                                    ...editForm,
                                    daftar_pelanggaran_id: e.target.value,
                                })
                            }
                            placeholder="Pilih Jenis Pelanggaran"
                            options={daftarPelanggaran.map((d: any) => ({
                                value: d.id,
                                label: d.nama_pelanggaran,
                            }))}
                        />
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium">Tanggal</label>
                        <Input
                            type="date"
                            value={editForm.tanggal}
                            onChange={(e) =>
                                setEditForm({
                                    ...editForm,
                                    tanggal: e.target.value,
                                })
                            }
                        />
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium">Sumber</label>
                        <Select
                            value={editForm.sumber_pencatatan}
                            onChange={(e) =>
                                setEditForm({
                                    ...editForm,
                                    sumber_pencatatan: e.target.value,
                                })
                            }
                            options={[
                                { value: 'petugas', label: 'Petugas' },
                                {
                                    value: 'ketua_kamar',
                                    label: 'Ketua Kamar',
                                },
                            ]}
                        />
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Keterangan
                        </label>
                        <Input
                            value={editForm.keterangan}
                            onChange={(e) =>
                                setEditForm({
                                    ...editForm,
                                    keterangan: e.target.value,
                                })
                            }
                        />
                    </div>

                    <div className="flex justify-end gap-3 pt-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowEditModal(false)}
                        >
                            Batal
                        </Button>
                        <Button onClick={submitEdit}>Simpan</Button>
                    </div>
                </div>
            </Modal>

            <Modal
                open={showModal}
                onClose={closeCatat}
                title={editing ? 'Edit Pelanggaran' : 'Catat Pelanggaran'}
            >
                <div className="space-y-4">
                    {!editing && voiceEnabled && (
                        <div className="space-y-3 rounded-lg border border-dashed p-3">
                            <div className="flex items-center gap-1 rounded-md bg-muted p-1">
                                {(
                                    [
                                        {
                                            nilai: 'manual',
                                            label: 'Catat Manual',
                                        },
                                        {
                                            nilai: 'voice',
                                            label: 'Catat dengan Voice',
                                        },
                                    ] as const
                                ).map((tab) => (
                                    <button
                                        key={tab.nilai}
                                        type="button"
                                        onClick={() => {
                                            setModeCatat(tab.nilai);
                                            setVoiceError(null);

                                            if (tab.nilai === 'manual') {
                                                setDraft(null);
                                                setMerekam(false);
                                                stopTimer();
                                            }
                                        }}
                                        className={cn(
                                            'flex-1 rounded px-3 py-1.5 text-sm font-medium transition-colors',
                                            modeCatat === tab.nilai
                                                ? 'bg-background text-foreground shadow-xs'
                                                : 'text-muted-foreground hover:text-foreground',
                                        )}
                                    >
                                        {tab.label}
                                    </button>
                                ))}
                            </div>

                            {modeCatat === 'voice' && (
                                <div className="space-y-3">
                                    <div className="flex items-center gap-3">
                                        <button
                                            type="button"
                                            onClick={
                                                merekam ? stopRekam : mulaiRekam
                                            }
                                            disabled={memproses}
                                            aria-label={
                                                merekam
                                                    ? 'Hentikan rekaman'
                                                    : 'Mulai rekaman'
                                            }
                                            className={cn(
                                                'flex h-11 w-11 shrink-0 items-center justify-center rounded-full transition-colors focus:ring-2 focus:ring-ring focus:outline-none disabled:opacity-50',
                                                merekam
                                                    ? 'bg-red-500 text-white hover:bg-red-600'
                                                    : 'bg-green-600 text-white hover:bg-green-700',
                                            )}
                                        >
                                            {memproses ? (
                                                <Loader2 className="h-5 w-5 animate-spin" />
                                            ) : merekam ? (
                                                <Square className="h-4 w-4 fill-current" />
                                            ) : (
                                                <Mic className="h-5 w-5" />
                                            )}
                                        </button>
                                        <p className="min-w-0 flex-1 text-sm text-muted-foreground">
                                            {memproses
                                                ? 'Memproses rekaman, sebentar...'
                                                : merekam
                                                  ? `Merekam ${detik} dari ${voiceMaxDuration} detik`
                                                  : 'Ucapkan kalimat, misalnya "nama fauzi tidak jubah gamis isya".'}
                                        </p>
                                    </div>

                                    {voiceError && (
                                        <p className="flex items-start gap-2 text-sm text-red-600">
                                            <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                                            {voiceError}
                                        </p>
                                    )}

                                    {draft && (
                                        <div className="space-y-2 rounded-md border p-3">
                                            <div>
                                                <p className="text-xs text-muted-foreground">
                                                    Yang terdengar
                                                </p>
                                                <p className="text-sm">
                                                    {draft.transcript}
                                                </p>
                                            </div>

                                            {draft.daftar_pelanggaran_label && (
                                                <p className="text-sm">
                                                    <span className="text-muted-foreground">
                                                        Jenis:{' '}
                                                    </span>
                                                    {
                                                        draft.daftar_pelanggaran_label
                                                    }
                                                </p>
                                            )}

                                            {draft.anonymous && (
                                                <p className="text-sm">
                                                    <span className="text-muted-foreground">
                                                        Tanpa nama:{' '}
                                                    </span>
                                                    {draft.jumlah || 1} orang
                                                </p>
                                            )}

                                            {draft.keterangan && (
                                                <p className="text-sm">
                                                    <span className="text-muted-foreground">
                                                        Keterangan:{' '}
                                                    </span>
                                                    {draft.keterangan}
                                                </p>
                                            )}

                                            {draft.needs_review?.length > 0 && (
                                                <p className="text-xs text-muted-foreground">
                                                    Perlu diperiksa:{' '}
                                                    {draft.needs_review
                                                        .map(
                                                            (item: string) =>
                                                                LABEL_PERIKSA[
                                                                    item
                                                                ] ?? item,
                                                        )
                                                        .join(', ')}
                                                </p>
                                            )}
                                        </div>
                                    )}

                                    <VoiceDraftTable
                                        entries={voiceEntries}
                                        asrama={asrama}
                                        santri={santri}
                                        daftarPelanggaran={daftarPelanggaran}
                                        tanggalDefault={tanggalDefault}
                                        onUbah={ubahBaris}
                                        onUbahAsrama={ubahAsramaBaris}
                                        onPilihSantri={pilihSantriBaris}
                                        onTambah={tambahBaris}
                                        onHapus={hapusBaris}
                                    />

                                    {voiceKesalahan.length > 0 && (
                                        <ul className="space-y-1 rounded-md border border-red-200 bg-red-50 p-3">
                                            {voiceKesalahan.map((pesan) => (
                                                <li
                                                    key={pesan}
                                                    className="flex items-start gap-2 text-sm text-red-700"
                                                >
                                                    <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                                                    {pesan}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            )}
                        </div>
                    )}

                    {modeCatat === 'manual' && (
                        <>
                            {/* Daerah */}
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Daerah
                                </label>
                                <Select
                                    value={daerahId}
                                    onChange={(e) => {
                                        setDaerahId(e.target.value);
                                        setForm({ ...form, asrama_id: '' });
                                        setSantriEntries([]);
                                    }}
                                    placeholder="Pilih Daerah"
                                    options={daerahList.map((d: any) => ({
                                        value: d.id,
                                        label: d.nama_daerah,
                                    }))}
                                    disabled={!!editing}
                                />
                            </div>

                            {/* Asrama (setelah daerah) */}
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Asrama
                                </label>
                                <Select
                                    value={form.asrama_id}
                                    onChange={(e) => {
                                        setForm({
                                            ...form,
                                            asrama_id: e.target.value,
                                        });
                                        setSantriEntries([]);
                                    }}
                                    placeholder="Pilih Asrama"
                                    options={filteredAsrama.map((a: any) => ({
                                        value: a.id,
                                        label: a.daerah?.kode
                                            ? `${a.daerah.kode.charAt(0)}.${a.nomor}`
                                            : `Asrama ${a.nomor}`,
                                    }))}
                                    disabled={!daerahId || !!editing}
                                />
                            </div>

                            {/* Santri (multi-entry, allow duplicates) */}
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Santri
                                </label>
                                {santriEntries.length > 0 && (
                                    <div className="mb-3 space-y-2">
                                        {santriEntries.map((s) => (
                                            <div
                                                key={s.uid}
                                                className="space-y-2 rounded-lg border p-2"
                                            >
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm font-medium">
                                                        {s.nama}
                                                    </span>
                                                    {!editing && (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                removeSantri(
                                                                    s.uid,
                                                                )
                                                            }
                                                            className="rounded-md p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Select
                                                        value={
                                                            s.daftar_pelanggaran_id
                                                        }
                                                        onChange={(e) =>
                                                            updateSantriPelanggaran(
                                                                s.uid,
                                                                e.target.value,
                                                            )
                                                        }
                                                        placeholder="Pilih"
                                                        options={daftarPelanggaran.map(
                                                            (d: any) => ({
                                                                value: d.id,
                                                                label: d.nama_pelanggaran,
                                                            }),
                                                        )}
                                                        disabled={!!editing}
                                                        className="min-w-[160px] flex-1"
                                                    />
                                                    <Input
                                                        type="date"
                                                        value={s.tanggal}
                                                        onChange={(e) =>
                                                            updateSantriTanggal(
                                                                s.uid,
                                                                e.target.value,
                                                            )
                                                        }
                                                        disabled={!!editing}
                                                        className="w-[140px] shrink-0"
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                {!editing && (
                                    <div className="flex flex-col gap-2">
                                        <div className="flex gap-2">
                                            <div className="relative min-w-[150px] flex-1">
                                                <Input
                                                    value={pendingSantriSearch}
                                                    onChange={(e) => {
                                                        setPendingSantriSearch(
                                                            e.target.value,
                                                        );
                                                        setPendingSantriId('');
                                                        setShowSantriDropdown(
                                                            true,
                                                        );
                                                    }}
                                                    onFocus={() =>
                                                        setShowSantriDropdown(
                                                            true,
                                                        )
                                                    }
                                                    onBlur={() =>
                                                        setTimeout(
                                                            () =>
                                                                setShowSantriDropdown(
                                                                    false,
                                                                ),
                                                            200,
                                                        )
                                                    }
                                                    placeholder="Cari santri..."
                                                    disabled={!form.asrama_id}
                                                />
                                                {showSantriDropdown &&
                                                    pendingsantri_id === '' &&
                                                    form.asrama_id && (
                                                        <div
                                                            className="absolute z-50 mt-1 max-h-48 min-w-max overflow-y-auto rounded-md border bg-popover whitespace-nowrap shadow-md [&::-webkit-scrollbar]:hidden"
                                                            style={{
                                                                scrollbarWidth:
                                                                    'none',
                                                                msOverflowStyle:
                                                                    'none',
                                                            }}
                                                        >
                                                            {filteredSantriBySearch.length >
                                                            0 ? (
                                                                filteredSantriBySearch.map(
                                                                    (
                                                                        s: any,
                                                                    ) => (
                                                                        <button
                                                                            key={
                                                                                s.id
                                                                            }
                                                                            type="button"
                                                                            className="flex w-full items-center px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground"
                                                                            onClick={() => {
                                                                                setPendingSantriId(
                                                                                    String(
                                                                                        s.id,
                                                                                    ),
                                                                                );
                                                                                setPendingSantriSearch(
                                                                                    s.nama,
                                                                                );
                                                                                setShowSantriDropdown(
                                                                                    false,
                                                                                );
                                                                            }}
                                                                        >
                                                                            {
                                                                                s.nama
                                                                            }
                                                                        </button>
                                                                    ),
                                                                )
                                                            ) : (
                                                                <p className="px-3 py-2 text-sm text-muted-foreground">
                                                                    Santri tidak
                                                                    ditemukan
                                                                </p>
                                                            )}
                                                        </div>
                                                    )}
                                            </div>
                                            <Select
                                                value={pendingPelanggaranId}
                                                onChange={(e) =>
                                                    setPendingPelanggaranId(
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Jenis Pelanggaran"
                                                options={daftarPelanggaran.map(
                                                    (d: any) => ({
                                                        value: d.id,
                                                        label: d.nama_pelanggaran,
                                                    }),
                                                )}
                                                className="max-w-[220px] min-w-[160px]"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={addSantri}
                                                disabled={
                                                    !pendingsantri_id ||
                                                    !pendingPelanggaranId
                                                }
                                            >
                                                <Plus className="h-4 w-4" />
                                            </Button>
                                        </div>
                                        {!form.asrama_id && (
                                            <p className="text-xs text-muted-foreground">
                                                Pilih daerah dan asrama terlebih
                                                dahulu
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>

                            {/* Tanpa Nama (row-based, independent) */}
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Tanpa Nama
                                </label>
                                {anonymousEntries.length > 0 && (
                                    <div className="mb-3 space-y-2">
                                        {anonymousEntries.map((a) => (
                                            <div
                                                key={a.uid}
                                                className="space-y-2 rounded-lg border p-2"
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        <Input
                                                            type="number"
                                                            min={1}
                                                            value={a.jumlah}
                                                            onChange={(e) =>
                                                                updateAnonymousJumlah(
                                                                    a.uid,
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            className="w-16 shrink-0"
                                                            disabled={!!editing}
                                                        />
                                                        <span className="text-xs text-muted-foreground">
                                                            orang
                                                        </span>
                                                    </div>
                                                    {!editing && (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                removeAnonymous(
                                                                    a.uid,
                                                                )
                                                            }
                                                            className="rounded-md p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Select
                                                        value={
                                                            a.daftar_pelanggaran_id
                                                        }
                                                        onChange={(e) =>
                                                            updateAnonymousPelanggaran(
                                                                a.uid,
                                                                e.target.value,
                                                            )
                                                        }
                                                        placeholder="Jenis Pelanggaran"
                                                        options={daftarPelanggaran.map(
                                                            (d: any) => ({
                                                                value: d.id,
                                                                label: d.nama_pelanggaran,
                                                            }),
                                                        )}
                                                        disabled={!!editing}
                                                        className="min-w-[160px] flex-1"
                                                    />
                                                    <Input
                                                        type="date"
                                                        value={a.tanggal}
                                                        onChange={(e) =>
                                                            updateAnonymousTanggal(
                                                                a.uid,
                                                                e.target.value,
                                                            )
                                                        }
                                                        disabled={!!editing}
                                                        className="w-[140px] shrink-0"
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                {!editing && (
                                    <div className="flex gap-2">
                                        <Input
                                            type="number"
                                            min={1}
                                            value={pendingAnonJumlah}
                                            onChange={(e) =>
                                                setPendingAnonJumlah(
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Jumlah"
                                            className="w-20 shrink-0"
                                        />
                                        <Select
                                            value={pendingAnonPelanggaranId}
                                            onChange={(e) =>
                                                setPendingAnonPelanggaranId(
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Jenis Pelanggaran"
                                            options={daftarPelanggaran.map(
                                                (d: any) => ({
                                                    value: d.id,
                                                    label: d.nama_pelanggaran,
                                                }),
                                            )}
                                            className="min-w-[160px]"
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={addAnonymous}
                                            disabled={
                                                !pendingAnonJumlah ||
                                                !pendingAnonPelanggaranId
                                            }
                                        >
                                            <Plus className="h-4 w-4" />
                                        </Button>
                                    </div>
                                )}
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                {editing && (
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">
                                            Tanggal
                                        </label>
                                        <Input
                                            type="date"
                                            value={form.tanggal}
                                            onChange={(e) =>
                                                setForm({
                                                    ...form,
                                                    tanggal: e.target.value,
                                                })
                                            }
                                        />
                                    </div>
                                )}
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Sumber
                                    </label>
                                    <Select
                                        value={form.sumber_pencatatan}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                sumber_pencatatan:
                                                    e.target.value,
                                            })
                                        }
                                        options={[
                                            {
                                                value: 'petugas',
                                                label: 'Petugas',
                                            },
                                            {
                                                value: 'ketua_kamar',
                                                label: 'Ketua Kamar',
                                            },
                                        ]}
                                    />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Keterangan
                                </label>
                                <Input
                                    value={form.keterangan}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            keterangan: e.target.value,
                                        })
                                    }
                                />
                            </div>
                        </>
                    )}
                    <div className="flex justify-end gap-3 pt-2">
                        <Button variant="outline" onClick={closeCatat}>
                            Batal
                        </Button>
                        {modeCatat === 'voice' ? (
                            <Button
                                onClick={submitVoice}
                                disabled={
                                    memproses || voiceEntries.length === 0
                                }
                            >
                                {editing ? 'Simpan' : 'Catat'}
                            </Button>
                        ) : (
                            <Button onClick={submit}>
                                {editing ? 'Simpan' : 'Catat'}
                            </Button>
                        )}
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
