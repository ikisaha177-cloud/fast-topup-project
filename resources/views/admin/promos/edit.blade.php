@extends('layouts.app')

@section('title', 'Edit Kode Promo - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Edit Kode Promo</h1>
                <p class="section-subtitle mb-0">Perbarui syarat potongan kode <strong class="mono">{{ $promo->code }}</strong>.</p>
            </div>
        </div>
        @include('partials.admin-nav')

        <div class="card" style="max-width:640px;">
            <div class="promo-stats muted" style="margin-bottom:16px;">
                Terpakai <strong>{{ $promo->used_count }}</strong>{{ $promo->usage_limit ? ' dari ' . $promo->usage_limit : '' }} kali.
                Status saat ini: <strong>{{ $promo->statusLabel() }}</strong>.
            </div>

            <form action="{{ route('admin.promos.update', $promo->id) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.promos._form', ['promo' => $promo])
                <button type="submit" class="btn btn-primary btn-block mt-16">Update</button>
                <a href="{{ route('admin.promos.index') }}" class="btn btn-outline btn-block mt-16">Batal</a>
            </form>
        </div>
    </div>
</section>
@endsection
