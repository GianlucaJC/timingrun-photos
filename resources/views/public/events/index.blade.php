@extends('layouts.public')

@section('title', 'Eventi TimingRun')

@section('content')
    <div class="card">
        <div class="card-header">
            <h1 class="mb-0">Eventi Disponibili</h1>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($events as $event)
                <a href="{{ route('public.events.show', $event->slug) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">{{ $event->name }}</h5>
                        <div class="text-nowrap">
                            <span class="badge bg-primary me-2">{{ $event->photos_count }} foto</span>
                            <small class="text-white-50">{{ $event->date->format('d/m/Y') }}</small>
                        </div>
                    </div>
                    <p class="mb-1 text-white-50">{{ $event->location }}</p>
                </a>
            @empty
                <div class="list-group-item">
                    <p class="mb-0 text-center py-4">Nessun evento con foto disponibili al momento.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection