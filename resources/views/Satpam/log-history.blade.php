@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/satpam/log-history.css') }}">
@endpush

@section('content')

    @section('title', 'Log History')

    @if(session('success'))
        <div class="flash-message success" style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
            {{ session('success') }}
        </div>
    @endif

    <div class="log-history-container">
        <h1>Log History</h1>

        <!-- Filter -->
        <div class="filter-bar">
            <select name="lokasi_nama" id="filterLokasi" class="filter-input">
                <option value="">Location</option>
                @foreach($lokasis as $lokasi)
                    <option value="{{ strtolower(trim($lokasi->nama_lokasi)) }}"
                        @if($lokasi->is_active == 0) class="inactive-option" @endif>
                        {{ $lokasi->nama_lokasi }} - status: {{ $lokasi->is_active ? 'Active' : 'Inactivate' }}
                    </option>                
                @endforeach
            </select>

            <select name="shift_nama" id="filterShift" class="filter-input">
                <option value="">Shift</option>
                @foreach($shifts as $shift)
                    <option value="{{ strtolower(trim($shift->nama_shift)) }}"
                        @if($shift->is_active == 0) class="inactive-option" @endif>
                        {{ $shift->nama_shift }} - status: {{ $shift->is_active ? 'Active' : 'Inactivate' }}
                    </option>
                @endforeach
            </select>

            <input type="date"  id="filterTanggal" name="tanggal" class="filter-input" value="{{ request('tanggal') }}">

            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" name="search" id="searchbarr" class="filter-search" placeholder="Search" value="{{ request('search') }}">
            </div>
        </div>

        <!-- Table -->
        <div class="table-bg">
            <div class="table-container">
                <table id="historytable">
                    <thead>
                        <tr>
                            <th class="col-date">Date</th>
                            <th class="col-location">Location</th>
                            <th class="col-shift">Shift</th>
                            <th class="col-name">Name</th>
                            <th class="col-role">Role</th>
                            <th class="col-updated">Updated</th>
                            <th class="col-status">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jurnals as $jurnal)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($jurnal->tanggal)->format('F d, Y') }}</td>
                                <td>{{ $jurnal->lokasi->nama_lokasi ?? '-' }}</td>
                                <td>{{ $jurnal->shift->nama_shift ?? '-' }}</td>
                                <td>{{ $jurnal->satpam->nama ?? '-' }}</td>
                                <td>{{ $jurnal->satpam->role ?? '-' }}</td>
                                <td>{{ $jurnal->updatedBySatpam->nama ?? '-' }}</td>
                                <td>
                                    <div class="action-content">
                                        @php $status = strtolower($jurnal->status); @endphp

                                        @if(Auth::user()->role === 'Kepala Satpam')
                                            <button type="button"
                                                    class="badge-btn status-open"
                                                    data-id="{{ $jurnal->id }}"
                                                    data-status="{{ $status }}"
                                                    style="all:unset; cursor:pointer;">
                                                @if($status === 'approve')
                                                    <span class="badge green">Approve</span>
                                                @elseif($status === 'reject')
                                                    <span class="badge red">Reject</span>
                                                @else
                                                    <span class="badge yellow">Waiting</span>
                                                @endif
                                            </button>
                                        @else
                                            @if($status === 'approve')
                                                <span class="badge green">Approve</span>
                                            @elseif($status === 'reject')
                                                <span class="badge red">Reject</span>
                                            @else
                                                <span class="badge yellow">Waiting</span>
                                            @endif
                                        @endif
                                        
                                        <a href="javascript:void(0);" 
                                            class="view-detail-btn" 
                                            data-jurnal='@json($jurnal)'
                                            title="View Jurnal">
                                            <i class="bi bi-eye-slash-fill view-icon"></i>
                                        </a>

                                        <a href="{{ route('jurnal.edit', $jurnal->id) }}" class="edit-btn" title="Edit Jurnal">
                                            <i class="bi bi-pencil-square" style="font-size:18px; color: #007bff;"></i>
                                        </a>

                                        <a href="{{ route('log-history.download', $jurnal->id) }}" class="download-btn" target="_blank" title="Download Jurnal">
                                            <i class="bi bi-file-earmark-arrow-down-fill" style="font-size:18px; color: #e63946;"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div id="confirmDeleteModal" class="modal-overlay" style="display: none;">
                    <div class="modal-content">
                        <p id="confirmMessage">Yakin ingin menghapus jurnal ini?</p>
                        <div class="modal-actions">
                            <button id="cancelDeleteBtn" class="btn-cancel">Tidak</button>
                            <button id="confirmDeleteBtn" class="btn-confirm-delete">Ya, Hapus</button>
                        </div>
                    </div>
                </div>

                <!-- Pop Up Journal Detail -->
                <div id="popup-detail" class="popup-detail-overlay" style="display: none;">
                    <div class="popup-detail-content">
                        <button class="popup-close-btn" onclick="closeDetailPopup()">&times;</button>
                        <h2>Journal Detail</h2>
                        <table class="popup-detail-table" id="popup-detail-table">
                            <!-- Diisi oleh JavaScript -->
                        </table>
                    </div>
                </div>

                <!-- Approve/Reject Modal (only used by Kepala) -->
                @if(Auth::user()->role === 'Kepala Satpam')
                    <div id="statusModal">
                        <div class="modal-content">
                            <button type="button" id="statusClose" class="close-btn">&times;</button>
                            <h2>Ubah Status</h2>

                            <div class="form-row">
                                <label>Status</label>
                                <select id="statusSelect">
                                    <option value="approve">Approve</option>
                                    <option value="reject">Reject</option>
                                </select>
                            </div>

                            <button id="statusSave">Save</button>
                        </div>
                    </div>
                @endif
            </div>
        </div> 
    </div>

    <script>
        //// log history
        document.addEventListener("DOMContentLoaded", function () {
            const lokasiSelect = document.getElementById('filterLokasi');
            const shiftSelect = document.getElementById('filterShift');
            const tanggalInput = document.getElementById('filterTanggal');
            const searchInput = document.getElementById('searchbarr');
            const rows = document.querySelectorAll('#historytable tbody tr');

            function filterTable() {
                const lokasi = lokasiSelect.value.toLowerCase();
                const shift = shiftSelect.value.toLowerCase();
                const tanggal = tanggalInput.value;
                const search = searchInput.value.toLowerCase();

                rows.forEach(row => {
                    const tds = row.querySelectorAll('td');
                    const tanggalVal = tds[0]?.textContent || '';
                    const lokasiVal = tds[1]?.textContent.toLowerCase() || '';
                    const shiftVal = tds[2]?.textContent.toLowerCase() || '';
                    const namaVal = tds[3]?.textContent.toLowerCase() || '';
                    const roleVal = tds[4]?.textContent.toLowerCase() || '';
                    const actionVal = tds[5]?.textContent.toLowerCase() || '';

                    const matchLokasi = lokasi === '' || lokasiVal.includes(lokasi);
                    const matchShift = shift === '' || shiftVal.includes(shift);
                    const matchTanggal = tanggal === '' || tanggalVal.includes(formatTanggal(tanggal));
                    const matchSearch =
                        search === '' || namaVal.includes(search) ||
                        roleVal.includes(search) || actionVal.includes(search);

                    const isVisible = matchLokasi && matchShift && matchTanggal && matchSearch;
                    row.style.display = isVisible ? '' : 'none';
                });
            }

            function formatTanggal(input) {
                // Ubah dari yyyy-mm-dd ke format 'Month dd, yyyy'
                const dateObj = new Date(input);
                return dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: '2-digit' });
            }

            lokasiSelect.addEventListener('change', filterTable);
            shiftSelect.addEventListener('change', filterTable);
            tanggalInput.addEventListener('change', filterTable);
            searchInput.addEventListener('input', filterTable);
        });

        //// pop up journal detail
        document.addEventListener("DOMContentLoaded", function () {
            // === POPUP DETAIL FUNCTIONALITY ===
            const detailButtons = document.querySelectorAll('.view-detail-btn');
            const popup = document.getElementById('popup-detail');
            const table = document.getElementById('popup-detail-table');

            detailButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    const jurnal = JSON.parse(this.dataset.jurnal);

                    const format = (val) => val ?? '-';

                    const rows = [
                        ['Location', format(jurnal.lokasi?.nama_lokasi)],
                        ['Shift', format(jurnal.shift?.nama_shift)],
                        ['Date', new Date(jurnal.tanggal).toLocaleDateString('en-US', {
                            year: 'numeric', month: 'long', day: 'numeric'
                        })],
                        ['Cuaca', format(jurnal.cuaca)],
                        ['Laporan Kejadian/Temuan', format(jurnal.kejadian_temuan)],
                        ['Lembur', format(jurnal.lembur)],
                        ['Proyek/Vendor', format(jurnal.proyek_vendor)],
                        ['Paket/Dokumen', format(jurnal.paket_dokumen)],
                        ['Tamu Belum Keluar', format(jurnal.tamu_belum_keluar)],
                        ['Karyawan Dinas Luar', format(jurnal.karyawan_dinas_keluar)],
                        ['Barang Inventaris Keluar', format(jurnal.barang_keluar)],
                        ['Kendaraan Dinas Luar', format(jurnal.kendaraan_dinas_keluar)],
                        ['Lampu/Penerangan Mati', format(jurnal.lampu_mati)],
                        ['Informasi Tambahan', format(jurnal.info_tambahan)],
                    ];

                    let html = '';
                    rows.forEach(([label, value]) => {
                        html += `<tr><td><strong>${label}</strong></td><td>:</td><td>${value}</td></tr>`;
                    });

                    if (jurnal.uploads && jurnal.uploads.length > 0) {
                        html += `<tr><td><strong>Lampiran</strong></td><td>:</td><td>`;
                        jurnal.uploads.forEach(file => {
                            html += `<a href="/storage/${file.file_path}" target="_blank" style="margin-right:5px;">
                                        <i class="bi bi-file-earmark-arrow-down-fill" style="color:red;font-size:20px;"></i>
                                    </a>`;
                        });
                        html += `</td></tr>`;
                    }

                    table.innerHTML = html;
                    popup.style.display = 'block';
                });
            });
        });

        function closeDetailPopup() {
            document.getElementById('popup-detail').style.display = 'none';
        }
    </script>

@endsection
