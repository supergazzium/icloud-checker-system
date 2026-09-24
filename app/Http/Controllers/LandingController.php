<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Service;

class LandingController extends Controller
{
    /**
     * Public marketing homepage.
     *
     * Authenticated visitors are sent straight to their dashboard so the
     * landing page is only ever shown to logged-out guests. Pricing cards
     * are rendered from the live Service catalogue (active rows only) so the
     * numbers stay in step with what a customer actually pays after login.
     */
    public function index()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        // Cheapest-first within the default sort so the "from ฿X" framing
        // reads naturally. Only active services are ever public.
        $services = Service::query()
            ->where('active', 1)
            ->orderBy('sort_order')
            ->get(['id', 'name_th', 'name_en', 'description_th', 'description_en', 'device_type', 'sell_price']);

        return view('landing', compact('services'));
    }
}
