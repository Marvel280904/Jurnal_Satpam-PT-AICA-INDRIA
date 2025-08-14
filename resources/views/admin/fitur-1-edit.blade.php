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
                <label>Lokasi</label>
                <select name="lokasi_id" id="lokasiSelect" required>
                    <option value="">-- Pilih Lokasi --</option>
                    @foreach($lokasis as $lokasi)
                        <option value="{{ $lokasi->id }}" {{ $jurnal->lokasi_id == $lokasi->id ? 'selected' : '' }}>
                            {{ $lokasi->nama_lokasi }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Shift</label>
                <select name="shift_id" id="shiftSelect" required>
                    <option value="">-- Pilih Shift --</option>
                    @foreach($shifts as $shift)
                        @if($shift->lokasi_id == $jurnal->lokasi_id)
                            <option value="{{ $shift->id }}" {{ $jurnal->shift_id == $shift->id ? 'selected' : '' }}>
                                {{ $shift->nama_shift }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div>
                <label>Tanggal</label>
                <input type="date" name="tanggal" value="{{ \Carbon\Carbon::parse($jurnal->tanggal)->format('Y-m-d') }}" required>
            </div>

        </div>

        <div class="form-group">
            <label>Cuaca</label>
            <input type="text" name="cuaca" value="{{ $jurnal->cuaca }}" required>
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
                    <label><input type="radio" name="is_{{ $key }}" value="1" {{ $jurnal->{"is_{$key}"} ? 'checked' : '' }}> Yes</label>
                    <label><input type="radio" name="is_{{ $key }}" value="0" {{ !$jurnal->{"is_{$key}"} ? 'checked' : '' }}> No</label>
                </div>
                <textarea name="{{ $key }}">{{ $jurnal->$key }}</textarea>
            </div>
        @endforeach

        <div class="form-group">
            <label>Informasi Tambahan</label>
            <textarea name="info_tambahan" required>{{ $jurnal->info_tambahan }}</textarea>
        </div>

        <div class="form-group">
            <label>Upload Foto/File Baru (boleh lebih dari 1)</label>
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
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');
    const form = document.getElementById('jurnalForm');
    const lokasiSelect = document.getElementById('lokasiSelect');
    const shiftSelect = document.getElementById('shiftSelect');

    let newFiles = [];         // Untuk file baru
    let filesToDelete = [];    // Untuk file yang ingin dihapus

    // A. Hapus file lama dari tampilan dan simpan ID-nya
    fileList.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-existing')) {
            const li = e.target.closest('li');
            const fileId = e.target.dataset.id;
            filesToDelete.push(fileId);
            li.remove();
        }
    });

    // B. Tambah file baru
    fileInput.addEventListener('change', function () {
        const files = Array.from(this.files);
        newFiles = newFiles.concat(files);
        renderFileList();
        this.value = ''; // reset input
    });

    function renderFileList() {
        // Ambil ulang file lama yang belum dihapus
        const existingLis = document.querySelectorAll('.existing-file');
        fileList.innerHTML = '';
        existingLis.forEach(el => fileList.appendChild(el));

        newFiles.forEach((file, index) => {
            const li = document.createElement('li');
            li.innerHTML = `
                <span>${file.name}</span>
                <button type="button" class="remove-btn" onclick="removeNewFile(${index})">x</button>
            `;
            fileList.appendChild(li);
        });
    }

    // Hapus file baru dari daftar
    window.removeNewFile = function (index) {
        newFiles.splice(index, 1);
        renderFileList();
    };

    // C. Auto-load shift saat lokasi diganti
    lokasiSelect.addEventListener('change', function () {
        const lokasiId = this.value;
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
        }
    });

    // D. Saat submit → tambahkan input untuk file & file yang dihapus, lalu submit form biasa
    form.addEventListener('submit', function (e) {
        // Buat input file hidden untuk file baru
        const uploadContainer = document.createElement('div');
        uploadContainer.style.display = 'none';

        const uploadsInput = document.createElement('input');
        uploadsInput.type = 'file';
        uploadsInput.name = 'uploads[]';
        uploadsInput.multiple = true;

        const dataTransfer = new DataTransfer();
        newFiles.forEach(file => dataTransfer.items.add(file));
        uploadsInput.files = dataTransfer.files;

        uploadContainer.appendChild(uploadsInput);
        form.appendChild(uploadContainer);

        // Tambahkan hidden input untuk setiap file yang ingin dihapus
        filesToDelete.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_existing[]';
            input.value = id;
            form.appendChild(input);
        });

        // Lanjutkan submit form normal (biarkan Laravel handle PUT)
    });
});
</script>



@endsection