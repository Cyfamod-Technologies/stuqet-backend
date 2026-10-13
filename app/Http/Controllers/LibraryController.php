<?php

namespace App\Http\Controllers;

use App\Models\LibraryDepartment;
use App\Models\LibraryFaculty;
use App\Models\LibraryLevel;
use App\Models\LibraryResource;
use App\Models\LibraryResourceReport;
use App\Models\LibraryResourceUnlock;
use App\Models\LibraryResourceVote;
use App\Models\School;
use App\Models\UserLibraryProfile;
use App\Services\LibraryResourceFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LibraryController extends Controller
{
    protected $fileService;

    public function __construct(LibraryResourceFileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index(Request $request)
    {
        $query = LibraryResource::with(['user', 'school', 'faculty', 'department', 'level'])
            ->where('is_published', true)
            ->latest();

        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%' . $search . '%')
                    ->orWhere('course_code', 'like', '%' . $search . '%')
                    ->orWhereHas('department', function ($departmentQuery) use ($search) {
                        $departmentQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        foreach (['school_id', 'library_faculty_id', 'library_department_id', 'library_level_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->$filter);
            }
        }

        if ($request->filled('resource_type')) {
            $query->where('resource_type', $request->resource_type);
        }

        $resources = $query->paginate(12)->withQueryString();
        $schools = School::orderBy('name')->get();
        $faculties = $request->filled('school_id')
            ? LibraryFaculty::where('school_id', $request->school_id)->orderBy('name')->get()
            : collect();
        $departments = $request->filled('library_faculty_id')
            ? LibraryDepartment::where('library_faculty_id', $request->library_faculty_id)->orderBy('name')->get()
            : collect();
        $levels = $request->filled('library_department_id')
            ? LibraryLevel::where('library_department_id', $request->library_department_id)->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        $profile = Auth::check()
            ? UserLibraryProfile::with(['school', 'faculty', 'department', 'level'])->where('user_id', Auth::id())->first()
            : null;

        return view('frontend.library.index', compact('resources', 'schools', 'faculties', 'departments', 'levels', 'profile'));
    }

    public function show(LibraryResource $resource)
    {
        $resource->load(['user', 'school', 'faculty', 'department', 'level']);

        $hasUnlocked = $this->hasAccess($resource, Auth::user());
        $relatedResources = LibraryResource::with(['school', 'department', 'level'])
            ->where('id', '!=', $resource->id)
            ->where('library_department_id', $resource->library_department_id)
            ->latest()
            ->take(6)
            ->get();

        $userVote = Auth::check()
            ? LibraryResourceVote::where('library_resource_id', $resource->id)->where('user_id', Auth::id())->value('vote')
            : null;

        return view('frontend.library.show', compact('resource', 'hasUnlocked', 'relatedResources', 'userVote'));
    }

    public function create()
    {
        $schools = School::orderBy('name')->get();
        $profile = Auth::user()->libraryProfile()->with(['school', 'faculty', 'department', 'level'])->first();

        return view('frontend.library.create', compact('schools', 'profile'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'resource_type' => 'required|in:textbook,lecture_handout,past_question,revision_notes',
            'school_id' => 'required|exists:schools,id',
            'faculty_name' => 'required|string|max:120',
            'department_name' => 'required|string|max:120',
            'level_name' => 'required|string|max:50',
            'course_code' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:3000',
            'access_type' => 'required|in:free,paid',
            'price' => 'nullable|required_if:access_type,paid|numeric|min:0',
            'document' => 'required|file|mimes:pdf,docx,jpg,jpeg,png|max:40960',
        ]);

        $resource = null;

        DB::transaction(function () use ($request, &$resource) {
            $school = School::findOrFail($request->school_id);
            $faculty = LibraryFaculty::firstOrCreate(
                ['school_id' => $school->id, 'name' => trim($request->faculty_name)],
                ['slug' => $this->generateUniqueSlug(trim($request->faculty_name), LibraryFaculty::class)]
            );
            $department = LibraryDepartment::firstOrCreate(
                ['library_faculty_id' => $faculty->id, 'name' => trim($request->department_name)],
                ['slug' => $this->generateUniqueSlug(trim($request->department_name), LibraryDepartment::class)]
            );
            $level = LibraryLevel::firstOrCreate(
                ['library_department_id' => $department->id, 'name' => strtoupper(trim($request->level_name))],
                ['sort_order' => (int) filter_var($request->level_name, FILTER_SANITIZE_NUMBER_INT)]
            );

            $fileDetails = $this->fileService->processUpload($request->file('document'));

            $resource = LibraryResource::create([
                'user_id' => Auth::id(),
                'school_id' => $school->id,
                'library_faculty_id' => $faculty->id,
                'library_department_id' => $department->id,
                'library_level_id' => $level->id,
                'resource_type' => $request->resource_type,
                'title' => trim($request->title),
                'slug' => $this->generateUniqueSlug(trim($request->title), LibraryResource::class),
                'course_code' => strtoupper(trim($request->course_code)),
                'description' => $request->description,
                'access_type' => $request->access_type,
                'price' => $request->access_type === 'paid' ? $request->price : 0,
                'file_original_name' => $fileDetails['file_original_name'],
                'file_path' => $fileDetails['file_path'],
                'file_extension' => $fileDetails['file_extension'],
                'mime_type' => $fileDetails['mime_type'],
                'file_size' => $fileDetails['file_size'],
                'page_count' => $fileDetails['page_count'],
                'cover_image_path' => $fileDetails['cover_image_path'],
                'preview_page_paths' => $fileDetails['preview_page_paths'],
                'preview_pdf_path' => $fileDetails['preview_pdf_path'],
            ]);

            UserLibraryProfile::updateOrCreate(
                ['user_id' => Auth::id()],
                [
                    'school_id' => $school->id,
                    'library_faculty_id' => $faculty->id,
                    'library_department_id' => $department->id,
                    'library_level_id' => $level->id,
                ]
            );
        });

        flash(translate('Library material uploaded successfully.'))->success();
        return redirect()->route('library.show', $resource->slug);
    }

    public function download(LibraryResource $resource)
    {
        if (!$this->hasAccess($resource, Auth::user())) {
            flash(translate('Unlock this document first to continue.'))->warning();
            return redirect()->route('library.show', $resource->slug);
        }

        $absolutePath = public_path($resource->file_path);
        if (!file_exists($absolutePath)) {
            flash(translate('File does not exist!'))->error();
            return back();
        }

        $resource->increment('download_count');

        return response()->download($absolutePath, $resource->file_original_name);
    }

    public function unlock(LibraryResource $resource)
    {
        if ($resource->access_type !== 'paid') {
            return redirect()->route('library.download', $resource->slug);
        }

        $user = Auth::user();
        if ($this->hasAccess($resource, $user)) {
            flash(translate('This document is already unlocked for you.'))->success();
            return redirect()->route('library.show', $resource->slug);
        }

        if ((float) $user->balance < (float) $resource->price) {
            flash(translate("You don't have enough wallet balance."))->error();
            return redirect()->route('wallet.index');
        }

        DB::transaction(function () use ($resource, $user) {
            $user->balance = $user->balance - $resource->price;
            $user->save();

            LibraryResourceUnlock::create([
                'library_resource_id' => $resource->id,
                'user_id' => $user->id,
                'amount' => $resource->price,
                'unlocked_at' => now(),
            ]);

            $resource->increment('unlock_count');
        });

        flash(translate('Document unlocked successfully.'))->success();
        return redirect()->route('library.show', $resource->slug);
    }

    public function vote(Request $request, LibraryResource $resource)
    {
        $request->validate([
            'vote' => 'required|in:1,-1',
        ]);

        LibraryResourceVote::updateOrCreate(
            [
                'library_resource_id' => $resource->id,
                'user_id' => Auth::id(),
            ],
            [
                'vote' => (int) $request->vote,
            ]
        );

        $this->refreshVoteMetrics($resource);

        flash(translate('Your feedback has been recorded.'))->success();
        return back();
    }

    public function report(Request $request, LibraryResource $resource)
    {
        $request->validate([
            'reason' => 'required|string|max:120',
            'details' => 'nullable|string|max:1000',
        ]);

        LibraryResourceReport::create([
            'library_resource_id' => $resource->id,
            'user_id' => Auth::id(),
            'reason' => $request->reason,
            'details' => $request->details,
        ]);

        flash(translate('Report submitted successfully.'))->success();
        return back();
    }

    public function faculties(Request $request)
    {
        $faculties = LibraryFaculty::where('school_id', $request->school_id)->orderBy('name')->get(['id', 'name']);
        return response()->json($faculties);
    }

    public function departments(Request $request)
    {
        $departments = LibraryDepartment::where('library_faculty_id', $request->faculty_id)->orderBy('name')->get(['id', 'name']);
        return response()->json($departments);
    }

    public function levels(Request $request)
    {
        $levels = LibraryLevel::where('library_department_id', $request->department_id)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        return response()->json($levels);
    }

    public function myDepartment()
    {
        $profile = Auth::user()->libraryProfile;
        if (!$profile || !$profile->library_department_id) {
            flash(translate('Set your department context by uploading a material first.'))->warning();
            return redirect()->route('library.index');
        }

        return redirect()->route('library.index', [
            'school_id' => $profile->school_id,
            'library_faculty_id' => $profile->library_faculty_id,
            'library_department_id' => $profile->library_department_id,
            'library_level_id' => $profile->library_level_id,
        ]);
    }

    protected function hasAccess(LibraryResource $resource, $user): bool
    {
        if ($resource->access_type === 'free') {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ((int) $resource->user_id === (int) $user->id || $user->user_type === 'admin') {
            return true;
        }

        return LibraryResourceUnlock::where('library_resource_id', $resource->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    protected function refreshVoteMetrics(LibraryResource $resource): void
    {
        $upvotes = LibraryResourceVote::where('library_resource_id', $resource->id)->where('vote', 1)->count();
        $downvotes = LibraryResourceVote::where('library_resource_id', $resource->id)->where('vote', -1)->count();

        $resource->update([
            'upvote_count' => $upvotes,
            'downvote_count' => $downvotes,
            'is_flagged' => $downvotes >= 3,
        ]);
    }

    protected function generateUniqueSlug(string $value, string $modelClass): string
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug ?: Str::lower(Str::random(8));
        $counter = 1;

        while ($modelClass::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
