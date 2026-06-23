<?php

namespace App\Jobs;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncEventPhotosToLegacyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Recupera gli eventi locali (collegati all'API esterna) che hanno almeno una foto pubblicata
            $eventsWithPhotos = Event::whereHas('photos', function ($query) {
                $query->where('status', 'published');
            })
            ->whereNotNull('api_id')
            ->get(['api_id', 'slug']);

            $eventsPayload = [];
            foreach ($eventsWithPhotos as $event) {
                $eventsPayload[] = [
                    'id' => (int)$event->api_id,
                    'slug' => $event->slug,
                ];
            }

            Log::debug('Inizio sincronizzazione presenza foto con l\'API legacy.', [
                'count' => count($eventsPayload),
            ]);

            $apiUrl = config('services.timingrun.api_url');
            $apiKey = config('services.timingrun.api_key');

            if (empty($apiUrl)) {
                Log::warning('Configurazione TIMINGRUN_API_URL mancante. Sincronizzazione foto interrotta.');
                return;
            }

            $response = Http::withHeaders([
                'X-API-KEY' => $apiKey,
            ])->post($apiUrl . '/api_events.php', [
                'action' => 'update_photos',
                'events' => $eventsPayload,
            ]);

            if ($response->failed()) {
                Log::error('Errore nella chiamata API esterna per la sincronizzazione foto.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } else {
                Log::info('Sincronizzazione presenza foto completata con successo.', [
                    'api_response' => $response->json(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Eccezione durante la sincronizzazione presenza foto.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
