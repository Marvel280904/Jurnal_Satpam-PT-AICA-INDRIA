@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/location_shift.css') }}">
    <link rel="stylesheet" href="{{ asset('css/satpam/journal-submission.css') }}">

@endpush

@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    $role = Auth::user()->role;
@endphp

@if ($role === 'Admin')
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
                                <img src="{{ $location->foto ? asset('storage/' . $location->foto) : asset('images/default.jpg') }}" alt="Lokasi">
                                <div class="location-info">
                                    <h3><i class="bi bi-geo-alt-fill icon-loc"></i> {{ $location->nama_lokasi }}</h3>
                                    <p>{{ $location->alamat_lokasi }}</p>

                                    <!-- Button Activate / Inactivate -->
                                    <form action="{{ route('location.toggleStatus', $location->id) }}" method="POST" style="margin-top: 10px;">
                                        @csrf
                                        <button type="submit" class="status-btn">
                                            {{ $location->is_active ? 'Inactivate' : 'Activate' }}
                                        </button>
                                    </form>

                                    <button type="button"
                                        class="edit-btn btn-loc"
                                        data-id="{{ $location->id }}"
                                        data-nama="{{ e($location->nama_lokasi) }}"
                                        data-alamat="{{ e($location->alamat_lokasi) }}"
                                        data-foto="{{ $location->foto ? asset('storage/'.$location->foto) : '' }}"
                                        onclick="openEditLocationPopup(this)">

                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
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
                                    <button class="edit-btn-shift" onclick="openEditShiftPopup({{ $shift->id }}, '{{ $shift->nama_shift }}', '{{ $shift->mulai_shift }}', '{{ $shift->selesai_shift }}')">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>

                                    <!-- Tombol Activate/Inactivate -->
                                    <form action="{{ route('shift.toggleStatus', $shift->id,) }}" method="POST">
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

@elseif ($role === 'Satpam' || $role === 'Kepala Satpam')
    @section('title', 'Journal Submission')
    
    @if(session('flash_notification'))
        @php
            // 1. Ambil array notifikasi dari session ke dalam satu variabel
            $notification = session('flash_notification');
            
            // 2. Tentukan kelas CSS dari 'type' di dalam array
            $flashTypeClass = $notification['type'] ?? 'warning';
        @endphp
        <div id="notification-overlay" class="modal-overlay is-visible">
            <div id="persistent-notification" class="flash-toast {{ $flashTypeClass }}">
                
                {{-- 3. Tampilkan 'message' dari dalam array --}}
                <span class="flash-text">{{ $notification['message'] }}</span>
                
                <button id="close-notification-btn" class="flash-close">&times;</button>
            </div>
        </div>
    @endif
    
    <div id="top-anchor" class="journal-container">
        <h1>Journal Submission</h1>

        <div id="successMessage" class="flash-message success" style="display: none;">
            Jurnal berhasil disubmit.
        </div>

        <form id="jurnalForm" enctype="multipart/form-data">
            @csrf

            <div class="form-group-row">
                <div>
                    <label>Lokasi <i class="bi bi-asterisk"></i> </label>
                    <select name="lokasi_id" id="lokasiSelect" required>
                        <option value="" disabled selected>-- Pilih Lokasi --</option>
                        @foreach($lokasis as $lokasi)
                            <option value="{{ $lokasi->id }}">{{ $lokasi->nama_lokasi }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label>Shift <i class="bi bi-asterisk"></i> </label>
                    <select name="shift_id" id="shiftSelect" required>
                        <option value="" disabled selected >-- Pilih Shift --</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->nama_shift }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label>Tanggal <i class="bi bi-asterisk"></i> </label>
                    <input type="date" name="tanggal" value="{{ \Carbon\Carbon::today()->toDateString() }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>Cuaca <i class="bi bi-asterisk"></i> </label>
                <input type="text" name="cuaca" placeholder="Wajib diisi" required>
            </div>

            @php
                $items = [
                    'kejadian_temuan' => 'Laporan Kejadian/Temuan',
                    'lembur' => 'Lembur',
                    'proyek_vendor' => 'Proyek/Vendor',
                    'paket_dokumen' => 'Paket/Dokumen',
                    'tamu_belum_keluar' => 'Tamu Belum Keluar',
                    'karyawan_dinas_keluar' => 'Karyawan Dinas Luar',
                    'barang_keluar' => 'Barang Inventaris Keluar',
                    'kendaraan_dinas_keluar' => 'Kendaraan Dinas Luar',
                    'lampu_mati' => 'Lampu/Penerangan Mati'
                ];
            @endphp

            @foreach($items as $key => $label)
                <div class="form-group">
                    <label>{{ $label }} <i class="bi bi-asterisk"></i> </label>
                    <div class="radio-group">
                        <label><input type="radio" name="is_{{ $key }}" value="1" required> Yes</label>
                        <label><input type="radio" name="is_{{ $key }}" value="0" required> No</label>
                    </div>
                    <textarea name="{{ $key }}" placeholder="Keterangan (wajib jika Yes)"></textarea>
                </div>
            @endforeach

            <div class="form-group">
                <label>Informasi Tambahan <i class="bi bi-asterisk"></i> </label>
                <textarea name="info_tambahan" placeholder="Wajib diisi" required></textarea>
            </div>

            <div class="form-group">
                <label>Upload Foto/File (Max: 2 MB)</label>
                <input type="file" id="fileInput" multiple>
                <ul id="fileList" class="file-preview-list"></ul>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Submit</button>
            </div>
        </form>
    </div>
@endif


