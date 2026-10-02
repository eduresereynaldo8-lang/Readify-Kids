@props(['student', 'size' => 40])
@php
    $photoUrl = $student->profile_picture_url;
    $avatarInitials = mb_strtoupper(mb_substr($student->firstname ?? '', 0, 1).mb_substr($student->lastname ?? '', 0, 1)) ?: 'S';
@endphp
<span {{ $attributes->class('profile-avatar') }} style="--avatar-size: {{ (int) $size }}px" role="img" aria-label="{{ $student->firstname }} {{ $student->lastname }} profile picture">
    <span aria-hidden="true">{{ $avatarInitials }}</span>
    @if($photoUrl)
        <img src="{{ $photoUrl }}" alt="" data-profile-photo>
    @endif
</span>
