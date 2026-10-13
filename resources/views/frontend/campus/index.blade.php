@extends('frontend.layouts.app')

@section('content')
    @php
        $hour = now()->format('G');
        $greeting = $hour < 12 ? translate('Good morning') : ($hour < 17 ? translate('Good afternoon') : translate('Good evening'));
        $displayName = Auth::check() ? strtok(Auth::user()->name, ' ') : translate('Boss');
        $foodHero = $campusFoodProducts->first() ?: $campusRecommendedProducts->first();
        $serviceHero = $campusServiceProducts->first() ?: $campusRecommendedProducts->skip(1)->first();
        $currentCampusSchool = $campusSchools->first(fn ($school) => str_contains(strtolower($school->name), 'university of lagos'))
            ?: $campusSchools->first();
        $campusHeaderLogo = get_setting('header_logo');

        $quickTypes = [
            ['label' => translate('Food'), 'icon' => 'la-hamburger', 'url' => route('search') . '?keyword=food', 'active' => true],
            ['label' => translate('Services'), 'icon' => 'la-tools', 'url' => route('search') . '?keyword=services', 'active' => false],
            ['label' => translate('Products'), 'icon' => 'la-shopping-bag', 'url' => route('search'), 'active' => false],
            ['label' => translate('Library'), 'icon' => 'la-book-open', 'url' => route('library.index'), 'active' => false],
        ];

        $fallbackCategories = [
            ['label' => translate('Restaurants'), 'icon' => 'la-hamburger', 'url' => route('search') . '?keyword=restaurants'],
            ['label' => translate('Fashion'), 'icon' => 'la-tshirt', 'url' => route('search') . '?keyword=fashion'],
            ['label' => translate('Electronics'), 'icon' => 'la-headphones', 'url' => route('search') . '?keyword=electronics'],
            ['label' => translate('Beauty'), 'icon' => 'la-spray-can', 'url' => route('search') . '?keyword=beauty'],
            ['label' => translate('Tutoring'), 'icon' => 'la-graduation-cap', 'url' => route('search') . '?keyword=tutoring'],
            ['label' => translate('Repairs'), 'icon' => 'la-tools', 'url' => route('search') . '?keyword=repairs'],
            ['label' => translate('Laundry'), 'icon' => 'la-soap', 'url' => route('search') . '?keyword=laundry'],
            ['label' => translate('Design'), 'icon' => 'la-palette', 'url' => route('search') . '?keyword=design'],
            ['label' => translate('Photography'), 'icon' => 'la-camera', 'url' => route('search') . '?keyword=photography'],
        ];

        $shownCategories = $campusCategories->isNotEmpty()
            ? $campusCategories->map(function ($category) {
                return [
                    'label' => $category->getTranslation('name'),
                    'icon' => null,
                    'image' => isset($category->catIcon->file_name) ? my_asset($category->catIcon->file_name) : null,
                    'url' => route('products.category', $category->slug),
                ];
            })->values()->all()
            : $fallbackCategories;
    @endphp

    <style>
        .aiz-campus > .top-banner,
        .aiz-campus > header,
        .aiz-campus > .aiz-top-menu-sidebar {
            display: none !important;
        }

        .campus-home {
            background: #f7f8fb;
            color: #16171d;
            min-height: 100vh;
            padding: 24px 0 34px;
        }

        .campus-shell {
            width: min(100%, 980px);
            margin: 0 auto;
            padding: 0 18px;
        }

        .campus-topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
        }

        .campus-greeting h1 {
            font-size: 26px;
            line-height: 1.15;
            font-weight: 800;
            margin: 0 0 6px;
            letter-spacing: 0;
        }

        .campus-logo {
            display: inline-flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .campus-logo img {
            display: block;
            width: auto;
            height: 34px;
            max-width: 128px;
            object-fit: contain;
        }

        .campus-greeting p {
            color: #777b86;
            font-size: 15px;
            margin: 0;
        }

        .campus-location {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-shrink: 0;
        }

        .campus-school {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 0;
            background: transparent;
            padding: 0;
            color: #2b2d34;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
        }

        .campus-school-picker {
            position: relative;
        }

        .campus-school-menu {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: min(320px, calc(100vw - 32px));
            max-height: 320px;
            overflow-y: auto;
            padding: 8px;
            background: #fff;
            border: 1px solid #edf0f4;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(21, 28, 42, 0.14);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity 160ms ease, transform 160ms ease, visibility 160ms ease;
            z-index: 20;
        }

        .campus-school-picker.open .campus-school-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .campus-school-option {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
            padding: 8px 10px;
            border-radius: 7px;
            color: #23262d;
            font-size: 13px;
            font-weight: 700;
        }

        .campus-school-option:hover,
        .campus-school-option:focus {
            color: #e85f00;
            background: #fff5ec;
        }

        .campus-school-logo {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 28px;
            background: #f1f3f6;
            color: #5d6470;
            overflow: hidden;
        }

        .campus-school-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .campus-school-empty {
            padding: 12px;
            color: #777b86;
            font-size: 13px;
            font-weight: 600;
        }

        .campus-bell {
            position: relative;
            display: inline-flex;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            color: #22242b;
            background: #fff;
            border: 1px solid #edf0f4;
            border-radius: 50%;
        }

        .campus-bell span {
            position: absolute;
            top: -4px;
            right: -2px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #ff6a00;
            color: #fff;
            font-size: 10px;
            line-height: 18px;
            text-align: center;
            font-weight: 800;
        }

        .campus-search {
            display: grid;
            grid-template-columns: 1fr 64px;
            gap: 0;
            background: #fff;
            border: 1px solid #edf0f4;
            border-radius: 8px;
            box-shadow: 0 10px 26px rgba(21, 28, 42, 0.05);
            overflow: hidden;
            margin-bottom: 22px;
        }

        .campus-search-input {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 18px;
            min-height: 58px;
            color: #9aa0aa;
        }

        .campus-search-input input {
            border: 0;
            outline: 0;
            width: 100%;
            font-size: 14px;
            color: #20222a;
            background: transparent;
        }

        .campus-search button {
            border: 0;
            background: #ff6a00;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
        }

        .campus-quick-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .campus-quick-card {
            display: flex;
            min-height: 118px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #fff;
            border: 1px solid #eff1f5;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(21, 28, 42, 0.045);
            color: #171920;
            font-weight: 800;
        }

        .campus-quick-card.active {
            background: #fff8f1;
            color: #e85f00;
        }

        .campus-quick-card i {
            font-size: 38px;
            color: inherit;
        }

        .campus-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 16px;
        }

        .campus-section-head h2 {
            font-size: 17px;
            line-height: 1.2;
            font-weight: 800;
            margin: 0;
        }

        .campus-section-head a {
            color: #50545e;
            font-size: 13px;
            font-weight: 700;
        }

        .campus-categories {
            display: grid;
            grid-template-columns: repeat(9, minmax(70px, 1fr));
            gap: 12px;
            margin-bottom: 28px;
        }

        .campus-category {
            display: flex;
            min-width: 0;
            flex-direction: column;
            align-items: center;
            gap: 9px;
            color: #23262d;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
        }

        .campus-category-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid #eef1f4;
            box-shadow: 0 10px 24px rgba(21, 28, 42, 0.04);
            overflow: hidden;
        }

        .campus-category-icon img {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }

        .campus-category-icon i {
            font-size: 28px;
            color: #1f2937;
        }

        .campus-promos {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .campus-promo {
            min-height: 154px;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            padding: 24px;
            color: #fff;
            isolation: isolate;
        }

        .campus-promo.food {
            background: #ef831a;
        }

        .campus-promo.service {
            background: #7864cf;
        }

        .campus-promo::after {
            content: "";
            position: absolute;
            inset: 0 0 0 auto;
            width: 52%;
            background-size: cover;
            background-position: center;
            z-index: -2;
            opacity: 0.95;
        }

        .campus-promo::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(0, 0, 0, 0.12), rgba(0, 0, 0, 0));
            z-index: -1;
        }

        .campus-promo.food::after {
            background-image: url('{{ $foodHero ? get_image($foodHero->thumbnail) : static_asset('assets/img/placeholder-rect.jpg') }}');
        }

        .campus-promo.service::after {
            background-image: url('{{ $serviceHero ? get_image($serviceHero->thumbnail) : static_asset('assets/img/placeholder-rect.jpg') }}');
        }

        .campus-promo-content {
            width: 58%;
            position: relative;
            z-index: 1;
        }

        .campus-promo h3 {
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 8px;
            letter-spacing: 0;
        }

        .campus-promo p {
            font-size: 14px;
            line-height: 1.45;
            margin: 0 0 20px;
            color: rgba(255, 255, 255, 0.9);
        }

        .campus-promo .btn {
            border-radius: 999px;
            background: #fff;
            color: #2c3038;
            font-size: 12px;
            font-weight: 800;
            padding: 10px 18px;
            border: 0;
        }

        .campus-card-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .campus-product-card {
            background: #fff;
            border: 1px solid #edf0f4;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(21, 28, 42, 0.045);
        }

        .campus-product-media {
            position: relative;
            height: 200px;
            display: block;
            overflow: hidden;
            background: #eef1f4;
        }

        .campus-product-media img,
        .campus-near-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .campus-product-badge {
            position: absolute;
            left: 10px;
            bottom: 8px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 20px;
            padding: 0 8px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.88);
            color: #1e242c;
            font-size: 11px;
            font-weight: 800;
        }

        .campus-product-badge.available {
            color: #138f54;
        }

        .campus-discount {
            position: absolute;
            right: 10px;
            bottom: 8px;
            border-radius: 999px;
            background: #ff6a00;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 3px 8px;
        }

        .campus-product-body {
            padding: 12px 14px 14px;
        }

        .campus-product-title {
            color: #171920;
            font-size: 14px;
            line-height: 1.3;
            font-weight: 800;
            min-height: 37px;
            margin-bottom: 7px;
        }

        .campus-rating {
            color: #7b808b;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .campus-rating i {
            color: #f6a609;
        }

        .campus-product-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .campus-price {
            color: #11131a;
            font-size: 14px;
            font-weight: 900;
        }

        .campus-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 32px;
            border-radius: 7px;
            border: 0;
            background: #ff6a00;
            color: #fff;
            font-size: 13px;
            font-weight: 800;
            padding: 0 11px;
        }

        .campus-near-row {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
        }

        .campus-near-item {
            display: block;
            aspect-ratio: 1.52 / 1;
            overflow: hidden;
            border-radius: 8px;
            background: #edf0f4;
            box-shadow: 0 10px 24px rgba(21, 28, 42, 0.05);
        }

        @media (max-width: 991px) {
            .campus-card-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 10px;
                margin-bottom: 24px;
            }

            .campus-product-card {
                border-radius: 7px;
            }

            .campus-product-media {
                height: 92px !important;
                max-height: 92px !important;
                aspect-ratio: auto !important;
            }

            .campus-product-body {
                padding: 8px;
            }

            .campus-product-title {
                font-size: 11px;
                min-height: 29px;
                margin-bottom: 5px;
            }

            .campus-rating {
                font-size: 10px;
                margin-bottom: 7px;
            }

            .campus-price {
                font-size: 11px;
            }

            .campus-action {
                min-width: 26px;
                height: 26px;
                font-size: 10px;
                padding: 0 7px;
            }

            .campus-product-badge,
            .campus-discount {
                font-size: 9px;
                min-height: 17px;
                padding: 0 6px;
                left: 6px;
                bottom: 6px;
            }

            .campus-discount {
                right: 6px;
                left: auto;
            }
        }

        @media (max-width: 767px) {
            .campus-home {
                padding-top: 18px;
            }

            .campus-topbar {
                align-items: flex-start;
            }

            .campus-greeting h1 {
                font-size: 21px;
            }

            .campus-logo {
                margin-bottom: 10px;
            }

            .campus-logo img {
                height: 30px;
                max-width: 112px;
            }

            .campus-greeting p,
            .campus-school {
                font-size: 12px;
            }

            .campus-location {
                gap: 10px;
            }

            .campus-search {
                grid-template-columns: 1fr 54px;
            }

            .campus-quick-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 8px;
                margin-bottom: 22px;
                overflow-x: visible;
                padding-bottom: 0;
            }

            .campus-quick-card {
                min-height: 82px;
                gap: 7px;
                font-size: 11px;
                line-height: 1.15;
                text-align: center;
                padding: 10px 4px;
            }

            .campus-quick-card i {
                font-size: 27px;
            }

            .campus-categories {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                column-gap: 8px;
                row-gap: 14px;
                overflow-x: visible;
                padding-bottom: 0;
                margin-bottom: 26px;
            }

            .campus-near-row {
                display: flex;
                overflow-x: auto;
                padding-bottom: 6px;
            }

            .campus-category {
                width: auto;
                min-width: 0;
                flex: initial;
                font-size: 11px;
            }

            .campus-category-icon {
                width: 48px;
                height: 48px;
            }

            .campus-promos {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 24px;
            }

            .campus-promo {
                min-height: 122px;
                padding: 13px;
            }

            .campus-promo::after {
                width: 50%;
            }

            .campus-promo-content {
                width: 64%;
            }

            .campus-promo h3 {
                font-size: 16px;
                margin-bottom: 5px;
            }

            .campus-promo p {
                font-size: 11px;
                line-height: 1.3;
                margin-bottom: 12px;
            }

            .campus-promo .btn {
                font-size: 10px;
                padding: 7px 10px;
            }

            .campus-card-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 10px;
                margin-bottom: 24px;
            }

            .campus-product-media {
                height: 82px !important;
                max-height: 82px !important;
            }

            .campus-near-item {
                width: 132px;
                flex: 0 0 132px;
            }
        }

        @media (max-width: 480px) {
            .campus-shell {
                padding: 0 12px;
            }

            .campus-school-label {
                max-width: 112px;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .campus-promo {
                min-height: 108px;
                padding: 10px;
            }

            .campus-promo-content {
                width: 68%;
            }

            .campus-promo h3 {
                font-size: 15px;
            }

            .campus-promo p {
                font-size: 10px;
                margin-bottom: 9px;
            }

            .campus-promo .btn {
                font-size: 9px;
                padding: 6px 8px;
            }

            .campus-card-grid {
                gap: 8px;
            }

            .campus-product-media {
                height: 74px !important;
                max-height: 74px !important;
            }

            .campus-product-body {
                padding: 7px;
            }
        }
    </style>

    <main class="campus-home">
        <div class="campus-shell">
            <div class="campus-topbar">
                <div class="campus-greeting">
                    <a class="campus-logo" href="{{ route('home') }}" aria-label="{{ env('APP_NAME') }}">
                        <img src="{{ $campusHeaderLogo ? uploaded_asset($campusHeaderLogo) : static_asset('assets/img/logo.png') }}" alt="{{ env('APP_NAME') }}" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/logo.png') }}';">
                    </a>
                    <h1>{{ $greeting }}, {{ $displayName }}</h1>
                    <p>{{ translate('What are you looking for today?') }}</p>
                </div>
                <div class="campus-location">
                    <div class="campus-school-picker" data-campus-school-picker>
                        <button class="campus-school" type="button" aria-haspopup="true" aria-expanded="false" data-campus-school-toggle>
                            <i class="las la-map-marker"></i>
                            <span class="campus-school-label">{{ $currentCampusSchool?->name ?? translate('University of Lagos') }}</span>
                            <i class="las la-angle-down"></i>
                        </button>
                        <div class="campus-school-menu" role="menu">
                            @forelse ($campusSchools as $school)
                                <a class="campus-school-option" role="menuitem" href="{{ route('search', ['school' => $school->slug]) }}">
                                    <span class="campus-school-logo">
                                        @if ($school->logo)
                                            <img src="{{ uploaded_asset($school->logo) }}" alt="{{ $school->name }}" onerror="this.onerror=null;this.parentElement.innerHTML='<i class=&quot;las la-university&quot;></i>';">
                                        @else
                                            <i class="las la-university"></i>
                                        @endif
                                    </span>
                                    <span class="text-truncate">{{ $school->name }}</span>
                                </a>
                            @empty
                                <div class="campus-school-empty">{{ translate('No schools have been added yet.') }}</div>
                            @endforelse
                        </div>
                    </div>
                    <a class="campus-bell" href="{{ route('dashboard') }}">
                        <i class="las la-bell"></i>
                        <span>2</span>
                    </a>
                </div>
            </div>

            <form class="campus-search" action="{{ route('search') }}" method="GET">
                <label class="campus-search-input">
                    <i class="las la-search"></i>
                    <input type="text" name="keyword" placeholder="{{ translate('Search for food, services, products...') }}">
                </label>
                <button type="submit" aria-label="{{ translate('Search') }}"><i class="las la-search"></i></button>
            </form>

            <div class="campus-quick-grid">
                @foreach ($quickTypes as $type)
                    <a class="campus-quick-card {{ $type['active'] ? 'active' : '' }}" href="{{ $type['url'] }}">
                        <i class="las {{ $type['icon'] }}"></i>
                        <span>{{ $type['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <section>
                <div class="campus-section-head">
                    <h2>{{ translate('Popular Categories') }}</h2>
                    <a href="{{ route('categories.all') }}">{{ translate('View all') }}</a>
                </div>
                <div class="campus-categories">
                    @foreach ($shownCategories as $category)
                        <a class="campus-category" href="{{ $category['url'] }}">
                            <span class="campus-category-icon">
                                @if (!empty($category['image']))
                                    <img src="{{ $category['image'] }}" alt="{{ $category['label'] }}" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                @else
                                    <i class="las {{ $category['icon'] ?? 'la-store' }}"></i>
                                @endif
                            </span>
                            <span class="text-truncate w-100">{{ $category['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <div class="campus-promos">
                <article class="campus-promo food">
                    <div class="campus-promo-content">
                        <h3>{{ translate('Hungry?') }}</h3>
                        <p>{{ translate('Get meals delivered fast on campus.') }}</p>
                        <a class="btn" href="{{ route('search') }}?keyword=food">{{ translate('Order Food') }}</a>
                    </div>
                </article>
                <article class="campus-promo service">
                    <div class="campus-promo-content">
                        <h3>{{ translate('Need help?') }}</h3>
                        <p>{{ translate('Book trusted services near you.') }}</p>
                        <a class="btn" href="{{ route('search') }}?keyword=services">{{ translate('Book Service') }}</a>
                    </div>
                </article>
            </div>

            <section>
                <div class="campus-section-head">
                    <h2>{{ translate('Recommended For You') }}</h2>
                    <a href="{{ route('search') }}">{{ translate('See all') }}</a>
                </div>
                <div class="campus-card-grid">
                    @foreach ($campusRecommendedProducts->take(3) as $product)
                        @php
                            $isService = ($product->listing_type ?? null) === 'service';
                            $isFood = ($product->listing_type ?? null) === 'food';
                            $discount = discount_in_percentage($product);
                        @endphp
                        <article class="campus-product-card">
                            <a class="campus-product-media" href="{{ route('product', $product->slug) }}">
                                <img src="{{ get_image($product->thumbnail) }}" alt="{{ $product->getTranslation('name') }}" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                                <span class="campus-product-badge {{ $isService ? 'available' : '' }}">
                                    <i class="las {{ $isService ? 'la-check-circle' : 'la-clock' }}"></i>
                                    {{ $isService ? translate('Available') : ($isFood ? '20-30 min' : translate('Campus')) }}
                                </span>
                                @if ($discount > 0)
                                    <span class="campus-discount">{{ $discount }}% {{ translate('OFF') }}</span>
                                @endif
                            </a>
                            <div class="campus-product-body">
                                <a href="{{ route('product', $product->slug) }}" class="campus-product-title text-truncate-2">{{ $product->getTranslation('name') }}</a>
                                <div class="campus-rating">
                                    <i class="las la-star"></i>
                                    {{ number_format((float) $product->rating, 1) }} ({{ $product->num_of_sale ?: 12 }})
                                </div>
                                <div class="campus-product-foot">
                                    <div class="campus-price">{{ home_discounted_base_price($product) }}</div>
                                    @if ($isService)
                                        <a class="campus-action" href="{{ route('product', $product->slug) }}">{{ translate('Book') }}</a>
                                    @else
                                        <a class="campus-action" href="javascript:void(0)" @if (Auth::check() || get_setting('guest_checkout_activation') == 1) onclick="addToCartSingleProduct({{ $product->id }})" @else onclick="showLoginModal()" @endif>
                                            <i class="las la-plus"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section>
                <div class="campus-section-head">
                    <h2>{{ translate('Popular Near You') }}</h2>
                    <a href="{{ route('search') }}">{{ translate('See all') }}</a>
                </div>
                <div class="campus-near-row">
                    @foreach (($campusPopularProducts->isNotEmpty() ? $campusPopularProducts : $campusRecommendedProducts)->take(5) as $product)
                        <a class="campus-near-item" href="{{ route('product', $product->slug) }}">
                            <img src="{{ get_image($product->thumbnail) }}" alt="{{ $product->getTranslation('name') }}" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const picker = document.querySelector('[data-campus-school-picker]');
            if (!picker) return;

            const toggle = picker.querySelector('[data-campus-school-toggle]');
            const closePicker = function () {
                picker.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            };

            toggle.addEventListener('click', function (event) {
                event.stopPropagation();
                const isOpen = picker.classList.toggle('open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            document.addEventListener('click', function (event) {
                if (!picker.contains(event.target)) {
                    closePicker();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closePicker();
                }
            });
        });
    </script>
@endsection
