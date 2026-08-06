<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Services\PropertyDocumentManager;
use Illuminate\Http\Request;

class PropertyDocumentController extends Controller
{
    public function __construct(private PropertyDocumentManager $documents) {}

    public function store(Property $property, Request $request)
    {
        $request->validate([
            'documents' => ['required', 'array', 'max:10'],
            'documents.*' => ['file', 'mimes:pdf,txt', 'max:15360'],
        ]);

        foreach ($request->file('documents', []) as $file) {
            $this->documents->store($property, $file);
        }

        return back()->with('success', 'Documento(s) añadido(s). La IA los usará como referencia.');
    }

    public function destroy(Property $property, PropertyDocument $document)
    {
        abort_unless($document->property_id === $property->id, 404);

        $this->documents->destroy($document);

        return back()->with('success', 'Documento eliminado.');
    }
}
