@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kepala_satpam/dashboard.css') }}">
@endpush

@section('content')
    @php
        $role = Auth::user()->role;
    @endphp

    @if ($role === 'Kepala Satpam')
        @section('title', 'Dashboard - Kepala Satpam')
    @else
    @section('title', 'Dashboard - Satpam')
@endif
<div class="dashboard-kepala">
    <h1>Dashboard</h1>

    <div class="top-panels">
        <!-- DAFTAR LOKASI & SHIFT -->
        <div class="lokasi-shift-panel">
            <div class="journal-information-panel">
                <div class="header-row">
                    <h3>Informasi Jurnal</h3>
                    <select onchange="location = '?lokasi_id=' + this.value">
                        @foreach ($lokasis as $lokasi)
                            <option value="{{ $lokasi->id }}" {{ $lokasiFilterId == $lokasi->id ? 'selected' : '' }}>
                                {{ $lokasi->nama_lokasi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="scroll-panel">
                    @forelse($pendingJournals as $pending)
                        <div class="satpam-card">
                            <div class="satpam-info">
                                <img src="{{ $pending->foto ? asset('storage/' . $pending->foto) : asset('images/profile.jpeg') }}"
                                    alt="foto">
                                <strong>{{ $pending->user }}</strong>
                            </div>
                            <div class="satpam-info-loc">
                                <span class="lokasi-info-item">
                                    <i class="bi bi-geo-fill iconone"></i>
                                    <small>{{ $pending->lokasi }}</small>
                                </span>
                                <span class="lokasi-info-item">
                                    <i class="bi bi-calendar-check-fill icontwo"></i>
                                    <small>{{ $pending->shift }}</small>
                                </span>
                            </div>
                            <div class="satpam-info-status">
                                <span class="badge red">
                                    {{ $pending->status }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="empty-message">Tidak ada journal yang belum dikumpulkan.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- STATUS JURNAL HARI INI -->
        <div class="jurnal-status-panel">
            <h3>Status Pengisian Jurnal</h3>
            <div class="scroll-panel">
                @forelse($latestJurnals as $jurnal)
                    <div class="jurnal-card">
                        <div class="jurnal-info">
                            <strong>Jurnal - {{ $jurnal->shift->nama_shift }}</strong><br>
                            <small>Terakhir disimpan:
                                {{ \Carbon\Carbon::parse($jurnal->created_at)->format('M d, Y') }}</small>
                        </div>

                        <div class="jurnal-info-loc">
                            <i class="bi bi-geo-fill"></i>
                            <small>{{ $jurnal->lokasi->nama_lokasi ?? '-' }}</small><br>
                        </div>

                        <div class="jurnal-info-status">
                            <span class="badge {{ strtolower($jurnal->status) }}">
                                {{ ucfirst($jurnal->status) }}
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
        <h3>Riwayat Pengisian Jurnal</h3>
        <table class="history-table">
            <thead>
                <tr>
                    <th class="col-date">Tanggal</th>
                    <th class="col-location">Lokasi</th>
                    <th>Shift</th>
                    <th class="col-name">Nama</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jurnalHistory as $index => $jurnal)
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
                @empty
                    <tr>
                        <td colspan="6">Tidak ada jurnal.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

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
            const errorContainer = document.getElementById(errorContainerId) || form.querySelector(
                '.form-errors');
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
