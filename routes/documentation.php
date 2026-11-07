<?php

use App\Http\Controllers\ApiDocumentationController;
use Illuminate\Support\Facades\Route;

Route::get('api/docs', [ApiDocumentationController::class, 'index'])->name('api.documentation');
Route::get('api/openapi.json', [ApiDocumentationController::class, 'specification'])->name('api.openapi');
