@extends('auth.layouts.authentication')

@section('css')
    @include('auth.partials.seller_registration_wizard_css')
@endsection

@section('content')
    <div class="aiz-main-wrapper d-flex flex-column justify-content-md-center bg-white">
        <section class="bg-white overflow-hidden">
            <div class="row">
                <div class="col-xxl-6 col-xl-9 col-lg-10 col-md-7 mx-auto py-lg-4">
                    <div class="card shadow-none rounded-0 border-0">
                        <div class="row no-gutters">
                            <div class="col-lg-6">
                                <img src="{{ uploaded_asset(get_setting('seller_register_page_image')) }}" alt="" class="img-fit h-100">
                            </div>

                            <div class="col-lg-6 p-4 p-lg-5 d-flex flex-column justify-content-center border right-content" style="height: auto;">
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

                    <div class="mt-3 mr-4 mr-md-0">
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('script')
    @include('auth.partials.seller_registration_wizard_script')
@endsection
