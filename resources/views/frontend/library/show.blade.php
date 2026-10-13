@extends('frontend.layouts.app')

@section('meta_title'){{ $resource->title }}@stop
@section('meta_description'){{ $resource->description ?: $resource->course_code }}@stop

@php
    $resourceTypeLabels = [
        'textbook' => translate('Text Book'),
        'lecture_handout' => translate('Lecture Handout'),
        'past_question' => translate('Past Question Paper'),
        'revision_notes' => translate('Personal Revision Notes'),
    ];
    $score = $resource->upvote_count - $resource->downvote_count;
@endphp

@section('content')
<section class="pt-4 pb-5" style="background:#f8fafc;">
    <div class="container">
        <div class="mb-4">
            <a href="{{ route('library.index') }}" class="fs-14 text-primary"><i class="las la-angle-left"></i> {{ translate('Back to Library') }}</a>
        </div>

        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="bg-white rounded-3 shadow-sm p-3 p-md-4">
                    <div class="d-flex flex-wrap align-items-center mb-3">
                        <span class="badge badge-inline badge-soft-info mr-2">{{ $resourceTypeLabels[$resource->resource_type] ?? ucfirst($resource->resource_type) }}</span>
                        <span class="badge badge-inline {{ $resource->access_type === 'paid' ? 'badge-soft-warning' : 'badge-soft-success' }}">
                            {{ $resource->access_type === 'paid' ? translate('Premium') : translate('Free') }}
                        </span>
                    </div>
                    <h1 class="fs-24 fw-700 mb-2">{{ $resource->title }}</h1>
                    <div class="d-flex flex-wrap text-secondary fs-14 mb-4" style="gap: 16px;">
                        <span><strong>{{ translate('Course Code:') }}</strong> {{ $resource->course_code }}</span>
                        <span><strong>{{ translate('Pages:') }}</strong> {{ $resource->page_count ?: translate('N/A') }}</span>
                        <span><strong>{{ translate('Score:') }}</strong> {{ $score }}</span>
                        <span><strong>{{ translate('Downloads:') }}</strong> {{ $resource->download_count }}</span>
                    </div>

                    <div class="row mb-4">
                        @forelse (($resource->preview_page_paths ?? []) as $previewPath)
                            <div class="col-md-4 mb-3">
                                <div class="border rounded-2 overflow-hidden bg-light">
                                    <img src="{{ my_asset($previewPath) }}" alt="{{ $resource->title }}" class="img-fluid w-100">
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="border rounded-2 bg-light p-4 text-center text-secondary">
                                    {{ translate('Preview pages are not available for this file yet.') }}
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <div class="border rounded-2 p-3 mb-4" style="background:#f8fafc;">
                        <h3 class="fs-16 fw-700 mb-2">{{ translate('Material Details') }}</h3>
                        <p class="fs-14 text-secondary mb-2"><strong>{{ translate('School:') }}</strong> {{ $resource->school->name ?? '' }}</p>
                        <p class="fs-14 text-secondary mb-2"><strong>{{ translate('Faculty:') }}</strong> {{ $resource->faculty->name ?? '' }}</p>
                        <p class="fs-14 text-secondary mb-2"><strong>{{ translate('Department:') }}</strong> {{ $resource->department->name ?? '' }}</p>
                        <p class="fs-14 text-secondary mb-2"><strong>{{ translate('Level:') }}</strong> {{ $resource->level->name ?? '' }}</p>
                        <p class="fs-14 text-secondary mb-0"><strong>{{ translate('Contributor:') }}</strong> {{ $resource->user->name }}</p>
                    </div>

                    @if ($resource->description)
                        <div>
                            <h3 class="fs-16 fw-700 mb-2">{{ translate('Description') }}</h3>
                            <p class="fs-14 text-secondary mb-0">{!! nl2br(e($resource->description)) !!}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-5">
                <div class="bg-white rounded-3 shadow-sm p-3 p-md-4 mb-4">
                    <h3 class="fs-18 fw-700 mb-3">{{ translate('Access This Document') }}</h3>
                    @if ($resource->access_type === 'paid')
                        <p class="fs-14 text-secondary mb-3">{{ translate('This resource is protected and unlocks permanently for your account once paid from your wallet balance.') }}</p>
                        <div class="fs-24 fw-700 text-primary mb-3">{{ single_price($resource->price) }}</div>
                    @else
                        <p class="fs-14 text-secondary mb-3">{{ translate('This resource is free to access and download.') }}</p>
                    @endif

                    @if ($hasUnlocked)
                        <a href="{{ route('library.download', $resource->slug) }}" class="btn btn-primary btn-block">{{ translate('Download to Device') }}</a>
                    @elseif ($resource->access_type === 'paid')
                        <form action="{{ route('library.unlock', $resource->slug) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-block">{{ translate('Unlock Document for') }} {{ single_price($resource->price) }}</button>
                        </form>
                    @else
                        <a href="{{ route('library.download', $resource->slug) }}" class="btn btn-primary btn-block">{{ translate('Download to Device') }}</a>
                    @endif
                </div>

                @auth
                    <div class="bg-white rounded-3 shadow-sm p-3 p-md-4 mb-4">
                        <h3 class="fs-18 fw-700 mb-3">{{ translate('Peer Review') }}</h3>
                        <div class="d-flex mb-3">
                            <form action="{{ route('library.vote', $resource->slug) }}" method="POST" class="mr-2">
                                @csrf
                                <input type="hidden" name="vote" value="1">
                                <button type="submit" class="btn {{ $userVote === 1 ? 'btn-success' : 'btn-soft-success' }}">{{ translate('Upvote') }}</button>
                            </form>
                            <form action="{{ route('library.vote', $resource->slug) }}" method="POST">
                                @csrf
                                <input type="hidden" name="vote" value="-1">
                                <button type="submit" class="btn {{ $userVote === -1 ? 'btn-danger' : 'btn-soft-danger' }}">{{ translate('Downvote') }}</button>
                            </form>
                        </div>
                        <p class="fs-13 text-secondary mb-0">{{ translate('High downvotes automatically flag a document for review.') }}</p>
                    </div>

                    <div class="bg-white rounded-3 shadow-sm p-3 p-md-4">
                        <h3 class="fs-18 fw-700 mb-3">{{ translate('Report Abuse or Broken File') }}</h3>
                        <form action="{{ route('library.report', $resource->slug) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label class="fs-13 fw-700">{{ translate('Reason') }}</label>
                                <select name="reason" class="form-control aiz-selectpicker" required>
                                    <option value="wrong_material">{{ translate('Wrong material uploaded') }}</option>
                                    <option value="blurry_scan">{{ translate('Blurry or unreadable scan') }}</option>
                                    <option value="broken_download">{{ translate('Broken download') }}</option>
                                    <option value="academic_honesty">{{ translate('Academic honesty concern') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="fs-13 fw-700">{{ translate('Details') }}</label>
                                <textarea name="details" rows="4" class="form-control" placeholder="{{ translate('Explain the problem briefly') }}"></textarea>
                            </div>
                            <button type="submit" class="btn btn-soft-danger">{{ translate('Submit Report') }}</button>
                        </form>
                    </div>
                @else
                    <div class="bg-white rounded-3 shadow-sm p-3 p-md-4">
                        <h3 class="fs-18 fw-700 mb-2">{{ translate('Join the library community') }}</h3>
                        <p class="fs-14 text-secondary mb-3">{{ translate('Log in to upvote, report a document, unlock premium files, or contribute your own materials.') }}</p>
                        <a href="{{ route('user.login') }}" class="btn btn-soft-primary">{{ translate('Login') }}</a>
                    </div>
                @endauth
            </div>
        </div>

        @if ($relatedResources->count() > 0)
            <div class="mt-4">
                <h2 class="fs-20 fw-700 mb-3">{{ translate('More from this department') }}</h2>
                <div class="row">
                    @foreach ($relatedResources as $related)
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <a href="{{ route('library.show', $related->slug) }}" class="text-reset">
                                    <div class="h-180px overflow-hidden bg-light">
                                        <img src="{{ $related->cover_image_path ? my_asset($related->cover_image_path) : static_asset('assets/img/placeholder-rect.jpg') }}" alt="{{ $related->title }}" class="img-fluid w-100 h-100" style="object-fit: cover;">
                                    </div>
                                </a>
                                <div class="card-body">
                                    <div class="fs-13 fw-700 text-primary mb-1">{{ $related->course_code }}</div>
                                    <h3 class="fs-16 fw-700 mb-2 text-truncate-2"><a href="{{ route('library.show', $related->slug) }}" class="text-reset">{{ $related->title }}</a></h3>
                                    <p class="fs-13 text-secondary mb-0">{{ $related->level->name ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
