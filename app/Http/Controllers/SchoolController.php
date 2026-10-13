<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolRequest;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\School;
use Illuminate\Support\Str;

class SchoolController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:view_all_schools'])->only('index');
        $this->middleware(['permission:add_school'])->only('create');
        $this->middleware(['permission:edit_school'])->only('edit');
        $this->middleware(['permission:delete_school'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $school_tabs = ['All Schools', 'Inactive Schools'];
        return view('backend.product.schools.index', compact('school_tabs'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('backend.product.schools.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SchoolRequest $request)
    {
        $school = new School;
        $school->name = $request->name;
        $school->meta_title = $request->meta_title;
        $school->meta_description = $request->meta_description;
        $school->is_active = $request->is_active ? true : false;
        
        if ($request->slug != null) {
            $school->slug = str_replace(' ', '-', $request->slug);
        } else {
            $school->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)) . '-' . Str::random(5);
        }

        $school->logo = $request->logo;
        $school->save();

        return response()->json([
            'success' => true,
            'message' => translate('School has been inserted successfully'),
            'redirect' => route('schools.index')
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $school = School::findOrFail($id);
        return view('backend.product.schools.edit', compact('school'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(SchoolRequest $request, $id)
    {
        $school = School::findOrFail($id);
        $school->name = $request->name;
        $school->meta_title = $request->meta_title;
        $school->meta_description = $request->meta_description;
        $school->is_active = $request->is_active ? true : false;
        
        if ($request->slug != null) {
            $school->slug = strtolower($request->slug);
        } else {
            $school->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)) . '-' . Str::random(5);
        }
        
        $school->logo = $request->logo;
        $school->save();

        return response()->json([
            'success' => true,
            'message' => translate('School has been updated successfully'),
            'redirect' => route('schools.index')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $school = School::findOrFail($id);
        Product::where('school_id', $school->id)->update(['school_id' => null]);
        School::destroy($id);
        return 1;
    }

    public function get_schools_by_filter(Request $request)
    {
        $sort_search = null;
        $schools = School::withCount('products')->orderBy('created_at', 'desc');
        
        if ($request->has('school_type')) {
            if ($request->school_type == 'inactive_schools') {
                $schools = $schools->where('is_active', 0);
            } elseif ($request->school_type == 'active_schools') {
                $schools = $schools->where('is_active', 1);
            }
        }
        
        if ($request->has('search')) {
            $sort_search = $request->search;
            $schools = $schools->where('name', 'like', '%' . $sort_search . '%');
        }
        
        $schools = $schools->paginate(15);
        $view = view('backend.product.schools.school_table', compact('schools', 'sort_search'))->render();
        return response()->json(['html' => $view]);
    }

    public function bulk_schools_delete(Request $request)
    {
        $school_ids = $request->id;
        foreach ($school_ids as $id) {
            Product::where('school_id', $id)->update(['school_id' => null]);
            School::destroy($id);
        }
        return 1;
    }

    public function options()
    {
        return response()->json([
            'data' => School::query()
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        ]);
    }
}
