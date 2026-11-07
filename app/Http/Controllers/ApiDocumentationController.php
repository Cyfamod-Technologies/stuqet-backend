<?php

namespace App\Http\Controllers;

class ApiDocumentationController extends Controller
{
    public function index()
    {
        $specification = json_decode(file_get_contents(resource_path('docs/openapi.json')), true, 512, JSON_THROW_ON_ERROR);

        $counts = [];
        foreach ($specification['paths'] as $operations) {
            foreach (array_unique(array_merge(...array_column($operations, 'tags'))) as $tag) {
                $counts[$tag] = ($counts[$tag] ?? 0) + 1;
            }
        }

        return view('documentation.mobile', [
            'unavailable' => $specification['x-unavailable-endpoints'],
            'counts' => $counts,
            'endpointCount' => count($specification['paths']),
        ]);
    }

    public function specification()
    {
        return response()->file(resource_path('docs/openapi.json'), ['Content-Type' => 'application/json']);
    }
}
