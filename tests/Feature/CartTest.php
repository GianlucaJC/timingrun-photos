<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private User $photographer;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->photographer = User::create([
            'name' => 'Photographer User',
            'email' => 'photo@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->event = Event::create([
            'name' => 'Test Event',
            'slug' => 'test-event',
            'date' => now()->toDateString(),
            'location' => 'Rome',
            'photo_usage_type' => 'commercial',
        ]);
    }

    /**
     * Test adding photos to the cart updates the session and auto-selects promotion.
     */
    public function test_add_photos_to_cart(): void
    {
        $photo1 = Photo::create([
            'user_id' => $this->photographer->id,
            'event_id' => $this->event->id,
            'original_path' => 'events/1/originals/photo1.jpg',
            'thumbnail_path' => 'events/1/thumbnails/photo1.jpg',
            'watermarked_path' => 'events/1/watermarked/photo1.jpg',
            'status' => 'published',
            'photo_usage_type' => 'commercial',
        ]);

        $photo2 = Photo::create([
            'user_id' => $this->photographer->id,
            'event_id' => $this->event->id,
            'original_path' => 'events/1/originals/photo2.jpg',
            'thumbnail_path' => 'events/1/thumbnails/photo2.jpg',
            'watermarked_path' => 'events/1/watermarked/photo2.jpg',
            'status' => 'published',
            'photo_usage_type' => 'commercial',
        ]);

        // Add both photos
        $response = $this->post(route('public.cart.add'), [
            'photo_ids' => [$photo1->id, $photo2->id]
        ]);

        $response->assertRedirect(route('public.cart.index'));
        $this->assertEquals([$photo1->id, $photo2->id], session('cart'));
        
        // Since we have 2 photos, the best promo is 'simple'
        $this->assertEquals('simple', session('cart_promotion'));
    }

    /**
     * Test promotion auto-selection.
     */
    public function test_promotion_auto_selection(): void
    {
        $photos = [];
        for ($i = 0; $i < 5; $i++) {
            $photos[] = Photo::create([
                'user_id' => $this->photographer->id,
                'event_id' => $this->event->id,
                'original_path' => "events/1/originals/photo{$i}.jpg",
                'thumbnail_path' => "events/1/thumbnails/photo{$i}.jpg",
                'watermarked_path' => "events/1/watermarked/photo{$i}.jpg",
                'status' => 'published',
                'photo_usage_type' => 'commercial',
            ]);
        }

        // Add 3 photos -> should select promo_3x2
        $this->post(route('public.cart.add'), [
            'photo_ids' => [$photos[0]->id, $photos[1]->id, $photos[2]->id]
        ]);
        $this->assertEquals('promo_3x2', session('cart_promotion'));

        // Clear cart for next check
        session()->forget(['cart', 'cart_promotion']);

        // Add 5 photos -> should select promo_gold
        $this->post(route('public.cart.add'), [
            'photo_ids' => collect($photos)->pluck('id')->toArray()
        ]);
        $this->assertEquals('promo_gold', session('cart_promotion'));
    }

    /**
     * Test setting promotion manually.
     */
    public function test_set_promotion_manually(): void
    {
        $photos = [];
        for ($i = 0; $i < 5; $i++) {
            $photos[] = Photo::create([
                'user_id' => $this->photographer->id,
                'event_id' => $this->event->id,
                'original_path' => "events/1/originals/photo{$i}.jpg",
                'status' => 'published',
                'photo_usage_type' => 'commercial',
            ]);
        }

        // Add 5 photos
        $this->post(route('public.cart.add'), [
            'photo_ids' => collect($photos)->pluck('id')->toArray()
        ]);
        
        // Initial auto-selection is promo_gold
        $this->assertEquals('promo_gold', session('cart_promotion'));

        // Change manually to promo_3x2
        $response = $this->post(route('public.cart.set_promotion'), [
            'promotion' => 'promo_3x2'
        ]);
        $response->assertRedirect(route('public.cart.index'));
        $this->assertEquals('promo_3x2', session('cart_promotion'));

        // Change manually to an ineligible promotion (e.g. promo_gold when we only have 2 photos)
        session()->put('cart', [$photos[0]->id, $photos[1]->id]);
        $response2 = $this->post(route('public.cart.set_promotion'), [
            'promotion' => 'promo_gold'
        ]);
        $response2->assertSessionHas('error');
    }

    /**
     * Test removing a photo from the cart.
     */
    public function test_remove_photo_from_cart(): void
    {
        $photo1 = Photo::create([
            'user_id' => $this->photographer->id,
            'event_id' => $this->event->id,
            'original_path' => 'events/1/originals/photo1.jpg',
            'status' => 'published',
            'photo_usage_type' => 'commercial',
        ]);

        session()->put('cart', [$photo1->id]);

        $response = $this->post(route('public.cart.remove', $photo1->id));
        $response->assertRedirect(route('public.cart.index'));
        $this->assertEmpty(session('cart'));
    }

    /**
     * Test clearing the cart and simulation checkout.
     */
    public function test_clear_cart_and_simulation(): void
    {
        $photo1 = Photo::create([
            'user_id' => $this->photographer->id,
            'event_id' => $this->event->id,
            'original_path' => 'events/1/originals/photo1.jpg',
            'status' => 'published',
            'photo_usage_type' => 'commercial',
        ]);

        session()->put('cart', [$photo1->id]);

        // Standard clear
        $response = $this->post(route('public.cart.clear'));
        $response->assertRedirect(route('public.cart.index'));
        $this->assertNull(session('cart'));

        // Simulation clear
        session()->put('cart', [$photo1->id]);
        $response2 = $this->post(route('public.cart.clear'), ['simulation' => 'true']);
        $response2->assertRedirect(route('public.events.index'));
        $response2->assertSessionHas('success');
        $this->assertNull(session('cart'));
    }
}
