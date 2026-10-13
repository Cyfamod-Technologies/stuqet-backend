@php
    $schools = \App\Models\School::query()->where('is_active', 1)->orderBy('name')->get();
@endphp

<div class="seller-reg-wizard" id="seller-reg-wizard">
    <div class="seller-reg-wizard__steps">
        <button type="button" class="seller-reg-wizard__step" data-step-target="seller-step-personal">
            <span class="seller-reg-wizard__step-index">1</span>
            <span>
                <span class="seller-reg-wizard__step-label">{{ translate('Personal') }}</span>
                <span class="seller-reg-wizard__step-note">{{ translate('Account setup details') }}</span>
            </span>
        </button>
        <button type="button" class="seller-reg-wizard__step" data-step-target="seller-step-student" data-student-step>
            <span class="seller-reg-wizard__step-index">2</span>
            <span>
                <span class="seller-reg-wizard__step-label">{{ translate('Student') }}</span>
                <span class="seller-reg-wizard__step-note">{{ translate('Only for student sellers') }}</span>
            </span>
        </button>
        <button type="button" class="seller-reg-wizard__step" data-step-target="seller-step-business">
            <span class="seller-reg-wizard__step-index">3</span>
            <span>
                <span class="seller-reg-wizard__step-label">{{ translate('Business') }}</span>
                <span class="seller-reg-wizard__step-note">{{ translate('Shop profile and category') }}</span>
            </span>
        </button>
    </div>

    <div class="seller-reg-wizard__body">
        <form id="reg-form" class="form-default" role="form" action="{{ route('shops.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            @if ($errors->any())
                <div class="seller-reg-error-summary" role="alert">
                    <div class="seller-reg-error-summary__title">{{ translate('Please fix the following before continuing:') }}</div>
                    <ul class="seller-reg-error-summary__list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="seller-reg-wizard__panel" id="seller-step-personal">
                <div class="seller-reg-wizard__title">{{ translate('Personal Info') }}</div>
                <div class="seller-reg-wizard__subtitle">{{ translate('Collect the seller account details first.') }}</div>

                <div class="seller-reg-grid">
                    <div class="form-group seller-reg-grid__full">
                        <label for="name" class="fs-12 fw-700 text-soft-dark">{{ translate('Your Name') }}</label>
                        <input type="text" class="form-control rounded-0{{ $errors->has('name') ? ' is-invalid' : '' }}" value="{{ old('name') }}" placeholder="{{ translate('Full Name') }}" name="name" required>
                        @if ($errors->has('name'))
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $errors->first('name') }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group seller-reg-grid__full">
                        <label>{{ translate('Your Email') }}</label>
                        <input type="email" class="form-control rounded-0{{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ $email ?? old('email') }}" placeholder="{{ translate('Email') }}" name="email" required {{ !empty($email) ? 'readonly' : '' }}>
                        @if ($errors->has('email'))
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $errors->first('email') }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group seller-reg-grid__full">
                        <label>{{ translate('Your Phone') }}</label>
                        <input type="tel" class="form-control rounded-0{{ $errors->has('phone') ? ' is-invalid' : '' }}" value="{{ $phone ?? old('phone') }}" placeholder="{{ translate('Phone') }}" name="phone" required {{ !empty($phone) ? 'readonly' : '' }}>
                        @if ($errors->has('phone'))
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $errors->first('phone') }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group seller-reg-grid__full">
                        <label for="student_status" class="fs-12 fw-700 text-soft-dark">{{ translate('Student or non-student') }}</label>
                        <select id="student_status" class="form-control rounded-0{{ $errors->has('student_status') ? ' is-invalid' : '' }}" name="student_status" required>
                            <option value="">{{ translate('Select type') }}</option>
                            <option value="student" {{ old('student_status') === 'student' ? 'selected' : '' }}>{{ translate('Student') }}</option>
                            <option value="non_student" {{ old('student_status') === 'non_student' ? 'selected' : '' }}>{{ translate('Non-student') }}</option>
                        </select>
                        @if ($errors->has('student_status'))
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $errors->first('student_status') }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group mb-0">
                        <label for="password" class="fs-12 fw-700 text-soft-dark">{{ translate('Password') }}</label>
                        <div class="position-relative">
                            <input type="password" class="form-control rounded-0{{ $errors->has('password') ? ' is-invalid' : '' }}" placeholder="{{ translate('Password') }}" name="password" required>
                            <i class="password-toggle las la-2x la-eye"></i>
                        </div>
                        <span class="seller-reg-helper">{{ translate('Password must contain at least 6 digits') }}</span>
                        @if ($errors->has('password'))
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $errors->first('password') }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="fs-12 fw-700 text-soft-dark">{{ translate('Confirm Password') }}</label>
                        <div class="position-relative">
                            <input type="password" class="form-control rounded-0" placeholder="{{ translate('Confirm Password') }}" name="password_confirmation" required>
                            <i class="password-toggle las la-2x la-eye"></i>
                        </div>
                    </div>
                </div>

                <div class="seller-reg-wizard__actions seller-reg-wizard__actions--end">
                    <button type="button" class="btn btn-primary btn-block fw-600 rounded-0" data-next-from="personal">
                        {{ translate('Next') }}
                    </button>
                </div>
            </div>

            <div class="seller-reg-wizard__panel" id="seller-step-student">
                <div class="seller-reg-wizard__title">{{ translate('Student Verification') }}</div>
                <div class="seller-reg-wizard__subtitle">{{ translate('This section only appears when the seller is a student.') }}</div>

                <div class="seller-reg-grid">
                    <div class="form-group seller-reg-grid__full">
                        <label for="school_id" class="fs-12 fw-700 text-soft-dark">{{ translate('School Selection') }}</label>
                        <select id="school_id" class="form-control rounded-0{{ $errors->has('school_id') ? ' is-invalid' : '' }}" name="school_id" data-student-required>
                            <option value="">{{ translate('Select school') }}</option>
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" {{ (string) old('school_id') === (string) $school->id ? 'selected' : '' }}>
                                    {{ $school->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('school_id'))
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $errors->first('school_id') }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="school_id_front" class="fs-12 fw-700 text-soft-dark">{{ translate('Upload School ID Front') }}</label>
                        <input type="file" class="form-control rounded-0{{ $errors->has('school_id_front') ? ' is-invalid' : '' }}" name="school_id_front" id="school_id_front" accept=".jpg,.jpeg,.png,.pdf" data-student-required>
                        @if ($errors->has('school_id_front'))
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $errors->first('school_id_front') }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="school_id_back" class="fs-12 fw-700 text-soft-dark">{{ translate('Upload School ID Back') }}</label>
                        <input type="file" class="form-control rounded-0{{ $errors->has('school_id_back') ? ' is-invalid' : '' }}" name="school_id_back" id="school_id_back" accept=".jpg,.jpeg,.png,.pdf" data-student-required>
                        @if ($errors->has('school_id_back'))
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $errors->first('school_id_back') }}</strong>
                            </span>
                        @endif
                    </div>
                </div>

                <div class="seller-reg-wizard__actions">
                    <button type="button" class="btn btn-outline-primary btn-block fw-600 rounded-0" data-prev-from="student">
                        {{ translate('Previous') }}
                    </button>
                    <button type="button" class="btn btn-primary btn-block fw-600 rounded-0" data-next-from="student">
                        {{ translate('Next') }}
                    </button>
                </div>
            </div>

            <div class="seller-reg-wizard__panel" id="seller-step-business">
                <div class="seller-reg-wizard__title">{{ translate('Business Info') }}</div>
                <div class="seller-reg-wizard__subtitle">{{ translate('Finish with the shop profile and seller category.') }}</div>

                <div class="seller-reg-grid">
                    <div class="form-group">
                        <label for="shop_name" class="fs-12 fw-700 text-soft-dark">{{ translate('Shop Name') }}</label>
                        <input type="text" class="form-control rounded-0{{ $errors->has('shop_name') ? ' is-invalid' : '' }}" value="{{ old('shop_name') }}" placeholder="{{ translate('Shop Name') }}" name="shop_name" required>
                        @if ($errors->has('shop_name'))
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $errors->first('shop_name') }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="business_category" class="fs-12 fw-700 text-soft-dark">{{ translate('Business Category') }}</label>
                        <select id="business_category" class="form-control rounded-0{{ $errors->has('business_category') ? ' is-invalid' : '' }}" name="business_category" required>
                            <option value="">{{ translate('Select category') }}</option>
                            <option value="food" {{ old('business_category') === 'food' ? 'selected' : '' }}>{{ translate('Food vendor') }}</option>
                            <option value="product" {{ old('business_category') === 'product' ? 'selected' : '' }}>{{ translate('Product vendor') }}</option>
                            <option value="service" {{ old('business_category') === 'service' ? 'selected' : '' }}>{{ translate('Service renderer') }}</option>
                        </select>
                        @if ($errors->has('business_category'))
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $errors->first('business_category') }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group seller-reg-grid__full">
                        <label for="address" class="fs-12 fw-700 text-soft-dark">{{ translate('Address') }}</label>
                        <input type="text" class="form-control rounded-0{{ $errors->has('address') ? ' is-invalid' : '' }}" value="{{ old('address') }}" placeholder="{{ translate('Address') }}" name="address" required>
                        @if ($errors->has('address'))
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $errors->first('address') }}</strong>
                            </span>
                        @endif
                    </div>
                </div>

                @if(get_setting('google_recaptcha') == 1 && get_setting('recaptcha_seller_register') == 1)
                    @if ($errors->has('g-recaptcha-response'))
                        <span class="border invalid-feedback rounded p-2 mb-3 bg-danger text-white" role="alert" style="display: block;">
                            <strong>{{ $errors->first('g-recaptcha-response') }}</strong>
                        </span>
                    @endif
                @endif

                <div class="seller-reg-wizard__actions">
                    <button type="button" class="btn btn-outline-primary btn-block fw-600 rounded-0" data-prev-from="business">
                        {{ translate('Previous') }}
                    </button>
                    <button type="submit" class="btn btn-primary btn-block fw-600 rounded-0">
                        {{ translate('Register Shop') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
