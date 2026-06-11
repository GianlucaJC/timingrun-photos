<?php

namespace App\Services;

use App\Models\Photo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BibRecognitionService
{
    public function recognize(Photo $photo): void
    {
        $apiKey = config('services.ocr.api_key');
        $apiUrl = config('services.ocr.url'); // Es: https://vision.googleapis.com/v1/images:annotate

        if (empty($apiKey) || empty($apiUrl)) {
            Log::warning("OCR non configurato. Chiave API o URL mancanti. Salto il riconoscimento per la foto ID {$photo->id}.");
            return;
        }

        try {
            // Leggi il contenuto binario dell'immagine originale
            $imageContent = Storage::disk('public')->get($photo->original_path);
            if (!$imageContent) {
                Log::error("Impossibile leggere il file immagine per la foto ID {$photo->id} dal percorso: {$photo->original_path}");
                return;
            }
            // Codifica l'immagine in Base64
            $base64Image = base64_encode($imageContent);
            Log::debug("Tentativo di riconoscimento per la foto ID {$photo->id} inviando il contenuto dell'immagine (Base64).");

            // La struttura della richiesta è specifica per Google Cloud Vision con dati Base64
            $requestBody = [
                'requests' => [
                    [
                        'image' => [
                            // Usa 'content' per i dati base64 invece di 'source' per l'URL
                            'content' => $base64Image,
                        ],
                        'features' => [
                            [
                                'type' => 'TEXT_DETECTION',
                                'maxResults' => 50, // Aumenta se necessario
                            ],
                        ],
                    ],
                ],
            ];

            // Google Vision richiede la chiave API come parametro nell'URL
            $response = Http::post($apiUrl . '?key=' . $apiKey, $requestBody);

            if ($response->successful()) {
                $data = $response->json();
                Log::debug("Risposta API ricevuta per foto ID {$photo->id}", $data);

                // Pulisci i pettorali esistenti per questa foto prima di inserirne di nuovi
                $photo->bibs()->delete();

                // Analizza la risposta di Google Vision
                $annotations = $data['responses'][0]['textAnnotations'] ?? [];

                // Il primo elemento è l'intero blocco di testo, lo saltiamo
                array_shift($annotations);

                foreach ($annotations as $annotation) {
                    $text = trim($annotation['description']);
                    // Filtra solo i risultati che sono puramente numerici
                    if (is_numeric($text) && strlen($text) > 1) { // Ignora numeri singoli
                        $photo->bibs()->create([
                            'bib_number' => $text,
                            'confidence' => $annotation['score'] ?? null, // Google non sempre fornisce 'score' per TEXT_DETECTION
                        ]);
                        Log::info("Foto ID {$photo->id}: Riconosciuto pettorale {$text}");
                    }
                }
            } else {
                Log::error("API Recognition fallita per foto ID {$photo->id}. Status: " . $response->status(), [
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Eccezione durante il riconoscimento per foto ID {$photo->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
