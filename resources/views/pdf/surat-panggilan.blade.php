<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Surat Panggilan Ubudiyah</title>
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

        .surat-info {
            margin-bottom: 6px;
        }

        .surat-info table {
            border: none;
            width: auto;
        }

        .surat-info td {
            border: none;
            padding: 0 10px 1px 0;
            vertical-align: top;
        }

        /* .surat-info td:first-child {
            width: 80px;
        } */

        .alamat-tujuan {
            margin: 6px 0;
        }

        .salam {
            margin: 6px 0;
        }

        .isi {
            margin: 6px 0;
            text-align: justify;
        }

        table.pelanggaran {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 10pt;
        }

        table.pelanggaran th {
            border: 1px solid #000;
            padding: 3px 6px;
            text-align: center;
            font-weight: bold;
            background-color: #f0f0f0;
        }

        table.pelanggaran td {
            border: 1px solid #000;
            padding: 2px 6px;
            vertical-align: top;
        }

        table.pelanggaran td:first-child {
            text-align: center;
        }

        table.pelanggaran td:nth-child(4) {
            text-align: center;
            white-space: nowrap;
        }

        .penutup {
            margin-top: 10px;
        }

        .ttd {
            margin-top: 10px;
            float: right;
            position: relative;
            overflow: visible;
        }

        .ttd .kota {
            margin-bottom: 2px;
        }

        .ttd .gambar-ttd {
            position: absolute;
            top: 70px;
            left: -20;
            width: 250px;
            height: auto;
        }

        .ttd p:last-child {
            padding-top: 90px;
        }

        .clear {
            clear: both;
        }

        .continuation-header {
            text-align: center;
            margin: 15px 0 10px 0;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }

        .continuation-header p {
            margin: 2px 0;
        }

        .continuation-header .cont-title {
            font-weight: bold;
            font-size: 12pt;
        }

        .continuation-header .cont-info {
            font-size: 10pt;
        }

        .page-break {
            page-break-after: always;
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
    </style>
</head>

<body>

    @foreach ($letters as $index => $letter)
        @php
            $asrama = $letter['asrama'];
            $surat = $letter['surat'];
            $pelanggaranList = $letter['pelanggaran'];

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
            $tglCetak = $surat->tanggal_cetak;
            $hari = $hariIndo[$tglCetak->dayOfWeek];
            $tgl = $tglCetak->day;
            $bln = $bulanIndo[$tglCetak->month - 1];
            $thn = $tglCetak->year;

            $chunkSize = 12;
            $chunks = $pelanggaranList->isEmpty() ? collect([collect()]) : $pelanggaranList->chunk($chunkSize);
            $counter = 0;
        @endphp

        @foreach ($chunks as $chunkIndex => $chunk)
            @php $isFirstChunk = $chunkIndex === 0; @endphp

            @if ($isFirstChunk)
                <div class="kop">
                    <img src="{{ public_path('logo/p2s3.png') }}" alt="Logo P2S3" class="logo-ponpes">
                    <p class="nama-ponpes">PONDOK PESANTREN "SALAFIYAH SYAFI'IYAH" SUKOREJO</p>
                    <p class="bidang">BIDANG KEPESANTRENAN</p>
                    <p class="alamat">SUMBEREJO BANYUPUTIH SITUBONDO JAWA TIMUR</p>
                    <p class="notaris">Akte Notaris No. 4/25.08.1970 & No. 3/05.07.2001</p>
                </div>

                <div class="content">
                    <table class="surat-info">
                        <tr>
                            <td>Perihal</td>
                            <td>: <strong>PANGGILAN UBUDIYAH</strong></td>
                        </tr>
                    </table>

                    <div class="alamat-tujuan">
                        <p>Kepada Yth.<br>
                            <strong>Ketua Kamar Asrama {{ $asrama->daerah->nama_daerah }} No.
                                {{ $asrama->nomor }}</strong><br>
                            di Tempat
                        </p>
                    </div>

                    <div class="salam">
                        <p><em><strong>Assalamu'alaikum Warahmatullahi Wabarakatuh</strong></em></p>
                    </div>

                    <div class="isi">
                        <p>Dengan hormat,</p>
                        <p>Sehubungan dengan adanya pelanggaran yang dilakukan oleh santri asrama Ustadz, dengan ini
                            kami mohon
                            agar santri-santri berikut dapat melakukan pembinaan di <strong>Kantor Ubudiyah</strong>
                            pada
                            <strong>20.30 - 22.00 WIB</strong>.
                        </p>

                        <table class="pelanggaran">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">No</th>
                                    <th>Nama Santri</th>
                                    <th>Pelanggaran</th>
                                    <th style="width: 80px;">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($chunk as $item)
                                    @php $counter++; @endphp
                                    <tr>
                                        <td>{{ $counter }}</td>
                                        <td>{{ $item->santri?->nama ?? $item->jumlah . ' Tanpa Nama' }}</td>
                                        <td>{{ $item->daftarPelanggaran?->nama_pelanggaran ?? '-' }}</td>
                                        <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" style="text-align: center;">Tidak ada data pelanggaran</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="penutup">
                        <p>Demikian surat panggilan ini disampaikan. Atas perhatian dan kerjasamanya, kami ucapkan
                            terima kasih.
                        </p>
                        <p><em><strong>Wassalamu'alaikum Warahmatullahi Wabarakatuh</strong></em></p>
                    </div>

                    <div class="ttd">
                        <p class="kota">Sukorejo, {{ $tgl }} {{ $bln }} {{ $thn }}
                            <br>Kasubag Ubudiyah,
                        </p>
                        <img src="{{ public_path('ttd-kasubag.png') }}" alt="Tanda Tangan Kasubag" class="gambar-ttd">
                        <p><strong>Suhamar Iskandar, S.Ag.</strong></p>
                    </div>
                    <div class="clear"></div>
                </div>
            @else
                <div class="continuation-header">
                    <p class="cont-title">LANJUTAN SURAT PANGGILAN UBUDIYAH</p>
                    <p class="cont-info">Asrama {{ $asrama->daerah->nama_daerah }} No. {{ $asrama->nomor }} &mdash;
                        Halaman {{ $chunkIndex + 1 }}</p>
                </div>
                <table class="pelanggaran">
                    <thead>
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th>Nama Santri</th>
                            <th>Pelanggaran</th>
                            <th style="width: 80px;">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($chunk as $item)
                            @php $counter++; @endphp
                            <tr>
                                <td>{{ $counter }}</td>
                                <td>{{ $item->santri?->nama ?? $item->jumlah . ' Tanpa Nama' }}</td>
                                <td>{{ $item->daftarPelanggaran?->nama_pelanggaran ?? '-' }}</td>
                                <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @php
                $isLastChunk = $loop->last;
                $isLastLetter = $loop->parent->last;
            @endphp
            @if (!($isLastChunk && $isLastLetter))
                <div class="page-break"></div>
            @endif
        @endforeach
    @endforeach

</body>

</html>
