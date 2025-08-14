@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/user_role.css') }}">
<link rel="stylesheet" href="{{ asset('css/satpam/log-history.css') }}">

@endpush

@section('content')

@php
    $role = Auth::user()->role;
@endphp

@if ($role === 'Admin')
    {{-- Konten User & Role Management untuk Admin --}}
    @section('title', 'User & Role Management')
    <div class="user-role-container">
        <div class="user-role-header">
            <h1>User & Role Management</h1>
        </div>

        <div class="user-role-controls">
            <div class="search-boxx">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInputt" placeholder="Search">
            </div>
            <button class="add-btn" onclick="togglePopup('popup-add-user')">
                <i class="bi bi-person-fill-add"></i> Add User
            </button>
        </div>

        <div class="data-container">
            @if($users->isEmpty())
                <p class="empty-text">Tidak ada user.</p>
            @else
                <div class="user-table-wrapper">
                    <table class="user-table" id="userTable">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $index => $user)
                            <tr class="{{ $index % 2 === 0 ? 'row-white' : 'row-grey' }}">
                                <td>{{ $user->nama }}</td>
                                <td>{{ $user->username }}</td>
                                <td>{{ $user->role }}</td>
                                <td>
                                    <button class="icon-btn" onclick="openEditUser({{ $user->id }}, '{{ $user->nama }}', '{{ $user->username }}', '{{ $user->password }}', '{{ $user->role }}')">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form method="POST" action="{{ route('user.destroy', $user->id) }}" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-btn" onclick="return confirm('Yakin ingin menghapus user ini?')">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Add User Popup -->
    <div class="popup" id="popup-add-user">
        <div class="popup-content">
            <span class="close-btn" onclick="togglePopup('popup-add-user')">&times;</span>
            <h3>Tambah User</h3>
            <form action="{{ route('user.store') }}" method="POST">
                @csrf
                <label>Nama User</label>
                <input type="text" name="nama" required>
                <label>Username</label>
                <input type="text" name="username" required>
                <label>Password</label>
                <input type="text" name="password" required>
                <label>Role</label>
                <select name="role" required>
                    <option value="Satpam">Satpam</option>
                    <option value="Kepala Satpam">Kepala Satpam</option>
                </select>
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <!-- Edit User Popup -->
    <div class="popup" id="popup-edit-user">
        <div class="popup-content">
            <span class="close-btn" onclick="togglePopup('popup-edit-user')">&times;</span>
            <h3>Edit User</h3>
            <form id="editUserForm" method="POST">
                @csrf
                @method('PUT')
                <label>Nama User</label>
                <input type="text" name="nama" id="editNama" required>
                <label>Username</label>
                <input type="text" name="username" id="editUsername" required>
                <label>New Password</label>
                <input type="password" name="password" id="editPassword" placeholder="Isi jika ingin ubah password">
                <label>Role</label>
                <select name="role" id="editRole" required>
                    <option value="Satpam">Satpam</option>
                    <option value="Kepala Satpam">Kepala Satpam</option>
                </select>
                <button type="submit">Save</button>
            </form>
        </div>
    </div>

@elseif ($role === 'Satpam' || $role === 'Kepala Satpam')
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
            <select id="filterLokasi" class="filter-input">
                <option value="">Location</option>
                @foreach($lokasis as $lokasi)
                    <option value="{{ strtolower(trim($lokasi->nama_lokasi)) }}">{{ $lokasi->nama_lokasi }}</option>
                @endforeach
            </select>

            <select name="shift_nama" id="filterShift" class="filter-input">
                <option value="">Shift</option>
                <option value="Pagi" {{ request('shift_nama') == 'Pagi' ? 'selected' : '' }}>Pagi</option>
                <option value="Siang" {{ request('shift_nama') == 'Siang' ? 'selected' : '' }}>Siang</option>
                <option value="Malam" {{ request('shift_nama') == 'Malam' ? 'selected' : '' }}>Malam</option>
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
                            <th>Date</th>
                            <th>Location</th>
                            <th>Shift</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Status</th>
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
                                            data-jurnal='@json($jurnal)'>
                                            <i class="bi bi-eye-slash-fill view-icon"></i>
                                        </a>

                                        <a href="{{ route('jurnal.edit', $jurnal->id) }}" class="edit-btn">
                                            <i class="bi bi-pencil-square" style="font-size:18px; color: #007bff;"></i>
                                        </a>

                                        @if(Auth::user()->role === 'Kepala Satpam')
                                            <form action="{{ route('jurnal.destroy', $jurnal->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Yakin ingin menghapus jurnal ini?')" style="background: none; border: none; cursor: pointer;">
                                                    <i class="bi bi-trash-fill" style="font-size:18px;"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <a href="{{ route('log-history.download', $jurnal->id) }}" class="download-btn" target="_blank">
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
@endif


