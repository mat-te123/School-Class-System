<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 16px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
        th { background: #e5e7eb; }
    </style>
</head>

<body>
    <h1>{{ $title }}</h1>
    <table>
        <thead>
            <tr>
                <th>NISN</th>
                <th>Nama Lengkap</th>
                <th>Kelas Asal</th>
                <th>Paket Penempatan</th>
                <th>Skor</th>
                <th>Rata-rata 6 Mapel</th>
                <th>Mekanisme</th>
                <th>Override</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $row)
                <tr>
                    <td>{{ $row->siswa?->nisn ?? '-' }}</td>
                    <td>{{ $row->siswa?->nama_lengkap ?? '-' }}</td>
                    <td>{{ $row->siswa?->kelasAsalRelation?->nama_kelas ?? '-' }}</td>
                    <td>{{ $row->paketMenuPilihan?->nama_menu ?? '-' }}</td>
                    <td>{{ $row->skor_penempatan ?? '-' }}</td>
                    <td>{{ $row->rata_6_mapel ?? '-' }}</td>
                    <td>{{ $row->mekanisme ?? '-' }}</td>
                    <td>{{ $row->is_manual_override ? 'Ya' : 'Tidak' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Tidak ada data hasil penjurusan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
