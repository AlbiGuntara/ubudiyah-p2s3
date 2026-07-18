<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Cetak Pembinaan Ubudiyah</title>
    <style>
        @page {
            size: 215mm 330mm;
            margin: 15mm 15mm 15mm 15mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
        }

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

        .content {
            margin-top: 10px;
        }

        .daerah-header {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }

        table.pembinaan {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 9pt;
        }

        table.pembinaan th {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
            font-weight: bold;
            background-color: #f0f0f0;
        }

        table.pembinaan td {
            border: 1px solid #000;
            padding: 3px 6px;
            vertical-align: middle;
        }

        table.pembinaan td:nth-child(1) {
            text-align: center;
            width: 30px;
        }

        table.pembinaan td:nth-child(4) {
            text-align: center;
        }

        table.pembinaan td:nth-child(5) {
            text-align: center;
        }

        table.pembinaan td:nth-child(6) {
            text-align: center;
        }

        .footer-cetak {
            position: fixed;
            bottom: 10mm;
            left: 15mm;
            right: 15mm;
            font-size: 8pt;
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
            $pembinaans = $group['pembinaans'];
            $totalSisaSanksi = $pembinaans->sum('sisa_sanksi');
        @endphp

        <div class="kop">
            <img src="{{ public_path('logo/p2s3.png') }}" alt="Logo P2S3" class="logo-ponpes">
            <p class="nama-ponpes">PONDOK PESANTREN "SALAFIYAH SYAFI'IYAH" SUKOREJO</p>
            <p class="bidang">BIDANG KEPESANTRENAN</p>
            <p class="alamat">SUMBEREJO BANYUPUTIH SITUBONDO JAWA TIMUR</p>
            <p class="notaris">Akte Notaris No. 4/25.08.1970 & No. 3/05.07.2001</p>
        </div>

        <div class="content">
            <div class="daerah-header">
                DATA PELANGGARAN UBUDIYAH <br> DAERAH {{ strtoupper($daerah->nama_daerah) }}
            </div>

            <table class="pembinaan">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Santri</th>
                        <th>Asrama</th>
                        <th>IKSASS</th>
                        <th>Total Sanksi</th>
                        <th>Sanksi Disetor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pembinaans as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->santri?->nama ?? 'Tanpa Nama' }}</td>
                            <td>
                                @if ($item->santri?->asrama)
                                    {{ $item->santri->asrama->daerah?->kode ? substr($item->santri->asrama->daerah->kode, 0, 1) . '.' . $item->santri->asrama->nomor : $item->santri->asrama->nomor }}
                                @elseif ($item->asrama)
                                    {{ $item->asrama->daerah?->kode ? substr($item->asrama->daerah->kode, 0, 1) . '.' . $item->asrama->nomor : $item->asrama->nomor }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $item->santri?->iksass ?? '-' }}</td>
                            <td>{{ number_format($item->sisa_sanksi) }}</td>
                            <td></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center;">Tidak ada data pembinaan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>

</html>
