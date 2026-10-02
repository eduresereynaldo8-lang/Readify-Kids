@extends('layouts.'.$profileRole)
@section('title', 'My Profile')
@section('page-title', 'My Profile')
@section('page-sub', 'Your information and account settings.')

@section('content')
<div class="profile-page">
    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <section class="dash-card profile-heading mb-4" aria-labelledby="profile-heading">
        @if($profileRole === 'student')
            <x-student-avatar :student="$person" :size="100" />
        @else
            <span class="profile-avatar" style="--avatar-size: 80px" aria-hidden="true">{{ $profileRole === 'admin' ? mb_strtoupper(mb_substr($account->username, 0, 1)) : mb_strtoupper(mb_substr($person->firstname, 0, 1).mb_substr($person->lastname, 0, 1)) }}</span>
        @endif
        <div>
            <h2 id="profile-heading">{{ $profileRole === 'admin' ? 'Administrator Profile' : $person->firstname.' '.$person->lastname }}</h2>
            <p class="text-muted mb-2">{{ ucfirst($profileRole) }} account · {{ $account->username }}</p>
            <div class="d-flex flex-wrap gap-2">
                @if($profileRole !== 'student')
                    <a href="#edit-profile" class="btn btn-sm btn-primary"><i class="ti ti-edit" aria-hidden="true"></i> Edit Profile</a>
                @else
                    <a href="#profile-photo" class="btn btn-sm btn-primary"><i class="ti ti-camera" aria-hidden="true"></i> Change Photo</a>
                @endif
                <a href="#account-security" class="btn btn-sm btn-outline-primary">Change Password</a>
            </div>
        </div>
    </section>

    <section class="dash-card mb-4" aria-labelledby="account-information">
        <h2 class="dash-card-title" id="account-information">{{ $profileRole === 'student' ? 'Student Information' : 'Account Information' }}</h2>
        <dl class="profile-details">
            @if($profileRole !== 'admin')
                <div><dt>Full Name</dt><dd>{{ $person->firstname }} {{ $person->lastname }}</dd></div>
            @endif
            <div><dt>Username</dt><dd>{{ $account->username }}</dd></div>
            @if($profileRole !== 'student')
                <div><dt>Email</dt><dd>{{ $account->email ?: 'Not provided' }}</dd></div>
            @endif
            @if($profileRole === 'teacher')
                <div><dt>School</dt><dd>{{ $person->school_name ?: 'Not provided' }}</dd></div>
            @endif
            @if($profileRole === 'student')
                <div><dt>LRN No.</dt><dd>{{ $person->lrn_no ?: 'Not provided' }}</dd></div>
                <div><dt>Birthday</dt><dd>{{ $person->birthday?->format('F j, Y') ?? 'Not provided' }}</dd></div>
                <div><dt>Age</dt><dd>{{ $person->age ?? 'Not provided' }}</dd></div>
                <div><dt>Gender</dt><dd>{{ $person->gender ?: 'Not provided' }}</dd></div>
                <div><dt>Section</dt><dd>{{ $person->section ?: 'Not provided' }}</dd></div>
                <div><dt>Current Level</dt><dd>Level {{ $person->current_level }}</dd></div>
            @endif
            <div><dt>Role</dt><dd>{{ ucfirst($account->role) }}</dd></div>
            <div><dt>Date Joined</dt><dd>{{ $account->created_at?->format('F j, Y') ?? 'Not available' }}</dd></div>
        </dl>
        @if($profileRole === 'student')
            <p class="text-muted small mb-0">Your school manages your name, username, and school records. Ask your teacher if they need correcting.</p>
        @endif
    </section>

    @if($profileRole !== 'student')
    <section class="dash-card mb-4" id="edit-profile" aria-labelledby="edit-profile-heading">
        <h2 class="dash-card-title" id="edit-profile-heading">Edit Profile</h2>
        <form method="POST" action="{{ route($profileRole.'.profile.update') }}">
            @csrf @method('PUT')
            <div class="row g-3">
                @php
                    $editableFields = $profileRole === 'teacher'
                        ? ['firstname' => ['First Name', 100], 'lastname' => ['Last Name', 100], 'school_name' => ['School', 255], 'username' => ['Username', 100], 'email' => ['Email', 150]]
                        : ['username' => ['Username', 100], 'email' => ['Email', 150]];
                @endphp
                @foreach($editableFields as $field => [$label, $length])
                <div class="col-md-6">
                    <label class="form-label" for="profile-{{ $field }}">{{ $label }}{{ $field === 'email' ? ' (optional)' : '' }}</label>
                    <input class="form-control @error($field, 'profile') is-invalid @enderror" id="profile-{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}"
                        maxlength="{{ $length }}" value="{{ old($field, in_array($field, ['username', 'email']) ? $account->$field : $person->$field) }}"
                        @if($field !== 'email') required @endif
                        @error($field, 'profile') aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror>
                    @error($field, 'profile')<div class="invalid-feedback" id="error-{{ $field }}">{{ $message }}</div>@enderror
                </div>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save Profile</button>
        </form>
    </section>
    @else
    <section class="dash-card mb-4" id="profile-photo" aria-labelledby="photo-heading">
        <h2 class="dash-card-title" id="photo-heading">Profile Picture</h2>
        <p class="text-muted small" id="photo-help">Choose a JPG, PNG, or WebP image, up to 2 MB.</p>
        <form method="POST" action="{{ route('student.profile.photo') }}" enctype="multipart/form-data">
            @csrf
            <label class="form-label" for="profile-picture">Choose a photo</label>
            <input class="form-control @error('profile_picture', 'photo') is-invalid @enderror" id="profile-picture" name="profile_picture" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required
                aria-describedby="photo-help @error('profile_picture', 'photo') photo-error @enderror"
                @error('profile_picture', 'photo') aria-invalid="true" @enderror>
            @error('profile_picture', 'photo')<div class="invalid-feedback" id="photo-error">{{ $message }}</div>@enderror
            <button type="submit" class="btn btn-primary mt-3">Save Photo</button>
        </form>
        @if($person->profile_picture)
        <form method="POST" action="{{ route('student.profile.photo.destroy') }}" class="mt-3">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">Remove Photo</button>
        </form>
        @endif
    </section>
    @endif

    <section class="dash-card" id="account-security" aria-labelledby="security-heading">
        <h2 class="dash-card-title" id="security-heading">Account Security</h2>
        <p class="text-muted small" id="password-help">Enter your current password and choose a new password with at least 8 characters.</p>
        <form method="POST" action="{{ route($profileRole.'.profile.password') }}">
            @csrf @method('PUT')
            <div class="row g-3">
                @foreach(['current_password' => 'Current Password', 'password' => 'New Password', 'password_confirmation' => 'Confirm New Password'] as $field => $label)
                <div class="col-md-4">
                    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                    <div class="input-group">
                        <input class="form-control @error($field, 'password') is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="password" required
                            autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}"
                            @if($field !== 'current_password') minlength="8" @endif
                            aria-describedby="password-help @error($field, 'password') error-{{ $field }} @enderror"
                            @error($field, 'password') aria-invalid="true" @enderror>
                        <button class="btn btn-outline-secondary" type="button" data-password-toggle="{{ $field }}" aria-controls="{{ $field }}" aria-pressed="false" aria-label="Show {{ strtolower($label) }}"><i class="ti ti-eye" aria-hidden="true"></i></button>
                    </div>
                    @error($field, 'password')<div class="text-danger small mt-1" id="error-{{ $field }}">{{ $message }}</div>@enderror
                </div>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary mt-3">Change Password</button>
        </form>
    </section>
</div>
@endsection
