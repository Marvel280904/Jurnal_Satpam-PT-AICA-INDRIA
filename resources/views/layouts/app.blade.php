<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PT AICA INDRIA')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @stack('styles')
</head>
<body>

@php
    $role = Auth::user()->role;
@endphp

@if ($role === 'Admin')
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar collapsed">
            
            <div class="logo">
                <img src="{{ asset('images/logo.png') }}" alt="Logo">
            </div>

            <nav class="nav">
                
                    <a href="/dashboard-admin" class="{{ Request::is('dashboard-admin') ? 'active' : '' }}">
                        <i class="bi bi-grid"></i><span>Dashboard</span>
                    </a>
                    <a href="/location-shift" class="{{ Request::is('location-shift') ? 'active' : '' }}">
                        <i class="bi bi-geo-alt-fill"></i><span>Location & Shift Management</span>
                    </a>
                    <a href="/user-role" class="{{ Request::is('user-role') ? 'active' : '' }}">
                        <i class="bi bi-person-fill-gear"></i><span>User & Role Management</span>
                    </a>
                    <a href="/system-logs" class="{{ Request::is('system-logs') ? 'active' : '' }}">
                        <i class="bi bi-display"></i><span>System Logs</span>
                    </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main">
            <!-- Topbar -->
            <header class="topbar">
                    <div id="searchbox" class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" id="searchInput" placeholder="Search" oninput="filterMenu()">
                        <ul id="searchDropdown" class="search-dropdown"></ul>
                    </div>
                    <div class="profile-section">
                        <img src="{{ Auth::user()->foto ? asset('storage/' . Auth::user()->foto) : asset('images/profile.jpeg') }}" alt="Profile" class="profile-pic">
                        <div class="profile-name">
                            <span>{{ Auth::user()->nama }}</span>
                            <small>{{ Auth::user()->role }}</small>
                        </div>
                        <div class="dropdown">
                            <i class="bi bi-caret-down-fill dropdown-toggle" onclick="toggleDropdown()"></i>
                            <ul class="dropdown-menu" id="dropdownMenu">
                                <li><a href="/my-profile">My Profile</a></li>
                                <li>
                                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                                </li>
                            </ul>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </div>
            </header>

            <!-- Page Specific Content -->
            <section class="content">
                @yield('content')
            </section>
        </main>
    </div>
