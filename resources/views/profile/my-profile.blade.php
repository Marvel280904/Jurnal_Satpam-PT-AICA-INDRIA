@extends('layouts.app')

@section('title', 'My Profile')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endpush

@section('content')
<div class="profile-container">
    <div class="profile-header">
        <i class="bi bi-person-fill"></i>
        <h1>My Profile</h1>
    </div>
    <div class="profile-content">
        <div class="profile-left">
            <img src="{{ Auth::check() && Auth::user()->foto ? asset('storage/' . Auth::user()->foto) : asset('images/profile.jpeg') }}" alt="Profile" class="profile-photo">
            <form id="photoForm" action="{{ route('profile.updatePhoto') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="foto" id="fotoInput" accept="image/*" style="display: none;" onchange="document.getElementById('photoForm').submit();">
                <button type="button" class="edit-profile-btn" onclick="document.getElementById('fotoInput').click()">Edit Profile</button>
            </form>
        </div>

        <div class="profile-right">
            @if (session('success'))
                <div class="flash-message success">
                    {{ session('success') }}
                </div>
            @endif


            <form action="{{ route('profile.updatePassword') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" value="{{ Auth::user()->nama }}" disabled>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <input type="text" value="{{ Auth::user()->role }}" disabled>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="{{ Auth::user()->username }}" disabled>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="New Password" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
                </div>
                <button type="submit" class="save-btn">Save</button>
            </form>
        </div>
    </div>
</div>
@endsection
