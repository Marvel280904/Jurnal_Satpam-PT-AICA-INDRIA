{{-- resources/views/jadwal/create.blade.php --}}
<link rel="stylesheet" href="{{ asset('css/jadwal.css') }}">

<div class="jadwal-container">
    <h2>Tambah Jadwal</h2>

    <div class="date-range">
        <label>Start Date</label>
        <input type="date" name="start_date" id="jadwalStartDate" required>
        <label>End Date</label>
        <input type="date" name="end_date" id="jadwalEndDate" required>
    </div>

    <div class="jadwal-flex">
        <div class="list-satpam">
            <h3>List Satpam</h3>
            <div class="search-box3">
                <i class="bi bi-search"></i>
                <input type="text" id="jadwalSearchSatpam" placeholder="Search">
            </div>
            <div class="satpam-list" id="jadwalSatpamList">
                @foreach($satpams as $satpam)
                    <div class="satpam-item" data-user="{{ $satpam->id }}">
                        <span>{{ $satpam->nama }}</span>
                        <i class="bi bi-person-fill-check add-icon" title="Add"></i>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="lokasi-container">
            <form id="jadwalForm">
                @csrf
                @foreach($lokasis as $lokasi)
                    <div class="lokasi-block">
                        <h3><i class="bi bi-geo-fill"></i> {{ $lokasi->nama_lokasi }}</h3>
                        <div class="shift-row">
                            @foreach(['Shift Pagi', 'Shift Siang', 'Shift Malam'] as $shift)
                                <div class="shift-block" data-lokasi="{{ $lokasi->id }}" data-shift="{{ $shift }}">
                                    <label><i class="bi bi-calendar-check-fill"></i> {{ $shift }}</label>
                                    <div class="assigned-list"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <hr>
                @endforeach

                <input type="hidden" name="assign" id="jadwalAssignData">

                <div class="form-footer">
                    <button type="submit" class="btn-submit">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Popup --}}
<div class="popup-add" id="jadwalPopupAdd" style="display:none;">
    <div class="popup-content">
        <h3>Add to</h3>
        <label>Lokasi</label>
        <select id="jadwalPopupLokasi">
            @foreach($lokasis as $lokasi)
                <option value="{{ $lokasi->id }}">{{ $lokasi->nama_lokasi }}</option>
            @endforeach
        </select>
        <label>Shift</label>
        <select id="jadwalPopupShift">
            <option value="Shift Pagi">Shift Pagi</option>
            <option value="Shift Siang">Shift Siang</option>
            <option value="Shift Malam">Shift Malam</option>
        </select>
        <div class="popup-buttons">
            <button type="button" id="jadwalPopupCancel">Cancel</button>
            <button type="button" id="jadwalPopupAddBtn">Add</button>
        </div>
    </div>
</div>
