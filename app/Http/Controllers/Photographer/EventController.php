<?php

namespace App\Http\Controllers\Photographer;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPhotoJob;
use App\Models\Event;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        // 1. Recupera gli eventi dall'API esterna
        $response = Http::withHeaders([
            'X-API-KEY' => config('services.timingrun.api_key')
        ])->get(config('services.timingrun.api_url') . '/api_events.php');

        if ($response->failed()) {
            Log::error('Fallimento nel recuperare gli eventi dall\'API di TimingRun.', ['status' => $response->status(), 'body' => $response->body()]);
            session()->flash('error', 'Impossibile caricare la lista degli eventi al momento.');
        } else {
            $apiEvents = $response->json() ?? [];

            if (is_array($apiEvents)) {
                // 2. Prepara i dati per la sincronizzazione
                $eventsToUpsert = [];
                $apiIds = [];

                foreach ($apiEvents as $apiEvent) {
                    if (empty($apiEvent['id']) || empty($apiEvent['name'])) {
                        continue;
                    }
                    $apiIds[] = $apiEvent['id'];
                    $eventsToUpsert[] = [
                        'api_id'   => $apiEvent['id'],
                        'name'     => $apiEvent['name'],
                        'slug'     => Str::slug(($apiEvent['slug'] ?: $apiEvent['name']) . '-' . $apiEvent['id']),
                        'location' => $apiEvent['location'],
                        'date'     => $apiEvent['date'],
                    ];
                }

                // 3. Esegui la sincronizzazione in una transazione per garantire l'integrità dei dati
                DB::transaction(function () use ($eventsToUpsert, $apiIds) {
                    // 3a. Ripristina gli eventi che erano stati cancellati (soft-deleted) ma sono tornati nell'API.
                    // Questo è fondamentale per gestire gli eventi che vengono "depubblicati" e poi "ripubblicati".
                    Event::onlyTrashed()->whereIn('api_id', $apiIds)->restore();

                    // 3b. Inserisci o aggiorna gli eventi dall'API usando `api_id` come chiave univoca.
                    if (!empty($eventsToUpsert)) {
                        Event::upsert(
                            $eventsToUpsert,
                            ['api_id'], // Colonna univoca per identificare i record
                            ['name', 'slug', 'location', 'date'] // Colonne da aggiornare se il record esiste
                        );
                    }

                    // 3c. Esegui il soft-delete degli eventi locali che non sono (o non sono più) presenti nella risposta dell'API.
                    Event::whereNotIn('api_id', $apiIds)->delete();
                });
            }
        }

        // 4. Ora, recupera tutti gli eventi validi (non soft-deleted) dal DB locale.
        // Il model Event con il trait SoftDeletes gestirà automaticamente il filtro `deleted_at IS NULL`.
        $events = Event::withCount('photos')->orderBy('date', 'desc')->get();

        return view('photographer.events.index', compact('events'));
    }

    public function show(Event $event)
    {
        // Carica le foto dell'evento, includendo i dati del fotografo (user) per ogni foto.
        // Le foto vengono poi raggruppate per nome del fotografo.
        $photosByPhotographer = $event->photos()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('user.name');

        return view('photographer.events.show', compact('event', 'photosByPhotographer'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'photo_usage_type' => ['required', 'string', 'in:commercial,public'],
        ]);

        $originalUsageType = $event->photo_usage_type;

        // Se il tipo di utilizzo non è cambiato, non facciamo nulla.
        if ($originalUsageType === $validated['photo_usage_type']) {
            return back()->with('info', 'Le impostazioni non sono state modificate.');
        }

        $event->update($validated);

        return back()->with('success', 'Impostazioni predefinite per i nuovi caricamenti aggiornate con successo.');
    }
}