@elseif ($role === 'Kepala Satpam')
    <div class="container1">
        <!-- Sidebar -->
        <aside class="sidebar1 collapsed">
            
            <div class="logo">
                <img src="{{ asset('images/logo.png') }}" alt="Logo">
            </div>

            <nav class="nav">
                <a href="/dashboard-kepala" class="{{ Request::is('dashboard-kepala') ? 'active' : '' }}">
                    <i class="bi bi-grid"></i><span>Dashboard</span>
                </a>
                <a href="/journal-submission" class="{{ Request::is('journal-submission') ? 'active' : '' }}">
                    <i class="bi bi-journal-check"></i><span>Journal Submission</span>
                </a>
                <a href="/log-history" class="{{ Request::is('log-history') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i><span>Log History</span>
                </a>
                <a href="/guard-data" class="{{ Request::is('guard-data') ? 'active' : '' }}">
                    <i class="bi bi-person-fill-gear"></i></i><span>Guard Data</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main">
            <!-- Topbar -->
            <header class="topbar1">
                <div id="searchbox" class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Search" oninput="filterMenu()">
                    <ul id="searchDropdown" class="search-dropdown"></ul>
                </div>

                <div class="notif">
                    <button id="notifBtn" class="notif-button">
                        <i class="bi bi-bell-fill"></i>
                        <span class="notif-badge">{{ $reminderCount ?? 0 }}</span>
                    </button>

                    <div id="notifPanel" class="notif-panel">
                        <div class="notif-head">Reminders</div>
                        <div class="notif-core">
                            @php $list = $reminders ?? []; @endphp
                            @forelse($list as $item)
                                <div class="notif-item" @if(!empty($item['url'])) data-url="{{ $item['url'] }}" @endif>
                                    <div class="notif-icon"><i class="bi {{ $item['icon'] }}"></i></div>
                                    <div class="notif-text">
                                    <div class="notif-title">{{ $item['title'] }}</div>
                                    <div class="notif-desc">{{ $item['desc'] }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="notif-empty">Tidak ada pengingat</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="profile-section">
                    <img src="{{ Auth::user()->foto ? asset('storage/' . Auth::user()->foto) : asset('images/profile.jpeg') }}" alt="Profile" class="profile-pic">
                    <div class="profile-name1">
                        <span>{{ Auth::user()->nama }}</span>
                        <small>{{ Auth::user()->role }}</small>
                    </div>
                    <div class="dropdown1">
                        <i class="bi bi-caret-down-fill dropdown-toggle" onclick="toggleDropdown()"></i>
                        <ul class="dropdown-menu" id="dropdownMenu">
                            <li><a href="/my-profile">My Profile</a></li>
                            <li>
                                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                            </li>
                        </ul>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </header>

            <!-- Page Specific Content -->
            <section class="content">
                @yield('content')
            </section>
        </main>
    </div>
@elseif ($role === 'Satpam')
    <div class="container1">
        <!-- Sidebar -->
        <aside class="sidebar1 collapsed">
            
            <div class="logo">
                <img src="{{ asset('images/logo.png') }}" alt="Logo">
            </div>

            <nav class="nav">
                <a href="/dashboard-satpam" class="{{ Request::is('dashboard-satpam') ? 'active' : '' }}">
                    <i class="bi bi-grid"></i><span>Dashboard</span>
                </a>
                <a href="/journal-submission" class="{{ Request::is('journal-submission') ? 'active' : '' }}">
                    <i class="bi bi-journal-check"></i><span>Journal Submission</span>
                </a>
                <a href="/log-history" class="{{ Request::is('log-history') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i><span>Log History</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main">
            <!-- Topbar -->
            <header class="topbar1">
                <div id="searchbox" class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Search" oninput="filterMenu()">
                    <ul id="searchDropdown" class="search-dropdown"></ul>
                </div>

                <div class="notif">
                    <button id="notifBtn" class="notif-button">
                        <i class="bi bi-bell-fill"></i>
                        <span class="notif-badge">{{ $reminderCount ?? 0 }}</span>
                    </button>

                    <div id="notifPanel" class="notif-panel">
                        <div class="notif-head">Reminders</div>
                        <div class="notif-core">
                            @php $list = $reminders ?? []; @endphp
                            @forelse($list as $item)
                                <div class="notif-item" @if(!empty($item['url'])) data-url="{{ $item['url'] }}" @endif>
                                    <div class="notif-icon"><i class="bi {{ $item['icon'] }}"></i></div>
                                    <div class="notif-text">
                                    <div class="notif-title">{{ $item['title'] }}</div>
                                    <div class="notif-desc">{{ $item['desc'] }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="notif-empty">Tidak ada pengingat</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                
                <div class="profile-section">
                    <img src="{{ Auth::user()->foto ? asset('storage/' . Auth::user()->foto) : asset('images/profile.jpeg') }}" alt="Profile" class="profile-pic">
                    <div class="profile-name1">
                        <span>{{ Auth::user()->nama }}</span>
                        <small>{{ Auth::user()->role }}</small>
                    </div>
                    <div class="dropdown1">
                        <i class="bi bi-caret-down-fill dropdown-toggle" onclick="toggleDropdown()"></i>
                        <ul class="dropdown-menu" id="dropdownMenu">
                            <li><a href="/my-profile">My Profile</a></li>
                            <li>
                                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                            </li>
                        </ul>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </header>

            <!-- Page Specific Content -->
            <section class="content">
                @yield('content')
            </section>
        </main>
    </div>
@endif

@stack('scripts')

@if ($role === 'Admin')
    <script>
        function toggleDropdown() {
            document.getElementById('dropdownMenu').classList.toggle('show');
        }

        const menuItems = [
            { name: "Dashboard", url: "/dashboard-admin" },
            { name: "Location & Shift Management", url: "/location-shift" },
            { name: "User & Role Management", url: "/user-role" },
            { name: "System Logs", url: "/system-logs" }
        ];

        function filterMenu() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toLowerCase();
            const dropdown = document.getElementById("searchDropdown");

            dropdown.innerHTML = ""; // bersihkan isi dulu

            if (filter.trim() === "") {
                dropdown.style.display = "none"; // kosong, sembunyikan
                return;
            }

            const filteredItems = menuItems.filter(item =>
                item.name.toLowerCase().includes(filter)
            );

            if (filteredItems.length === 0) {
                dropdown.style.display = "none";
                return;
            }

            filteredItems.forEach(item => {
                const li = document.createElement("li");
                li.textContent = item.name;
                li.onclick = () => window.location.href = item.url;
                dropdown.appendChild(li);
            });

            dropdown.style.display = "block"; // hanya tampil kalau ada hasil
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const searchBox = document.getElementById("searchbox");
            if (!searchBox.contains(e.target)) {
                document.getElementById("searchDropdown").style.display = "none";
            }
        });
    </script>
