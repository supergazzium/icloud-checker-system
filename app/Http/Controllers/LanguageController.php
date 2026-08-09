<?php
namespace App\Http\Controllers;

class LanguageController extends Controller {
    public function switch(string $locale) {
        $locale = in_array($locale, ['th','en']) ? $locale : 'th';
        session(['locale' => $locale]);
        if (auth()->check()) auth()->user()->update(['locale' => $locale]);
        return redirect()->back();
    }
}
