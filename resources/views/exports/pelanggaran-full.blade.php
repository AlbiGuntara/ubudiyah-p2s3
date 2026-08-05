<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Export Pelanggaran Full</title>
    <style>
        @page {
            size: 215mm 330mm;
            margin: 12mm 12mm 12mm 12mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #000;
        }

        .daerah-header {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin: 5px 0 10px 0;
        }

        .tanggal-cetak {
            text-align: right;
            font-size: 9pt;
            margin-bottom: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 8pt;
        }

        table th {
            border: 1px solid #000;
            padding: 4px 4px;
            text-align: center;
            font-weight: bold;
            background-color: #f0f0f0;
            font-size: 7.5pt;
        }

        table td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
        }

        table td:nth-child(1) {
            text-align: center;
            width: 20px;
        }

        table td:nth-child(3) {
            text-align: center;
        }

        table td:nth-child(4) {
            text-align: center;
        }

        table td:nth-child(5) {
            text-align: left;
            white-space: nowrap;
        }

        table td:nth-child(6) {
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }

        .txt-selesai {
            color: #16a34a;
        }

        .txt-sebagian {
            color: #ca8a04;
        }

        .txt-belum {
            color: #000000;
        }

        .catatan-warna {
            font-size: 8pt;
            margin-top: 8px;
            padding: 6px 8px;
            border: 1px solid #000;
        }

        .catatan-warna b {
            font-size: 8.5pt;
        }

        .warna-sw {
            display: inline-block;
            width: 8px;
            height: 8px;
            margin-right: 3px;
            border: 1px solid #000;
        }

        .warna-sw.txt-selesai {
            background-color: #16a34a;
        }

        .warna-sw.txt-sebagian {
            background-color: #ca8a04;
        }

        .warna-sw.txt-belum {
            background-color: #000000;
        }
    </style>
</head>

<body>

    @php
        $hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulanIndo = [
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];
        $tglCetak = now();
        $hari = $hariIndo[$tglCetak->dayOfWeek];
        $tgl = $tglCetak->day;
        $bln = $bulanIndo[$tglCetak->month - 1];
        $thn = $tglCetak->year;
    @endphp

    @foreach ($daerahGroups as $groupIndex => $group)
        @php
            $daerah = $group['daerah'];
            $santriList = $group['santri'];
        @endphp

        <div class="daerah-header">
            DATA PELANGGARAN UBUDIYAH <br> DAERAH {{ strtoupper($daerah->nama_daerah) }}
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Santri</th>
                    <th>NIS</th>
                    <th>Asrama</th>
                    <th>Jenis Pelanggaran</th>
                    <th>Tgl. Pelanggaran</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach ($santriList as $santriKey => $santriData)
                    @php
                        $santriNama = $santriData['santri_nama'];
                        $santriNis = $santriData['santri_nis'];
                        $asramaLabel = $santriData['asrama_label'];
                        $pelanggarans = $santriData['pelanggarans'];
                        $isAnon = $santriData['is_anonymous'] ?? false;
                        $anonSummary = $santriData['anon_summary'] ?? null;
                        $warnaStatus = $santriData['warna_status'] ?? 'belum';
                    @endphp

                    @if ($isAnon)
                        @foreach ($pelanggarans as $item)
                            <tr class="txt-{{ $warnaStatus }}">
                                <td style="text-align: center;">{{ $no }}</td>
                                <td>{{ $santriNama }}</td>
                                <td style="text-align: center;">{{ $santriNis }}</td>
                                <td style="text-align: center;">{{ $asramaLabel }}</td>
                                <td style="white-space: nowrap; text-align: left;">{{ $item->jumlah }}
                                    {{ $item->daftarPelanggaran?->nama_pelanggaran ?? '-' }}</td>
                                <td style="text-align: center;">
                                    {{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    @elseif ($pelanggarans->isEmpty())
                        <tr class="txt-{{ $warnaStatus }}">
                            <td style="text-align: center;">{{ $no }}</td>
                            <td>{{ $santriNama }}</td>
                            <td style="text-align: center;">{{ $santriNis }}</td>
                            <td style="text-align: center;">{{ $asramaLabel }}</td>
                            <td style="white-space: nowrap; text-align: left;">-</td>
                            <td style="text-align: center;">-</td>
                        </tr>
                    @else
                        @foreach ($pelanggarans as $item)
                            <tr class="txt-{{ $warnaStatus }}">
                                <td style="text-align: center;">{{ $no }}</td>
                                <td>{{ $santriNama }}</td>
                                <td style="text-align: center;">{{ $santriNis }}</td>
                                <td style="text-align: center;">{{ $asramaLabel }}</td>
                                <td style="white-space: nowrap; text-align: left;">
                                    {{ $item->daftarPelanggaran?->nama_pelanggaran ?? '-' }}</td>
                                <td style="text-align: center;">
                                    {{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                    @php $no++; @endphp
                @endforeach
            </tbody>
        </table>

        <div class="catatan-warna">
            <b>Keterangan Warna Teks:</b>
            <div style="margin-top: 2px;">
                <span class="warna-sw txt-selesai"></span>
                <b class="txt-selesai">Hijau</b> : Sudah menyelesaikan semua pembinaan
            </div>
            <div>
                <span class="warna-sw txt-sebagian"></span>
                <b class="txt-sebagian">Kuning</b> : Sudah menyelesaikan sebagian pembinaan
            </div>
            <div>
                <span class="warna-sw txt-belum"></span>
                <b class="txt-belum">Hitam</b> : Belum melaksanakan pembinaan sama sekali
            </div>
        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>

</html>
