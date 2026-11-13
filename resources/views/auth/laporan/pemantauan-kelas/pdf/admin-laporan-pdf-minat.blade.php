<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 16px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #e5e7eb;
        }

        .header-wrapper {
            display: flex;
            justify-content: space-between;
            flex-direction: row;
            align-items: center;

            .print-button {
                height: fit-content;
            }

        }

        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="header-wrapper">
        <h1>{{ $title }}</h1>
        <button class="print-button" type="button" onclick="window.print()">print pages</button>
    </div>
    <table>
        <thead>
            <tr>
                <th>NISN</th>
                <th>Nama Lengkap</th>
                <th>Kelas Asal</th>
                @for ($i = 1; $i <= $maxPilihan; $i++)
                    <th>Pilihan {{ $i }}</th>
                @endfor
                <th>Tanggal Submit</th>
                <th>Status Pendaftaran</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $row)
                @php
                    $pilihan = [];
                    foreach ($row->detailPendaftaran as $detail) {
                        $pilihan[$detail->urutan_pilihan] = $detail->paketMenuPilihan?->nama_menu;
                    }
                @endphp
                <tr>
                    <td>{{ $row->siswa?->nisn ?? '-' }}</td>
                    <td>{{ $row->siswa?->nama_lengkap ?? '-' }}</td>
                    <td>{{ $row->siswa?->kelasAsalRelation?->nama_kelas ?? '-' }}</td>
                    @for ($i = 1; $i <= $maxPilihan; $i++)
                        <td>{{ $pilihan[$i] ?? '-' }}</td>
                    @endfor
                    <td>{{ $row->tanggal_submit?->format('Y-m-d H:i:s') ?? '-' }}</td>
                    <td>{{ ucfirst($row->status ?? '') ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 5 + $maxPilihan }}">Tidak ada data minat siswa.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
