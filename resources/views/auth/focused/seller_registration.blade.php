@extends('auth.layouts.authentication')

@section('css')
    @include('auth.partials.seller_registration_wizard_css')
@endsection

@section('content')
    <div class="aiz-main-wrapper d-flex flex-column justify-content-center bg-white">
        <section class="bg-white overflow-hidden" style="min-height:100vh;">
            <div class="row no-gutters" style="min-height: 100vh;">
                <div class="col-xxl-1 col-lg-1">
                    <div class="h-100" style="max-height: 100vh">
                        <img src="{{ uploaded_asset(get_setting('seller_register_page_image')) }}" alt="" class="img-fit h-100">
                    </div>
                </div>

                <div class="col-xxl-11 col-lg-11">
                    <div class="d-flex align-items-center justify-content-start right-content">
                        <div class="py-2 py-lg-3 px-3 px-xl-3 px-xxl-4 flex-grow-1 w-100">
                            <div class="size-48px mb-3 mx-auto mx-lg-0">
                                <img src="{{ uploaded_asset(get_setting('site_icon')) }}" alt="{{ translate('Site Icon')}}" class="img-fit h-100">
                            </div>

                            <div class="text-center text-lg-left">
                                <h1 class="fs-20 fs-md-24 fw-700 text-primary" style="text-transform: uppercase;">{{ translate('Register your shop')}}</h1>
                            </div>

                            <div class="pt-3 pt-lg-4">
                                @include('auth.partials.seller_registration_wizard_form')

                                <p class="fs-12 text-gray mb-0 mt-3">
                                    {{ translate('Already have an account?')}}
                                    <a href="{{ route('seller.login') }}" class="ml-2 fs-14 fw-700 animate-underline-primary">{{ translate('Log In')}}</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('script')
    @include('auth.partials.seller_registration_wizard_script')
@endsection
