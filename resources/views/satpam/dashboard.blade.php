@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/satpam/dashboard.css') }}">
@endpush

@section('content')
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
                        <th class="col-date">Tanggal</th>
                        <th class="col-location">Lokasi</th>
                        <th>Shift</th>
                        <th class="col-name">Nama</th>
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
