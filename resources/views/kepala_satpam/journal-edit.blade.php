@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kepala_satpam/journal-edit.css') }}">
@endpush

@section('content')
    @section('title', 'Edit Journal Submission')
    <div id="top-anchor" class="journal-container">
        <h1>Edit Journal Submission</h1>

        <div id="errorMessage" class="flash-message error" style="display: none;"></div>

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
                    <label>Next Shift <i class="bi bi-asterisk"></i> </label>
                    <select name="next_shift_user_id" id="nextShiftSelect" required>
                        <option value="" disabled selected>-- Pilih Grup --</option>
                        {{-- Loop semua satpam yang dikirim dari controller --}}
                        @foreach($satpams as $satpam)
                            <option value="{{ $satpam->id }}" {{ $jurnal->next_shift_user_id == $satpam->id ? 'selected' : '' }}>
                                {{ $satpam->nama }}
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
                <label>Laporan Kegiatan <i class="bi bi-asterisk"></i></label>
                <textarea name="laporan_kegiatan" placeholder="Wajib diisi" required>{{ $jurnal->laporan_kegiatan }}</textarea>
            </div>

            @php
                $itemsYesNo = [
                    'kejadian_temuan' => 'Laporan Kejadian/Temuan',
                    'lembur' => 'Lembur',
                    'proyek_vendor' => 'Proyek/Vendor',
                ];
            @endphp

            @foreach($itemsYesNo as $key => $label)
                <div class="form-group">
                    <label>{{ $label }} <i class="bi bi-asterisk"></i></label>
                    <div class="radio-group">
                        <label><input type="radio" name="is_{{ $key }}" value="1" {{ $jurnal->{"is_{$key}"} == 1 ? 'checked' : '' }} required> Yes</label>
                        <label><input type="radio" name="is_{{ $key }}" value="0" {{ $jurnal->{"is_{$key}"} == 0 ? 'checked' : '' }} required> No</label>
                    </div>
                    <textarea name="{{ $key }}" placeholder="Keterangan (wajib jika Yes)">{{ $jurnal->$key }}</textarea>
                </div>
            @endforeach

            {{-- Grup Masuk/Keluar --}}
            @php
                $itemsMasukKeluar = [
                    'barang_keluar' => 'Barang Inventaris',
                    'kendaraan_dinas_keluar' => 'Kendaraan Dinas',
                ];
            @endphp

            @foreach($itemsMasukKeluar as $key => $label)
                <div class="form-group">
                    <label>{{ $label }}</label>
                    <div class="radio-group">
                        <label><input type="radio" name="is_{{ $key }}" value="1" {{ $jurnal->{"is_{$key}"} === 1 ? 'checked' : '' }}> Masuk</label>
                        <label><input type="radio" name="is_{{ $key }}" value="0" {{ $jurnal->{"is_{$key}"} === 0 ? 'checked' : '' }}> Keluar</label>
                    </div>
                    <textarea name="{{ $key }}" placeholder="Keterangan (wajib jika Masuk/Keluar)">{{ $jurnal->$key }}</textarea>
                </div>
            @endforeach

            <div class="form-group">
                <label>Informasi Tambahan</label>
                <textarea name="info_tambahan" placeholder="Tidak wajib diisi">{{ $jurnal->info_tambahan }}</textarea>
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
            @foreach($itemsYesNo as $key => $label)
                (function(){
                    const yes = document.querySelector('input[name="is_{{ $key }}"][value="1"]');
                    const no  = document.querySelector('input[name="is_{{ $key }}"][value="0"]');
                    const ta  = document.querySelector('textarea[name="{{ $key }}"]');
                    if (!yes || !no || !ta) return;

                    function sync() {
                        if (yes.checked) {
                            ta.required = true;
                            if (ta.value.trim() === '') ta.setCustomValidity('Keterangan wajib diisi jika memilih Yes.');
                            else ta.setCustomValidity('');
                        } else {
                            ta.required = false;
                            ta.setCustomValidity('');
                        }
                    }
                    sync();
                    yes.addEventListener('change', sync);
                    no.addEventListener('change', sync);
                    ta.addEventListener('input', sync);
                })();
            @endforeach

            // --- Logika validasi untuk grup "Masuk/Keluar" ---
            @foreach($itemsMasukKeluar as $key => $label)
                (function(){
                    const masuk  = document.querySelector('input[name="is_{{ $key }}"][value="1"]');
                    const keluar = document.querySelector('input[name="is_{{ $key }}"][value="0"]');
                    const ta     = document.querySelector('textarea[name="{{ $key }}"]');
                    if (!masuk || !keluar || !ta) return;

                    function sync() {
                        if (masuk.checked || keluar.checked) {
                            ta.required = true;
                            if (ta.value.trim() === '') ta.setCustomValidity('Keterangan wajib diisi jika memilih Masuk atau Keluar.');
                            else ta.setCustomValidity('');
                        } else {
                            ta.required = false;
                            ta.setCustomValidity('');
                        }
                    }
                    sync();
                    masuk.addEventListener('change', sync);
                    keluar.addEventListener('change', sync);
                    ta.addEventListener('input', sync);
                })();
            @endforeach


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