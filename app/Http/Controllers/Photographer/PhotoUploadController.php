<?php

namespace App\Http\Controllers\Photographer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Jobs\ProcessPhotoJob;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PhotoUploadController extends Controller
{
    public function store(Request $request, Event $event)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:10240'], // 10MB Max
        ]);

        // Usa l'ID del fotografo autenticato
        $photographerId = Auth::id();

        $file = $request->file('photo');
        $originalName = $file->getClientOriginalName();

        // Salva il file in: storage/app/public/events/{event_id}/originals
        $path = $file->store("events/{$event->id}/originals", 'public');

        // Crea il record della foto nel database
        $photo = Photo::create([
            'user_id' => $photographerId,
            'event_id' => $event->id,
            'original_path' => $path,
            'status' => 'pending', // Verrà elaborata in un secondo momento
            'photo_usage_type' => $event->photo_usage_type, // Imposta il tipo di utilizzo predefinito dall'evento
        ]);

        // Avvia il processo in background per l'elaborazione dell'immagine
        ProcessPhotoJob::dispatch($photo);

        return response()->json([
            'success' => true,
            'photo_id' => $photo->id,
            'original_name' => $originalName,
        ]);
    }

    public function destroy(Photo $photo)
    {
        // Verifica l'autorizzazione tramite la PhotoPolicy.
        // L'utente può cancellare la foto solo se ne è il proprietario.
        $this->authorize('delete', $photo);

        // Prima di cancellare la foto, cancelliamo i record collegati (es. pettorali)
        // per evitare errori di foreign key constraint.
        $photo->bibs()->delete();

        // Cancella i file fisici (originale e le future varianti)
        // Usiamo array_filter per essere sicuri di non passare valori null a `delete`.
        Storage::disk('public')->delete(array_filter([
            $photo->original_path,
            $photo->watermarked_path,
            $photo->thumbnail_path,
            $photo->admin_thumbnail_path
        ]));

        $photo->delete();

        return response()->json(['success' => true]);
    }

    public function updateUsage(Request $request, Photo $photo)
    {
        // Verifica l'autorizzazione tramite la PhotoPolicy.
        // L'utente può aggiornare la foto solo se ne è il proprietario.
        $this->authorize('update', $photo);

        $validated = $request->validate([
            'photo_usage_type' => ['required', 'string', 'in:commercial,public'],
        ]);

        // Non ri-processare se il tipo non è cambiato
        if ($photo->photo_usage_type === $validated['photo_usage_type']) {
            return response()->json(['success' => true, 'message' => 'Nessuna modifica.']);
        }

        $photo->update($validated);

        // Avvia la ri-elaborazione della foto con le nuove impostazioni
        // Il flag 'true' indica di pulire i vecchi file (miniature, watermark)
        ProcessPhotoJob::dispatch($photo, true);

        return response()->json([
            'success' => true,
            'message' => 'La foto verrà ri-elaborata in background.',
            'updated_at_timestamp' => $photo->updated_at->timestamp,
        ]);
    }
}