<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Support\Setting;

class StaticPageController extends Controller
{
    public function workshop()
    {
        $content = Setting::firstWhere('key', 'workshop_info')?->value ?? [
            'address' => 'Atelier RIPAIR, 10 rue des Artisans, 75000 Paris',
            'schedule' => 'Du lundi au samedi - 9h / 19h',
            'services' => ['Retrait atelier', 'Diagnostiques express', 'Stock pro dédié'],
        ];

        return view('front.pages.workshop', compact('content'));
    }
}
