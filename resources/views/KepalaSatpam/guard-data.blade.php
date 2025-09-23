@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kepala/guard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kepala/jadwal.css') }}">
@endpush

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @section('title', 'Guard Data')
    <div class="table-bg">
        <h1>Guard Data</h1>

        @if(session('success'))
            <div class="flash-message success" style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" class="filter-bar">
            <select class="filter-select" name="lokasi_id" onchange="this.form.submit()">
                <option value="">Location</option>
                @foreach($lokasis as $lokasi)
                    <option value="{{ $lokasi->id }}" {{ request('lokasi_id') == $lokasi->id ? 'selected' : '' }}>
                        {{ $lokasi->nama_lokasi }}
                    </option>
                @endforeach
            </select>

            <select class="filter-select" name="shift_id" onchange="this.form.submit()">
                <option value="">Shift</option>
                @foreach($shifts as $shift)
                    <option value="{{ $shift->id }}" {{ request('shift_id') == $shift->id ? 'selected' : '' }}>
                        {{ $shift->nama_shift }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="tanggal" value="{{ request('tanggal', now()->toDateString()) }}" class="filter-date">
            <button type="button" class="btn-tambah-jadwal">
                <i class="bi bi-plus-lg"></i> Tambah Jadwal
            </button>

            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Search" value="{{ request('search') }}" id="filterSearch">
            </div>
        </form>

        <!-- Pop up Tambah Jadwal -->
        <div id="jadwalModal" class="modal" style="display:none;">
            <div class="modal-content">
                <button type="button" class="btn-close-modal">&times;</button>
                <div id="jadwalModalBody">
                    @php
                        $lokasisAktif = $lokasis->where('is_active', 1);
                        $shiftsAktif = $shifts->where('is_active', 1);
                    @endphp

                    <div class="jadwal-container">
                        <h2>Tambah Jadwal</h2>

                        <div class="date-range">
                            <label>Start Date <i class="bi bi-asterisk"></i> </label>
                            <input type="date" name="start_date" id="jadwalStartDate" required>
                            <label>End Date <i class="bi bi-asterisk"></i> </label>
                            <input type="date" name="end_date" id="jadwalEndDate" required>
                        </div>

                        <div class="jadwal-flex">
                            <div class="list-satpam">
                                <h3>List Satpam</h3>
                                <div class="search-box3">
                                    <i class="bi bi-search"></i>
                                    <input type="text" id="jadwalSearchSatpam" placeholder="Search">
                                </div>
                                <div class="satpam-list" id="jadwalSatpamList">
                                    @foreach($satpams as $satpam)
                                        <div class="satpam-item" data-user="{{ $satpam->id }}">
                                            <span>{{ $satpam->nama }}</span>
                                            <i class="bi bi-person-fill-check add-icon" title="Add"></i>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="lokasi-container">
                                <form id="jadwalForm">
                                    @csrf
                                    @foreach($lokasisAktif as $lokasi)
                                        <div class="lokasi-block">
                                            <h3><i class="bi bi-geo-fill"></i> {{ $lokasi->nama_lokasi }}</h3>
                                            <div class="shift-row">
                                                @foreach($shiftsAktif as $shift)
                                                    <div class="shift-block" data-lokasi="{{ $lokasi->id }}" data-shift="{{ $shift->id }}">
                                                        <label><i class="bi bi-calendar-check-fill"></i> {{ $shift->nama_shift }} <i class="bi bi-asterisk req"></i> </label>
                                                        <div class="assigned-list"></div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <hr>
                                    @endforeach

                                    <input type="hidden" name="assign" id="jadwalAssignData">
                                </form>
                            </div>
                        </div>

                        <div class="form-footer">
                            <button type="submit" class="btn-submit" form="jadwalForm">Submit</button>
                        </div>

                    </div>

                    <!-- Pop up Add to -->
                    <div class="popup-add" id="jadwalPopupAdd" style="display:none;">
                        <div class="popup-content">
                            <h3>Add to</h3>
                            <label>Lokasi</label>
                            <select id="jadwalPopupLokasi">
                                @foreach($lokasisAktif as $lokasi)
                                    <option value="{{ $lokasi->id }}">{{ $lokasi->nama_lokasi }}</option>
                                @endforeach
                            </select>
                            <label>Shift</label>
                            <select id="jadwalPopupShift">
                                @foreach($shiftsAktif as $shift)
                                    <option value="{{ $shift->id }}">{{ $shift->nama_shift }}</option>
                                @endforeach
                            </select>
                            <div class="popup-buttons">
                                <button type="button" id="jadwalPopupCancel">Cancel</button>
                                <button type="button" id="jadwalPopupAddBtn">Add</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-main">
            <div class="table-container">
                <table id="historytable">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Location</th>
                            <th>Shift</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jadwals as $jadwal)
                            <tr data-id="{{ $jadwal->id }}">
                                <td>{{ $jadwal->satpam->nama ?? '-' }}</td>
                                <td>
                                    <select class="lokasi-select">
                                        <option value="">-- Pilih Lokasi --</option>
                                        @foreach($lokasisAktif as $lokasi)
                                            <option value="{{ $lokasi->id }}" {{ $jadwal->lokasi_id == $lokasi->id ? 'selected' : '' }}>
                                                {{ $lokasi->nama_lokasi }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="shift-select">
                                        <option value="">-- Pilih Shift --</option>
                                        @foreach($shifts as $shift)
                                            <option value="{{ $shift->id }}" {{ $jadwal->shift_id == $shift->id ? 'selected' : '' }}>
                                                {{ $shift->nama_shift }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <span class="status-badge {{ $jadwal->status == 'On Duty' ? 'status-on' : 'status-off' }}">
                                        {{ $jadwal->status }}
                                    </span>
                                </td>
                                <td>{{ $jadwal->createdBySatpam->nama ?? '-' }}</td>
                                <td class="updated-by-cell">{{ $jadwal->updatedBySatpam->nama ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="kosong">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        /* -------------------------
        UPDATE GUARD DATA
        ------------------------- */
        document.querySelectorAll('tr[data-id]').forEach(row => {
            const id = row.getAttribute('data-id');
            const lokasiSelect = row.querySelector('.lokasi-select');
            const shiftSelect = row.querySelector('.shift-select');
            const statusBadge = row.querySelector('.status-badge');
            const updatedByCell = row.querySelector('.updated-by-cell');

            const update = (lokasi_id, shift_id, status) => {
                fetch(`/guard-data/update/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ lokasi_id, shift_id, status })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (updatedByCell && data.updated_by_name) {
                            updatedByCell.textContent = data.updated_by_name;
                        }
                    } else {
                        alert('Update gagal.');
                    }
                })
                .catch(err => console.error(err));
            };

            if (lokasiSelect) {
                lokasiSelect.addEventListener('change', () => {
                    update(lokasiSelect.value, shiftSelect.value, statusBadge.textContent.trim());
                });
            }

            if (shiftSelect) {
                shiftSelect.addEventListener('change', () => {
                    update(lokasiSelect.value, shiftSelect.value, statusBadge.textContent.trim());
                });
            }

            if (statusBadge) {
                statusBadge.addEventListener('click', () => {
                    const newStatus = statusBadge.textContent.trim() === 'On Duty' ? 'Off Duty' : 'On Duty';
                    statusBadge.textContent = newStatus;
                    statusBadge.classList.toggle('status-on', newStatus === 'On Duty');
                    statusBadge.classList.toggle('status-off', newStatus === 'Off Duty');
                    update(lokasiSelect.value, shiftSelect.value, newStatus);
                });
            }
        });


        /* -------------------------
        FILTER SEARCH
        ------------------------- */
        const searchInput = document.getElementById('filterSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => {
                    const form = this.closest('form');
                    if (form) form.submit();
                }, 500);
            });
        }


        /* -------------------------
        FILTER Date
        ------------------------- */
        const dateInput = document.querySelector('.filter-date');
        if (dateInput) {
        // submit immediately when the user picks a date
        dateInput.addEventListener('change', () => {
            const form = dateInput.closest('form');
            if (form) form.submit();
        });

        // optional: also submit on Enter if someone types the date
        dateInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
            e.preventDefault();
            const form = dateInput.closest('form');
            if (form) form.submit();
            }
        });
        }

        /* -------------------------
        MODAL TAMBAH JADWAL
        ------------------------- */
        const modal     = document.getElementById('jadwalModal');
        const modalBody = document.getElementById('jadwalModalBody');
        const btnOpen   = document.querySelector('.btn-tambah-jadwal');
        const btnClose  = document.querySelector('.btn-close-modal');

        // Modal open/close
        btnOpen?.addEventListener('click', () => {
            modal.style.display = 'flex';
        });
        btnClose?.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        // Elements inside modal
        const form        = document.getElementById('jadwalForm');
        const popup       = document.getElementById('jadwalPopupAdd');
        const popupLokasi = document.getElementById('jadwalPopupLokasi');
        const popupShift  = document.getElementById('jadwalPopupShift');
        const popupAddBtn = document.getElementById('jadwalPopupAddBtn');
        const popupCancel = document.getElementById('jadwalPopupCancel');

        const startDate   = document.getElementById('jadwalStartDate');
        const endDate     = document.getElementById('jadwalEndDate');
        const assignField = document.getElementById('jadwalAssignData');

        const searchBox   = document.getElementById('jadwalSearchSatpam');
        const satpamList  = document.getElementById('jadwalSatpamList');

        let currentUserId = null;
        let currentUserName = null;

        // Filter satpam by search
        searchBox?.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#jadwalSatpamList .satpam-item').forEach(item => {
            const name = (item.querySelector('span')?.textContent || '').toLowerCase();
            item.style.display = name.includes(q) ? '' : 'none';
            });
        });

        // Event delegation for add/remove icons
        modalBody.addEventListener('click', (e) => {
            const addIcon = e.target.closest('.add-icon');
            if (addIcon) {
            const parent = addIcon.closest('.satpam-item');
            if (!parent) return;
            currentUserId = parent.dataset.user;
            currentUserName = parent.querySelector('span').textContent;
            popup.style.display = 'flex';
            return;
            }

            const removeIcon = e.target.closest('.remove-icon');
            if (removeIcon) {
            const row = removeIcon.closest('.assigned-item');
            if (!row) return;
            const uid  = row.dataset.user;
            const name = row.dataset.username || row.querySelector('span').textContent;
            row.remove();
            restoreSatpam(uid, name);
            }
        });

        // Popup handlers
        popupCancel?.addEventListener('click', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            popup.style.display = 'none';
        });
        popup?.addEventListener('click', (ev) => {
            if (!ev.target.closest('.popup-content')) popup.style.display = 'none';
        });
        popupAddBtn?.addEventListener('click', () => {
            if (!currentUserId) return;
            const lokasiId  = popupLokasi.value;
            const shiftId = popupShift.value;

            const target = document.querySelector(`.shift-block[data-lokasi="${lokasiId}"][data-shift="${shiftId}"] .assigned-list`);
            if (target) {
            const row = document.createElement('div');
            row.className = 'assigned-item';
            row.dataset.user = currentUserId;
            row.dataset.username = currentUserName;
            row.innerHTML = `<span><i class="bi bi-person-fill icon_user"></i>${currentUserName}</span><i class="bi bi-x remove-icon" title="Remove"></i>`;
            target.appendChild(row);

            // Remove from left list
            document.querySelector(`.satpam-item[data-user="${currentUserId}"]`)?.remove();
            }
            popup.style.display = 'none';
        });

        function restoreSatpam(id, name) {
            const item = document.createElement('div');
            item.className = 'satpam-item';
            item.dataset.user = id;
            item.innerHTML = `<span>${name}</span><i class="bi bi-person-fill-check add-icon" title="Add"></i>`;
            satpamList.appendChild(item);
        }

        // Submit jadwal
        form?.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitButton = document.querySelector('button[form="jadwalForm"]');
            const originalBtnText = submitButton.innerHTML;

            if (!startDate.value || !endDate.value) {
                alert('Tanggal mulai dan akhir wajib diisi.');
                return;
            }

            submitButton.disabled = true;
            submitButton.innerHTML = 'Menyimpan...';

            // Build assign JSON (logika ini tetap sama)
            const assignData = {};
            document.querySelectorAll('.shift-block').forEach(block => {
                const lokasiId = block.dataset.lokasi;
                const shift    = block.dataset.shift;
                const userIds  = Array.from(block.querySelectorAll('.assigned-item')).map(i => i.dataset.user);
                if (!assignData[lokasiId]) assignData[lokasiId] = {};
                assignData[lokasiId][shift] = userIds;
            });
            const unassigned = Array.from(document.querySelectorAll('#jadwalSatpamList .satpam-item')).map(i => i.dataset.user);
            if (unassigned.length) {
                if (!assignData['null']) assignData['null'] = {};
                assignData['null']['null'] = unassigned;
            }
            assignField.value = JSON.stringify(assignData);

            // HAPUS BAGIAN FETCH KE CHECK_JADWAL DI SINI
            const ROUTE_STORE = @json(route('guard.jadwal.store'));
            const ROUTE_AFTER = @json(route('guard.data'));

            // Langsung kirim data untuk disimpan. Pengecekan dilakukan di backend.
            const payload = new FormData(form);
            payload.append('start_date', startDate.value);
            payload.append('end_date', endDate.value);

            fetch(ROUTE_STORE, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: payload
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    window.location.href = ROUTE_AFTER;
                } else if (data) {
                    alert(data.message || 'Gagal menyimpan jadwal.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan saat menyimpan jadwal.');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalBtnText;
            });
        });
    });
    </script>

@endsection
