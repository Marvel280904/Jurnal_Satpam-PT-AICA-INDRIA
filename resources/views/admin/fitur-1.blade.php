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
                                    <h3><i class="bi bi-geo-alt-fill"></i> {{ $location->nama_lokasi }}</h3>
                                    <p>{{ $location->alamat_lokasi }}</p>

                                    <!-- Button Activate / Inactivate -->
                                    <form action="{{ route('location.toggleStatus', $location->id) }}" method="POST" style="margin-top: 10px;">
                                        @csrf
                                        <button type="submit" class="status-btn">
                                            {{ $location->is_active ? 'Inactivate' : 'Activate' }}
                                        </button>
                                    </form>
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

                @php $shifts = $locations->flatMap->shifts; @endphp

                @if($shifts->isEmpty())
                    <p class="empty-text">Belum ada shift.</p>
                @else
                    <ul class="shift-list">
                        @foreach($shifts as $shift)
                            <li class="shift-item">
                                <div class="shift-left">
                                    <i class="bi bi-calendar-plus calendar-icon"></i>
                                    <div class="shift-text">
                                        <strong>{{ $shift->nama_shift }}</strong>
                                        <div>{{ \Carbon\Carbon::parse($shift->mulai_shift)->format('H.i') }} - {{ \Carbon\Carbon::parse($shift->selesai_shift)->format('H.i') }}</div>
                                        <small><i class="bi bi-geo-alt-fill"> </i> {{ $shift->location->nama_lokasi }}</small>
                                    </div>
                                </div>
                                <button class="edit-btn" onclick="openEditShiftPopup({{ $shift->id }}, '{{ $shift->nama_shift }}', '{{ $shift->mulai_shift }}', '{{ $shift->selesai_shift }}')">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
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
    <div class="popup" id="popup-shift">
        <div class="popup-content">
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

    <!-- POPUP: EDIT SHIFT -->
    <div class="popup" id="popup-edit-shift">
        <div class="popup-content">
            <span class="close-btn" onclick="togglePopup('popup-edit-shift')">&times;</span>
            <h3>Edit Shift</h3>
            <form id="editShiftForm" method="POST">
                @csrf
                @method('PUT')

                <label>Nama Shift</label>
                <input type="text" name="nama_shift" id="editNamaShift" required>

                <label>Mulai Shift</label>
                <input type="time" name="mulai_shift" id="editMulaiShift" required>

                <label>Selesai Shift</label>
                <input type="time" name="selesai_shift" id="editSelesaiShift" required>

                <button type="submit">Save</button>
            </form>
        </div>
    </div>

@elseif ($role === 'Satpam' || $role === 'Kepala Satpam')
    @section('title', 'Journal Submission')
    <div id="top-anchor" class="journal-container">
        <h1>Journal Submission</h1>

        <div id="successMessage" class="flash-message success" style="display: none;">
            Jurnal berhasil disubmit.
        </div>

        <form id="jurnalForm" enctype="multipart/form-data">
            @csrf

            <div class="form-group-row">
                <div>
                    <label>Lokasi</label>
                    <select name="lokasi_id" id="lokasiSelect" required>
                        <option value="">-- Pilih Lokasi --</option>
                        @foreach($lokasis as $lokasi)
                            <option value="{{ $lokasi->id }}">{{ $lokasi->nama_lokasi }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label>Shift</label>
                    <select name="shift_id" id="shiftSelect" required disabled>
                        <option value="">-- Pilih Lokasi terlebih dahulu --</option>
                    </select>
                </div>

                <div>
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" value="{{ \Carbon\Carbon::today()->toDateString() }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>Cuaca</label>
                <input type="text" name="cuaca" placeholder="Wajib diisi" required>
            </div>

            @php
                $items = [
                    'kejadian_temuan' => 'Laporan Kejadian/Temuan',
                    'lembur' => 'Lembur',
                    'proyek_vendor' => 'Proyek/Vendor',
                    'paket_dokumen' => 'Paket/Dokumen',
                    'tamu_belum_keluar' => 'Tamu Belum Keluar',
                    'karyawan_dinas_luar' => 'Karyawan Dinas Luar',
                    'barang_inventaris_keluar' => 'Barang Inventaris Keluar',
                    'kendaraan_dinas_luar' => 'Kendaraan Dinas Luar',
                    'lampu_mati' => 'Lampu/Penerangan Mati'
                ];
            @endphp

            @foreach($items as $key => $label)
                <div class="form-group">
                    <label>{{ $label }}</label>
                    <div class="radio-group">
                        <label><input type="radio" name="is_{{ $key }}" value="1" required> Yes</label>
                        <label><input type="radio" name="is_{{ $key }}" value="0" required> No</label>
                    </div>
                    <textarea name="{{ $key }}" placeholder="Keterangan (wajib jika Yes)"></textarea>
                </div>
            @endforeach

            <div class="form-group">
                <label>Informasi Tambahan</label>
                <textarea name="info_tambahan" placeholder="Wajib diisi" required></textarea>
            </div>

            <div class="form-group">
                <label>Upload Foto/File (boleh lebih dari 1)</label>
                <input type="file" id="fileInput">
                <ul id="fileList" class="file-preview-list"></ul>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Submit</button>
            </div>
        </form>
    </div>
@endif




<script>

    ///// script location & shift management
    function togglePopup(id) {
        document.getElementById(id).classList.toggle('show');
    }

    function openEditShiftPopup(id, namaShift, mulai, selesai) {
        // Ubah action URL
        const form = document.getElementById('editShiftForm');
        form.action = `/shift/${id}`; // pastikan route-nya benar

        // Isi form
        document.getElementById('editNamaShift').value = namaShift;
        document.getElementById('editMulaiShift').value = mulai;
        document.getElementById('editSelesaiShift').value = selesai;

        // Tampilkan popup
        togglePopup('popup-edit-shift');
    }


    ///// script journal submission
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');
    const form = document.getElementById('jurnalForm');
    const successBox = document.getElementById('successMessage');

    let filesArray = [];

    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            filesArray.push(file);
            renderFileList();
        }
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

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(form);
        filesArray.forEach((file) => {
            formData.append('uploads[]', file);
        });

        fetch("{{ route('jurnal.submission') }}", {
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(response => response.json())
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
        .catch(error => {
            alert("Terjadi kesalahan!");
            console.error(error);
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

    //// load shift sesuai lokasi
    document.getElementById('lokasiSelect').addEventListener('change', function () {
        const lokasiId = this.value;
        const shiftSelect = document.getElementById('shiftSelect');

        // Reset dropdown shift
        shiftSelect.innerHTML = '<option value="">-- Pilih Shift --</option>';
        shiftSelect.disabled = true;

        if (lokasiId) {
            fetch(`/shifts/by-location/${lokasiId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(shift => {
                        const option = document.createElement('option');
                        option.value = shift.id;
                        option.textContent = shift.nama_shift;
                        shiftSelect.appendChild(option);
                    });
                    shiftSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Gagal mengambil data shift:', error);
                });
        } else {
            // Jika lokasi dikosongkan kembali
            shiftSelect.innerHTML = '<option value="">-- Pilih Lokasi terlebih dahulu --</option>';
            shiftSelect.disabled = true;
        }
    });

</script>
@endsection