@if ($role === 'Admin')
    <script>
        function togglePopup(id) {
            document.getElementById(id).classList.toggle('show');
        }

        function openEditUser(id, nama, username, role) {
            const form = document.getElementById('editUserForm');
            form.action = `/user-role/${id}`;
            document.getElementById('editNama').value = nama;
            document.getElementById('editUsername').value = username;
            document.getElementById('editPassword').value = '';
            document.getElementById('editRole').value = role;
            togglePopup('popup-edit-user');
        }

        document.getElementById('searchInputt').addEventListener('input', function () {
            const value = this.value.toLowerCase();
            const rows = document.querySelectorAll('#userTable tbody tr');
            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
            });
        });
    </script>
@elseif ($role === 'Kepala Satpam')
    <script>
        // log history
        document.addEventListener('DOMContentLoaded', function () {
            const lokasiSelect  = document.getElementById('filterLokasi');
            const shiftSelect   = document.getElementById('filterShift');
            const tanggalInput  = document.getElementById('filterTanggal');
            const searchInput   = document.getElementById('searchbarr');
            const tbody         = document.querySelector('#historytable tbody');

            if (!tbody) return;

            // Ensure a "no data" row exists (and keep it out of filtering)
            let noDataRow = document.getElementById('noDataRow');
            if (!noDataRow) {
                noDataRow = document.createElement('tr');
                noDataRow.id = 'noDataRow';
                noDataRow.style.display = 'none';
                const td = document.createElement('td');
                td.colSpan = 6;
                td.textContent = 'Tidak ada data.';
                td.style.textAlign = 'center';
                td.style.verticalAlign = 'middle';
                noDataRow.appendChild(td);
                tbody.appendChild(noDataRow);
            }

            function getDataRows() {
                // all rows except the no-data row
                return Array.from(tbody.querySelectorAll('tr')).filter(tr => tr.id !== 'noDataRow');
            }

            function formatTanggal(input) {
                if (!input) return '';
                const d = new Date(input);
                // Blade shows: \Carbon\Carbon::parse(...)->format('F d, Y')  e.g. "August 12, 2025"
                // This matches that string (with comma).
                return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: '2-digit' });
            }

            function filterTable() {
                const lokasi  = (lokasiSelect?.value || '').toLowerCase();
                const shift   = (shiftSelect?.value  || '').toLowerCase();
                const tanggal = (tanggalInput?.value || '');
                const search  = (searchInput?.value  || '').toLowerCase();

                const rows = getDataRows();
                let visibleCount = 0;

                rows.forEach(row => {
                const tds = row.querySelectorAll('td');
                const tanggalVal = (tds[0]?.textContent || '');              // "August 12, 2025"
                const lokasiVal  = (tds[1]?.textContent || '').toLowerCase();
                const shiftVal   = (tds[2]?.textContent || '').toLowerCase();
                const namaVal    = (tds[3]?.textContent || '').toLowerCase();
                const roleVal    = (tds[4]?.textContent || '').toLowerCase();
                const actionVal  = (tds[5]?.textContent || '').toLowerCase();

                const matchLokasi  = !lokasi  || lokasiVal.includes(lokasi);
                const matchShift   = !shift   || shiftVal.includes(shift);
                const matchTanggal = !tanggal || tanggalVal.includes(formatTanggal(tanggal));
                const matchSearch  = !search  || namaVal.includes(search) || roleVal.includes(search) || actionVal.includes(search);

                const show = matchLokasi && matchShift && matchTanggal && matchSearch;
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
                });

                noDataRow.style.display = (visibleCount === 0) ? '' : 'none';
            }

            // Bind
            lokasiSelect?.addEventListener('change', filterTable);
            shiftSelect?.addEventListener('change', filterTable);
            tanggalInput?.addEventListener('change', filterTable);
            searchInput?.addEventListener('input', filterTable);

            // First run
            filterTable();
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
                        ['Laporan Kejadian/Temuan', format(jurnal.kejadian)],
                        ['Lembur', format(jurnal.lembur)],
                        ['Proyek/Vendor', format(jurnal.proyek)],
                        ['Paket/Dokumen', format(jurnal.paket)],
                        ['Tamu Belum Keluar', format(jurnal.tamu)],
                        ['Karyawan Dinas Luar', format(jurnal.karyawan)],
                        ['Barang Inventaris Keluar', format(jurnal.barang)],
                        ['Kendaraan Dinas Luar', format(jurnal.kendaraan)],
                        ['Lampu/Penerangan Mati', format(jurnal.lampu)],
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

        // pop up ubah status
        (function(){
            const openBtns   = document.querySelectorAll('.status-open');
            const modal      = document.getElementById('statusModal');
            const btnClose   = document.getElementById('statusClose');
            const btnSave    = document.getElementById('statusSave');
            const selectEl   = document.getElementById('statusSelect');
            let currentId    = null;
            const csrf       = document.querySelector('meta[name="csrf-token"]').content;

            openBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                currentId = btn.dataset.id;
                const now = btn.dataset.status || 'waiting';
                // set select; default to 'approve' if waiting
                selectEl.value = (now === 'approve' || now === 'reject') ? now : 'approve';
                modal.style.display = 'flex';
                });
            });

            btnClose?.addEventListener('click', () => modal.style.display = 'none');
            modal?.addEventListener('click', e => {
                if (!e.target.closest('.modal-content')) {
                    modal.style.display = 'none';
                }
            });

            btnSave?.addEventListener('click', () => {
                if (!currentId) return;
                const status = selectEl.value;

                fetch(@json(route('jurnal.updateStatus', ['id' => '__ID__'])).replace('__ID__', currentId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status })
                })
                .then(r => r.json())
                .then(data => {
                if (!data.success) throw new Error('Update gagal');

                // Update badge in table (text + color) for this row
                const btn = document.querySelector(`.status-open[data-id="${currentId}"]`);
                if (btn) {
                    btn.dataset.status = data.status;
                    const span = btn.querySelector('.badge');
                    if (span) {
                    span.textContent = data.status === 'approve' ? 'Approve' : 'Reject';
                    span.classList.remove('green','red','yellow');
                    span.classList.add(data.status === 'approve' ? 'green' : 'red');
                    }
                }

                modal.style.display = 'none';
                })
                .catch(err => {
                console.error(err);
                alert('Tidak bisa mengubah status.');
                });
            });
        })();

        function closeDetailPopup() {
            document.getElementById('popup-detail').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const flash = document.getElementById('flashMessage');
            if (flash) {
                setTimeout(() => {
                    flash.remove();
                }, 5000); // Hapus setelah 5 detik
            }
        });
    </script>
@elseif ($role === 'Satpam')
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
                        ['Laporan Kejadian/Temuan', format(jurnal.kejadian)],
                        ['Lembur', format(jurnal.lembur)],
                        ['Proyek/Vendor', format(jurnal.proyek)],
                        ['Paket/Dokumen', format(jurnal.paket)],
                        ['Tamu Belum Keluar', format(jurnal.tamu)],
                        ['Karyawan Dinas Luar', format(jurnal.karyawan)],
                        ['Barang Inventaris Keluar', format(jurnal.barang)],
                        ['Kendaraan Dinas Luar', format(jurnal.kendaraan)],
                        ['Lampu/Penerangan Mati', format(jurnal.lampu)],
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
@endif

@endsection
