@if ($product->school)
    <div class="text-center mt-1">
        <a href="{{ route('products.school', $product->school->slug) }}"
            class="fs-12 fw-600 text-info d-inline-block text-truncate mw-100"
            title="{{ $product->school->name }}">
            {{ $product->school->name }}
        </a>
    </div>
@endif
