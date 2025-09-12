@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/satpam/journal-submission.css') }}">
@endpush

@section('content')

@section('title', 'Edit Journal Submission')

<div id="top-anchor" class="journal-container">
    <h1>Edit Journal Submission</h1>

    <div id="successMessage" class="flash-message success" style="display: none;">
        Jurnal berhasil diperbarui.
    </div>

    <form id="jurnalForm" action="{{ route('jurnal.update', $jurnal->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-group-row">
            <div>
                <label>Lokasi <i class="bi bi-asterisk"></i> </label>
                <select name="lokasi_id" id="lokasiSelect" required>
                    <option value="" disabled selected>-- Pilih Lokasi --</option>
                    @foreach($lokasis as $lokasi)
                        <option value="{{ $lokasi->id }}" {{ $jurnal->lokasi_id == $lokasi->id ? 'selected' : '' }}>
                            {{ $lokasi->nama_lokasi }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Shift <i class="bi bi-asterisk"></i> </label>
                <select name="shift_id" id="shiftSelect" required>
                    <option value="" disabled selected>-- Pilih Shift --</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" {{ $jurnal->shift_id == $shift->id ? 'selected' : '' }}>
                            {{ $shift->nama_shift }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Tanggal <i class="bi bi-asterisk"></i> </label>
                <input type="date" name="tanggal" value="{{ \Carbon\Carbon::parse($jurnal->tanggal)->format('Y-m-d') }}" required>
            </div>

        </div>

        <div class="form-group">
            <label>Cuaca <i class="bi bi-asterisk"></i> </label>
            <input type="text" name="cuaca" value="{{ $jurnal->cuaca }}" required>
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
                    <label><input type="radio" name="is_{{ $key }}" value="1" {{ $jurnal->{"is_{$key}"} ? 'checked' : '' }}> Yes</label>
                    <label><input type="radio" name="is_{{ $key }}" value="0" {{ !$jurnal->{"is_{$key}"} ? 'checked' : '' }}> No</label>
                </div>
                <textarea name="{{ $key }}">{{ $jurnal->$key }}</textarea>
            </div>
        @endforeach

        <div class="form-group">
            <label>Informasi Tambahan <i class="bi bi-asterisk"></i> </label>
            <textarea name="info_tambahan" required>{{ $jurnal->info_tambahan }}</textarea>
        </div>

        <div class="form-group">
            <label>Upload Foto/File Baru (Max: 2 MB)</label>
            <input type="file" id="fileInput" multiple>

            <ul id="fileList" class="file-preview-list">
                {{-- File dari database --}}
                @foreach($jurnal->uploads as $upload)
                    <li class="existing-file" data-id="{{ $upload->id }}">
                        <span>{{ basename($upload->file_path) }}</span>
                        <button type="button" class="remove-btn remove-existing" data-id="{{ $upload->id }}">x</button>
                    </li>
                @endforeach
            </ul>
        </div>

    
        <div class="form-actions">
            <button type="submit" class="btn-submit">Save</button>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // ==========================================================
    // A. SETUP AWAL
    // ==========================================================
    const form = document.getElementById('jurnalForm');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');
    const lokasiSelect = document.getElementById('lokasiSelect'); // Variabel ditambahkan
    const shiftSelect = document.getElementById('shiftSelect');   // Variabel ditambahkan

    let newFiles = [];        // Menyimpan file BARU yang akan diupload
    let filesToDelete = [];   // Menyimpan ID file LAMA yang akan dihapus

    // ==========================================================
    // B. MANAJEMEN FILE (TAMBAH & HAPUS)
    // ==========================================================
    // Event listener untuk tombol hapus (baik file lama maupun baru)
    fileList.addEventListener('click', function (e) {
        if (!e.target.classList.contains('remove-btn')) return;
        const li = e.target.closest('li');
        if (li.classList.contains('existing-file')) {
            const fileId = e.target.dataset.id;
            if (!filesToDelete.includes(fileId)) {
                filesToDelete.push(fileId);
            }
            li.style.display = 'none';
        }
        else if (li.dataset.newFileIndex !== undefined) {
            const index = parseInt(li.dataset.newFileIndex, 10);
            newFiles.splice(index, 1);
            li.remove();
        }
    });

    // Event listener saat user memilih file baru
    fileInput.addEventListener('change', function () {
        Array.from(this.files).forEach(f => newFiles.push(f));
        renderNewFiles();
        this.value = '';
    });

    // Fungsi untuk menampilkan HANYA file baru yang ditambahkan
    function renderNewFiles() {
        document.querySelectorAll('li[data-new-file-index]').forEach(el => el.remove());
        newFiles.forEach((file, index) => {
            const li = document.createElement('li');
            li.dataset.newFileIndex = index;
            li.innerHTML = `
                <span>${file.name} <span class="badge-new"></span></span>
                <button type="button" class="remove-btn">x</button>
            `;
            fileList.appendChild(li);
        });
    }

    // ==========================================================
    // C. LOGIKA VALIDASI RADIO BUTTON & TEXTAREA
    // ==========================================================
    @if(isset($items))
        @foreach($items as $key => $label)
            (function(){
            const yes = document.querySelector('input[name="is_{{ $key }}"][value="1"]');
            const no  = document.querySelector('input[name="is_{{ $key }}"][value="0"]');
            const ta  = document.querySelector('textarea[name="{{ $key }}"]');
            function sync_{{ $key }}() {
                if (!yes || !ta) return;
                if (yes.checked) {
                    ta.required = true;
                    if (ta.value.trim() === '') {
                        ta.setCustomValidity('Keterangan wajib diisi jika memilih Yes.');
                    } else {
                        ta.setCustomValidity('');
                    }
                } else {
                    ta.required = false;
                    ta.setCustomValidity('');
                }
            }
            sync_{{ $key }}();
            if (yes) yes.addEventListener('change', sync_{{ $key }});
            if (no) no.addEventListener('change',  sync_{{ $key }});
            if (ta) ta.addEventListener('input', sync_{{ $key }});
            })();
        @endforeach
    @endif


    // ==========================================================
    // D. PROSES SUBMIT FORM DENGAN FETCH (AJAX)
    // ==========================================================
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        const originalBtnText = submitButton.innerHTML;

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        submitButton.disabled = true;
        submitButton.innerHTML = 'Menyimpan...';

        const formData = new FormData(form);
        newFiles.forEach(file => {
            formData.append('uploads[]', file, file.name);
        });
        filesToDelete.forEach(id => {
            formData.append('delete_existing[]', id);
        });
        formData.append('_method', 'PUT');

        fetch(form.action, {
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(async (res) => {
            if (!res.ok) {
                const text = await res.text();
                console.error('Server Error:', text);
                throw new Error('Terjadi kesalahan pada server.');
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect_url;
            } else {
                alert("Gagal memperbarui jurnal.");
            }
        })
        .catch(err => {
            alert(err.message);
            console.error(err);
        });
    });
});
</script>



@endsection