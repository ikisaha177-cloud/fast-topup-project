{{-- Partial: ulasan pembeli untuk satu produk marketplace.
     Pemakaian:
       @include('partials.product-reviews', [
           'product'  => $product,
           'reviews'  => $reviews,
           'myOrder'  => $myOrder,   // MarketplaceOrder|null: pesanan Selesai milik user
           'myReview' => $myReview,  // Review|null: ulasan user untuk produk ini
       ])

     Kejujuran yang dijaga partial ini:
       - Angka rating hanya muncul bila ada ulasan nyata.
       - Tanpa ulasan -> "Belum ada ulasan" (bukan "0.0" atau bintang kosong).
       - Daftar ulasan selalu menyebut berapa yang ditampilkan dari total. --}}
@php
    $summary  = $product->ratingSummary();
    $count    = $summary['count'];
    $average  = $summary['average'];
    $reviews  = $reviews ?? collect();
    $myOrder  = $myOrder ?? null;
    $myReview = $myReview ?? null;
@endphp

<div class="card review-panel" id="ulasan">
    <div class="review-head">
        <div>
            <h2 class="review-title">Ulasan Pembeli</h2>
            <p class="muted review-subtitle mb-0">Hanya dari pembeli yang pesanannya sudah Selesai.</p>
        </div>

        <div class="review-summary">
            @if($count > 0)
                @include('partials.rating-stars', ['rating' => $average, 'count' => $count])
                <span class="review-score">{{ number_format($average, 1, ',', '.') }}</span>
                <span class="muted review-count">
                    dari 5 &middot; {{ number_format($count) }} ulasan
                </span>
            @else
                <span class="review-empty">Belum ada ulasan</span>
            @endif
        </div>
    </div>

    @if($count === 0)
        <p class="muted mb-0">
            Produk ini belum pernah diulas. Ulasan hanya bisa ditulis oleh pembeli
            yang pesanannya sudah selesai, jadi tidak ada rating yang dikarang di sini.
        </p>
    @else
        <div class="review-list">
            @foreach($reviews as $review)
                <div class="review-item">
                    <div class="review-item-head">
                        <span class="review-author">{{ $review->reviewerLabel() }}</span>
                        @include('partials.rating-stars', ['rating' => $review->rating])
                        <span class="badge badge-success badge-plain review-verified">
                            @include('partials.icon', ['name' => 'check', 'size' => 12]) Pembelian terverifikasi
                        </span>
                        <span class="review-date muted">
                            {{ $review->created_at->format('d-m-Y') }}@if($review->wasEdited()) &middot; disunting @endif
                        </span>
                    </div>
                    @if($review->comment)
                        <p class="review-comment">{{ $review->comment }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        @if($count > $reviews->count())
            <p class="muted review-more mb-0">
                Menampilkan {{ $reviews->count() }} ulasan terbaru dari {{ number_format($count) }} ulasan.
            </p>
        @endif
    @endif

    @auth
        @if(! auth()->user()->isAdmin())
            @if($myOrder)
                <form action="{{ route('reviews.store', $myOrder->order_code) }}" method="POST" class="review-form">
                    @csrf
                    <input type="hidden" name="marketplace_product_id" value="{{ $product->id }}">

                    <h3 class="review-form-title">
                        {{ $myReview ? 'Perbarui ulasanmu' : 'Beri ulasan untuk produk ini' }}
                    </h3>
                    <p class="muted review-form-hint">
                        Dari pesanan <span class="mono">{{ $myOrder->order_code }}</span>
                        yang sudah selesai.
                    </p>

                    <fieldset class="rating-field">
                        <legend class="form-label">Rating</legend>
                        <div class="rating-input">
                            @foreach(\App\Models\Review::ratings() as $value)
                                <input type="radio"
                                       id="rating-{{ $product->id }}-{{ $value }}"
                                       name="rating"
                                       value="{{ $value }}"
                                       aria-label="{{ $value }} dari 5 bintang"
                                       {{ (int) old('rating', $myReview->rating ?? 0) === $value ? 'checked' : '' }}
                                       required>
                                <label for="rating-{{ $product->id }}-{{ $value }}" aria-hidden="true">&#9733;</label>
                            @endforeach
                        </div>
                        @error('rating') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </fieldset>

                    <div class="form-group">
                        <label class="form-label" for="review-comment">Komentar (opsional)</label>
                        <textarea id="review-comment" name="comment" class="form-control" rows="3" maxlength="1000"
                                  placeholder="Ceritakan pengalamanmu dengan produk ini...">{{ old('comment', $myReview->comment ?? '') }}</textarea>
                        @error('comment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        {{ $myReview ? 'Perbarui Ulasan' : 'Kirim Ulasan' }}
                    </button>
                </form>
            @elseif($count === 0)
                <p class="muted review-cta-hint mb-0">
                    Sudah pernah membeli produk ini? Ulasanmu muncul di sini setelah
                    pesananmu berstatus Selesai.
                </p>
            @endif
        @endif
    @endauth
</div>
