<?php

namespace App\Jobs;

use App\Services\BibRecognitionService;
use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProcessPhotoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param Photo $photo La foto da elaborare.
     * @param bool $cleanupFirst Se true, elimina i file generati in precedenza prima di ri-processare.
     */
    public function __construct(public Photo $photo, public bool $cleanupFirst = false)
    {
    }

    public function handle(): void
    {
        // Aumenta temporaneamente il limite di memoria per questo job.
        // L'elaborazione di immagini può richiedere molta RAM.
        ini_set('memory_limit', '512M');

        // Crea un'istanza del manager per Intervention Image v3 con il driver GD
        $manager = new ImageManager(new Driver());

        try { // Wrap the entire handle method in a try-catch to ensure status update on error
            // Se richiesto, esegue la pulizia dei file generati in precedenza.
            // Utile quando si ri-processa una foto dopo un cambio di impostazioni.
            if ($this->cleanupFirst) {
                Log::debug("Pulizia file esistenti per la foto ID: {$this->photo->id}");
                Storage::disk('public')->delete(array_filter([
                    $this->photo->watermarked_path,
                    $this->photo->thumbnail_path,
                    $this->photo->admin_thumbnail_path, // Anche la miniatura admin
                ]));
            }

            Log::debug("Inizio elaborazione foto ID: {$this->photo->id}");
            $this->photo->update(['status' => 'processing']);

            $originalPath = $this->photo->original_path;            
            $filename = basename($originalPath);
            // Percorso più robusto basato sull'ID dell'evento, invece di parsare la stringa.
            $eventDir = 'events/' . $this->photo->event_id;

            // Legge l'immagine direttamente dal percorso del file per ottimizzare la memoria
            Log::debug("Percorso originale: " . Storage::disk('public')->path($originalPath));

            // Determina il tipo di utilizzo direttamente dalla foto
            $isCommercial = $this->photo->photo_usage_type === 'commercial';

            $image = $manager->read(Storage::disk('public')->path($originalPath));

            // 1. Crea la miniatura pubblica (il contenuto dipende dal tipo di evento)
            $publicThumbnailDir = $eventDir . '/thumbnails';
            $publicThumbnailPath = $publicThumbnailDir . '/' . $filename;
            Storage::disk('public')->makeDirectory($publicThumbnailDir);

            if ($isCommercial) {
                // Per uso commerciale: miniatura piccola, degradata e con watermark
                $publicThumbnail = (clone $image)->cover(240, 180);
                $this->applyWatermarks($publicThumbnail, 20, 20, 30);
                Storage::disk('public')->put($publicThumbnailPath, (string) $publicThumbnail->toJpeg(35));
            } else {
                // Per uso pubblico: miniatura di buona qualità, senza watermark
                $publicThumbnail = (clone $image)->cover(480, 360);
                Storage::disk('public')->put($publicThumbnailPath, (string) $publicThumbnail->toJpeg(85));
            }
            Log::debug("Miniatura pubblica creata: " . $publicThumbnailPath);

            // 2. Crea la miniatura per l'area fotografi (sempre di alta qualità, senza watermark)
            $adminThumbnail = (clone $image)->cover(480, 360);
            $adminThumbnailDir = $eventDir . '/admin_thumbnails';
            $adminThumbnailPath = $adminThumbnailDir . '/' . $filename;
            Storage::disk('public')->makeDirectory($adminThumbnailDir);
            Storage::disk('public')->put($adminThumbnailPath, (string) $adminThumbnail->toJpeg(85));
            Log::debug("Miniatura admin creata: " . $adminThumbnailPath);

            // 3. Crea la versione con watermark (solo per uso commerciale)
            $watermarkedPath = null;
            if ($isCommercial) {
                $watermarked = (clone $image)->scaleDown(1024);
                $this->applyAggressiveWatermark($watermarked);
                $watermarkedDir = $eventDir . '/watermarked';
                $watermarkedPath = $watermarkedDir . '/' . $filename;
                Storage::disk('public')->makeDirectory($watermarkedDir);
                // Salva in JPEG con qualità 75% per scoraggiare il download
                Storage::disk('public')->put($watermarkedPath, (string) $watermarked->toJpeg(75));
                Log::debug("Immagine con watermark creata: " . $watermarkedPath);
            }

            // 4. Aggiorna il modello della foto con i nuovi percorsi e lo stato
            $this->photo->update([
                'thumbnail_path' => $publicThumbnailPath,
                'admin_thumbnail_path' => $adminThumbnailPath,
                'watermarked_path' => $watermarkedPath,
                'status' => 'published',
            ]);

            // 5. Avvia il riconoscimento del pettorale (dopo che lo stato è 'published')
            $bibRecognizer = new BibRecognitionService();
            $bibRecognizer->recognize($this->photo);

            // 6. Se è la prima foto pubblicata per questo evento, avvia la sincronizzazione con l'API legacy
            $publishedPhotosCount = Photo::where('event_id', $this->photo->event_id)
                ->where('status', 'published')
                ->count();

            if ($publishedPhotosCount === 1) {
                SyncEventPhotosToLegacyJob::dispatch();
            }

        } catch (\Exception $e) {
            Log::error("Fallimento elaborazione foto ID: {$this->photo->id}. Errore: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
            // Aggiorna lo stato usando una query diretta per evitare problemi con attributi "dirty"
            // che potrebbero causare un secondo errore se la colonna non esiste.
            Photo::where('id', $this->photo->id)->update(['status' => 'error']);
        }
    }

    /**
     * Applica una serie di watermark randomizzati a un'immagine.
     *
     * @param \Intervention\Image\Image $image
     * @param int $count Il numero di watermark da applicare.
     * @param int $minFontSize La dimensione minima del font.
     * @param int $maxFontSize La dimensione massima del font.
     * @return void
     */
    private function applyWatermarks(\Intervention\Image\Image $image, int $count, int $minFontSize, int $maxFontSize): void
    {
        $fontNames = ['arial.ttf', 'times.ttf', 'verdana.ttf', 'cour.ttf'];
        $availableFonts = [];

        // Verifica quali font sono effettivamente disponibili per evitare errori
        foreach ($fontNames as $fontName) {
            if (file_exists(public_path('fonts/' . $fontName))) {
                $availableFonts[] = public_path('fonts/' . $fontName);
            }
        }

        if (empty($availableFonts)) {
            throw new \Exception("Nessun file di font trovato in public/fonts/. Copiare i file: " . implode(', ', $fontNames));
        }

        for ($i = 0; $i < $count; $i++) {
            $image->text('TimingRun.it', rand(0, $image->width()), rand(0, $image->height()), function($font) use ($availableFonts, $minFontSize, $maxFontSize) {
                $font->file($availableFonts[array_rand($availableFonts)]); // Scegli un font a caso
                $font->size(rand($minFontSize, $maxFontSize)); // Dimensione casuale
                $font->color('rgba(255, 255, 255, ' . (rand(35, 50) / 100) . ')'); // Opacità casuale
                $font->align('center');
                $font->valign('middle');
                $font->angle(rand(-45, 45)); // Angolo casuale
            });
        }
    }

    /**
     * Applica un watermark aggressivo al centro dell'immagine.
     *
     * @param \Intervention\Image\Image $image
     * @return void
     */
    private function applyAggressiveWatermark(\Intervention\Image\Image $image): void
    {
        $watermarkPath = public_path('logo/watermark-logo.png');

        if (!file_exists($watermarkPath)) {
            Log::warning("File del watermark non trovato in {$watermarkPath}. Salto l'applicazione del watermark aggressivo.");
            // Applica il vecchio metodo come fallback
            $this->applyWatermarks($image, 1, 120, 120);
            return;
        }

        // Ridimensiona il watermark al 60% della larghezza dell'immagine mantenendo l'aspect ratio
        $image->place($watermarkPath, 'center', 0, 0, 60);
    }
}