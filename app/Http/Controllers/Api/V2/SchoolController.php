<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\SchoolResource;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:100', 'per_page' => 'nullable|integer|min:1|max:100', 'page' => 'nullable|integer|min:1']);
        $schools = School::where('is_active', true);
        if ($request->filled('search')) {
            $schools->where('name', 'like', '%'.$request->input('search').'%');
        }
        return SchoolResource::collection($schools->orderBy('name')->orderBy('id')->paginate($request->input('per_page', 20)));
    }

    public function show($id)
    {
        return new SchoolResource(School::where('is_active', true)->findOrFail($id));
    }
}
