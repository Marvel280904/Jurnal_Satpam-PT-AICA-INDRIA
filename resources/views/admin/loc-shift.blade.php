@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/location_shift.css') }}">
@endpush

@section('content')

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Konten Location & Shift Management untuk Admin --}}
    @section('title', 'Location & Shift Management')
        <div class="location-shift-container">
        <h1>Location & Shift Management</h1>

        @if(session('success'))
            <div id="flashToast" class="flash-toast {{ session('flash_type', 'success') }}">
                <span class="flash-dot"></span>
                <span class="flash-text">{{ session('success') }}</span>
                <button class="flash-close" onclick="document.getElementById('flashToast').classList.add('hide')">&times;</button>
            </div>
        @endif


        <div class="section-wrapper">
            <!-- LOCATION SECTION -->
            <div class="location-section">
                <div class="section-header">
                    <h2>Location</h2>
                    <button onclick="togglePopup('popup-location')">➕ Add Location</button>
                </div>

                @if($locations->isEmpty())
                    <p class="empty-text">Belum ada lokasi.</p>
                @else
                    <div class="location-cards">
                        @foreach($locations as $location)
                                <div class="location-card {{ !$location->is_active ? 'inactive' : '' }}">
                                <img src="{{ $location->foto ? asset($location->foto) : asset('images/default.jpg') }}" alt="Lokasi">
                                <div class="location-info">
                                    <h3><i class="bi bi-geo-alt-fill icon-loc"></i> {{ $location->nama_lokasi }}</h3>
                                    <p>{{ $location->alamat_lokasi }}</p>

                                    <!-- Button Activate / Inactivate -->
                                    <div class="location-card-actions">
                                        <button type="button"
                                            class="edit-btn" {{-- Hapus kelas .btn-loc --}}
                                            data-id="{{ $location->id }}"
                                            data-nama="{{ e($location->nama_lokasi) }}"
                                            data-alamat="{{ e($location->alamat_lokasi) }}"
                                            data-foto="{{ $location->foto ? asset($location->foto) : '' }}"
                                            onclick="openEditLocationPopup(this)"
                                            {{ !$location->is_active ? 'disabled' : '' }}>
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>

                                        <form action="{{ route('location.toggleStatus', $location->id) }}" method="POST" class="toggle-status-form">
                                            @csrf
                                            <button type="submit" class="status-btn">
                                                {{ $location->is_active ? 'Inactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- SHIFT SECTION -->
            <div class="shift-section">
                <div class="section-header">
                    <h2>Shift</h2>
                    <button onclick="togglePopup('popup-shift')">➕ Add Shift</button>
                </div>

                @if($shifts->isEmpty())
                    <p class="empty-text">Belum ada shift.</p>
                @else
                    <ul class="shift-list">
                        @foreach($shifts as $shift)
                            <li class="shift-item {{ $shift->is_active ? '' : 'inactive' }}">
                                <div class="shift-left">
                                    <i class="bi bi-calendar-plus calendar-icon"></i>
                                    <div class="shift-text">
                                        <strong>{{ $shift->nama_shift }}</strong>
                                        <div>{{ \Carbon\Carbon::parse($shift->mulai_shift)->format('H.i') }} - {{ \Carbon\Carbon::parse($shift->selesai_shift)->format('H.i') }}</div>
                                    </div>
                                </div>
                                <div class="shift-actions">
                                    <!-- Tombol Edit -->
                                    <button class="edit-btn-shift" onclick="openEditShiftPopup({{ $shift->id }}, '{{ $shift->nama_shift }}', '{{ $shift->mulai_shift }}', '{{ $shift->selesai_shift }}')"
                                        {{ !$shift->is_active ? 'disabled' : '' }}>
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>

                                    <!-- Tombol Activate/Inactivate -->
                                    <form action="{{ route('shift.toggleStatus', $shift->id,) }}" method="POST" class="toggle-status-form">
                                        @csrf
                                        <button type="submit" class="status-btn-shift">
                                            {{ $shift->is_active ? 'Inactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <!-- POPUP: ADD LOCATION -->
    <div class="popup" id="popup-location">
        <div class="popup-content">
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

    <!-- POPUP: EDIT LOCATION -->
    <div class="popup" id="popup-edit-loc">
        <div class="popup-content">
            <span class="close-btn" onclick="togglePopup('popup-edit-loc')">&times;</span>
            <h3>Edit Lokasi</h3>

            <form id="editLocationForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div id="editLocationErrors" class="form-errors hide" style="display:none;"></div>

                <label>Nama Lokasi <i class="bi bi-asterisk"></i> </label>
                <input type="text" name="nama_lokasi" id="editNamaLokasi" required placeholder="Wajib diisi!">

                <label>Alamat Lokasi <i class="bi bi-asterisk"></i> </label>
                <textarea name="alamat_lokasi" id="editAlamatLokasi" required placeholder="Wajib diisi!"></textarea>

                <label>Upload Foto <i class="bi bi-asterisk"></i> </label>
                <input type="file" name="foto" id="editFotoLokasi" accept="image/*">
                <!-- <small id="currentFotoText"></small> -->
                <img id="editFotoPreview" src="" alt="Foto lokasi" style="display:none;max-width:30%;border-radius:8px;margin-bottom:8px;padding-top:5px;"/>

                <button type="submit">Save</button>
            </form>
        </div>
    </div>

    <!-- POPUP: ADD SHIFT -->
    <div class="popup" id="popup-shift">
        <div class="popup-content">
            <span class="close-btn" onclick="togglePopup('popup-shift')">&times;</span>
            <h3>Tambah Shift</h3>
            <form id="addShiftForm" action="{{ route('shift.store') }}" method="POST" novalidate>
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

    <!-- POPUP: EDIT SHIFT -->
    <div class="popup" id="popup-edit-shift">
        <div class="popup-content">
            <span class="close-btn" onclick="togglePopup('popup-edit-shift')">&times;</span>
            <h3>Edit Shift</h3>
            <form id="editShiftForm" method="POST" novalidate>
                @csrf
                @method('PUT')

                <div id="editShiftErrors" class="form-errors hide" style="display:none;"></div>

                <label>Nama Shift <i class="bi bi-asterisk"></i> </label>
                <input type="text" name="nama_shift" id="editNamaShift" required placeholder="Wajib diisi!">

                <label>Mulai Shift <i class="bi bi-asterisk"></i> </label>
                <input type="time" name="mulai_shift" id="editMulaiShift" required>

                <label>Selesai Shift <i class="bi bi-asterisk"></i> </label>
                <input type="time" name="selesai_shift" id="editSelesaiShift" required>

                <button type="submit">Save</button>
            </form>
        </div>
    </div>

    <script>
        /**
         * Toggles the visibility of a popup and clears any error messages within it.
         * @param {string} id The ID of the popup element.
         */
        function togglePopup(id) {
            const popup = document.getElementById(id);
            if (!popup) return;

            const errorDiv = popup.querySelector('.form-errors');
            if (errorDiv) {
                errorDiv.classList.remove('show', 'hide');
                errorDiv.style.display = 'none';
                errorDiv.innerHTML = '';
            }
            popup.classList.toggle('show');
        }

        /**
         * Opens the edit shift popup and populates it with data.
         */
        function openEditShiftPopup(id, namaShift, mulai, selesai) {
            const form = document.getElementById('editShiftForm');
            form.action = `/shift/${id}`; // Adjust this route if it's different

            document.getElementById('editNamaShift').value = namaShift;
            document.getElementById('editMulaiShift').value = mulai;
            document.getElementById('editSelesaiShift').value = selesai;

            togglePopup('popup-edit-shift');
        }
        
        /**
         * Opens the edit location popup and populates it with data.
         */
        function openEditLocationPopup(btn) {
            const id     = btn.dataset.id;
            const nama   = btn.dataset.nama   || '';
            const alamat = btn.dataset.alamat || '';
            const foto   = btn.dataset.foto   || '';

            const form = document.getElementById('editLocationForm');
            form.action = `/location/${id}`;

            document.getElementById('editNamaLokasi').value   = nama;
            document.getElementById('editAlamatLokasi').value = alamat;

            const fileInput = document.getElementById('editFotoLokasi');
            fileInput.value = '';

            const preview = document.getElementById('editFotoPreview');
            if (foto) {
                preview.src = foto;
                preview.style.display = 'block';
            } else {
                preview.removeAttribute('src');
                preview.style.display = 'none';
            }

            togglePopup('popup-edit-loc');
        }

        // --- EVENT LISTENER UTAMA ---
        document.addEventListener('DOMContentLoaded', () => {
            
            // --- Location Edit Image Preview Logic ---
            const editFotoInput = document.getElementById('editFotoLokasi');
            const editFotoPreview = document.getElementById('editFotoPreview');

            // [PERBAIKAN] Cek apakah elemen ada sebelum menambahkan listener
            if (editFotoInput && editFotoPreview) {
                editFotoInput.addEventListener('change', () => {
                    const file = editFotoInput.files && editFotoInput.files[0];
                    if (file) {
                        const url = URL.createObjectURL(file);
                        editFotoPreview.src = url;
                        editFotoPreview.style.display = 'block';
                        editFotoPreview.onload = () => URL.revokeObjectURL(url);
                    } else {
                        editFotoPreview.removeAttribute('src');
                        editFotoPreview.style.display = 'none';
                    }
                });
            }

            // --- AJAX Form Submission Logic for Location & Shifts ---
            const addLocationForm = document.getElementById('addLocationForm');
            const editLocationForm = document.getElementById('editLocationForm');
            const addShiftForm = document.getElementById('addShiftForm');
            const editShiftForm = document.getElementById('editShiftForm');
            const toggleForms = document.querySelectorAll('.toggle-status-form');
            let errorTimer;

            toggleForms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = 'Laoding...';
                    }
                });
            });
            
            const handleFormSubmit = async (form, errorContainerId) => {
                // dapatkan container error, fallback ke elemen .form-errors di dalam form
                const errorContainer = document.getElementById(errorContainerId) || form.querySelector('.form-errors');
                if (!errorContainer) {
                    console.warn('Tidak menemukan container error:', errorContainerId);
                }

                const submitButton = form.querySelector('button[type="submit"]') || form.querySelector('button');
                const originalButtonText = submitButton ? submitButton.innerHTML : '';

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = 'Menyimpan...';
                }

                if (errorContainer) {
                    errorContainer.classList.remove('show', 'hide');
                    errorContainer.style.display = 'none';
                    errorContainer.innerHTML = '';
                }
                clearTimeout(errorTimer);

                try {
                    const formData = new FormData(form);
                    const spoof = form.querySelector('input[name="_method"]');
                    if (spoof) formData.set('_method', spoof.value);

                    const response = await fetch(form.action, {
                        method: 'POST', // selalu POST, Laravel akan membaca _method jika perlu
                        headers: {
                            'X-CSRF-TOKEN': formData.get('_token'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData,
                    });

                    // coba parse JSON saat content-type JSON
                    let data = null;
                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        data = await response.json();
                    } else {
                        // kalau bukan JSON, coba parse; jika gagal, set data = null
                        try { data = await response.json(); } catch (e) { data = null; }
                    }

                    if (!response.ok) {
                        // tampilkan pesan validasi jika ada
                        if (data && data.errors) {
                            let errorHtml = '<ul>';
                            Object.values(data.errors).forEach(errorMessages => {
                                errorMessages.forEach(message => {
                                    errorHtml += `<li>${message}</li>`;
                                });
                            });
                            errorHtml += '</ul>';

                            if (errorContainer) {
                                errorContainer.innerHTML = errorHtml;
                                errorContainer.classList.add('show');
                            } else {
                                alert('Errors:\n' + JSON.stringify(data.errors));
                            }

                            // hide setelah 5s
                            errorTimer = setTimeout(() => {
                                if (errorContainer) {
                                    errorContainer.classList.remove('show');
                                    errorContainer.classList.add('hide');
                                }
                            }, 5000);
                        } else {
                            // fallback generic
                            const msg = (data && data.message) ? data.message : 'Terjadi kesalahan.';
                            if (errorContainer) {
                                errorContainer.innerHTML = `<ul><li>${msg}</li></ul>`;
                                errorContainer.style.display = 'block';
                                errorContainer.classList.add('show');
                            } else {
                                alert(msg);
                            }
                        }
                        return;
                    }

                    // sukses -> redirect ke index (atau reload)
                    if (data && data.success) {
                        window.location.href = data.redirect_url || window.location.href;
                    } else {
                        window.location.href = window.location.href;
                    }

                } catch (error) {
                    console.error('Fetch error:', error);
                    if (errorContainer) {
                        errorContainer.innerHTML = '<ul><li>Terjadi kesalahan jaringan.</li></ul>';
                        errorContainer.style.display = 'block';
                        errorContainer.classList.add('show');
                    } else {
                        alert('Terjadi kesalahan jaringan.');
                    }
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    }
                }
            };


            // Pasang event listener ke form "Tambah Shift"
            if (addShiftForm) {
                addShiftForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    handleFormSubmit(addShiftForm, 'addShiftErrors');
                });
            }

            // Pasang event listener ke form "Edit Shift"
            if (editShiftForm) {
                editShiftForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    handleFormSubmit(editShiftForm, 'editShiftErrors');
                });
            }

            if (addLocationForm) {
                addLocationForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    handleFormSubmit(addLocationForm, 'addLocationErrors');
                });
            }

            if (editLocationForm) {
                editLocationForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    handleFormSubmit(editLocationForm, 'editLocationErrors');
                });
            }

            // --- Flash Toast Logic ---
            const toast = document.getElementById('flashToast');
            if (toast) {
                setTimeout(() => toast.classList.add('hide'), 3500);
            }
        });
    </script>

@endsection
