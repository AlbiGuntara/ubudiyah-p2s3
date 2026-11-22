<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Feedback Aplikasi</title>
</head>
<body style="margin:0;padding:24px;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#18181b;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e4e4e7;border-radius:12px;overflow:hidden;">
        <div style="background:#0f766e;color:#ffffff;padding:20px 24px;">
            <h1 style="margin:0;font-size:18px;">Feedback Aplikasi Ubudiyah</h1>
            <p style="margin:4px 0 0;font-size:13px;opacity:.85;">Pesan baru dari formulir feedback</p>
        </div>

        <div style="padding:24px;">
            <table role="presentation" style="width:100%;border-collapse:collapse;font-size:14px;">
                <tr>
                    <td style="padding:6px 0;width:130px;color:#71717a;">Kategori</td>
                    <td style="padding:6px 0;font-weight:bold;">{{ ucfirst($feedback['kategori'] ?? '-') }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#71717a;">Nama</td>
                    <td style="padding:6px 0;">{{ $feedback['nama'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#71717a;">Username</td>
                    <td style="padding:6px 0;">{{ $feedback['username'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#71717a;">Email</td>
                    <td style="padding:6px 0;">{{ $feedback['email'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#71717a;">Role</td>
                    <td style="padding:6px 0;">{{ $feedback['role'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#71717a;">Halaman</td>
                    <td style="padding:6px 0;">{{ $feedback['halaman'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#71717a;">Waktu</td>
                    <td style="padding:6px 0;">{{ $feedback['waktu'] ?? '-' }}</td>
                </tr>
            </table>

            <h2 style="margin:24px 0 8px;font-size:14px;color:#71717a;font-weight:normal;">Pesan</h2>
            <div style="white-space:pre-wrap;border-left:3px solid #0f766e;background:#f4f4f5;padding:12px 16px;border-radius:6px;font-size:14px;line-height:1.6;">{{ $feedback['pesan'] ?? '-' }}</div>

            <p style="margin:24px 0 0;font-size:12px;color:#a1a1aa;">Browser: {{ $feedback['user_agent'] ?? '-' }}</p>
        </div>
    </div>
</body>
</html>
