@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h3 class="h3">{{ translate('Seller Package Subscriptions') }}</h3>
        </div>
    </div>
</div>

<!-- Revenue Summary -->
<div class="row gutters-16 mb-3">
    <div class="col-md-4">
        <div class="card bg-soft-success border-0">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="fs-20 fw-600 text-success mb-1">{{ single_price($total_revenue) }}</h2>
                    <p class="fs-13 text-secondary mb-0">{{ translate('Total Revenue (Approved)') }}</p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-soft-primary border-0">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="fs-20 fw-600 text-primary mb-1">{{ $subscriptions->total() }}</h2>
                    <p class="fs-13 text-secondary mb-0">{{ translate('Total Subscriptions (Filtered)') }}</p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#6610f2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0" style="background: #fff3cd;">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="fs-20 fw-600 text-warning mb-1">
                        {{ single_price($subscriptions->sum('amount')) }}
                    </h2>
                    <p class="fs-13 text-secondary mb-0">{{ translate('Filtered Results Amount') }}</p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ffc107" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Filter Subscriptions') }}</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('seller_packages.subscriptions') }}">
            <div class="row gutters-10">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="fs-12 fw-600 text-secondary">{{ translate('Package') }}</label>
                        <select name="package_id" class="form-control form-control-sm aiz-selectpicker">
                            <option value="">{{ translate('All Packages') }}</option>
                            @foreach ($seller_packages as $package)
                                <option value="{{ $package->id }}" {{ request('package_id') == $package->id ? 'selected' : '' }}>
                                    {{ $package->getTranslation('name') }} — {{ single_price($package->amount) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="fs-12 fw-600 text-secondary">{{ translate('Payment Method') }}</label>
                        <select name="payment_method" class="form-control form-control-sm aiz-selectpicker">
                            <option value="">{{ translate('All Methods') }}</option>
                            <option value="cash_on_delivery" {{ request('payment_method') == 'cash_on_delivery' ? 'selected' : '' }}>{{ translate('Cash on Delivery') }}</option>
                            <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>{{ translate('Bank Transfer') }}</option>
                            <option value="paypal" {{ request('payment_method') == 'paypal' ? 'selected' : '' }}>{{ translate('PayPal') }}</option>
                            <option value="stripe" {{ request('payment_method') == 'stripe' ? 'selected' : '' }}>{{ translate('Stripe') }}</option>
                            <option value="wallet" {{ request('payment_method') == 'wallet' ? 'selected' : '' }}>{{ translate('Wallet') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="fs-12 fw-600 text-secondary">{{ translate('Status') }}</label>
                        <select name="approval" class="form-control form-control-sm aiz-selectpicker">
                            <option value="">{{ translate('All Statuses') }}</option>
                            <option value="1" {{ request('approval') === '1' ? 'selected' : '' }}>{{ translate('Approved') }}</option>
                            <option value="0" {{ request('approval') === '0' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-group w-100">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">{{ translate('Filter') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Subscriptions Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('All Subscriptions') }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Seller') }}</th>
                        <th>{{ translate('Package') }}</th>
                        <th>{{ translate('Amount Paid') }}</th>
                        <th data-breakpoints="md">{{ translate('Payment Method') }}</th>
                        <th data-breakpoints="md">{{ translate('Type') }}</th>
                        <th data-breakpoints="lg">{{ translate('Date') }}</th>
                        <th>{{ translate('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subscriptions as $key => $payment)
                        <tr>
                            <td>{{ ($key + 1) + ($subscriptions->currentPage() - 1) * $subscriptions->perPage() }}</td>
                            <td>
                                @if ($payment->user)
                                    <div class="d-flex align-items-center">
                                        <div class="symbol size-30px rounded-circle overflow-hidden mr-2">
                                            <img src="{{ uploaded_asset($payment->user->avatar_original) }}"
                                                alt="{{ $payment->user->name }}"
                                                class="h-100 img-fit"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                        </div>
                                        <div>
                                            <span class="d-block fw-600">{{ $payment->user->name }}</span>
                                            <span class="fs-11 text-secondary">{{ $payment->user->email }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">{{ translate('User Removed') }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($payment->seller_package)
                                    <span class="badge badge-soft-primary">{{ $payment->seller_package->getTranslation('name') }}</span>
                                @else
                                    <span class="text-muted">{{ translate('Package Removed') }}</span>
                                @endif
                            </td>
                            <td class="fw-600 text-success">{{ single_price($payment->amount) }}</td>
                            <td>{{ $payment->payment_method ? ucwords(str_replace('_', ' ', $payment->payment_method)) : '—' }}</td>
                            <td>
                                @if ($payment->offline_payment == 1)
                                    <span class="badge badge-soft-warning">{{ translate('Offline') }}</span>
                                @else
                                    <span class="badge badge-soft-info">{{ translate('Online') }}</span>
                                @endif
                            </td>
                            <td>{{ $payment->created_at ? $payment->created_at->format('d M Y') : '—' }}</td>
                            <td>
                                @if ($payment->approval == 1)
                                    <span class="badge badge-soft-success">{{ translate('Approved') }}</span>
                                @else
                                    <span class="badge badge-soft-danger">{{ translate('Pending') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <img src="{{ static_asset('assets/img/empty-cart.png') }}" alt="{{ translate('No Data') }}" class="mb-3" style="height: 80px; opacity: 0.4;" onerror="this.style.display='none'">
                                <p class="text-muted mb-0">{{ translate('No subscriptions found') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="aiz-pagination px-3">
            {{ $subscriptions->links() }}
        </div>
    </div>
</div>
@endsection
