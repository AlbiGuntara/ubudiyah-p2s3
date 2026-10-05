@php
    // DomPDF tidak mendukung `table-layout: fixed` dan mengabaikan lebar
    // persentase pada tabel, sehingga lebar kolom harus dinyatakan dalam
    // satuan absolut. Nilai `width` pada sel hanya mencakup bagian isi sel,
    // jadi padding dan border dikurangi agar lebar akhir kolom sesuai
    // persentase yang diinginkan.
    $persenKolom = [5, 31, 13, 16, 13, 11, 11];
    $lebarIsiHalaman = 524.4; // 185mm dalam poin (215mm - margin 15mm x 2)
    $lebarTambahan = 6; // padding kiri+kanan 6px + border 2px, dalam poin
    $lebarKolom = array_map(
        fn(int $persen): float => round(($lebarIsiHalaman * $persen) / 100 - $lebarTambahan, 1),
        $persenKolom,
    );

    // Baris kosong sebagai tempat mencatat santri baru di asrama tersebut.
    $jumlahBarisKosong = 5;
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Validasi Data Santri</title>
    <style>
        @page {
            size: 215mm 330mm;
            margin: 15mm 15mm 15mm 15mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
        }

        /* Kop surat sama dengan surat panggilan. */
        .kop {
            text-align: center;
            margin-bottom: 1px;
            border-bottom: 3px solid #000;
            padding-bottom: 6px;
        }

        .kop .logo-ponpes {
            height: 70px;
            margin-bottom: 3px;
        }

        .kop .nama-ponpes {
            font-size: 12pt;
            letter-spacing: 1px;
            margin: 0 0 1px 0;
        }

        .kop .bidang {
            font-size: 12pt;
            font-weight: bold;
            margin: 0 0 1px 0;
        }

        .kop .alamat {
            font-size: 10pt;
            margin: 0;
        }

        .kop .notaris {
            font-size: 8pt;
            margin: 2px 0 0 0;
        }

        /* Setiap asrama dipisah sebagai satu blok terpisah, satu asrama satu
           halaman, karena setiap blok diserahkan ke asrama yang berbeda. */
        .asrama-blok {
            page-break-before: always;
        }

        .asrama-blok-pertama {
            page-break-before: avoid;
        }

        .alamat-tujuan {
            margin: 10px 0 4px 0;
        }

        .perihal {
            margin-bottom: 6px;
        }

        .judul-blik {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }

        table th {
            border: 1px solid #000;
            padding: 5px 3px;
            text-align: center;
            font-weight: bold;
            background-color: #f0f0f0;
        }

        /* Judul kolom diulang di setiap halaman. */
        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        table td {
            border: 1px solid #000;
            padding: 5px 3px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        /* Lebar kolom mengikuti isi konten. */
        @foreach ($lebarKolom as $i => $lebar)
            th:nth-child({{ $i + 1 }}),
            td:nth-child({{ $i + 1 }}) {
                width: {{ $lebar }}pt;
            }

        @endforeach

        td.no,
        td.asrama,
        td.status,
        td.jabatan {
            text-align: center;
            white-space: nowrap;
        }

        td.nis,
        td.iksass {
            text-align: center;
        }

        .kosong {
            text-align: center;
            font-style: italic;
            padding: 14px 0;
        }

        /* Baris kosong untuk diisi manual, diberi tinggi lebih agar muat tulis. */
        .baris-kosong td {
            padding: 11px 3px;
        }

        .catatan {
            margin-top: 12px;
            font-size: 10pt;
            border: 1px solid #000;
            padding: 7px 9px;
            page-break-inside: avoid;
        }

        .catatan-judul {
            font-weight: bold;
            margin-bottom: 4px;
            text-decoration: underline;
        }

        .catatan ul {
            margin: 0;
            padding-left: 18px;
        }

        .catatan li {
            margin-bottom: 2px;
        }

        .ttd {
            margin-top: 22px;
            text-align: right;
            font-size: 10pt;
            page-break-inside: avoid;
        }

        .ttd .ruang {
            height: 38px;
        }
    </style>
</head>

<body>
    @forelse ($daerahGroups as $daerahIndex => $daerah)
        @forelse ($daerah['asrama'] as $asramaIndex => $asrama)
            <div class="asrama-blok {{ $asramaIndex === 0 && $daerahIndex === 0 ? 'asrama-blok-pertama' : '' }}">
                <div class="kop">
                    <img src="{{ public_path('logo/p2s3.png') }}" alt="Logo P2S3" class="logo-ponpes">
                    <p class="nama-ponpes">PONDOK PESANTREN "SALAFIYAH SYAFI'IYAH" SUKOREJO</p>
                    <p class="bidang">BIDANG KEPESANTRENAN</p>
                    <p class="alamat">SUMBEREJO BANYUPUTIH SITUBONDO JAWA TIMUR</p>
                    <p class="notaris">Akte Notaris No. 4/25.08.1970 &amp; No. 3/05.07.2001</p>
                </div>

                <p class="perihal">Perihal: <strong>VALIDASI DATA SANTRI</strong></p>

                <div class="alamat-tujuan">
                    Kepada Yth.<br>
                    <strong>Ketua Kamar {{ $daerah['nama_daerah'] }} No. {{ $asrama['nomor'] }}</strong><br>
                    di Tempat
                </div>

                <p class="judul-blik">DATA SANTRI ASRAMA {{ $asrama['label'] }}</p>

                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>NIS</th>
                            <th>IKSASS</th>
                            <th>Asrama</th>
                            <th>Status</th>
                            <th>Jabatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($asrama['santri'] as $nomor => $baris)
                            <tr>
                                <td class="no">{{ $nomor + 1 }}</td>
                                <td class="nama">{{ $baris['nama'] }}</td>
                                <td class="nis">{{ $baris['nis'] }}</td>
                                <td class="iksass">{{ $baris['iksass'] }}</td>
                                <td class="asrama">{{ $baris['asrama'] }}</td>
                                <td class="status">{{ $baris['status'] }}</td>
                                <td class="jabatan">{{ $baris['jabatan'] }}</td>
                            </tr>
                        @endforeach

                        @php $nomorBaris = count($asrama['santri']); @endphp
                        @for ($kosong = 0; $kosong < $jumlahBarisKosong; $kosong++)
                            <tr class="baris-kosong">
                                <td class="no">{{ $nomorBaris + $kosong + 1 }}</td>
                                <td class="nama"></td>
                                <td class="nis"></td>
                                <td class="iksass"></td>
                                <td class="asrama"></td>
                                <td class="status"></td>
                                <td class="jabatan"></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>

                <div class="catatan">
                    <div class="catatan-judul">Keterangan</div>
                    <ul>
                        <li>Mohon melengkapi data yang masih kosong dan memperbaiki data yang belum sesuai.</li>
                        <li>Penulisan jabatan wajib dicantumkan di dalam tanda kurung pada kolom Jabatan. Contoh:
                            (Ketua)
                            , (Wakil), (Ubudiyah).</li>
                        <li>Bagi santri yang berpindah asrama, kolom Asrama harap diisi dengan nama asrama yang baru.
                        </li>
                    </ul>
                </div>

                <div class="ttd">
                    <div>Ketua Kamar</div>
                    <div class="ruang"></div>
                    <div>( ................................................ )</div>
                </div>
            </div>
        @empty
            @if ($daerahIndex === 0)
                <p class="kosong">Tidak ada data Santri.</p>
            @endif
        @endforelse
    @empty
        <p class="kosong">Tidak ada data Santri.</p>
    @endforelse
</body>

</html>
