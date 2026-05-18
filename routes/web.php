<?php

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Cache;

// Route::get('/', function () {
//     return view('index');
// });

Route::get('/', function () {
    return response()->json(['status' => 'OK']);
});



// Route::get('/test-redis', function () {
//     $data = Cache::remember('jmeter_test_key', 60, function () {
//         return [
//             'status' => 'success',
//             'message' => 'Hello from Redis Cache!',
//             'timestamp' => now()->toDateTimeString()
//         ];
//     });

//     return response()->json($data);
// });