@extends('layouts.public')

@section('title', 'Il Tuo Carrello')

@section('content')
<div class="row g-4">
    <div class="col-12 mb-2">
        <h1 class="h2 d-flex align-items-center gap-2">
            <i class="bi bi-cart3 text-orange" style="color: var(--timingrun-orange);"></i>
            Il Tuo Carrello
        </h1>
        <p class="text-white-50">Seleziona e configura le tue foto commerciali prima dell'acquisto simulato.</p>
    </div>

    @if(session('success'))
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show border-0" role="alert" style="background-color: var(--timingrun-green); color: #111;">
                <strong><i class="bi bi-check-circle-fill"></i> Successo!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show border-0" role="alert" style="background-color: #ff3333; color: #fff;">
                <strong><i class="bi bi-exclamation-triangle-fill"></i> Errore!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if($photos->isEmpty())
        <div class="col-12 text-center py-5">
            <div class="card p-5 border-dashed border-2">
                <div class="card-body">
                    <i class="bi bi-cart-x text-white-50" style="font-size: 4rem;"></i>
                    <h3 class="mt-3">Il carrello è vuoto</h3>
                    <p class="text-white-50 mb-4">Non hai ancora selezionato alcuna foto commerciale per l'acquisto.</p>
                    <a href="{{ route('public.events.index') }}" class="btn btn-primary btn-lg rounded-pill">
                        Sfoglia gli Eventi
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- Lista Elementi Carrello -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Foto Selezionate ({{ $photos->count() }})</h5>
                    <form action="{{ route('public.cart.clear') }}" method="POST" onsubmit="return confirm('Sei sicuro di voler svuotare il carrello?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                            <i class="bi bi-trash"></i> Svuota Carrello
                        </button>
                    </form>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($photos as $photo)
                        <div class="list-group-item p-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#photoModal" data-preview-src="{{ asset('uploads/' . $photo->watermarked_path) . '?v=' . $photo->updated_at->timestamp }}">
                                    <img src="{{ asset('uploads/' . $photo->thumbnail_path) . '?v=' . $photo->updated_at->timestamp }}" 
                                         alt="Thumbnail" 
                                         class="rounded" 
                                         style="width: 80px; aspect-ratio: 4/3; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                                </a>
                                <div>
                                    <h6 class="mb-1 text-white">{{ $photo->event->name }}</h6>
                                    <p class="mb-0 small text-white-50">
                                        File: {{ basename($photo->original_path) }}
                                        @if($photo->bibs->isNotEmpty())
                                            | Pettorale: 
                                            @foreach($photo->bibs as $bib)
                                                <span class="badge bg-secondary">#{{ $bib->bib_number }}</span>
                                            @endforeach
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between justify-content-sm-end gap-3">
                                <span class="fw-bold">€5.00</span>
                                <form action="{{ route('public.cart.remove', $photo->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Rimuovi dal carrello">
                                        <i class="bi bi-x-circle-fill fs-5"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Sezione Promozioni -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Seleziona la tua Promozione</h5>
                </div>
                <div class="card-body">
                    <form id="promo-form" action="{{ route('public.cart.set_promotion') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            @foreach($totals as $key => $data)
                                <div class="col-md-12">
                                    <div class="card promo-card h-100 {{ !$data['eligible'] ? 'opacity-50' : '' }} {{ $activePromotion === $key ? 'border-primary bg-dark-green' : '' }}" 
                                         style="border-width: 2px; transition: all 0.2s ease;">
                                        <div class="card-body d-flex align-items-start gap-3 p-3">
                                            <div class="form-check mt-1">
                                                <input class="form-check-input promo-radio" 
                                                       type="radio" 
                                                       name="promotion" 
                                                       value="{{ $key }}" 
                                                       id="promo-{{ $key }}"
                                                       {{ $activePromotion === $key ? 'checked' : '' }}
                                                       {{ !$data['eligible'] ? 'disabled' : '' }}>
                                            </div>
                                            <div class="flex-grow-1 cursor-pointer" onclick="if({{ $data['eligible'] ? 'true' : 'false' }}) document.getElementById('promo-{{ $key }}').click();">
                                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                                    <h6 class="mb-0 fw-bold">{{ $data['label'] }}</h6>
                                                    <span class="badge bg-success" style="font-size: 0.9rem;">
                                                        Totale Promo: €{{ number_format($data['value'], 2) }}
                                                    </span>
                                                </div>
                                                <p class="small text-white-50 mb-1 mt-1">{{ $data['description'] }}</p>
                                                @if($data['discount'] > 0)
                                                    <span class="text-success small fw-bold">
                                                        <i class="bi bi-tag-fill"></i> Risparmi €{{ number_format($data['discount'], 2) }}!
                                                    </span>
                                                @endif
                                                @if(!$data['eligible'])
                                                    @if($key === 'promo_3x2')
                                                        <span class="text-danger small fw-bold d-block mt-1">
                                                            <i class="bi bi-info-circle-fill"></i> Sblocca questa promo selezionando almeno 3 foto.
                                                        </span>
                                                    @elseif($key === 'promo_gold')
                                                        <span class="text-danger small fw-bold d-block mt-1">
                                                            <i class="bi bi-info-circle-fill"></i> Sblocca questa promo selezionando almeno 5 foto.
                                                        </span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Riepilogo e Checkout -->
        <div class="col-lg-4">
            <div class="card position-sticky" style="top: 90px;">
                <div class="card-header">
                    <h5 class="mb-0">Riepilogo Ordine</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-white-50">Prezzo originale ({{ $photos->count() }} foto):</span>
                        <span class="text-decoration-line-through">€{{ number_format($photos->count() * 5.00, 2) }}</span>
                    </div>
                    
                    @if($totalData['discount'] > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-success">Sconto ({{ $totalData['label'] }}):</span>
                            <span class="text-success fw-bold">-€{{ number_format($totalData['discount'], 2) }}</span>
                        </div>
                    @endif
                    
                    <hr class="border-secondary">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fs-5 fw-bold">Totale da Pagare:</span>
                        <span class="fs-4 fw-bold text-orange" style="color: var(--timingrun-orange);">€{{ number_format($totalData['value'], 2) }}</span>
                    </div>

                    <button type="button" class="btn btn-primary w-100 btn-lg rounded-pill mb-3" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                        Procedi all'acquisto
                    </button>
                    
                    <a href="{{ route('public.events.index') }}" class="btn btn-outline-light w-100 rounded-pill">
                        Continua a cercare
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Modal di Checkout Simulato -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #1a1a1a; border: 1px solid rgba(255,255,255,0.1);">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="checkoutModalLabel text-orange" style="color: var(--timingrun-orange);">
                    <i class="bi bi-credit-card-2-front-fill me-2"></i>
                    Simulazione Pagamento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-wallet2 text-success" style="font-size: 3rem;"></i>
                <h4 class="mt-3">Simula l'Acquisto</h4>
                <p class="text-white-50">Stai per acquistare <strong>{{ $photos ?? 0 ? $photos->count() : 0 }}</strong> foto per un totale di <strong class="text-orange" style="color: var(--timingrun-orange);">€{{ number_format($totalData['value'] ?? 0, 2) }}</strong>.</p>
                
                <div class="alert alert-info border-0 text-start small mb-0" style="background-color: rgba(153, 204, 51, 0.1); border-left: 4px solid var(--timingrun-green) !important; color: #eee;">
                    <i class="bi bi-info-circle-fill text-green me-1" style="color: var(--timingrun-green);"></i>
                    <strong>Nota:</strong> Questo è un ambiente dimostrativo. Cliccando su "Conferma Acquisto", l'ordine verrà simulato come completato e il carrello verrà svuotato.
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-light rounded-pill" data-bs-dismiss="modal">Annulla</button>
                <form action="{{ route('public.cart.clear') }}" method="POST" class="m-0">
                    @csrf
                    <input type="hidden" name="simulation" value="true">
                    <button type="submit" class="btn btn-primary rounded-pill">Conferma Acquisto</button>
                </form>
            </div>
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

@push('styles')
<style>
.bg-dark-green {
    background-color: rgba(153, 204, 51, 0.08) !important;
}
.promo-card:hover:not(.opacity-50) {
    border-color: var(--timingrun-green) !important;
    transform: translateY(-2px);
}
.border-primary {
    border-color: var(--timingrun-green) !important;
}
.cursor-pointer {
    cursor: pointer;
}
.border-dashed {
    border-style: dashed !important;
}
</style>
@endpush

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

    // Invio automatico al cambio della promo
    const promoForm = document.getElementById('promo-form');
    const radios = document.querySelectorAll('.promo-radio');
    radios.forEach(radio => {
        radio.addEventListener('change', function () {
            if (promoForm) {
                promoForm.submit();
            }
        });
    });
});
</script>
@endpush
