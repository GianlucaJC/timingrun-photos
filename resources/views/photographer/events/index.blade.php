@extends('layouts.app')

@section('title', 'Seleziona Evento')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h1>Seleziona un Evento</h1>
        </div>
        <div class="list-group list-group-flush">
            @forelse ($events as $event)
                <a href="{{ route('photographer.events.show', $event) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">{{ $event->name }}</h5>
                        <div class="text-nowrap">
                            <span class="badge bg-primary me-2">{{ $event->photos_count }} foto</span>
                            <small>{{ $event->date->format('d/m/Y') }}</small>
                        </div>
                    </div>
                    <p class="mb-1">{{ $event->location }}</p>
                </a>
            @empty
                <div class="list-group-item">
                    @if(session('error'))
                        <p class="mb-0 text-danger">{{ session('error') }}</p>
                    @else
                        <p class="mb-0">Nessun evento concluso trovato. L'API non ha restituito eventi o non ci sono eventi che corrispondono ai criteri (pubblicati e con data passata).</p>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
@endsection