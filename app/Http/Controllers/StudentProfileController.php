<?php

namespace App\Http\Controllers;

use App\Services\StudentProfilePhoto;
use Illuminate\Http\Request;

class StudentProfileController extends ProfileController
{
    protected string $role = 'student';

    public function photo(Request $request, StudentProfilePhoto $photos)
    {
        $student = $this->account($request)->student()->firstOrFail();
        $request->validateWithBag('photo', [
            'profile_picture' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp', 'dimensions:min_width=1,min_height=1', 'max:2048'],
        ], [
            'profile_picture.image' => 'Profile picture must be JPG, PNG, or WebP.',
            'profile_picture.dimensions' => 'Profile picture must contain a valid JPG, PNG, or WebP image.',
            'profile_picture.mimes' => 'Profile picture must be JPG, PNG, or WebP.',
            'profile_picture.extensions' => 'Profile picture must be JPG, PNG, or WebP.',
            'profile_picture.max' => 'Profile picture must not exceed 2 MB.',
        ]);
        $photos->replace($student, $request->file('profile_picture'));

        return redirect()->route('student.profile.show')->with('success', 'Profile picture updated successfully.');
    }

    public function destroyPhoto(Request $request, StudentProfilePhoto $photos)
    {
        $student = $this->account($request)->student()->firstOrFail();
        $photos->remove($student);

        return redirect()->route('student.profile.show')->with('success', 'Profile picture removed.');
    }
}
