<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\OutpassDocumentController;


//Parents Views
Route::livewire('/', 'pages::welcome')->name('welcome');

//Admin Views
Route::livewire('/admin', 'pages::dashboard')->name('admin.dashboard')->middleware('auth');
Route::livewire('/admin/cadets-mgmt', 'admin.cadets-mgmt','')->name('admin.cadets-mgmt')->middleware('auth');
Route::livewire('/admin/schedule-mgmt', 'admin.schedule-mgmt','')->name('admin.schedule-mgmt')->middleware('auth');
Route::get('/admin/outpass-document/{schedule_id}/{document_type}/{house?}', [OutpassDocumentController::class, 'generate'])
    ->name('admin.outpass.document');


//Auth Routes
Route::livewire('/login', 'auth.login')->name('login');
Route::livewire('/no-user', 'auth.no-user')->name('auth.no-user');
Route::livewire('/users', 'auth.users')->name('register');
Route::get('/logout', function () {
    Auth::logout();
    return redirect()->route('login');
})->name('logout');