@elseif ($role === 'Kepala Satpam')
    <script>
        function toggleDropdown() {
            document.getElementById('dropdownMenu').classList.toggle('show');
        }

        const menuItems = [
            { name: "Dashboard", url: "/dashboard-kepala" },
            { name: "Journal Submission", url: "/journal-submission" },
            { name: "Log History", url: "/log-history" },
            { name: "Guard Data", url: "/guard-data" }
        ];

        function filterMenu() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toLowerCase();
            const dropdown = document.getElementById("searchDropdown");

            dropdown.innerHTML = ""; // bersihkan isi dulu

            if (filter.trim() === "") {
                dropdown.style.display = "none"; // kosong, sembunyikan
                return;
            }

            const filteredItems = menuItems.filter(item =>
                item.name.toLowerCase().includes(filter)
            );

            if (filteredItems.length === 0) {
                dropdown.style.display = "none";
                return;
            }

            filteredItems.forEach(item => {
                const li = document.createElement("li");
                li.textContent = item.name;
                li.onclick = () => window.location.href = item.url;
                dropdown.appendChild(li);
            });

            dropdown.style.display = "block"; // hanya tampil kalau ada hasil
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const searchBox = document.getElementById("searchbox");
            if (!searchBox.contains(e.target)) {
                document.getElementById("searchDropdown").style.display = "none";
            }
        });

        // notif reminders
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('notifBtn');
            const panel = document.getElementById('notifPanel');

            btn?.addEventListener('click', () => {
                panel?.classList.toggle('open');
            });

            document.addEventListener('click', (e) => {
                if (!panel) return;
                if (panel.contains(e.target) || btn.contains(e.target)) return;
                panel.classList.remove('open');
            });

            // Navigate when a reminder has data-url
            panel?.addEventListener('click', (e) => {
                const item = e.target.closest('.notif-item');
                if (!item) return;
                const url = item.dataset.url;
                if (url) window.location.href = url;
            });
        });
    </script>
@elseif ($role === 'Satpam')
    <script>
        function toggleDropdown() {
            document.getElementById('dropdownMenu').classList.toggle('show');
        }

        const menuItems = [
            { name: "Dashboard", url: "/dashboard-satpam" },
            { name: "Journal Submission", url: "/journal-submission" },
            { name: "Log History", url: "/log-history" }
        ];

        function filterMenu() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toLowerCase();
            const dropdown = document.getElementById("searchDropdown");

            dropdown.innerHTML = ""; // bersihkan isi dulu

            if (filter.trim() === "") {
                dropdown.style.display = "none"; // kosong, sembunyikan
                return;
            }

            const filteredItems = menuItems.filter(item =>
                item.name.toLowerCase().includes(filter)
            );

            if (filteredItems.length === 0) {
                dropdown.style.display = "none";
                return;
            }

            filteredItems.forEach(item => {
                const li = document.createElement("li");
                li.textContent = item.name;
                li.onclick = () => window.location.href = item.url;
                dropdown.appendChild(li);
            });

            dropdown.style.display = "block"; // hanya tampil kalau ada hasil
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const searchBox = document.getElementById("searchbox");
            if (!searchBox.contains(e.target)) {
                document.getElementById("searchDropdown").style.display = "none";
            }
        });

        // notif reminders
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('notifBtn');
            const panel = document.getElementById('notifPanel');

            btn?.addEventListener('click', () => {
                panel?.classList.toggle('open');
            });

            document.addEventListener('click', (e) => {
                if (!panel) return;
                if (panel.contains(e.target) || btn.contains(e.target)) return;
                panel.classList.remove('open');
            });

            // Navigate when a reminder has data-url
            panel?.addEventListener('click', (e) => {
                const item = e.target.closest('.notif-item');
                if (!item) return;
                const url = item.dataset.url;
                if (url) window.location.href = url;
            });
        });
    </script>
@endif
</body>
</html>
