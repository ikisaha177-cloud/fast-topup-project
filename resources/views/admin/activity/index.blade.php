@extends('layouts.app')

@section('title', 'Log Aktivitas - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Log Aktivitas</h1>
                <p class="section-subtitle mb-0">Rekam jejak aksi admin untuk audit.</p>
            </div>
        </div>
        @include('partials.admin-nav')

        <div class="card">
            <form action="{{ route('admin.activity.index') }}" method="GET" class="toolbar">
                <div class="searchbar">
                    <input type="text" name="q" value="{{ $search }}" placeholder="Cari deskripsi, aksi, atau admin...">
                </div>
                <button type="submit" class="btn btn-primary">Cari</button>
                @if($search !== '')
                    <a href="{{ route('admin.activity.index') }}" class="btn btn-ghost">Reset</a>
                @endif
            </form>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Admin</th>
                            <th>Aksi</th>
                            <th>Deskripsi</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="muted nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $log->user->name ?? 'System' }}</td>
                                <td><span class="badge badge-info">{{ $log->action }}</span></td>
                                <td class="wrap">{{ $log->description }}</td>
                                <td class="muted mono">{{ $log->ip }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center muted">Belum ada aktivitas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
