<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-title img {
            height: 50px;
        }

        .logo-title h3 {
            margin: 0;
            font-size: 18px;
            margin-left:65px;
            margin-top:-35px;
        }

        .tanggal span {
            font-size: 12px;
            margin-left:84%;
        }
        
        h1 {
            text-align: center;
            margin-top: 5px;
            font-size: xx-large;
            border-top: 1px solid black;
            padding-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        td {
            border: 0.5px solid #000;
            padding: 5px;
            vertical-align: top;
        }

        .flex-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start; /* ⬅ ini penting */
            gap: 20px;
            margin-top: 20px;
        }

        .left-info,
        .right-info {
            width: 48%;
        }

        .left-info table {
            width: 100%;
            border: none;
        }

        .left-info td {
            border: none;
            padding: 2px 0;
            font-size: 14px;
        }

        .left-info td:first-child {
            font-weight: bold;
            width: 169px;
        }

        .right-info td:first-child {
            font-weight: bold;
            width: 150px;
        }

        .right-info {
            font-size: 14px;
            margin-left:300px;
            margin-top:-140px;
            padding-bottom:90px;
        }

        .right-info b {
            display: inline-block;
            margin-bottom: 5px;
        }

        .right-info table {
            width: 100%;
            border: none;
            font-size: 14px;
        }

        .right-info td {
            border: none;
            vertical-align: top;
            padding: 2px 0;
        }

        td:first-child {
            width: 160px;
            font-weight: bold;
        }

        .info td {
            border: none;
            font-size: 15px;
        }

        .guards{
            margin-left: 8px;
            margin-top: -17px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-title">
            <img src="{{ public_path('/images/logo-lemfox.jpg') }}" alt="Logo">
            <h3>PT AICA INDRIA</h3>
        </div>
        <div class="tanggal">
            <span>{{ date('F d, Y') }}</span>
        </div>
    </div>

    <h1>Journal Detail</h1>

    <div class="flex-info">
        <div class="left-info">
            <table>
                <tr><td>Pengisi</td><td>: {{ $jurnal->satpam->nama ?? '-' }}</td></tr>
                <tr><td>Lokasi</td><td>: {{ $jurnal->lokasi->nama_lokasi ?? '-' }}</td></tr>
                <tr><td>Shift</td><td>: {{ $jurnal->shift->nama_shift ?? '-' }}</td></tr>
                <tr><td>Next Shift</td><td>: {{ $jurnal->nextShiftUser->nama ?? '-' }}</td></tr>
                <tr><td>Tanggal</td><td>: {{ \Carbon\Carbon::parse($jurnal->tanggal)->format('F d, Y') }}</td></tr>
                <tr><td>Next Shift Approval</td><td>: 
                    @if($jurnal->approval_status == 1)
                        Approve
                    @else
                        Waiting
                    @endif
                </td></tr>
                <tr><td>Journal Status</td><td>: {{ ucfirst($jurnal->status) }}</td></tr>
            </table>
        </div>
        <!-- <div class="right-info">
            <table>
                <tr>
                    <td style="font-weight: bold;">Anggota Shift</td>
                    <td>
                        :
                        <div class="guards">
                            @if(isset($anggotaShift) && count($anggotaShift) > 0)
                                @foreach($anggotaShift as $anggota)
                                    {{ $anggota }}<br>
                                @endforeach
                            @else
                                Tidak ada anggota shift
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div> -->
    </div>

    <table>
        <tr>
            <td>Laporan Kegiatan</td>
            <td>{!! nl2br(e($jurnal->laporan_kegiatan)) !!}</td>
        </tr>

        <tr>
            <td>Laporan Kejadian / Temuan</td>
            <td>{{ $jurnal->is_kejadian_temuan == 1 ? 'Yes' : ($jurnal->is_kejadian_temuan == 0 ? 'No' : '-') }}</td>
        </tr>
        <tr>
            <td></td>
            <td>{{ $jurnal->kejadian_temuan ?? '-' }}</td>
        </tr>

        <tr>
            <td>Lembur</td>
            <td>{{ $jurnal->is_lembur == 1 ? 'Yes' : ($jurnal->is_lembur == 0 ? 'No' : '-') }}</td>
        </tr>
        <tr>
            <td></td>
            <td>{{ $jurnal->lembur ?? '-' }}</td>
        </tr>

        <tr>
            <td>Proyek / Vendor</td>
            <td>{{ $jurnal->is_proyek_vendor == 1 ? 'Yes' : ($jurnal->is_proyek_vendor == 0 ? 'No' : '-') }}</td>
        </tr>
        <tr>
            <td></td>
            <td>{{ $jurnal->proyek_vendor ?? '-' }}</td>
        </tr>

        <tr>
            <td>Barang Inventaris Keluar</td>
            <td>{{ $jurnal->is_barang_keluar == 1 ? 'Yes' : ($jurnal->is_barang_keluar == 0 ? 'No' : '-') }}</td>
        </tr>
        <tr>
            <td></td>
            <td>{{ $jurnal->barang_keluar ?? '-' }}</td>
        </tr>

        <tr>
            <td>Kendaraan Dinas Luar</td>
            <td>{{ $jurnal->is_kendaraan_dinas_keluar == 1 ? 'Yes' : ($jurnal->is_kendaraan_dinas_keluar == 0 ? 'No' : '-') }}</td>
        </tr>
        <tr>
            <td></td>
            <td>{{ $jurnal->kendaraan_dinas_keluar ?? '-' }}</td>
        </tr>

        <tr>
            <td>Informasi Tambahan</td>
            <td>{{ $jurnal->info_tambahan ?? '-' }}</td>
        </tr>
    </table>
</body>
</html>
