<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        // Mostra solo gli eventi che hanno almeno una foto pubblicata.
        // Conta solo le foto pubblicate e ordina per data.
        $events = Event::withCount(['photos' => fn($q) => $q->where('status', 'published')])
            ->having('photos_count', '>', 0)
            ->orderBy('date', 'desc')
            ->get();

        return view('public.events.index', compact('events'));
    }

    public function show(Event $event)
    {
        // Carica solo le foto che sono state elaborate con successo ('published')
        $event->load(['photos' => fn($q) => $q->where('status', 'published')->orderBy('created_at', 'desc')]);

        return view('public.events.show', compact('event'));
    }

    public function search(Request $request, Event $event)
    {
        $request->validate([
            'bib' => 'required|numeric|digits_between:1,5',
        ]);

        $bibNumber = $request->input('bib');

        // Trova le foto che hanno il pettorale cercato
        $photos = Photo::where('event_id', $event->id)
            ->where('status', 'published')
            ->whereHas('bibs', function ($query) use ($bibNumber) {
                $query->where('bib_number', $bibNumber);
            })
            ->orderBy('created_at', 'desc')->get();

        return view('public.events.show', compact('event', 'photos', 'bibNumber'));
    }
}