<div class="sm-fields">
    <div class="sm-field">
        <label for="firstname">First Name <span aria-hidden="true">*</span></label>
        <div class="sm-input"><i class="ti ti-user" aria-hidden="true"></i><input type="text" id="firstname" name="firstname" class="form-control @error('firstname') is-invalid @enderror" value="{{ old('firstname', $student->firstname ?? '') }}" placeholder="Enter first name" maxlength="100" autocomplete="given-name" required @error('firstname') aria-invalid="true" aria-describedby="firstname-error" @enderror></div>
        @error('firstname')<small class="sm-error" id="firstname-error">{{ $message }}</small>@enderror
    </div>
    <div class="sm-field">
        <label for="lastname">Last Name <span aria-hidden="true">*</span></label>
        <div class="sm-input"><i class="ti ti-user" aria-hidden="true"></i><input type="text" id="lastname" name="lastname" class="form-control @error('lastname') is-invalid @enderror" value="{{ old('lastname', $student->lastname ?? '') }}" placeholder="Enter last name" maxlength="100" autocomplete="family-name" required @error('lastname') aria-invalid="true" aria-describedby="lastname-error" @enderror></div>
        @error('lastname')<small class="sm-error" id="lastname-error">{{ $message }}</small>@enderror
    </div>
    <div class="sm-field">
        <label for="lrn_no">LRN No. <span aria-hidden="true">*</span></label>
        <div class="sm-input"><i class="ti ti-id" aria-hidden="true"></i><input type="text" id="lrn_no" name="lrn_no" class="form-control @error('lrn_no') is-invalid @enderror" value="{{ old('lrn_no', $student->lrn_no ?? '') }}" placeholder="e.g. 123456789012" inputmode="numeric" pattern="[0-9]{12}" minlength="12" maxlength="12" title="Enter exactly 12 digits." required @error('lrn_no') aria-invalid="true" aria-describedby="lrn_no-error" @enderror></div>
        @error('lrn_no')<small class="sm-error" id="lrn_no-error">{{ $message }}</small>@enderror
    </div>
    <div class="sm-field">
        <label for="birthday">Birthday <span aria-hidden="true">*</span></label>
        <div class="sm-input"><i class="ti ti-calendar-event" aria-hidden="true"></i><input type="date" id="birthday" name="birthday" class="form-control @error('birthday') is-invalid @enderror" value="{{ old('birthday', isset($student) ? $student->birthday?->format('Y-m-d') : '') }}" max="{{ today()->toDateString() }}" autocomplete="bday" required @error('birthday') aria-invalid="true" aria-describedby="birthday-error" @enderror></div>
        @error('birthday')<small class="sm-error" id="birthday-error">{{ $message }}</small>@enderror
    </div>
    <div class="sm-field">
        <label for="age">Age</label>
        <div class="sm-input sm-readonly"><i class="ti ti-users" aria-hidden="true"></i><input type="text" id="age" class="form-control" value="{{ $student->age ?? '' }}" placeholder="—" readonly aria-describedby="age-help"></div>
        <small class="sm-help" id="age-help">Auto-calculated from birthday.</small>
    </div>
    <fieldset class="sm-field sm-gender">
        <legend>Gender <span aria-hidden="true">*</span></legend>
        <div class="sm-gender-options">
            @foreach(['Male' => 'gender-male', 'Female' => 'gender-female'] as $gender => $icon)
            <label class="sm-gender-choice"><input type="radio" name="gender" value="{{ $gender }}" required @checked(old('gender', $student->gender ?? '') === $gender) @error('gender') aria-invalid="true" aria-describedby="gender-error" @enderror><i class="ti ti-{{ $icon }}" aria-hidden="true"></i><span>{{ $gender }}</span></label>
            @endforeach
        </div>
        @error('gender')<small class="sm-error" id="gender-error">{{ $message }}</small>@enderror
    </fieldset>
    <div class="sm-field">
        <label for="section">Section <span aria-hidden="true">*</span></label>
        <div class="sm-input"><i class="ti ti-school" aria-hidden="true"></i><select id="section" name="section" class="form-select @error('section') is-invalid @enderror" required @error('section') aria-invalid="true" aria-describedby="section-error" @enderror><option value="">Select section</option>@foreach($sections as $section)<option value="{{ $section }}" @selected(old('section', $student->section ?? '') === $section)>{{ $section }}</option>@endforeach</select></div>
        @error('section')<small class="sm-error" id="section-error">{{ $message }}</small>@enderror
    </div>
    <div class="sm-field">
        <label for="current_level">{{ $editing ? 'Current Level' : 'Starting Level' }} <span aria-hidden="true">*</span></label>
        <div class="sm-input"><i class="ti ti-chart-bar" aria-hidden="true"></i><select id="current_level" name="current_level" class="form-select @error('current_level') is-invalid @enderror" required @error('current_level') aria-invalid="true" aria-describedby="current_level-error" @enderror><option value="">Select {{ $editing ? 'current' : 'starting' }} level</option>@foreach($levels as $level)<option value="{{ $level }}" @selected((string) old('current_level', $student->current_level ?? '') === (string) $level)>Level {{ $level }}</option>@endforeach</select></div>
        @error('current_level')<small class="sm-error" id="current_level-error">{{ $message }}</small>@enderror
    </div>
    <div class="sm-field sm-full">
        <label for="username">Username @unless($editing)<span aria-hidden="true">*</span>@endunless</label>
        <div class="sm-input {{ $editing ? 'sm-readonly' : '' }}"><i class="ti ti-at" aria-hidden="true"></i><input type="text" id="username" @unless($editing) name="username" @endunless class="form-control @error('username') is-invalid @enderror" value="{{ $editing ? $student->user?->username : old('username') }}" placeholder="Enter username" maxlength="100" autocomplete="off" @if($editing) readonly @else required @endif @error('username') aria-invalid="true" aria-describedby="username-error" @enderror></div>
        @error('username')<small class="sm-error" id="username-error">{{ $message }}</small>@enderror
    </div>
    @unless($editing)
    <div class="sm-field">
        <label for="password">Password <span aria-hidden="true">*</span></label>
        <div class="sm-input sm-password"><i class="ti ti-lock" aria-hidden="true"></i><input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Enter password" minlength="6" autocomplete="new-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror><button type="button" class="sm-password-toggle" data-password-target="password" aria-label="Show password" aria-pressed="false"><i class="ti ti-eye" aria-hidden="true"></i></button></div>
        @error('password')<small class="sm-error" id="password-error">{{ $message }}</small>@enderror
    </div>
    <div class="sm-field">
        <label for="password_confirmation">Confirm Password <span aria-hidden="true">*</span></label>
        <div class="sm-input sm-password"><i class="ti ti-lock" aria-hidden="true"></i><input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Confirm password" minlength="6" autocomplete="new-password" required><button type="button" class="sm-password-toggle" data-password-target="password_confirmation" aria-label="Show confirm password" aria-pressed="false"><i class="ti ti-eye" aria-hidden="true"></i></button></div>
    </div>
    @endunless
</div>
