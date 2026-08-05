<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        return response()->view('public.sitemap', [
            'properties' => Property::published()
                ->select(['slug', 'updated_at'])->latest('updated_at')->get(),
        ])->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
