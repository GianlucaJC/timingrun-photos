<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private float $basePrice = 5.00;

    /**
     * Mostra il carrello con i calcoli delle promozioni.
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        $photos = Photo::whereIn('id', $cart)
            ->where('status', 'published')
            ->where('photo_usage_type', 'commercial')
            ->get();

        // Allinea la sessione con le foto effettivamente valide e pubblicate
        $validIds = $photos->pluck('id')->toArray();
        if (count($cart) !== count($validIds)) {
            session()->put('cart', $validIds);
            $cart = $validIds;
        }

        $count = count($cart);

        // Calcola i totali per ciascuna promozione
        $totals = [
            'simple' => [
                'value' => $count * $this->basePrice,
                'label' => 'Acquisto Semplice',
                'description' => 'Prezzo standard senza promozioni (€' . number_format($this->basePrice, 2) . ' a foto).',
                'discount' => 0.00,
                'eligible' => true,
            ],
            'promo_3x2' => [
                'value' => ($count - floor($count / 3)) * $this->basePrice,
                'label' => 'Promo 3x2 (Compri 3, Paghi 2)',
                'description' => 'Ogni 3 foto selezionate, una è in omaggio.',
                'discount' => floor($count / 3) * $this->basePrice,
                'eligible' => $count >= 3,
            ],
            'promo_gold' => [
                'value' => $count >= 5 ? $count * 3.20 : $count * $this->basePrice,
                'label' => 'Multi-Foto Gold (5+ foto)',
                'description' => 'Sconto quantità su 5 o più foto: solo €3.20 a foto!',
                'discount' => $count >= 5 ? ($count * $this->basePrice) - ($count * 3.20) : 0.00,
                'eligible' => $count >= 5,
            ]
        ];

        // Determina la promozione attiva.
        // Se non impostata o non idonea, calcola la migliore in automatico.
        $activePromotion = session()->get('cart_promotion');
        if (!$activePromotion || empty($totals[$activePromotion]) || !$totals[$activePromotion]['eligible']) {
            $activePromotion = $this->getBestPromotion($count);
            session()->put('cart_promotion', $activePromotion);
        }

        $totalData = $totals[$activePromotion];

        return view('public.cart', compact('photos', 'totals', 'activePromotion', 'totalData'));
    }

    /**
     * Aggiunge una o più foto al carrello.
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'exists:photos,id',
        ]);

        $cart = session()->get('cart', []);
        $addedCount = 0;

        foreach ($validated['photo_ids'] as $id) {
            $photo = Photo::find($id);
            if ($photo && $photo->photo_usage_type === 'commercial' && $photo->status === 'published') {
                if (!in_array($id, $cart)) {
                    $cart[] = $id;
                    $addedCount++;
                }
            }
        }

        session()->put('cart', $cart);

        // Se sono state aggiunte nuove foto, suggerisci la promozione migliore
        $count = count($cart);
        $bestPromo = $this->getBestPromotion($count);
        session()->put('cart_promotion', $bestPromo);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cart_count' => $count,
                'added_count' => $addedCount,
                'message' => $addedCount > 0 ? 'Foto aggiunte al carrello!' : 'Foto già presenti nel carrello.'
            ]);
        }

        return redirect()->route('public.cart.index')->with('success', 'Foto aggiunte al carrello!');
    }

    /**
     * Rimuove una singola foto dal carrello.
     */
    public function remove(Photo $photo)
    {
        $cart = session()->get('cart', []);
        
        if (($key = array_search($photo->id, $cart)) !== false) {
            unset($cart[$key]);
            session()->put('cart', array_values($cart));
        }

        // Ricalcola la migliore promozione
        $count = count($cart);
        $activePromotion = session()->get('cart_promotion');
        if ($activePromotion && ($activePromotion === 'promo_gold' && $count < 5 || $activePromotion === 'promo_3x2' && $count < 3)) {
            session()->put('cart_promotion', $this->getBestPromotion($count));
        }

        return redirect()->route('public.cart.index')->with('success', 'Foto rimossa dal carrello.');
    }

    /**
     * Svuota completamente il carrello.
     */
    public function clear(Request $request)
    {
        session()->forget(['cart', 'cart_promotion']);
        
        if ($request->input('simulation') === 'true') {
            return redirect()->route('public.events.index')->with('success', 'Acquisto simulato con successo! Le foto sono state ordinate (simulazione). Il carrello è stato svuotato.');
        }

        return redirect()->route('public.cart.index')->with('success', 'Carrello svuotato.');
    }

    /**
     * Imposta manualmente la promozione desiderata dal carrello.
     */
    public function setPromotion(Request $request)
    {
        $validated = $request->validate([
            'promotion' => 'required|string|in:simple,promo_3x2,promo_gold',
        ]);

        $cart = session()->get('cart', []);
        $count = count($cart);

        // Verifica l'idoneità prima di salvare
        if ($validated['promotion'] === 'promo_gold' && $count < 5) {
            return redirect()->route('public.cart.index')->with('error', 'Non hai abbastanza foto per sbloccare la promozione Gold.');
        }

        if ($validated['promotion'] === 'promo_3x2' && $count < 3) {
            return redirect()->route('public.cart.index')->with('error', 'Non hai abbastanza foto per sbloccare la promozione 3x2.');
        }

        session()->put('cart_promotion', $validated['promotion']);

        return redirect()->route('public.cart.index')->with('success', 'Promozione aggiornata.');
    }

    /**
     * Calcola la promozione più conveniente per la quantità di foto.
     */
    private function getBestPromotion(int $count): string
    {
        if ($count >= 5) {
            return 'promo_gold';
        } elseif ($count >= 3) {
            return 'promo_3x2';
        }
        return 'simple';
    }
}
