<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Cetak Pembinaan Ubudiyah</title>
    <style>
        @page {
            size: 330mm 215mm landscape;
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

        table.pembinaan {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 8pt;
        }

        table.pembinaan th {
            border: 1px solid #000;
            padding: 4px 4px;
            text-align: center;
            font-weight: bold;
            background-color: #f0f0f0;
            font-size: 7.5pt;
        }

        table.pembinaan td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
        }

        table.pembinaan td:nth-child(1) {
            text-align: center;
            width: 20px;
        }

        table.pembinaan td:nth-child(4) {
            text-align: center;
        }

        table.pembinaan td:nth-child(5) {
            text-align: center;
        }

        table.pembinaan td:nth-child(6) {
            text-align: left;
            white-space: nowrap;
        }

        table.pembinaan td:nth-child(7) {
            text-align: center;
        }

        table.pembinaan td:nth-child(8) {
            text-align: center;
        }

        table.pembinaan td:nth-child(9) {
            text-align: center;
        }

        table.pembinaan td:nth-child(10) {
            text-align: center;
        }

        .footer-cetak {
            position: fixed;
            bottom: 8mm;
            left: 12mm;
            right: 12mm;
            font-size: 7pt;
            color: #666;
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 3px;
        }

        .page-break {
            page-break-after: always;
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

    @foreach ($groups as $groupIndex => $group)
        @php
            $daerah = $group['daerah'];
            $santriList = $group['santri'];
        @endphp

        <div class="daerah-header">
            DATA PELANGGARAN UBUDIYAH <br> DAERAH {{ strtoupper($daerah->nama_daerah) }}
        </div>

        <div class="tanggal-cetak">
            Dicetak: {{ $hari }}, {{ $tgl }} {{ $bln }} {{ $thn }}
        </div>

        <table class="pembinaan">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Santri</th>
                    <th>NIS</th>
                    <th>Asrama</th>
                    <th>IKSASS</th>
                    <th>Jenis Pelanggaran</th>
                    <th>Tgl. Pelanggaran</th>
                    <th>Total Sanksi</th>
                    <th>Sanksi Disetor</th>
                    <th>Tgl. Setor</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach ($santriList as $santriKey => $santriData)
                    @php
                        $santriItem = $santriData['santri'];
                        $asramaItem = $santriData['asrama'];
                        $pelanggarans = $santriData['pelanggarans'];
                        $totalSanksi = $santriData['total_sanksi'];

                        $asramaText = '-';
                        if ($asramaItem) {
                            $kodeDaerah = $asramaItem->daerah?->kode ?? '';
                            $asramaText = $kodeDaerah
                                ? substr($kodeDaerah, 0, 1) . '.' . $asramaItem->nomor
                                : (string) $asramaItem->nomor;
                        }
                    @endphp

                    @if ($pelanggarans->isEmpty())
                        <tr>
                            <td style="text-align: center;">{{ $no }}</td>
                            <td>{{ $santriItem?->nama ?? 'Tanpa Nama' }}</td>
                            <td style="text-align: center;">{{ $santriItem?->nis ?? '-' }}</td>
                            <td style="text-align: center;">{{ $asramaText }}</td>
                            <td style="text-align: center;">{{ $santriItem?->iksass ?? '-' }}</td>
                            <td style="white-space: nowrap; text-align: left;">-</td>
                            <td style="text-align: center;">-</td>
                            <td style="text-align: center;">{{ number_format($totalSanksi) }}</td>
                            <td style="text-align: center;"></td>
                            <td style="text-align: center;"></td>
                        </tr>
                    @else
                        @foreach ($pelanggarans as $item)
                            <tr>
                                <td style="text-align: center;">{{ $no }}</td>
                                <td>{{ $santriItem?->nama ?? 'Tanpa Nama' }}</td>
                                <td style="text-align: center;">{{ $santriItem?->nis ?? '-' }}</td>
                                <td style="text-align: center;">{{ $asramaText }}</td>
                                <td style="text-align: center;">{{ $santriItem?->iksass ?? '-' }}</td>
                                <td style="white-space: nowrap; text-align: left;">
                                    {{ $item->daftarPelanggaran?->nama_pelanggaran ?? '-' }}</td>
                                <td style="text-align: center;">
                                    {{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') : '-' }}
                                </td>
                                <td style="text-align: center;">{{ number_format($totalSanksi) }}</td>
                                <td style="text-align: center;"></td>
                                <td style="text-align: center;"></td>
                            </tr>
                        @endforeach
                    @endif
                    @php $no++; @endphp
                @endforeach
            </tbody>
        </table>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>

</html>
