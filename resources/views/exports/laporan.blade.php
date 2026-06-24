<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $judul }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 18px; margin: 0; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #16a34a; color: white; }
        .footer { margin-top: 30px; font-size: 11px; color: #666; }
        .footer p { margin: 3px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PONDOK PESANTREN SALAFIYAH SYAFI'IYAH SUKOREJO</h1>
        <h2>{{ $judul }}</h2>
        <p>Periode: {{ $periode }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Jumlah Pelanggaran</th>
                <th>Jumlah Santri Melanggar</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $data['jumlah_pelanggaran'] ?? 0 }}</td>
                <td>{{ $data['jumlah_santri'] ?? 0 }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Tanggal Cetak: {{ $tanggal_cetak }}</p>
        <p>Dicetak oleh: {{ $user }}</p>
    </div>
</body>
</html>
