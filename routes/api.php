<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\AdminAuthController; 
use App\Http\Controllers\CertificateController; 

// Student Routes
Route::post('/students/import', [StudentController::class, 'import']);
Route::post('/students/checkEmail', [StudentController::class, 'checkEmail']);
Route::get('/certificate/{id}', [CertificateController::class, 'show']);
// Send code dynamically based on email
Route::post('/sendCode', [VerificationController::class, 'sendCode']);
Route::post('/resendCode', [VerificationController::class, 'resendCode']); 
Route::post('/verifyCode', [VerificationController::class, 'verifyCode']);
Route::get('/available-certificates', [CertificateController::class, 'getAvailableCertificates']);
Route::post('/issue-certificate', [CertificateController::class, 'issueCertificate']);
// Admin Auth Routes
Route::prefix('admin/auth')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AdminAuthController::class, 'me']);
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::post('refresh', [AdminAuthController::class, 'refresh']);
    });
    // Route to get available certificates

});