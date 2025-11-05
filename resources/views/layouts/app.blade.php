<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PT AICA INDRIA')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="shortcut icon" href="{{ url('images/logo-lem-fox-bg.jpeg') }}" />
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
                    <span class="beta-mode">Beta Mode</span>
                    <div class="toggle-mode">
                        <label class="switch">
                            <input type="checkbox" class="checkbox">
                            <div class="slider"></div>
                        </label>
                    </div>
                    <div class="profile-section">
                        <img src="{{ Auth::user()->foto ? asset('storage/' . Auth::user()->foto) : asset('images/profile.jpeg') }}"
                            alt="Profile" class="profile-pic">
                        <div class="profile-name">
                            <span>{{ Auth::user()->nama }}</span>
                            <small>{{ Auth::user()->role }}</small>
                        </div>
                        <div class="dropdown">
                            <i class="bi bi-caret-down-fill dropdown-toggle" onclick="toggleDropdown()"></i>
                            <ul class="dropdown-menu" id="dropdownMenu">
                                <li><a href="/my-profile">My Profile</a></li>
                                <li>
                                    <a href="#"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                                </li>
                            </ul>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                style="display: none;">
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
    @else
        <div class="container1">
            <!-- Sidebar -->
            <aside class="sidebar1 collapsed">

                <div class="logo">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo">
                </div>

                <nav class="nav">
                    @if ($role === 'Kepala Satpam')
                        <a href="/dashboard-kepala" class="{{ Request::is('dashboard-kepala') ? 'active' : '' }}">
                            <i class="bi bi-grid"></i><span>Dashboard</span>
                        </a>
                    @elseif($role === 'Satpam')
                        <a href="/dashboard-satpam" class="{{ Request::is('dashboard-satpam') ? 'active' : '' }}">
                            <i class="bi bi-grid"></i><span>Dashboard</span>
                        </a>
                    @endif
                    <a href="/journal-submission" class="{{ Request::is('journal-submission') ? 'active' : '' }}">
                        <i class="bi bi-journal-check"></i><span>Isi Jurnal</span>
                    </a>
                    <a href="/log-history" class="{{ Request::is('log-history') ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i><span>List Jurnal</span>
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
                            <div class="notif-head">Notifikasi</div>
                            <div class="notif-core">
                                @php $list = $reminders ?? []; @endphp
                                @forelse($list as $item)
                                    <div class="notif-item"
                                        @if (!empty($item['url'])) data-url="{{ $item['url'] }}" @endif>
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
                        <img src="{{ Auth::user()->foto ? asset('storage/' . Auth::user()->foto) : asset('images/profile.jpeg') }}"
                            alt="Profile" class="profile-pic">
                        <div class="profile-name1">
                            <span>{{ Auth::user()->nama }}</span>
                            <small>{{ Auth::user()->role }}</small>
                        </div>
                        <div class="dropdown1">
                            <i class="bi bi-caret-down-fill dropdown-toggle" onclick="toggleDropdown()"></i>
                            <ul class="dropdown-menu" id="dropdownMenu">
                                <li><a href="/my-profile">My Profile</a></li>
                                <li>
                                    <a href="#"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                                </li>
                            </ul>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                style="display: none;">
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

            const menuItems = [{
                    name: "Dashboard",
                    url: "/dashboard-admin"
                },
                {
                    name: "Location & Shift Management",
                    url: "/location-shift"
                },
                {
                    name: "User & Role Management",
                    url: "/user-role"
                },
                {
                    name: "System Logs",
                    url: "/system-logs"
                }
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

            // Beta Mode Toggle Functionality
            document.addEventListener('DOMContentLoaded', function() {
                const betaToggle = document.querySelector('.checkbox');
                const betaModeText = document.querySelector('.beta-mode');

                // Fungsi untuk menampilkan flash toast
                function showFlashToast(message, type = 'success') {
                    // Hapus toast lama jika ada
                    const oldToast = document.getElementById('dynamicFlashToast');
                    if (oldToast) {
                        oldToast.remove();
                    }

                    // Buat toast baru
                    const toast = document.createElement('div');
                    toast.id = 'dynamicFlashToast';
                    toast.className = `flash-toast ${type}`;
                    toast.innerHTML = `
                    <span class="flash-dot"></span>
                    <span class="flash-text">${message}</span>
                    <button class="flash-close" onclick="this.parentElement.classList.add('hide')">&times;</button>
                `;

                    // Tambahkan ke body
                    document.body.appendChild(toast);

                    // Auto hide setelah 5 detik
                    setTimeout(() => {
                        toast.classList.add('hide');
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    }, 5000);
                }

                // Fungsi untuk update tampilan beta mode
                function updateBetaModeDisplay(isBetaMode) {
                    if (betaToggle) {
                        betaToggle.checked = isBetaMode;
                    }
                }

                // Load status beta mode dari server saat halaman dimuat
                function loadBetaModeStatus() {
                    fetch('/beta-mode/status')
                        .then(response => response.json())
                        .then(data => {
                            const isBetaMode = data.beta_mode === '1';
                            updateBetaModeDisplay(isBetaMode);
                        })
                        .catch(error => {
                            console.error('Error loading beta mode status:', error);
                        });
                }

                if (betaToggle) {
                    betaToggle.addEventListener('change', function() {
                        const isBetaMode = this.checked;

                        // Kirim request untuk toggle beta mode ke server
                        fetch('/beta-mode/toggle', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    beta_mode: isBetaMode
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update tampilan dengan nilai baru dari server
                                    updateBetaModeDisplay(data.beta_mode === '1');

                                    // Tampilkan flash toast
                                    showFlashToast(data.message, 'success');
                                } else {
                                    // Jika gagal, reset toggle ke posisi semula
                                    updateBetaModeDisplay(!isBetaMode);

                                    // Tampilkan error message
                                    showFlashToast(data.message, 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error toggling beta mode:', error);
                                // Jika error, reset toggle ke posisi semula
                                updateBetaModeDisplay(!isBetaMode);
                                showFlashToast('Terjadi error saat mengubah Beta Mode', 'error');
                            });
                    });

                    // Load status saat halaman dimuat
                    loadBetaModeStatus();
                }
            });
        </script>
    @else
        <script>
            function toggleDropdown() {
                document.getElementById('dropdownMenu').classList.toggle('show');
            }

            const userRole = @json(strtolower($role));

            const menuItems = [{
                    name: "Journal Submission",
                    url: "/journal-submission"
                },
                {
                    name: "Log History",
                    url: "/log-history"
                }
            ];

            let dashboardItem = null;
            if (userRole === 'kepala satpam') {
                dashboardItem = {
                    name: "Dashboard",
                    url: "/dashboard-kepala"
                };
            } else if (userRole === 'satpam') {
                dashboardItem = {
                    name: "Dashboard",
                    url: "/dashboard-satpam"
                };
            }

            if (dashboardItem) {
                menuItems.unshift(dashboardItem);
            }

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
