<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\FunctionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register', [AuthController::class, 'register'])->name('register-email');
Route::post('/verify', [AuthController::class, 'verify'])->name('verify-email');
Route::post('/login/facebook', [AuthController::class, 'loginFacebook'])->name('login-facebook');
Route::post('/login/email', [AuthController::class, 'login'])->name('login-email');
Route::post('/image/upload', [FileController::class, 'uploadImage']);

Route::group(['middleware' => ['attach.token', 'token.auth']], function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/change/aiart/url', [AuthController::class, 'changeURLAIArt']);
    Route::post('/login/check', [AuthController::class, 'checkAuth'])->name('check-auth');

    //Get data
    Route::post('/chat/info', [ChatController::class, 'detail']);
    Route::post('/chat/index', [ChatController::class, 'updateCurrentChatIndex']);

    //Folder
    Route::post('/chat/folder/create', [ChatController::class, 'addFolder']);
    Route::post('/chat/folder/update', [ChatController::class, 'updateFolder']);
    Route::post('/chat/folder/delete', [ChatController::class, 'deleteFolder']);

    //Chat
    Route::post('/chat/create', [ChatController::class, 'addChat']);
    Route::post('/chat/update', [ChatController::class, 'updateChat']);
    Route::post('/chat/delete', [ChatController::class, 'deleteChat']);
    
    //Message
    // Route::post('/chat/message/create', [ChatController::class, 'addMessage'])->middleware('check_token');
    Route::post('/chat/message/create', [ChatController::class, 'addMessage']);
    Route::post('/chat/message/update', [ChatController::class, 'updateMessage']);
    Route::post('/chat/message/delete', [ChatController::class, 'deleteMessage']);

    //Image
    Route::post('/image/txt2img', [FunctionController::class, 'txt2img']);
    Route::post('/image/queue', [FunctionController::class, 'queueStatus']);

    //Media
    Route::post('/media', [FunctionController::class, 'mediaPlayer']);
});

Route::get('/image/{task_id}', [FileController::class, 'getImage']);

Route::get('/audio', function() {
    $audioURL = $_GET['url'];

    // Set the appropriate Content-Type header based on the audio format
    header('Content-Type: audio/mpeg');

    // Forward the audio data from the remote server to the client
    readfile(urldecode($audioURL));
});