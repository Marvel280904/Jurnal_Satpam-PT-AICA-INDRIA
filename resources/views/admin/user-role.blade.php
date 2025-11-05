@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/user_role.css') }}">
@endpush

@section('content')

    {{-- Konten User & Role Management untuk Admin --}}
@section('title', 'User & Role Management')
<div class="user-role-container">
    <div class="user-role-header">
        <h1>User & Role Management</h1>

        @if (session('success'))
            <div id="flashToast" class="flash-toast {{ session('flash_type', 'success') }}">
                <span class="flash-dot"></span>
                <span class="flash-text">{{ session('success') }}</span>
                <button class="flash-close"
                    onclick="document.getElementById('flashToast').classList.add('hide')">&times;</button>
            </div>
        @endif
    </div>

    <div class="user-role-controls">
        <div class="search-boxx">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInputt" placeholder="Search">
        </div>
        <button class="add-btn" onclick="togglePopup('popup-add-user')">
            <i class="bi bi-person-fill-add"></i> Add User
        </button>
    </div>

    <div class="data-container">
        @if ($users->isEmpty())
            <p class="empty-text">Tidak ada user.</p>
        @else
            <div class="user-table-wrapper">
                <table class="user-table" id="userTable">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $index => $user)
                            <tr class="{{ $index % 2 === 0 ? 'row-white' : 'row-grey' }}">
                                <td>{{ $user->nama }}</td>
                                <td>{{ $user->username }}</td>
                                <td>{{ $user->role }}</td>
                                <td>
                                    <button class="icon-btn"
                                        onclick="openEditUser({{ $user->id }}, '{{ e($user->nama) }}', '{{ e($user->username) }}', '{{ $user->role }}')">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <form method="POST" action="{{ route('user.destroy', $user->id) }}"
                                        style="display:inline;" class="delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="icon-btn delete-user-btn"
                                            data-user-name="{{ e($user->nama) }}">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div id="confirmDeleteModal" class="modal-overlay" style="display: none;">
                    <div class="modal-content">
                        <p id="confirmMessage">Yakin ingin menghapus jurnal ini?</p>
                        <div class="modal-actions">
                            <button id="cancelDeleteBtn" class="btn-cancel">Tidak</button>
                            <button id="confirmDeleteBtn" class="btn-confirm-delete">Ya, Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Add User Popup -->
<div class="popup" id="popup-add-user">
    <div class="popup-content">
        <span class="close-btn" onclick="togglePopup('popup-add-user')">&times;</span>
        <h3>Tambah User</h3>
        <form id="addUserForm" action="{{ route('user.store') }}" method="POST">
            @csrf

            <div id="addUserErrors" class="form-errors hide" style="display:none;"></div>

            <label>Nama User <i class="bi bi-asterisk"></i> </label>
            <input type="text" name="nama" required placeholder="Wajib diisi!">
            <label>Username <i class="bi bi-asterisk"></i> </label>
            <input type="text" name="username" required placeholder="Wajib diisi!" autocomplete="off"
                autocapitalize="off" autocorrect="off" spellcheck="false">
            <label>Password <i class="bi bi-asterisk"></i> </label>
            <input type="password" name="password" required placeholder="Wajib diisi!" autocomplete="off"
                autocapitalize="off" autocorrect="off" spellcheck="false">
            <label>Role <i class="bi bi-asterisk"></i> </label>
            <select name="role" required>
                <option value="" disabled selected>-- Wajib pilih role! --</option>
                <option value="Admin">Admin</option>
                <option value="Satpam">Satpam</option>
                <option value="Kepala Satpam">Kepala Satpam</option>
            </select>
            <button type="submit">Submit</button>
        </form>
    </div>
</div>

<!-- Edit User Popup -->
<div class="popup" id="popup-edit-user">
    <div class="popup-content">
        <span class="close-btn" onclick="togglePopup('popup-edit-user')">&times;</span>
        <h3>Edit User</h3>

        <form id="editUserForm" method="POST">
            @csrf
            @method('PUT')

            <div id="editUserErrors" class="form-errors hide" style="display:none;"></div>

            <label>Nama User <i class="bi bi-asterisk"></i> </label>
            <input type="text" name="nama" id="editNama" required>

            <label>Username <i class="bi bi-asterisk"></i> </label>
            <input type="text" name="username" id="editUsername" required autocomplete="off" autocapitalize="off"
                autocorrect="off" spellcheck="false">

            <label>New Password <i class="bi bi-asterisk"></i> </label>
            <input type="password" name="password" id="editPassword" placeholder="Isi jika ingin ubah password"
                autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">

            <label>Role <i class="bi bi-asterisk"></i> </label>
            <select name="role" id="editRole" required></select>

            <button type="submit">Save</button>
        </form>
    </div>
</div>

