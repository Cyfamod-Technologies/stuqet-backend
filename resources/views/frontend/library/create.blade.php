@extends('frontend.layouts.app')

@section('meta_title'){{ translate('Contribute Library Material') }}@stop

@php
    $resourceTypeOptions = [
        'textbook' => translate('Text Book'),
        'lecture_handout' => translate('Lecture Handout'),
        'past_question' => translate('Past Question Paper'),
        'revision_notes' => translate('Personal Revision Notes'),
    ];
@endphp

@section('content')
<section class="pt-4 pb-5" style="background:#f7fafc;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="fs-24 fw-700 mb-2">{{ translate('Contribute Material') }}</h1>
                        <p class="fs-14 text-secondary mb-0">{{ translate('Upload academic resources to the isolated campus library. Supported formats: PDF, DOCX, JPG, PNG up to 40MB.') }}</p>
                    </div>
                    <a href="{{ route('library.index') }}" class="btn btn-soft-secondary mt-3 mt-lg-0">{{ translate('Back to Library') }}</a>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <form action="{{ route('library.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <label class="fs-13 fw-700">{{ translate('Resource Type') }}</label>
                                    <select name="resource_type" class="form-control aiz-selectpicker" required>
                                        @foreach ($resourceTypeOptions as $key => $label)
                                            <option value="{{ $key }}" @selected(old('resource_type') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label class="fs-13 fw-700">{{ translate('School') }}</label>
                                    <select name="school_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                                        <option value="">{{ translate('Select School') }}</option>
                                        @foreach ($schools as $school)
                                            <option value="{{ $school->id }}" @selected((string) old('school_id', optional($profile)->school_id) === (string) $school->id)>{{ $school->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-4 mb-3">
                                    <label class="fs-13 fw-700">{{ translate('Faculty') }}</label>
                                    <input type="text" name="faculty_name" value="{{ old('faculty_name', optional(optional($profile)->faculty)->name) }}" class="form-control text-capitalize" placeholder="{{ translate('e.g. Faculty of Science') }}" required>
                                </div>
                                <div class="col-lg-4 mb-3">
                                    <label class="fs-13 fw-700">{{ translate('Department') }}</label>
                                    <input type="text" name="department_name" value="{{ old('department_name', optional(optional($profile)->department)->name) }}" class="form-control text-capitalize" placeholder="{{ translate('e.g. Computer Science') }}" required>
                                </div>
                                <div class="col-lg-4 mb-3">
                                    <label class="fs-13 fw-700">{{ translate('Level') }}</label>
                                    <input type="text" name="level_name" value="{{ old('level_name', optional(optional($profile)->level)->name) }}" class="form-control text-uppercase" placeholder="{{ translate('e.g. 300L') }}" required>
                                </div>
                                <div class="col-lg-4 mb-3">
                                    <label class="fs-13 fw-700">{{ translate('Course Code') }}</label>
                                    <input type="text" name="course_code" value="{{ old('course_code') }}" class="form-control text-uppercase" placeholder="{{ translate('e.g. CSC 301') }}" required>
                                </div>
                                <div class="col-lg-8 mb-3">
                                    <label class="fs-13 fw-700">{{ translate('Material Title') }}</label>
                                    <input type="text" name="title" value="{{ old('title') }}" class="form-control" placeholder="{{ translate('Add a clear title students will recognize') }}" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="fs-13 fw-700">{{ translate('Description') }}</label>
                                    <textarea name="description" rows="4" class="form-control" placeholder="{{ translate('Optional notes about this material, lecturer, semester, or scan quality') }}">{{ old('description') }}</textarea>
                                </div>
                                <div class="col-lg-4 mb-3">
                                    <label class="fs-13 fw-700">{{ translate('Access Type') }}</label>
                                    <select name="access_type" id="library-access-type" class="form-control aiz-selectpicker" required>
                                        <option value="free" @selected(old('access_type') === 'free')>{{ translate('Free Download') }}</option>
                                        <option value="paid" @selected(old('access_type') === 'paid')>{{ translate('Paid Premium Access') }}</option>
                                    </select>
                                </div>
                                <div class="col-lg-4 mb-3 d-none" id="library-price-group">
                                    <label class="fs-13 fw-700">{{ translate('Wallet Unlock Price') }}</label>
                                    <input type="number" min="0" step="0.01" name="price" value="{{ old('price') }}" class="form-control" placeholder="{{ translate('e.g. 500') }}">
                                </div>
                                <div class="col-lg-12 mb-4">
                                    <label class="fs-13 fw-700">{{ translate('Document Upload') }}</label>
                                    <div class="border rounded-2 p-4 bg-light">
                                        <input type="file" name="document" class="form-control-file" accept=".pdf,.docx,.jpg,.jpeg,.png" required>
                                        <small class="text-muted d-block mt-2">{{ translate('We automatically generate a cover thumbnail and preview pages where possible.') }}</small>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary px-4">{{ translate('Upload Material') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
<script>
    function togglePriceGroup() {
        const accessType = $('#library-access-type').val();
        $('#library-price-group').toggleClass('d-none', accessType !== 'paid');
    }

    $(document).ready(function () {
        togglePriceGroup();
    });

    $(document).on('change', '#library-access-type', togglePriceGroup);
</script>
@endsection
