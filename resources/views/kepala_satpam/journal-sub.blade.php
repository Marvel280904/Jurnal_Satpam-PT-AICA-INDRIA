@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kepala_satpam/journal-submission.css') }}">
@endpush

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
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

        <div id="errorMessage" class="flash-message error" style="display: none;"></div>

        <form id="jurnalForm" enctype="multipart/form-data">
            @csrf
            <input name="lokasijaga" type="hidden">
            <input name="shiftjaga" type="hidden">
            <input name="tanggaljaga" type="hidden">
            <input name="nextshift" type="hidden">


            <div class="form-group-row">
                <div>
                    <label>Lokasi <i class="bi bi-asterisk"></i></label>
                    <select name="lokasi_id" id="lokasiSelect" required>
                        <option value="" disabled selected>-- Pilih Lokasi --</option>
                        @foreach($lokasis as $lokasi)
                            <option value="{{ $lokasi->id }}">{{ $lokasi->nama_lokasi }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label>Shift <i class="bi bi-asterisk"></i></label>
                    <select name="shift_id" id="shiftSelect" required>
                        <option value="" disabled selected>-- Pilih Shift --</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->nama_shift }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label>Next Shift <i class="bi bi-asterisk"></i> </label>
                    <select name="next_shift_user_id" id="nextShiftSelect" required>
                        <option value="" disabled selected>-- Pilih Grup --</option>
                        {{-- Loop semua satpam yang dikirim dari controller --}}
                        @foreach($satpams as $satpam)
                            <option value="{{ $satpam->id }}">{{ $satpam->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label>Tanggal <i class="bi bi-asterisk"></i></label>
                    {{-- Selalu menampilkan tanggal hari ini sebagai default --}}
                    <input type="date" name="tanggal" value="{{ \Carbon\Carbon::today()->toDateString() }}" required>
                </div>   
            </div>

            <div class="form-group">
                <label>Laporan Kegiatan <i class="bi bi-asterisk"></i> </label>
                <textarea name="laporan_kegiatan" required placeholder="Wajib diisi">1.
2.
3.
4.
5.
6.
7.
8.</textarea>
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
                        <label><input type="radio" name="is_{{ $key }}" value="1" required> Yes</label>
                        <label><input type="radio" name="is_{{ $key }}" value="0" required> No</label>
                    </div>
                    <textarea name="{{ $key }}" placeholder="Keterangan (wajib jika Yes)"></textarea>
                </div>
            @endforeach

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
                        <label><input type="radio" name="is_{{ $key }}" value="1"> Masuk</label>
                        <label><input type="radio" name="is_{{ $key }}" value="0"> Keluar</label>
                    </div>
                    <textarea name="{{ $key }}" placeholder="Keterangan (wajib jika Masuk/Keluar)"></textarea>
                </div>
            @endforeach

            <div class="form-group">
                <label>Informasi Tambahan</label>
                <textarea name="info_tambahan" placeholder="Tidak wajib diisi"></textarea>
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
            const errorBox = document.getElementById('errorMessage');

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

            // realtime cek radio yes/no dan textarea 
            @php
                $itemsYesNo = [
                    'kejadian_temuan' => 'Laporan Kejadian/Temuan',
                    'lembur' => 'Lembur',
                    'proyek_vendor' => 'Proyek/Vendor',
                ];
            @endphp
            @foreach($itemsYesNo as $key => $label)
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

            // realtime cek radio masuk/keluar dan textarea 
            @php
                $itemsMasukKeluar = [
                    'barang_keluar' => 'Barang Inventaris',
                    'kendaraan_dinas_keluar' => 'Kendaraan Dinas',
                ];
            @endphp
            @foreach($itemsMasukKeluar as $key => $label)
                (function(){
                    const masuk  = document.querySelector('input[name="is_{{ $key }}"][value="1"]');
                    const keluar = document.querySelector('input[name="is_{{ $key }}"][value="0"]');
                    const ta     = document.querySelector('textarea[name="{{ $key }}"]');
                    if (!masuk || !keluar || !ta) return;

                    function sync_masuk_keluar() {
                        // Jika 'Masuk' ATAU 'Keluar' dipilih -> textarea menjadi wajib
                        if (masuk.checked || keluar.checked) {
                            ta.required = true;
                            if (ta.value.trim() === '') {
                                ta.setCustomValidity('Keterangan wajib diisi jika memilih Masuk atau Keluar.');
                            } else {
                                ta.setCustomValidity('');
                            }
                        } else {
                            // Jika tidak ada yang dipilih -> textarea tidak wajib
                            ta.required = false;
                            ta.setCustomValidity('');
                        }
                    }

                    sync_masuk_keluar(); // Jalankan saat halaman dimuat
                    masuk.addEventListener('change', sync_masuk_keluar);
                    keluar.addEventListener('change', sync_masuk_keluar);
                    ta.addEventListener('input', sync_masuk_keluar);
                })();
            @endforeach

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const submitButton = form.querySelector('button[type="submit"]');
                const originalBtnText = submitButton.innerHTML;
                
                // Sembunyikan pesan error lama setiap kali submit baru
                if(errorBox) errorBox.style.display = 'none';

                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                submitButton.disabled = true;
                submitButton.innerHTML = 'Menyimpan...';

                const formData = new FormData(form);
                filesArray.forEach((file) => {
                    formData.append('uploads[]', file);
                });

                fetch("{{ route('jurnal.store') }}", { // Pastikan route ini benar
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(async (res) => {
                    if (!res.ok) {
                        if (res.status === 422) {
                            const errorData = await res.json();
                            throw new Error(errorData.message);
                        }
                        throw new Error('Terjadi kesalahan pada server.');
                    }
                    return res.json();
                })
                .then(data => {
                    // ===============================================
                    // KASUS SUKSES: REDIRECT KE HALAMAN LOG HISTORY
                    // ===============================================
                    if (data.success && data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        // Fallback jika redirect_url tidak ada
                        alert("Jurnal berhasil disimpan, tetapi gagal redirect.");
                    }
                })
                .catch(err => {
                    // ==============================================================
                    // KASUS GAGAL (DUPLIKAT): TAMPILKAN PESAN DI KOTAK ERROR MERAH
                    // ==============================================================
                    if (errorBox) {
                        errorBox.textContent = err.message;
                        errorBox.style.display = 'block';
                        
                        // Scroll ke atas agar user melihat pesan
                        const anchor = document.getElementById('top-anchor');
                        if (anchor) anchor.scrollIntoView({ behavior: 'smooth' });

                        // Sembunyikan pesan error setelah beberapa detik
                        setTimeout(() => {
                            errorBox.style.display = 'none';
                        }, 5000);
                    } else {
                        alert(err.message); // Fallback jika div error tidak ada
                    }
                    console.error(err);
                })
                .finally(() => {
                    // Aktifkan kembali tombol setelah selesai
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalBtnText;
                });
            });
        });
    </script>
@endsection