@if ($role === 'Admin')
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

        // --- Flash Toast Logic ---
        const toast = document.getElementById('flashToast');
        if (toast) {
            setTimeout(() => toast.classList.add('hide'), 3500);
        }

        // --- AJAX Form Submission Logic for Location & Shifts ---
        const addLocationForm = document.getElementById('addLocationForm');
        const editLocationForm = document.getElementById('editLocationForm');
        const addShiftForm = document.getElementById('addShiftForm');
        const editShiftForm = document.getElementById('editShiftForm');
        let errorTimer;

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
    });
</script>

@elseif ($role === 'Satpam' || $role === 'Kepala Satpam')
<script>
    ///// script journal submission
    document.addEventListener('DOMContentLoaded', function() {
        // ===================================================================
        // ===== LOGIKA UNTUK NOTIFIKASI DARI CONTROLLER (BARU) =====
        // ===================================================================
        const notificationModal = document.getElementById('persistent-notification');
        const closeBtn = document.getElementById('close-notification-btn');

        if (notificationModal && closeBtn) {
            // Tampilkan modal jika ada
            notificationModal.classList.add('is-visible');

            closeBtn.addEventListener('click', function() {
                // Redirect ke dashboard saat tombol close diklik
                window.location.href = "{{ route('dashboard') }}";
            });
        }


        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const form = document.getElementById('jurnalForm');
        const successBox = document.getElementById('successMessage');

        let filesArray = [];

        fileInput.addEventListener('change', function () {
            Array.from(this.files).forEach(f => filesArray.push(f));
            renderFileList();
            this.value = '';
        });

        function renderFileList() {
            fileList.innerHTML = '';
            filesArray.forEach((file, index) => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <span>${file.name}</span>
                    <button type="button" onclick="removeFile(${index})" class="remove-btn">x</button>
                `;
                fileList.appendChild(li);
            });
        }

        function removeFile(index) {
            filesArray.splice(index, 1);
            renderFileList();
        }

        // ---- realtime binding for every (radio yes/no, textarea) pair ----
        @foreach($items as $key => $label)
            (function(){
            const yes = document.querySelector('input[name="is_{{ $key }}"][value="1"]');
            const no  = document.querySelector('input[name="is_{{ $key }}"][value="0"]');
            const ta  = document.querySelector('textarea[name="{{ $key }}"]');

            function sync_{{ $key }}() {
                // if YES checked -> textarea required; else not required
                if (yes && yes.checked) {
                ta.required = true;
                // give friendly message while empty
                if (ta.value.trim() === '') {
                    ta.setCustomValidity('Keterangan wajib diisi jika memilih Yes.');
                } else {
                    ta.setCustomValidity('');
                }
                } else {
                ta.required = false;
                ta.setCustomValidity('');  // clear any previous error if user switches to No
                }
            }

            // run on load
            sync_{{ $key }}();

            // re-check whenever radio changes OR textarea content changes
            if (yes)  yes.addEventListener('change', sync_{{ $key }});
            if (no)   no.addEventListener('change',  sync_{{ $key }});
            ta.addEventListener('input', sync_{{ $key }});
            })();
        @endforeach

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitButton = form.querySelector('button[type="submit"]');
            const originalBtnText = submitButton.innerHTML;

            if (!form.checkValidity()) {
                form.reportValidity();   // shows which field is missing
                return;                  // stop; don't call fetch
            } 

            submitButton.disabled = true;
            submitButton.innerHTML = 'Menyimpan...';

            const formData = new FormData(form);
            filesArray.forEach((file) => {
                formData.append('uploads[]', file);
            });

            fetch("{{ route('jurnal.submission') }}", {
                method: "POST",
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: formData
            })
            .then(async (res) => {
                // kalau server error, baca sebagai teks untuk debugging
                if (!res.ok) {
                    const text = await res.text();
                    console.error(text);
                    throw new Error('Server error');
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showSuccessMessage();
                    form.reset();
                    filesArray = [];
                    renderFileList();
                } else {
                    alert("Gagal menyimpan jurnal.");
                }
            })
            .catch(err => {
                alert("Terjadi kesalahan!");
                console.error(err);
            });

        });

        function showSuccessMessage() {
            const anchor = document.getElementById('top-anchor');
            if (anchor) {
                anchor.scrollIntoView({ behavior: 'smooth' });
            }
            successBox.style.display = 'block';
            setTimeout(() => {
                successBox.style.display = 'none';
            }, 4000);
        }

        // //// load shift sesuai lokasi
        // document.getElementById('lokasiSelect').addEventListener('change', function () {
        //     const lokasiId = this.value;
        //     const shiftSelect = document.getElementById('shiftSelect');

        //     // Reset dropdown shift
        //     shiftSelect.innerHTML = '<option value="" disabled selected>-- Pilih Shift --</option>';
        //     shiftSelect.disabled = true;

        //     if (lokasiId) {
        //         fetch(`/shifts/by-location/${lokasiId}`)
        //             .then(response => response.json())
        //             .then(data => {
        //                 data.forEach(shift => {
        //                     const option = document.createElement('option');
        //                     option.value = shift.id;
        //                     option.textContent = shift.nama_shift;
        //                     shiftSelect.appendChild(option);
        //                 });
        //                 shiftSelect.disabled = false;
        //             })
        //             .catch(error => {
        //                 console.error('Gagal mengambil data shift:', error);
        //             });
        //     } else {
        //         // Jika lokasi dikosongkan kembali
        //         shiftSelect.innerHTML = '<option value="">-- Pilih Lokasi terlebih dahulu --</option>';
        //         shiftSelect.disabled = true;
        //     }
        // });
    });
</script>
@endif
@endsection
