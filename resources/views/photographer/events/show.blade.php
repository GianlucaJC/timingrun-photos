@extends('layouts.app')

@section('title', 'Upload Foto per ' . $event->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-0">{{ $event->name }}</h1>
            <p class="text-muted">{{ $event->date->format('d/m/Y') }} - {{ $event->location }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('photographer.events.index') }}" class="btn btn-secondary">Indietro</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Impostazioni Evento</div>
        <div class="card-body">
            <form action="{{ route('photographer.events.update', $event) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label fw-bold">Tipo di utilizzo delle foto</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="photo_usage_type" id="type_commercial" value="commercial" {{ $event->photo_usage_type === 'commercial' ? 'checked' : '' }}>
                        <label class="form-check-label" for="type_commercial">
                            <strong>Uso Commerciale:</strong> Le foto avranno un watermark e saranno destinate alla vendita.
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="photo_usage_type" id="type_public" value="public" {{ $event->photo_usage_type === 'public' ? 'checked' : '' }}>
                        <label class="form-check-label" for="type_public">
                            <strong>Uso Pubblico:</strong> Le foto saranno gratuite, senza watermark e visualizzabili a risoluzione originale.
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-info">Salva Impostazioni</button>
            </form>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h2>Carica Foto</h2>
        </div>
        <div class="card-body">
            <form id="upload-form" action="{{ route('photographer.photos.store', $event) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="photos-input" class="form-label">Seleziona le foto da caricare (formato JPG, PNG)</label>
                    <input class="form-control" type="file" id="photos-input" name="photos[]" multiple required>
                </div>
                <button type="submit" class="btn btn-primary">Carica Foto</button>
            </form>
        </div>
    </div>

    <div id="upload-progress-container" class="mt-4" style="display: none;">
        <div id="global-progress-section" class="mb-4">
            <h5>Progresso Totale</h5>
            <div class="progress" style="height: 25px;">
                <div id="global-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            <small id="global-progress-text" class="d-block text-end mt-1"></small>
        </div>

        <h5 class="mt-3">Caricamenti individuali</h5>
        <div id="progress-list" style="max-height: 250px; overflow-y: auto; padding-right: 15px;">
            {{-- I progress bar individuali verranno aggiunti qui dal JS --}}
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h2>Foto Caricate ({{ $photosByPhotographer->flatten()->count() }})</h2>
        </div>
        <div class="card-body">
            @if($photosByPhotographer->isEmpty())
                <p>Nessuna foto è stata ancora caricata per questo evento.</p>
            @else
                <div id="photo-gallery">
                    @foreach ($photosByPhotographer as $photographerName => $photos)
                        <div class="mb-5">
                            <h3 class="border-bottom pb-2 mb-3">
                                Foto di: {{ $photographerName }}
                                @if ($photos->first() && $photos->first()->user_id === Auth::id())
                                    <span class="badge bg-success ms-2">Le tue foto</span>
                                @endif
                            </h3>

                            <div class="row">
                                @foreach ($photos as $photo)
                                    <div class="col-6 col-md-4 col-lg-3 mb-4" id="photo-card-{{ $photo->id }}">
                                        <div class="card h-100">
                                            <div class="position-relative">
                                                <img src="{{ ($photo->admin_thumbnail_path ? Storage::url($photo->admin_thumbnail_path) : Storage::url($photo->original_path)) . '?v=' . $photo->updated_at->timestamp }}"
                                                     class="card-img-top" alt="Thumbnail" style="aspect-ratio: 4/3; object-fit: cover; cursor: pointer; {{ $photo->status !== 'published' ? 'opacity: 0.5;' : '' }}"
                                                     data-bs-toggle="modal"
                                                     data-bs-target="#photoModal"
                                                     data-preview-src="{{ Storage::url($photo->original_path) . '?v=' . $photo->updated_at->timestamp }}"
                                                >
                                                @if($photo->status !== 'published')
                                                    <span class="position-absolute top-0 start-50 translate-middle-x badge rounded-pill bg-warning text-dark mt-2">
                                                        @if($photo->status === 'pending')
                                                            In attesa
                                                        @elseif($photo->status === 'processing')
                                                            In elaborazione...
                                                        @elseif($photo->status === 'error')
                                                            Errore
                                                        @else
                                                            {{ $photo->status }}
                                                        @endif
                                                    </span>
                                                @endif
                                                <div class="photo-overlay-status" id="photo-overlay-{{ $photo->id }}"></div>
                                            </div>

                                            @if (Auth::id() === $photo->user_id)
                                                <div class="card-body p-2">
                                                    <select class="form-select form-select-sm usage-type-select" data-photo-id="{{ $photo->id }}" data-update-url="{{ route('photographer.photos.usage.update', $photo) }}">
                                                        <option value="commercial" {{ $photo->photo_usage_type === 'commercial' ? 'selected' : '' }}>Commerciale</option>
                                                        <option value="public" {{ $photo->photo_usage_type === 'public' ? 'selected' : '' }}>Pubblico</option>
                                                    </select>
                                                </div>
                                                <div class="card-footer p-2 d-flex justify-content-between align-items-center">
                                                    <small class="text-muted" id="photo-status-text-{{ $photo->id }}">
                                                        ID: {{ $photo->id }}
                                                    </small>
                                                    <button class="btn btn-sm btn-danger delete-photo-btn" data-delete-url="{{ route('photographer.photos.destroy', $photo) }}" title="Elimina foto">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            @else
                                                {{-- Per le foto di altri, mostriamo solo l'ID e non i controlli --}}
                                                <div class="card-footer p-2">
                                                    <small class="text-muted">ID: {{ $photo->id }}</small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Modal per Anteprima Foto -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center p-0">
            <img src="" id="modal-photo-img" class="img-fluid">
          </div>
        </div>
      </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('upload-form');
    const input = document.getElementById('photos-input');
    const progressContainer = document.getElementById('upload-progress-container');
    const progressList = document.getElementById('progress-list');
    const uploadUrl = form.action;

    // Elementi per la progress bar globale
    const globalProgressBar = document.getElementById('global-progress-bar');
    const globalProgressText = document.getElementById('global-progress-text');

    let totalSize = 0;
    let totalUploaded = 0;
    const uploadedPerFile = {};

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const files = input.files;
        if (files.length === 0) {
            Swal.fire('Attenzione', 'Per favore, seleziona almeno una foto.', 'warning');
            return;
        }

        // Reset per i caricamenti multipli
        progressContainer.style.display = 'block';
        progressList.innerHTML = '';
        totalSize = Array.from(files).reduce((acc, file) => acc + file.size, 0);
        totalUploaded = 0;
        Object.keys(uploadedPerFile).forEach(key => delete uploadedPerFile[key]);

        // Reset e mostra la progress bar globale
        globalProgressBar.style.width = '0%';
        globalProgressBar.innerText = '0%';
        globalProgressBar.classList.remove('bg-success', 'bg-danger');
        globalProgressBar.classList.add('progress-bar-striped', 'progress-bar-animated');
        globalProgressText.innerText = `Inizio caricamento di ${files.length} file...`;

        Array.from(files).forEach((file, index) => {
            const progressElement = document.createElement('div');
            // Aggiungo un ID all'elemento contenitore per poterlo rimuovere
            progressElement.id = `progress-element-${index}`;
            progressElement.innerHTML = `
                <div class="mb-2">
                    <small>${file.name}</small>
                    <div class="progress" style="height: 20px;">
                        <div id="progress-bar-${index}" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                </div>
            `;
            progressList.appendChild(progressElement);
        });

        uploadFiles(files);
    });

    async function uploadFiles(files) {
        // Esegue tutti i caricamenti in parallelo
        const uploadPromises = Array.from(files).map((file, index) => uploadFile(file, index));
        
        try {
            await Promise.all(uploadPromises);
            // Questo blocco viene eseguito se TUTTI i caricamenti hanno successo
            globalProgressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');
            globalProgressBar.classList.add('bg-success');
            globalProgressBar.innerText = 'Completato!';
            globalProgressText.innerText = `Tutti i ${files.length} file sono stati caricati con successo.`;
        } catch (error) {
            // Questo blocco viene eseguito se ANCHE SOLO UNO dei caricamenti fallisce
            globalProgressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');
            globalProgressBar.classList.add('bg-danger');
            globalProgressBar.innerText = 'Errore';
            globalProgressText.innerText = 'Alcuni file non sono stati caricati. Controlla la lista qui sotto.';
            console.error("Uno o più caricamenti sono falliti.", error);
        }
    }

    function uploadFile(file, index) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('photo', file);
            formData.append('_token', '{{ csrf_token() }}');
            const progressBar = document.getElementById(`progress-bar-${index}`);

            axios.post(uploadUrl, formData, {
                onUploadProgress: progressEvent => {
                    // Progresso individuale
                    const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    progressBar.style.width = percent + '%';
                    progressBar.innerText = percent + '%';

                    // Progresso globale
                    uploadedPerFile[index] = progressEvent.loaded;
                    totalUploaded = Object.values(uploadedPerFile).reduce((acc, loaded) => acc + loaded, 0);
                    const globalPercent = Math.round((totalUploaded * 100) / totalSize);
                    
                    globalProgressBar.style.width = globalPercent + '%';
                    globalProgressBar.innerText = globalPercent + '%';
                    
                    globalProgressText.innerText = `Caricati ${Math.round(totalUploaded / 1024 / 1024)} MB di ${Math.round(totalSize / 1024 / 1024)} MB`;
                }
            }).then(response => {
                if (response.data.success) {
                    progressBar.classList.add('bg-success');
                    progressBar.innerText = 'Completato';
                    // Rimuove la barra di progresso dopo 2 secondi con una dissolvenza
                    setTimeout(() => {
                        const elementToRemove = document.getElementById(`progress-element-${index}`);
                        if (elementToRemove) {
                            elementToRemove.style.transition = 'opacity 0.5s ease';
                            elementToRemove.style.opacity = '0';
                            setTimeout(() => elementToRemove.remove(), 500);
                        }
                    }, 2000);
                } else if (response.data.skipped) {
                    progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                    progressBar.classList.add('bg-info', 'text-dark');
                    progressBar.innerText = 'Già presente';
                }
                resolve(response.data); // Risolvo comunque per non far fallire Promise.all
            }).catch(error => {
                progressBar.classList.add('bg-danger');
                progressBar.innerText = 'Errore';
                console.error('Errore caricamento per ' + file.name + ':', error.response?.data);
                reject(error); // Rifiuta la promise per far scattare il catch in Promise.all
            });
        });
    }

    // --- Gestione Galleria Foto (Modal e Cancellazione) ---
    const photoGallery = document.getElementById('photo-gallery');

    if (photoGallery) {
        const photoModalEl = document.getElementById('photoModal');
        const modalPhotoImg = document.getElementById('modal-photo-img');

        // Listener per popolare la modale prima che venga mostrata
        photoModalEl.addEventListener('show.bs.modal', function (event) {
            const triggerElement = event.relatedTarget; // L'elemento che ha attivato la modale (l'immagine)
            const previewSrc = triggerElement.getAttribute('data-preview-src');
            modalPhotoImg.setAttribute('src', previewSrc);
        });

        // Listener per la cancellazione con event delegation
        photoGallery.addEventListener('click', function(e) {
            // Cerca il pulsante anche se si clicca sull'icona interna
            const deleteButton = e.target.closest('.delete-photo-btn');

            if (deleteButton) {
                e.preventDefault();
                const deleteUrl = deleteButton.dataset.deleteUrl;
                
                Swal.fire({
                    title: 'Sei sicuro?',
                    text: "Vuoi davvero eliminare questa foto? L'azione è irreversibile.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sì, elimina!',
                    cancelButtonText: 'Annulla'
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteButton.disabled = true;
                        deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                        axios.delete(deleteUrl)
                            .then(response => {
                                if (response.data.success) {
                                    const photoCard = deleteButton.closest('.col-6');
                                    photoCard.style.transition = 'opacity 0.5s ease';
                                    photoCard.style.opacity = '0';
                                    setTimeout(() => photoCard.remove(), 500);
                                } else {
                                    Swal.fire('Errore', 'Si è verificato un errore durante la cancellazione.', 'error');
                                    deleteButton.disabled = false;
                                    deleteButton.innerHTML = '<i class="bi bi-trash"></i>';
                                }
                            })
                            .catch(error => {
                                console.error('Errore durante la cancellazione:', error);
                                Swal.fire('Errore', 'Si è verificato un errore di rete durante la cancellazione.', 'error');
                                deleteButton.disabled = false;
                                deleteButton.innerHTML = '<i class="bi bi-trash"></i>';
                            });
                    }
                });
            }
        });
    }

    // --- Gestione Cambio Tipo Utilizzo Foto ---
    if (photoGallery) {
        photoGallery.addEventListener('change', function(e) {
            if (e.target.classList.contains('usage-type-select')) {
                const select = e.target;
                const updateUrl = select.dataset.updateUrl;
                const photoId = select.dataset.photoId;
                const usageType = select.value;
                const photoCard = document.getElementById(`photo-card-${photoId}`);
                const overlay = document.getElementById(`photo-overlay-${photoId}`);

                // Aggiungi un feedback visivo
                if(photoCard) photoCard.style.opacity = '0.5';
                select.disabled = true;

                axios.patch(updateUrl, {
                    photo_usage_type: usageType,
                    _token: '{{ csrf_token() }}'
                })
                .then(response => {
                    if (response.data.success) {
                        console.log(`Foto ${photoId}: ${response.data.message}`);

                        // Rimuovi il feedback visivo di "inibizione"
                        if(photoCard) photoCard.style.opacity = '1';
                        select.disabled = false;

                        // Aggiorna l'attributo src dell'immagine per forzare il ricaricamento
                        // con il nuovo timestamp (cache busting)
                        const newTimestamp = response.data.updated_at_timestamp;
                        const imgElement = photoCard.querySelector('.card-img-top');
                        if (imgElement && newTimestamp) {
                            let currentSrc = imgElement.getAttribute('src');
                            // Rimuovi il vecchio timestamp se presente e aggiungi il nuovo
                            currentSrc = currentSrc.split('?v=')[0];
                            imgElement.setAttribute('src', `${currentSrc}?v=${newTimestamp}`);
                        }
                        
                        // Aggiorna anche il data-preview-src per la modale
                        const previewSrcElement = photoCard.querySelector('[data-preview-src]');
                        if (previewSrcElement && newTimestamp) {
                            let currentPreviewSrc = previewSrcElement.getAttribute('data-preview-src');
                            currentPreviewSrc = currentPreviewSrc.split('?v=')[0];
                            previewSrcElement.setAttribute('data-preview-src', `${currentPreviewSrc}?v=${newTimestamp}`);
                        }
                    }
                })
                .catch(error => {
                    console.error('Errore durante l\'aggiornamento:', error);
                    if(photoCard) photoCard.style.opacity = '1';
                    select.disabled = false;
                });
            }
        });
    }
});
</script>
@endpush