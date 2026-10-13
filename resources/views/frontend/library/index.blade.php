@extends('frontend.layouts.app')

@section('meta_title'){{ translate('Campus Library') }}@stop
@section('meta_description'){{ translate('Search, preview, contribute, and unlock academic resources by school, faculty, department, and level.') }}@stop

@php
    $resourceTypeOptions = [
        'textbook' => translate('Text Book'),
        'lecture_handout' => translate('Lecture Handout'),
        'past_question' => translate('Past Question Paper'),
        'revision_notes' => translate('Personal Revision Notes'),
    ];
@endphp

@section('content')
<section class="pt-4 pb-5" style="background: linear-gradient(180deg, #f5f9ff 0%, #ffffff 32%);">
    <div class="container">
        <div class="rounded-3 overflow-hidden mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #172554 100%);">
            <div class="row align-items-center no-gutters">
                <div class="col-lg-8 p-4 p-md-5 text-white">
                    <span class="badge badge-inline badge-soft-light fs-12 fw-700 px-3 py-2 mb-3">{{ translate('Campus Library & Resource Hub') }}</span>
                    <h1 class="fs-24 fs-md-34 fw-700 mb-3 text-white">{{ translate('Find academic materials without digging through marketplace listings') }}</h1>
                    <p class="fs-14 fs-md-16 mb-4 opacity-80">
                        {{ translate('Search textbooks, handouts, past questions, and revision notes by school, faculty, department, level, and course code.') }}
                    </p>
                    <div class="d-flex flex-wrap">
                        <a href="{{ route('library.create') }}" class="btn btn-primary mr-3 mb-2">{{ translate('Contribute Material') }}</a>
                        @if ($profile)
                            <a href="{{ route('library.my_department') }}" class="btn btn-outline-light mb-2">{{ translate('My Department Quick Link') }}</a>
                        @endif
                    </div>
                </div>
                <div class="col-lg-4 p-4 p-md-5 bg-white">
                    <div class="rounded-2 p-3 p-md-4 border">
                        <p class="fs-12 fw-700 text-uppercase text-primary mb-2">{{ translate('Quick Access') }}</p>
                        @if ($profile && $profile->department)
                            <h3 class="fs-18 fw-700 mb-2">{{ $profile->department->name }}</h3>
                            <p class="fs-14 text-secondary mb-1">{{ $profile->school->name ?? translate('School not set') }}</p>
                            <p class="fs-14 text-secondary mb-3">{{ $profile->level->name ?? translate('Level not set') }}</p>
                            <a href="{{ route('library.my_department') }}" class="btn btn-soft-primary btn-block">{{ translate('Open My Department Files') }}</a>
                        @else
                            <p class="fs-14 text-secondary mb-0">{{ translate('Your department quick link will appear here after your first contribution.') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3 shadow-sm p-3 p-md-4 mb-4">
            <form action="{{ route('library.index') }}" method="GET">
                <div class="row">
                    <div class="col-lg-4 mb-3">
                        <label class="fs-13 fw-700">{{ translate('Search documents') }}</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ translate('Title, course code, or department') }}">
                    </div>
                    <div class="col-lg-2 col-md-6 mb-3">
                        <label class="fs-13 fw-700">{{ translate('School') }}</label>
                        <select name="school_id" id="library-school-filter" class="form-control aiz-selectpicker" data-live-search="true">
                            <option value="">{{ translate('All Schools') }}</option>
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" @selected((string) request('school_id') === (string) $school->id)>{{ $school->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-3">
                        <label class="fs-13 fw-700">{{ translate('Faculty') }}</label>
                        <select name="library_faculty_id" id="library-faculty-filter" class="form-control aiz-selectpicker" data-live-search="true">
                            <option value="">{{ translate('All Faculties') }}</option>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->id }}" @selected((string) request('library_faculty_id') === (string) $faculty->id)>{{ $faculty->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-3">
                        <label class="fs-13 fw-700">{{ translate('Department') }}</label>
                        <select name="library_department_id" id="library-department-filter" class="form-control aiz-selectpicker" data-live-search="true">
                            <option value="">{{ translate('All Departments') }}</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) request('library_department_id') === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-3">
                        <label class="fs-13 fw-700">{{ translate('Level') }}</label>
                        <select name="library_level_id" id="library-level-filter" class="form-control aiz-selectpicker">
                            <option value="">{{ translate('All Levels') }}</option>
                            @foreach ($levels as $level)
                                <option value="{{ $level->id }}" @selected((string) request('library_level_id') === (string) $level->id)>{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label class="fs-13 fw-700">{{ translate('Resource Type') }}</label>
                        <select name="resource_type" class="form-control aiz-selectpicker">
                            <option value="">{{ translate('All Types') }}</option>
                            @foreach ($resourceTypeOptions as $key => $label)
                                <option value="{{ $key }}" @selected(request('resource_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">{{ translate('Search Library') }}</button>
                        <a href="{{ route('library.index') }}" class="btn btn-soft-secondary">{{ translate('Reset') }}</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fs-20 fw-700 mb-0">{{ translate('Academic Resources') }}</h2>
            <span class="fs-13 text-secondary">{{ $resources->total() }} {{ translate('documents found') }}</span>
        </div>

        <div class="row">
            @forelse ($resources as $resource)
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <a href="{{ route('library.show', $resource->slug) }}" class="text-reset">
                            <div class="h-220px overflow-hidden rounded-top bg-light d-flex align-items-center justify-content-center">
                                <img
                                    src="{{ $resource->cover_image_path ? my_asset($resource->cover_image_path) : static_asset('assets/img/placeholder-rect.jpg') }}"
                                    alt="{{ $resource->title }}"
                                    class="img-fluid h-100 w-100"
                                    style="object-fit: cover;"
                                >
                            </div>
                        </a>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge badge-inline badge-soft-info fs-11">{{ $resourceTypeOptions[$resource->resource_type] ?? ucfirst($resource->resource_type) }}</span>
                                <span class="fs-12 text-secondary">{{ $resource->page_count ? $resource->page_count . ' ' . translate('pages') : translate('Pages N/A') }}</span>
                            </div>
                            <div class="fs-13 fw-700 text-primary mb-1">{{ $resource->course_code }}</div>
                            <h3 class="fs-16 fw-700 mb-2 text-truncate-2" title="{{ $resource->title }}">
                                <a href="{{ route('library.show', $resource->slug) }}" class="text-reset">{{ $resource->title }}</a>
                            </h3>
                            <p class="fs-13 text-secondary mb-3 text-truncate-2">
                                {{ $resource->department->name ?? '' }}{{ $resource->level ? ' • ' . $resource->level->name : '' }}
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <div>
                                    <span class="fs-12 text-secondary d-block">{{ translate('Contributor') }}</span>
                                    <span class="fs-13 fw-700">{{ $resource->user->name }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="fs-12 text-secondary d-block">{{ translate('Score') }}</span>
                                    <span class="fs-13 fw-700">{{ $resource->upvote_count - $resource->downvote_count }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 d-flex justify-content-between align-items-center">
                            @if ($resource->access_type === 'paid')
                                <span class="fs-14 fw-700 text-primary">{{ translate('Unlock for') }} {{ single_price($resource->price) }}</span>
                            @else
                                <span class="fs-14 fw-700 text-success">{{ translate('Free Download') }}</span>
                            @endif
                            <a href="{{ route('library.show', $resource->slug) }}" class="btn btn-sm btn-soft-primary">{{ translate('Open') }}</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="bg-white rounded-3 shadow-sm p-5 text-center">
                        <h3 class="fs-18 fw-700 mb-2">{{ translate('No materials found') }}</h3>
                        <p class="fs-14 text-secondary mb-3">{{ translate('Try another search term or be the first to contribute a document for this academic category.') }}</p>
                        <a href="{{ route('library.create') }}" class="btn btn-primary">{{ translate('Contribute Material') }}</a>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $resources->links() }}
        </div>
    </div>
</section>
@endsection

@section('script')
<script>
    function repopulateSelect($select, items, placeholder, selectedValue) {
        $select.empty().append(new Option(placeholder, ''));
        items.forEach(function (item) {
            const option = new Option(item.name, item.id, false, String(selectedValue) === String(item.id));
            $select.append(option);
        });
        $select.selectpicker('refresh');
    }

    $(document).on('change', '#library-school-filter', function () {
        const schoolId = $(this).val();
        const $faculty = $('#library-faculty-filter');
        const $department = $('#library-department-filter');
        const $level = $('#library-level-filter');

        repopulateSelect($faculty, [], '{{ translate('All Faculties') }}', '');
        repopulateSelect($department, [], '{{ translate('All Departments') }}', '');
        repopulateSelect($level, [], '{{ translate('All Levels') }}', '');

        if (!schoolId) {
            return;
        }

        $.get('{{ route('library.faculties') }}', { school_id: schoolId }, function (data) {
            repopulateSelect($faculty, data, '{{ translate('All Faculties') }}', '');
        });
    });

    $(document).on('change', '#library-faculty-filter', function () {
        const facultyId = $(this).val();
        const $department = $('#library-department-filter');
        const $level = $('#library-level-filter');

        repopulateSelect($department, [], '{{ translate('All Departments') }}', '');
        repopulateSelect($level, [], '{{ translate('All Levels') }}', '');

        if (!facultyId) {
            return;
        }

        $.get('{{ route('library.departments') }}', { faculty_id: facultyId }, function (data) {
            repopulateSelect($department, data, '{{ translate('All Departments') }}', '');
        });
    });

    $(document).on('change', '#library-department-filter', function () {
        const departmentId = $(this).val();
        const $level = $('#library-level-filter');

        repopulateSelect($level, [], '{{ translate('All Levels') }}', '');

        if (!departmentId) {
            return;
        }

        $.get('{{ route('library.levels') }}', { department_id: departmentId }, function (data) {
            repopulateSelect($level, data, '{{ translate('All Levels') }}', '');
        });
    });
</script>
@endsection
