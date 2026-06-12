@extends('layouts.public')

@section('title', $event->name)

@section('content')
    <div class="text-center mb-4">
        <h1 class="mb-0">{{ $event->name }}</h1>
        <p class="text-white-50">{{ $event->date->format('d/m/Y') }} - {{ $event->location }}</p>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title text-center">Cerca il tuo pettorale</h4>
            <form action="{{ route('public.events.search', $event->slug) }}" method="GET" class="d-flex justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <input type="search" name="bib" class="form-control form-control-lg" placeholder="Es. 123" value="{{ $bibNumber ?? '' }}" required>
                        <button class="btn btn-primary" type="submit">Cerca</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(isset($bibNumber) && $photos->isEmpty())
        <div class="alert alert-warning text-center" style="background-color: var(--timingrun-orange); color: #111; border: none;">
            Nessuna foto trovata per il pettorale <strong>{{ $bibNumber }}</strong>.
        </div>
    @endif

    <div class="row g-3">
        @forelse ($photos ?? $event->photos as $photo)
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card photo-card">
                    @if($photo->photo_usage_type === 'public')
                        <a href="#" data-bs-toggle="modal" data-bs-target="#photoModal" data-preview-src="{{ asset('uploads/' . $photo->original_path) . '?v=' . $photo->updated_at->timestamp }}">
                            <img src="{{ asset('uploads/' . $photo->thumbnail_path) . '?v=' . $photo->updated_at->timestamp }}" class="card-img-top" alt="Foto dell'evento" style="aspect-ratio: 4/3; object-fit: cover;">
                        </a>
                    @else
                        <div class="position-relative">
                            <img src="{{ asset('uploads/' . $photo->thumbnail_path) . '?v=' . $photo->updated_at->timestamp }}" class="card-img-top" alt="Foto dell'evento" style="aspect-ratio: 4/3; object-fit: cover;">
                            <div class="commercial-overlay"><i class="bi bi-cart-fill"></i></div>
                        </div>
                    @endif
                    @if($photo->photo_usage_type === 'commercial')
                        <div class="card-footer text-center p-2">
                            {{-- In futuro qui ci sarà il link per l'acquisto --}}
                            <button class="btn btn-sm btn-primary disabled">Acquista Foto</button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
             @if(!isset($bibNumber))
                <div class="col-12">
                    <div class="alert alert-info text-center" style="background-color: var(--timingrun-dark-bg);">
                        Nessuna foto disponibile per questo evento.
                    </div>
                </div>
            @endif
        @endforelse
    </div>

    <!-- Modal per Anteprima Foto -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="background: none; border: none;">
          <div class="modal-body text-center p-0">
            <img src="" id="modal-photo-img" class="img-fluid rounded">
          </div>
        </div>
      </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const photoModalEl = document.getElementById('photoModal');
    if (photoModalEl) {
        const modalPhotoImg = document.getElementById('modal-photo-img');
        photoModalEl.addEventListener('show.bs.modal', function (event) {
            const triggerElement = event.relatedTarget;
            const previewSrc = triggerElement.getAttribute('data-preview-src');
            modalPhotoImg.setAttribute('src', previewSrc);
        });
    }
});
</script>
@endpush