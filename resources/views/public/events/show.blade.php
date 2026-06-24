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
                        @if(isset($bibNumber))
                            <a href="{{ route('public.events.show', $event->slug) }}" class="btn btn-outline-danger d-flex align-items-center justify-content-center" title="Ripristina vista intera">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(isset($bibNumber))
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded" style="background-color: rgba(255, 153, 0, 0.15); border: 1px solid var(--timingrun-orange);">
                <span>Risultati per il pettorale <strong>#{{ $bibNumber }}</strong></span>
                <a href="{{ route('public.events.show', $event->slug) }}" class="btn btn-sm btn-orange text-white text-decoration-none px-2 py-0" style="background-color: var(--timingrun-orange); font-weight: bold; border-radius: 4px;">
                    &times; Rimuovi Filtro
                </a>
            </div>
        </div>
    @endif

    @if(isset($bibNumber) && $photos->isEmpty())
        <div class="alert alert-warning text-center" style="background-color: var(--timingrun-orange); color: #111; border: none;">
            Nessuna foto trovata per il pettorale <strong>{{ $bibNumber }}</strong>.
        </div>
    @endif

    <div class="row g-3">
        @forelse ($photos ?? $event->photos as $photo)
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card photo-card" id="photo-card-{{ $photo->id }}">
                    @if($photo->photo_usage_type === 'public')
                        <a href="#" data-bs-toggle="modal" data-bs-target="#photoModal" data-preview-src="{{ asset('uploads/' . $photo->original_path) . '?v=' . $photo->updated_at->timestamp }}">
                            <img src="{{ asset('uploads/' . $photo->thumbnail_path) . '?v=' . $photo->updated_at->timestamp }}" class="card-img-top" alt="Foto dell'evento" style="aspect-ratio: 4/3; object-fit: cover;">
                        </a>
                    @else
                        <a href="#" data-bs-toggle="modal" data-bs-target="#photoModal" data-preview-src="{{ asset('uploads/' . $photo->watermarked_path) . '?v=' . $photo->updated_at->timestamp }}">
                            <div class="position-relative">
                                <img src="{{ asset('uploads/' . $photo->thumbnail_path) . '?v=' . $photo->updated_at->timestamp }}" class="card-img-top" alt="Foto dell'evento" style="aspect-ratio: 4/3; object-fit: cover;">
                                <div class="commercial-overlay"><i class="bi bi-zoom-in"></i></div>
                            </div>
                        </a>
                    @endif
                    
                    @if($photo->photo_usage_type === 'commercial')
                        <div class="card-footer p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check mb-0">
                                    <input class="form-check-input photo-checkbox" type="checkbox" value="{{ $photo->id }}" id="photo-check-{{ $photo->id }}">
                                    <label class="form-check-label small text-white-50 cursor-pointer" for="photo-check-{{ $photo->id }}">
                                        Seleziona
                                    </label>
                                </div>
                                <form action="{{ route('public.cart.add') }}" method="POST" class="m-0">
                                    @csrf
                                    <input type="hidden" name="photo_ids[]" value="{{ $photo->id }}">
                                    <button type="submit" class="btn btn-xs btn-primary py-1 px-2" style="font-size: 0.75rem;">
                                        Acquista Ora
                                    </button>
                                </form>
                            </div>
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

    <!-- Floating Action Bar for multi-selection -->
    <div id="cart-floating-bar" class="cart-floating-bar d-none">
        <div class="container d-flex justify-content-between align-items-center py-2 px-3">
            <div class="floating-bar-info">
                <span class="fw-bold"><span id="selected-count" class="text-orange" style="color: var(--timingrun-orange);">0</span> foto selezionate</span>
                <span class="d-none d-md-inline text-white-50 ms-3">Totale stimato: <span id="estimated-total" class="fw-bold text-success">€0.00</span></span>
            </div>
            <div class="floating-bar-actions d-flex gap-2">
                <button type="button" class="btn btn-outline-light btn-sm rounded-pill" id="btn-deselect-all">Deseleziona tutto</button>
                <form id="bulk-cart-form" action="{{ route('public.cart.add') }}" method="POST" class="m-0">
                    @csrf
                    <div id="bulk-cart-inputs"></div>
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill">Metti nel carrello</button>
                </form>
            </div>
        </div>
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
    // Gestione modale anteprima
    const photoModalEl = document.getElementById('photoModal');
    if (photoModalEl) {
        const modalPhotoImg = document.getElementById('modal-photo-img');
        photoModalEl.addEventListener('show.bs.modal', function (event) {
            const triggerElement = event.relatedTarget;
            const previewSrc = triggerElement.getAttribute('data-preview-src');
            modalPhotoImg.setAttribute('src', previewSrc);
        });
    }

    // Gestione selezione foto
    const checkboxes = document.querySelectorAll('.photo-checkbox');
    const floatingBar = document.getElementById('cart-floating-bar');
    const selectedCountSpan = document.getElementById('selected-count');
    const estimatedTotalSpan = document.getElementById('estimated-total');
    const deselectAllBtn = document.getElementById('btn-deselect-all');
    const bulkInputsContainer = document.getElementById('bulk-cart-inputs');

    function updateSelection() {
        const checkedBoxes = document.querySelectorAll('.photo-checkbox:checked');
        const count = checkedBoxes.length;

        // Aggiorna classi grafiche sulle card
        checkboxes.forEach(cb => {
            const card = document.getElementById('photo-card-' + cb.value);
            if (card) {
                if (cb.checked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            }
        });

        if (count > 0) {
            // Calcola totale stimato con le promozioni
            let total = 0.00;
            if (count >= 5) {
                total = count * 3.20; // Gold Promo
            } else if (count >= 3) {
                total = (count - Math.floor(count / 3)) * 5.00; // 3x2 Promo
            } else {
                total = count * 5.00; // Semplice
            }

            selectedCountSpan.textContent = count;
            estimatedTotalSpan.textContent = '€' + total.toFixed(2);
            floatingBar.classList.remove('d-none');

            // Genera gli input nascosti per la form d'invio
            bulkInputsContainer.innerHTML = '';
            checkedBoxes.forEach(cb => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'photo_ids[]';
                hiddenInput.value = cb.value;
                bulkInputsContainer.appendChild(hiddenInput);
            });
        } else {
            floatingBar.classList.add('d-none');
            bulkInputsContainer.innerHTML = '';
        }
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelection);
    });

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function () {
            checkboxes.forEach(cb => {
                cb.checked = false;
            });
            updateSelection();
        });
    }
});
</script>
@endpush