<script>
    function togglePopup(id) {
        document.getElementById(id).classList.toggle('show');
    }

    function openEditUser(id, nama, username, role) {
        const form = document.getElementById('editUserForm');
        form.action = `/user-role/${id}`;

        // isi field
        document.getElementById('editNama').value = nama;
        document.getElementById('editUsername').value = username;
        document.getElementById('editPassword').value = '';

        // isi select role sesuai kondisi
        const roleSelect = document.getElementById('editRole');
        roleSelect.innerHTML = ''; // reset isi option

        const addOpt = (val, text) => {
            const opt = document.createElement('option');
            opt.value = val;
            opt.textContent = text;
            if (role === val) opt.selected = true;
            roleSelect.appendChild(opt);
        };

        if (role === 'Admin') {
            addOpt('Admin', 'Admin');
            addOpt('Kepala Satpam', 'Kepala Satpam');
            addOpt('Satpam', 'Satpam');
        } else {
            addOpt('Kepala Satpam', 'Kepala Satpam');
            addOpt('Satpam', 'Satpam');
        }

        togglePopup('popup-edit-user');
    }

    document.getElementById('searchInputt').addEventListener('input', function() {
        const value = this.value.toLowerCase();
        const rows = document.querySelectorAll('#userTable tbody tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        // --- Logika untuk form Tambah/Edit User ---
        const addUserForm = document.getElementById('addUserForm');
        const editUserForm = document.getElementById('editUserForm');
        const handleFormSubmit = async (form, errorContainerId) => {
            const errorContainer = document.getElementById(errorContainerId);
            let errorTimer;
            if (errorContainer) {
                errorContainer.classList.remove('show', 'hide');
                errorContainer.style.display = 'none';
                errorContainer.innerHTML = '';
            }
            clearTimeout(errorTimer);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalBtnText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = 'Menyimpan...';
            try {
                const formData = new FormData(form);
                const spoof = form.querySelector('input[name="_method"]');
                if (spoof) formData.set('_method', spoof.value);
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': formData.get('_token'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                const data = await response.json();
                if (!response.ok) {
                    if (data && data.errors) {
                        let errorHtml = '<ul>';
                        Object.values(data.errors).forEach(msgs => {
                            msgs.forEach(msg => errorHtml += `<li>${msg}</li>`);
                        });
                        errorHtml += '</ul>';
                        errorContainer.innerHTML = errorHtml;
                        errorContainer.classList.add('show');
                        errorTimer = setTimeout(() => {
                            errorContainer.classList.remove('show');
                            errorContainer.classList.add('hide');
                        }, 5000);
                    }
                    return;
                }
                if (data.success) {
                    window.location.href = data.redirect_url || window.location.href;
                }
            } catch (err) {
                console.error(err);
                errorContainer.innerHTML = '<ul><li>Terjadi kesalahan jaringan.</li></ul>';
                errorContainer.style.display = 'block';
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalBtnText;
            }
        };

        if (addUserForm) {
            addUserForm.addEventListener('submit', (e) => {
                e.preventDefault();
                handleFormSubmit(addUserForm, 'addUserErrors');
            });
        }
        if (editUserForm) {
            editUserForm.addEventListener('submit', (e) => {
                e.preventDefault();
                handleFormSubmit(editUserForm, 'editUserErrors');
            });
        }

        // --- Logika untuk Popup Konfirmasi Hapus User ---
        const deleteModal = document.getElementById('confirmDeleteModal');
        if (deleteModal) {
            const modalContent = deleteModal.querySelector('.modal-content');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const confirmMessage = document.getElementById('confirmMessage');
            const deleteUserButtons = document.querySelectorAll('.delete-user-btn');

            let userFormToSubmit = null;

            function showDeleteModal() {
                deleteModal.style.display = 'flex';
                modalContent.classList.remove('hide');
                modalContent.classList.add('show');
            }

            function hideDeleteModal() {
                modalContent.classList.remove('show');
                modalContent.classList.add('hide');
                modalContent.addEventListener('animationend', function handler() {
                    deleteModal.style.display = 'none';
                    userFormToSubmit = null;
                    modalContent.removeEventListener('animationend', handler);
                }, {
                    once: true
                });
            }

            deleteUserButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    userFormToSubmit = this.closest('form');
                    const userName = this.getAttribute('data-user-name');
                    confirmMessage.textContent = `Yakin ingin menghapus user "${userName}"?`;
                    showDeleteModal();
                });
            });

            cancelDeleteBtn.addEventListener('click', hideDeleteModal);

            confirmDeleteBtn.addEventListener('click', () => {
                confirmDeleteBtn.disabled = true;
                confirmDeleteBtn.innerHTML = 'Deleting...';

                if (userFormToSubmit) {
                    userFormToSubmit.submit();
                } else {
                    hideDeleteModal();
                }
            });

            deleteModal.addEventListener('click', (event) => {
                if (event.target === deleteModal) {
                    hideDeleteModal();
                }
            });
        }

        // --- Logika untuk Toast Notifikasi (HANYA ADA SATU) ---
        const toast = document.getElementById('flashToast');
        if (toast) {
            setTimeout(() => toast.classList.add('hide'), 3500);
        }
    });
</script>

@endsection
