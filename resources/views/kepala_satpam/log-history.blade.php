@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kepala_satpam/log-history.css') }}">
@endpush

@section('content')
    @php
        $role = Auth::user()->role;
        $currentUserId = Auth::id();
    @endphp

@section('title', 'List Jurnal')

@if (session('success'))
    <div class="flash-message success"
        style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
        {{ session('success') }}
    </div>
@endif

<div class="log-history-container">
    <h1>List Jurnal</h1>

    <!-- Filter -->
    <div class="filter-bar">
        <select name="lokasi_nama" id="filterLokasi" class="filter-input">
            <option value="">Lokasi</option>
            @foreach ($lokasis as $lokasi)
                <option value="{{ strtolower(trim($lokasi->nama_lokasi)) }}"
                    @if ($lokasi->is_active == 0) class="inactive-option" @endif>
                    {{ $lokasi->nama_lokasi }} - status: {{ $lokasi->is_active ? 'Active' : 'Inactivate' }}
                </option>
            @endforeach
        </select>

        <select name="shift_nama" id="filterShift" class="filter-input">
            <option value="">Shift</option>
            @foreach ($shifts as $shift)
                <option value="{{ strtolower(trim($shift->nama_shift)) }}"
                    @if ($shift->is_active == 0) class="inactive-option" @endif>
                    {{ $shift->nama_shift }} - status: {{ $shift->is_active ? 'Active' : 'Inactivate' }}
                </option>
            @endforeach
        </select>

        <input type="date" id="filterTanggal" name="tanggal" class="filter-input" value="{{ request('tanggal') }}">

        <div class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" id="searchbarr" class="filter-search" placeholder="Search"
                value="{{ request('search') }}">
        </div>
    </div>

    <!-- Table -->
    <div class="table-bg">
        <div class="table-container">
            <table id="historytable">
                <thead>
                    <tr>
                        <th class="col-date">Tanggal</th>
                        <th class="col-location">Lokasi</th>
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

                                    @if ($role === 'Kepala Satpam' && $jurnal->isApprove)
                                        <button type="button" class="badge-btn status-open"
                                            data-id="{{ $jurnal->id }}" data-status="{{ $status }}"
                                            title="Update Status" style="all:unset; cursor:pointer;">
                                            @if ($status === 'approve')
                                                <span class="badge green">Approve</span>
                                            @elseif($status === 'reject')
                                                <span class="badge red">Reject</span>
                                            @else
                                                <span class="badge yellow">Waiting</span>
                                            @endif
                                        </button>
                                    @else
                                        @if ($status === 'approve')
                                            <span class="badge green">Approve</span>
                                        @elseif($status === 'reject')
                                            <span class="badge red">Reject</span>
                                        @elseif($status === 'waiting')
                                            <span class="badge yellow">Waiting</span>
                                        @else
                                            <span class="badge grey">Pending</span>
                                        @endif
                                    @endif

                                    <a href="javascript:void(0);" class="view-detail-btn"
                                        data-jurnal='@json($jurnal)' title="View Jurnal">
                                        <i class="bi bi-eye-slash-fill view-icon"></i>
                                    </a>

                                    @if (($role === 'Kepala Satpam' || $user->id == $jurnal->responsibleUser->id) && $jurnal->isPending)
                                        <a href="{{ route('jurnal.edit', $jurnal->id) }}" class="edit-btn"
                                            title="Edit Jurnal">
                                            <i class="bi bi-pencil-square" style="font-size:18px; color: #007bff;"></i>
                                        </a>
                                    @endif

                                    @if ($role === 'Kepala Satpam')
                                        <form action="{{ route('jurnal.destroy', $jurnal->id) }}" method="POST"
                                            style="display: inline;" class="delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="delete-btn"
                                                style="border: none; cursor: pointer; margin-left: 0px; padding: 0;"
                                                title="Delete Jurnal">
                                                <i class="bi bi-trash-fill" style="font-size:18px; color: #dc3545;"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('log-history.download', $jurnal->id) }}" class="download-btn"
                                        target="_blank" title="Download Jurnal">
                                        <i class="bi bi-file-earmark-arrow-down-fill"
                                            style="font-size:18px; color: #e63946;"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Tidak ada data.</td>
                        </tr>
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

                    <div id="approve-section" class="Approve-section">
                        <button id="approveBtn" class="approve-btn">
                            Approve Journal
                        </button>
                    </div>
                </div>
            </div>

            <!-- Approve/Reject Modal (only used by Kepala) -->
            @if ($role === 'Kepala Satpam')
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
    // log history
    document.addEventListener('DOMContentLoaded', function() {
        const lokasiSelect = document.getElementById('filterLokasi');
        const shiftSelect = document.getElementById('filterShift');
        const tanggalInput = document.getElementById('filterTanggal');
        const searchInput = document.getElementById('searchbarr');
        const tbody = document.querySelector('#historytable tbody');

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
            return d.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: '2-digit'
            });
        }

        function filterTable() {
            const lokasi = (lokasiSelect?.value || '').toLowerCase();
            const shift = (shiftSelect?.value || '').toLowerCase();
            const tanggal = (tanggalInput?.value || '');
            const search = (searchInput?.value || '').toLowerCase();

            const rows = getDataRows();
            let visibleCount = 0;

            rows.forEach(row => {
                const tds = row.querySelectorAll('td');
                const tanggalVal = (tds[0]?.textContent || ''); // "August 12, 2025"
                const lokasiVal = (tds[1]?.textContent || '').toLowerCase();
                const shiftVal = (tds[2]?.textContent || '').toLowerCase();
                const namaVal = (tds[3]?.textContent || '').toLowerCase();
                const roleVal = (tds[4]?.textContent || '').toLowerCase();
                const actionVal = (tds[5]?.textContent || '').toLowerCase();
                const approvalVal = (tds[6]?.textContent || '').toLowerCase();
                const statusVal = (tds[7]?.textContent || '').toLowerCase();

                const matchLokasi = !lokasi || lokasiVal.includes(lokasi);
                const matchShift = !shift || shiftVal.includes(shift);
                const matchTanggal = !tanggal || tanggalVal.includes(formatTanggal(tanggal));
                const matchSearch = !search || namaVal.includes(search) || roleVal.includes(search) ||
                    actionVal.includes(search) || approvalVal.includes(search) || statusVal.includes(
                        search);

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

    //// POP UP JOURNAL DETAIL
    const currentUserId = {{ $currentUserId }};
    document.addEventListener("DOMContentLoaded", function() {
        // === POPUP DETAIL FUNCTIONALITY ===
        const detailButtons = document.querySelectorAll('.view-detail-btn');
        const popup = document.getElementById('popup-detail');
        const table = document.getElementById('popup-detail-table');
        const approveSection = document.getElementById('approve-section');
        const approveBtn = document.getElementById('approveBtn');

        detailButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const jurnal = JSON.parse(this.dataset.jurnal);

                const format = (val) => val ?? '-';
                const laporanKegiatan = jurnal.laporan_kegiatan ?
                    jurnal.laporan_kegiatan.replace(/\n/g, '<br>') :
                    '-';

                const rows = [
                    ['Lokasi', format(jurnal.lokasi?.nama_lokasi)],
                    ['Shift', format(jurnal.shift?.nama_shift)],
                    ['Shift Selanjutnya', format(jurnal.next_shift_user?.nama)],
                    ['Tanggal', new Date(jurnal.tanggal).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    })],
                    ['Laporan Kegiatan', laporanKegiatan],
                    ['Laporan Kejadian/Temuan', format(jurnal.kejadian_temuan)],
                    ['Lembur', format(jurnal.lembur)],
                    ['Proyek/Vendor', format(jurnal.proyek_vendor)],
                    ['Barang Inventaris Keluar', format(jurnal.barang_keluar)],
                    ['Informasi Tambahan', format(jurnal.info_tambahan)],
                ];

                let html = '';
                rows.forEach(([label, value]) => {
                    html +=
                        `<tr><td><strong>${label}</strong></td><td>:</td><td>${value}</td></tr>`;
                });

                if (jurnal.uploads && jurnal.uploads.length > 0) {
                    html += `<tr><td><strong>Lampiran</strong></td><td>:</td><td>`;
                    jurnal.uploads.forEach(file => {
                        html += `<a href="{{ asset('${file.file_path}') }}" target="_blank" style="margin-right:5px; color:white;">
                                        <i class="bi bi-file-earmark-arrow-down-fill" style="color:red;font-size:20px;"></i>
                                    </a>`;
                    });
                    html += `</td></tr>`;
                }

                table.innerHTML = html;

                // show button approve khusus user next shift
                const isNextShiftUser = jurnal.next_shift_user_id && currentUserId == jurnal
                    .next_shift_user_id;
                const isApproved = jurnal.status != 'pending';

                if (isNextShiftUser && !isApproved) {
                    approveSection.style.display = 'block';
                    approveBtn.disabled = false;
                    approveBtn.textContent = 'Approve Journal';
                    // Attach event listener (lihat poin 5)
                    approveBtn.onclick = () => handleApprove(jurnal.id);
                } else if (isApproved) {
                    approveSection.style.display = 'block';
                    approveBtn.disabled = true;
                    approveBtn.textContent = 'Already Approved';
                    approveBtn.onclick = null;
                } else {
                    approveSection.style.display = 'none';
                }
                popup.style.display = 'block';
            });
        });
    });

    // pop up ubah status
    (function() {
        const openBtns = document.querySelectorAll('.status-open');
        const modal = document.getElementById('statusModal');
        const btnClose = document.getElementById('statusClose');
        const btnSave = document.getElementById('statusSave');
        const selectEl = document.getElementById('statusSelect');
        let currentId = null;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

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
                    body: JSON.stringify({
                        status
                    })
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
                            span.classList.remove('green', 'red', 'yellow');
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

    // ubah status approval jurnal oleh next shift
    function handleApprove(jurnalId) {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        approveBtn.disabled = true;
        approveBtn.textContent = 'Approving...';
        approveBtn.style.background = '#6c757d';

        fetch(`/jurnal/${jurnalId}/approve`, { // Sesuaikan dengan route Anda
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect_url;
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal approve: ' + error.message);
                approveBtn.disabled = false;
                approveBtn.textContent = 'Approve Journal';
                approveBtn.style.background = '#28a745';
            });
    }


    // ===== SCRIPT UNTUK POPUP KONFIRMASI HAPUS =====
    document.addEventListener('DOMContentLoaded', function() {
        const modalOverlay = document.getElementById('confirmDeleteModal');
        if (!modalOverlay) return; // Hentikan jika modal tidak ada

        const modalContent = modalOverlay.querySelector('.modal-content');
        const cancelBtn = document.getElementById('cancelDeleteBtn');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const deleteButtons = document.querySelectorAll('.delete-btn');

        let formToSubmit = null;

        // Fungsi untuk menampilkan modal dengan animasi
        function showModal() {
            modalOverlay.style.display = 'flex';
            modalContent.classList.remove('hide');
            modalContent.classList.add('show');
        }

        // Fungsi untuk menyembunyikan modal dengan animasi
        function hideModal() {
            modalContent.classList.remove('show');
            modalContent.classList.add('hide');

            // Tunggu animasi selesai, baru sembunyikan overlay-nya
            modalContent.addEventListener('animationend', function handler() {
                modalOverlay.style.display = 'none';
                formToSubmit = null; // Reset form
                // Hapus event listener agar tidak menumpuk
                modalContent.removeEventListener('animationend', handler);
            });
        }

        deleteButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                formToSubmit = this.closest('form');
                showModal(); // Panggil fungsi untuk menampilkan
            });
        });

        cancelBtn.addEventListener('click', hideModal);

        confirmBtn.addEventListener('click', function() {
            // 1. Langsung ubah tombol menjadi state "loading"
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = 'Deleting...';

            // 2. Lanjutkan proses submit form
            if (formToSubmit) {
                formToSubmit.submit();
            } else {
                hideModal();
            }
        });

        modalOverlay.addEventListener('click', function(event) {
            if (event.target === modalOverlay) {
                hideModal();
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const flash = document.getElementById('flashMessage');
        if (flash) {
            setTimeout(() => {
                flash.remove();
            }, 5000); // Hapus setelah 5 detik
        }
    });
</script>

@endsection
