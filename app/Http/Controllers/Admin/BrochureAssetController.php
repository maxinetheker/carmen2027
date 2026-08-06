<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * Streams the fixed brand logo files (storage/logos_remax) for the admin's manual
 * logo picker. They live outside the Laravel disks on purpose (bundled brand
 * assets, not user uploads), so this reads them directly from config-known paths.
 */
class BrochureAssetController extends Controller
{
    public function logo(string $key)
    {
        $logo = config("brochure_templates.logos.{$key}");
        abort_unless($logo, 404);

        $path = storage_path(config('brochure_templates.logos_path').'/'.$logo['file']);
        abort_unless(is_file($path), 404);

        return response()->file($path);
    }
}
