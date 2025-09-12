@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/satpam/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kepala/dashboard.css') }}">

@endpush

@section('content')

    @php
        $role = Auth::user()->role;
    @endphp

    @if ($role === 'Admin')
        @section('title', 'Dashboard - Admin')
        <section class="dashboard">
            <h1>Dashboard</h1>

            @if(session('success'))
                <div id="flashToast" class="flash-toast {{ session('flash_type', 'success') }}">
                    <span class="flash-dot"></span>
                    <span class="flash-text">{{ session('success') }}</span>
                    <button class="flash-close" onclick="document.getElementById('flashToast').classList.add('hide')">&times;</button>
                </div>
            @endif

            <div class="stats-manage">
                <div class="statistics">
                    <div class="stat-card loc">
                        <h3>Total User</h3>
                        <p>{{ $totalUsers }}</p>
                        <a href="{{ route('user.index') }}" class="see-details-btn">See Details</a>
                    </div>
                    <div class="stat-card zz">
                        <h3>Total Location</h3>
                        <p>{{ $totalLocations }}</p>
                        <a href="{{ route('location.shift.index') }}" class="see-details-btn">See Details</a>
                    </div>
                    <div class="stat-card loc">
                        <h3>Total Shift</h3>
                        <p>{{ $totalShifts }}</p>
                        <a href="{{ route('location.shift.index') }}" class="see-details-btn">See Details</a>
                    </div>
                </div>


                <div class="manage">
                    <h3>Manage</h3>
                    <a href="#" onclick="togglePopup('popup-add-user')">
                        <i class="bi bi-person-fill-add"></i> Add User
                    </a>
                    <a href="#" onclick="togglePopup('popup-location')">
                        <i class="bi bi-geo-fill"></i> Add Location
                    </a>
                    <a href="#" onclick="togglePopup('popup-shift')">
                        <i class="bi bi-calendar-plus"></i> Add Shift
                    </a>
                </div>
            </div>

            <!-- Add User Popup -->
            <div class="dashboard-popup" id="popup-add-user">
                <div class="dashboard-popup-content">
                    <span class="close-btn" onclick="togglePopup('popup-add-user')">&times;</span>
                    <h3>Tambah User</h3>
                    <form id="addUserForm" action="{{ route('user.store') }}" method="POST">
                        @csrf

                        <div id="addUserErrors" class="form-errors hide" style="display:none;"></div>

                        <label>Nama User <i class="bi bi-asterisk"></i> </label>
                        <input type="text" name="nama" required placeholder="Wajib diisi!">
                        <label>Username <i class="bi bi-asterisk"></i> </label>
                        <input type="text" name="username" required placeholder="Wajib diisi!" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
                        <label>Password <i class="bi bi-asterisk"></i> </label>
                        <input type="password" name="password" required placeholder="Wajib diisi!" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
                        <label>Role <i class="bi bi-asterisk"></i> </label>
                        <select name="role" required>
                            <option value="" disabled selected>-- Wajib pilih role! --</option>
                            <option value="Satpam">Admin</option>
                            <option value="Satpam">Satpam</option>
                            <option value="Kepala Satpam">Kepala Satpam</option>
                        </select>
                        <button type="submit">Submit</button>
                    </form>
                </div>
            </div>

            <!-- POPUP: ADD LOCATION -->
            <div class="dashboard-popup" id="popup-location">
                <div class="dashboard-popup-content">
                    <span class="close-btn" onclick="togglePopup('popup-location')">&times;</span>
                    <h3>Tambah Lokasi</h3>
                    <form id="addLocationForm" action="{{ route('location.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div id="addLocationErrors" class="form-errors hide" style="display:none;"></div>

                        <label>Nama Lokasi <i class="bi bi-asterisk"></i> </label>
                        <input type="text" name="nama_lokasi" required placeholder="Wajib diisi!">

                        <label>Alamat Lokasi <i class="bi bi-asterisk"></i> </label>
                        <textarea name="alamat_lokasi" required placeholder="Wajib diisi!"></textarea>

                        <label>Upload Foto <i class="bi bi-asterisk"></i> </label>
                        <input type="file" name="foto" required>

                        <button type="submit">Submit</button>
                    </form>
                </div>
            </div>

            <!-- POPUP: ADD SHIFT -->
            <div class="dashboard-popup" id="popup-shift">
                <div class="dashboard-popup-content">
                    <span class="close-btn" onclick="togglePopup('popup-shift')">&times;</span>
                    <h3>Tambah Shift</h3>
                    <form id="addShiftForm" action="{{ route('shift.store') }}" method="POST">
                        @csrf
                    
                        <div id="addShiftErrors" class="form-errors hide" style="display:none;"></div>

                        <label>Nama Shift <i class="bi bi-asterisk"></i> </label>
                        <input type="text" name="nama_shift" required placeholder="Wajib diisi!">

                        <label>Mulai Shift <i class="bi bi-asterisk"></i> </label>
                        <input type="time" name="mulai_shift" required>

                        <label>Selesai Shift <i class="bi bi-asterisk"></i> </label>
                        <input type="time" name="selesai_shift" required>

                        <button type="submit">Submit</button>
                    </form>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="recent-activity">
                <h3>Recent Activity</h3>
                @if(count($recentActivities) > 0)
                    <ul>
                        @foreach($recentActivities as $index => $activity)
                            <li class="{{ $index % 2 === 0 ? 'row-white' : 'row-grey' }}">
                                <span>{{ \Carbon\Carbon::parse($activity->created_at)->format('d M Y') }}</span>
                                {{ $activity->description }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p>Belum ada aktifitas.</p>
                @endif
            </div>
        </section>

    @elseif ($role === 'Kepala Satpam')
        @section('title', 'Dashboard - Kepala Satpam')
        <div class="dashboard-kepala">
            <h1>Dashboard</h1>

            <div class="top-panels">
                <!-- DAFTAR LOKASI & SHIFT -->
                <div class="lokasi-shift-panel">
                    <div class="header-row">
                        <h3>Daftar Lokasi & Shift</h3>
                        <select onchange="location = '?shift_id=' + this.value">
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}" {{ $shiftFilterId == $shift->id ? 'selected' : '' }}>
                                    {{ $shift->nama_shift }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="scroll-panel">
                        @forelse($jadwals as $jadwal)
                            <div class="satpam-card">
                                <div class="satpam-info">
                                    <img src="{{ $jadwal->satpam->foto
                                                ? asset('storage/'.$jadwal->satpam->foto)
                                                : asset('images/profile.jpeg') }}"
                                    alt="foto">
                                    <strong>{{ $jadwal->satpam->nama }}</strong>
                                </div>
                                <div class="satpam-info-loc">
                                    <span class="lokasi-info">
                                        <i class="bi bi-geo-fill"></i>
                                        <small>{{ $jadwal->lokasi->nama_lokasi ?? '-' }}</small>
                                    </span>
                                </div>
                                <div class="satpam-info-status">
                                    <span class="badge {{ $jadwal->status == 'On Duty' ? 'green' : 'red' }}">
                                        {{ $jadwal->status }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="empty-message">Tidak ada jadwal hari ini.</p>
                        @endforelse
                    </div>

                </div>

                <!-- STATUS JURNAL HARI INI -->
                <div class="jurnal-status-panel">
                    <h3>Status Pengisian Jurnal</h3>
                    <div class="scroll-panel">
                        @forelse($jurnalToday as $jurnal)
                            <div class="jurnal-card">
                                <div class="jurnal-info">
                                    <strong>Jurnal - {{ $jurnal->shift->nama_shift }}</strong><br>
                                    <small>Terakhir disimpan: {{ \Carbon\Carbon::parse($jurnal->created_at)->format('H:i') }}</small>
                                </div>

                                <div class="jurnal-info-loc">
                                    <i class="bi bi-geo-fill"></i>
                                    <small>{{ $jurnal->lokasi->nama_lokasi ?? '-' }}</small><br>
                                </div>

                                <div class="jurnal-info-status">
                                    <span class="badge {{ strtolower($jurnal->status) }}">
                                        {{ ucfirst($jurnal->status == 'reject' ? 'Reject' : $jurnal->status) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="empty-message">Tidak ada jurnal</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- HISTORY -->
            <div class="history-panel">
                <h3>Histori Pengisian Jurnal</h3>
                <table class="history-table">
                    <thead>
                        <tr><th class="col-date">Tanggal</th><th class="col-location">Lokasi</th><th>Shift</th><th>Nama</th><th>Role</th></tr>
                    </thead>
                    <tbody>
                        @foreach($jurnalHistory as $index => $jurnal)
                            <tr class="{{ $index % 2 == 0 ? 'row-white' : 'row-grey' }}">
                                <td>{{ \Carbon\Carbon::parse($jurnal->tanggal)->format('d F Y') }}</td>
                                <td>{{ $jurnal->lokasi->nama_lokasi }}</td>
                                <td>{{ $jurnal->shift->nama_shift }}</td>
                                <td>{{ $jurnal->satpam->nama }}</td>
                                <td>
                                    <span class="badge {{ $jurnal->satpam->role == 'Kepala Satpam' ? 'pink' : 'blue' }}">
                                        {{ $jurnal->satpam->role }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @elseif ($role === 'Satpam')
        @section('title', 'Dashboard - Satpam')
        <section class="dashboard-satpam">
            <h1>Dashboard</h1>

            <div class="satpam-panels">
                <div class="panel shift-location">
                    <h3>Shift & Lokasi</h3>

                    @if($shift && $lokasi)
                        <div class="top-row">
                            <p class="title">
                                <i class="bi bi-calendar-event"></i>
                                {{ $lokasi->nama_lokasi }} - {{ $shift->nama_shift }}
                            </p>

                            <a href="{{ route('jurnal.submission') }}" class="btn-jurnal">
                                Input Jurnal
                            </a>
                        </div>

                        <p class="date">Tanggal: {{ \Carbon\Carbon::parse($today)->format('d F Y') }}</p>
                    @else
                        <p>Belum ada.</p>
                    @endif
                </div>

                <div class="panel jurnal-status">
                    <h3>Status Pengisian Jurnal</h3>

                    @if($latestJournal)
                        <div class="js-header">
                            <p class="title">
                                <i class="bi bi-journal-text"></i>
                                Jurnal - {{ $latestJournal->shift->nama_shift }}
                            </p>

                            @php $s = strtolower($latestJournal->status ?? 'waiting'); @endphp
                            <p class="status-line">
                                Status: <span class="status {{ $s }}">{{ ucfirst($s) }}</span>
                            </p>
                        </div>

                        <p class="recentsub">
                            Terakhir disubmit: {{ \Carbon\Carbon::parse($latestJournal->created_at)->format('d F Y H:i') }}
                        </p>
                    @else
                        <p>Tidak ada jurnal yang disubmit.</p>
                    @endif
                </div>
            </div>

            <div class="panel jurnal-history">
                <h3>Histori Pengisian Jurnal</h3>
                @if($jurnalHistory->count())
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Lokasi</th>
                            <th>Shift</th>
                            <th>Nama</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jurnalHistory as $jurnal)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($jurnal->created_at)->format('d F Y') }}</td>
                            <td>{{ $jurnal->lokasi->nama_lokasi ?? '-' }}</td>
                            <td>{{ $jurnal->shift->nama_shift ?? '-' }}</td>
                            <td>{{ $jurnal->satpam->nama }}</td>
                            <td>
                                <span class="badge {{ $jurnal->satpam->role == 'Kepala Satpam' ? 'pink' : 'blue' }}">
                                    {{ $jurnal->satpam->role }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <p>Belum ada jurnal yang disubmit.</p>
                @endif
            </div>
        </section>
    @endif

    <script>
        function togglePopup(id) {
            const popup = document.getElementById(id);
            if (!popup) return;

            // Reset error container (jika ada di dalam popup)
            const errorDiv = popup.querySelector('.form-errors');
            if (errorDiv) {
                errorDiv.classList.remove('show', 'hide');
                errorDiv.style.display = 'none';
                errorDiv.innerHTML = '';
            }

            // Toggle popup pakai class, bukan style
            if (popup.classList.contains('show')) {
                popup.classList.remove('show');
                popup.style.display = 'none';
            } else {
                popup.classList.add('show');
                popup.style.display = 'block';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const addUserForm = document.getElementById('addUserForm');
            const addLocationForm = document.getElementById('addLocationForm');
            const addShiftForm = document.getElementById('addShiftForm');
            let errorTimer;

            const handleFormSubmit = async (form, errorContainerId) => {
                const errorContainer = document.getElementById(errorContainerId) || form.querySelector('.form-errors');
                const submitButton = form.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;

                submitButton.disabled = true;
                submitButton.innerHTML = 'Menyimpan...';
                errorContainer.classList.remove('show', 'hide');
                errorContainer.style.display = 'none';
                errorContainer.innerHTML = '';
                clearTimeout(errorTimer);

                try {
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': formData.get('_token'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        if (data.errors) {
                            let errorHtml = '<ul>';
                            Object.values(data.errors).forEach(msgArr => {
                                msgArr.forEach(msg => {
                                    errorHtml += `<li>${msg}</li>`;
                                });
                            });
                            errorHtml += '</ul>';

                            errorContainer.innerHTML = errorHtml;
                            errorContainer.classList.add('show');

                            errorTimer = setTimeout(() => {
                                errorContainer.classList.remove('show');
                                errorContainer.classList.add('hide');
                            }, 5000);
                        }
                        return;
                    }

                    if (data.success) {
                        window.location.href = data.redirect_url || window.location.href;
                    }

                } catch (err) {
                    console.error('Fetch error:', err);
                    errorContainer.innerHTML = '<ul><li>Terjadi kesalahan jaringan.</li></ul>';
                    errorContainer.classList.add('show');
                } finally {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            };

            if (addUserForm) {
                addUserForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    handleFormSubmit(addUserForm, 'addUserErrors');
                });
            }

            if (addLocationForm) {
                addLocationForm.addEventListener('submit', e => {
                    e.preventDefault();
                    handleFormSubmit(addLocationForm, 'addLocationErrors');
                });
            }

            if (addShiftForm) {
                addShiftForm.addEventListener('submit', e => {
                    e.preventDefault();
                    handleFormSubmit(addShiftForm, 'addShiftErrors');
                });
            }
        });


        // Optional: Tutup popup jika klik di luar kontennya
        window.onclick = function(event) {
            document.querySelectorAll('.popup').forEach(function(popup) {
                if (event.target === popup) {
                    popup.style.display = 'none';
                }
            });
        };
        
        const toast = document.getElementById('flashToast');
        if (toast) {
            setTimeout(() => toast.classList.add('hide'), 3500);
        }
        
    </script>
@endsection
