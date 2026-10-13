@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <h5 class="mb-0 h6">{{translate('School Information')}}</h5>
</div>

<div class="col-lg-8 mx-auto">
    <div class="card">
        <div class="card-body p-0">
            <form class="p-4" action="{{ route('schools.update', $school->id) }}" method="POST" enctype="multipart/form-data" id="aizSubmitForm">
                <input name="_method" type="hidden" value="PATCH">
                @csrf
                <div class="form-group mb-3">
                    <label for="name">{{translate('Name')}}</label>
                    <input type="text" placeholder="{{translate('Name')}}" id="name" name="name" maxlength="255" value="{{ $school->name }}" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label for="logo">{{translate('Logo')}} <small>({{ translate('Recommended: 120x80') }})</small></label>
                    <div class="input-group" data-toggle="aizuploader" data-type="image">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
                        </div>
                        <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                        <input type="hidden" name="logo" value="{{$school->logo}}" class="selected-files">
                    </div>
                    <div class="file-preview box sm">
                    </div>
                    <small class="text-muted">{{ translate('Recommended dimensions: 120px width X 80px height.') }}</small>
                </div>
                <div class="form-group mb-3">
                    <label for="meta_title">{{translate('Meta Title')}}</label>
                    <input type="text" class="form-control" name="meta_title" value="{{ $school->meta_title }}" placeholder="{{translate('Meta Title')}}">
                </div>
                <div class="form-group mb-3">
                    <label for="meta_description">{{translate('Meta Description')}}</label>
                    <textarea name="meta_description" rows="8" class="form-control">{{ $school->meta_description }}</textarea>
                </div>
                <div class="form-group mb-3">
                    <label for="slug">{{translate('Slug')}}</label>
                    <input type="text" placeholder="{{translate('Slug')}}" id="slug" name="slug" value="{{ $school->slug }}" class="form-control">
                </div>
                <div class="form-group mb-3">
                    <label for="is_active">
                        <input type="checkbox" name="is_active" value="1" {{ $school->is_active ? 'checked' : '' }}>
                        {{translate('Active')}}
                    </label>
                </div>
                <div class="form-group mb-0 text-right">
                    <button type="submit" class="btn btn-primary">{{translate('Save')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
