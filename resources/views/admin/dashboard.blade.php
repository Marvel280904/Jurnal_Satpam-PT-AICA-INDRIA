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

            <!-- POPUP: ADD LOCATION -->
            <div class="dashboard-popup" id="popup-location">
                <div class="dashboard-popup-content">
                    <span class="close-btn" onclick="togglePopup('popup-location')">&times;</span>
                    <h3>Tambah Lokasi</h3>
                    <form action="{{ route('location.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <label>Nama Lokasi</label>
                        <input type="text" name="nama_lokasi" required>

                        <label>Alamat Lokasi</label>
                        <textarea name="alamat_lokasi" required></textarea>

                        <label>Upload Foto</label>
                        <input type="file" name="foto">

                        <button type="submit">Submit</button>
                    </form>
                </div>
            </div>

            <!-- POPUP: ADD SHIFT -->
            <div class="dashboard-popup" id="popup-shift">
                <div class="dashboard-popup-content">
                    <span class="close-btn" onclick="togglePopup('popup-shift')">&times;</span>
                    <h3>Tambah Shift</h3>
                    <form action="{{ route('shift.store') }}" method="POST">
                        @csrf
                        <label>Lokasi</label>
                        <select name="lokasi_id" required>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->nama_lokasi }}</option>
                            @endforeach
                        </select>

                        <label>Nama Shift</label>
                        <input type="text" name="nama_shift" required>

                        <label>Mulai Shift</label>
                        <input type="time" name="mulai_shift" required>

                        <label>Selesai Shift</label>
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
                        <select onchange="location = '?shift=' + this.value">
                            <option value="Shift Pagi" {{ $shiftFilter == 'Shift Pagi' ? 'selected' : '' }}>Shift Pagi</option>
                            <option value="Shift Siang" {{ $shiftFilter == 'Shift Siang' ? 'selected' : '' }}>Shift Siang</option>
                            <option value="Shift Malam" {{ $shiftFilter == 'Shift Malam' ? 'selected' : '' }}>Shift Malam</option>
                        </select>
                    </div>

                    <div class="scroll-panel">
                        @forelse($jadwals as $jadwal)
                            <div class="satpam-card">
                            <img src="{{ $jadwal->satpam->foto
                                        ? asset('storage/'.$jadwal->satpam->foto)
                                        : asset('images/profile.jpeg') }}"
                                alt="foto">
                            <div class="satpam-info">
                                <strong>{{ $jadwal->satpam->nama }}</strong>
                                <span class="lokasi-info">
                                <i class="bi bi-geo-fill"></i>
                                <small>{{ $jadwal->lokasi->nama_lokasi ?? '-' }}</small>
                                </span>
                            </div>
                            <span class="badge {{ $jadwal->status == 'On Duty' ? 'green' : 'red' }}">
                                {{ $jadwal->status }}
                            </span>
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

                                <i class="bi bi-geo-fill"></i>
                                <small>{{ $jurnal->lokasi->nama_lokasi ?? '-' }}</small><br>

                                <span class="badge {{ strtolower($jurnal->status) }}">
                                    {{ ucfirst($jurnal->status == 'reject' ? 'Late' : $jurnal->status) }}
                                </span>
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
                        <tr><th>Tanggal</th><th>Lokasi</th><th>Shift</th><th>Nama</th><th>Role</th></tr>
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
                                Mulai Pengisian Jurnal
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
            popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
        }

        // Optional: Tutup popup jika klik di luar kontennya
        window.onclick = function(event) {
            document.querySelectorAll('.popup').forEach(function(popup) {
                if (event.target === popup) {
                    popup.style.display = 'none';
                }
            });
        };
    </script>
@endsection
