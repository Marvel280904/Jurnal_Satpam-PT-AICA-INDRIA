@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/system_logs.css') }}">
@endpush

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Konten System Log untuk Admin --}}
    @section('title', 'System Log')
    <div class="logs-container">
        <h1>System Logs</h1>

        <div class="filter-bar">
            <form method="GET" action="{{ route('system.logs') }}" class="filter-form">
                <div class="filter-date">
                    <i class="bi bi-calendar-event-fill"></i>
                    <input type="date" name="date" value="{{ request('date') }}" id="filterDate">
                </div>
                <div class="filter-search">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Search" value="{{ request('search') }}" id="filterSearch">
                </div>
            </form>
        </div>

        <div class="logs-table">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Admin</th>
                        <th>Event</th>
                        <th>Severity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $index => $log)
                        <tr class="{{ $index % 2 == 0 ? 'row-white' : 'row-grey' }}">
                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('F d, Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('H.i') }}</td>
                            <td>{{ $log->user->nama ?? '-' }}</td>
                            <td>{{ $log->description }}</td>
                            <td>
                                <span class="severity {{ strtolower($log->severity) }}">
                                    {{ ucfirst($log->severity) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-log">Tidak ada log.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


    <script>
        // script system log
        // Ketika user memilih tanggal
        document.getElementById('filterDate').addEventListener('change', function () {
            this.form.submit();
        });

        // Ketika user mengetik keyword
        document.getElementById('filterSearch').addEventListener('input', function () {
            clearTimeout(this.delay);
            this.delay = setTimeout(() => this.form.submit(), 500); // tunggu 0.5 detik lalu submit
        });
    </script>

@endsection
