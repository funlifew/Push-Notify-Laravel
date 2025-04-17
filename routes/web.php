<?php

use Funlifew\PushNotify\Http\Controllers\MessageController;
use Funlifew\PushNotify\Http\Controllers\ScheduledNotificationController;
use Funlifew\PushNotify\Http\Controllers\SubscriptionController;
use Funlifew\PushNotify\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

// Get config values
$routePrefix = config('push-notify.routes.prefix', 'notify');
$routeMiddleware = config('push-notify.routes.middleware', ['web']);
$adminMiddleware = config('push-notify.routes.admin_middleware', ['web', 'auth']);
$disableCsrf = config('push-notify.routes.disable_csrf', true);

// API Routes
Route::group(['prefix' => $routePrefix, 'middleware' => $disableCsrf ? ['push-notify.csrf', 'push-notify.cors'] : []], function () {
    // Subscription API
    Route::post('/api/push-subscription', [SubscriptionController::class, 'store'])->name('push-notify.api.subscribe');
    
    // Unsubscribe API
    Route::post('/api/push-subscription/unsubscribe', [SubscriptionController::class, 'apiUnsubscribe'])->name('push-notify.api.unsubscribe');
});

// Admin Routes
Route::group(['prefix' => $routePrefix], function () {
    // Dashboard
    Route::get('/', [SubscriptionController::class, 'dashboard'])->name('notify.dashboard');
    
    // Subscriptions
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('notify.subscriptions.index');
        Route::get('/{id}/send', [SubscriptionController::class, 'showSendForm'])->name('notify.subscriptions.send');
        Route::post('/{id}/send', [SubscriptionController::class, 'sendNotification'])->name('notify.subscriptions.send.post');
        Route::delete('/{id}', [SubscriptionController::class, 'destroy'])->name('notify.subscriptions.destroy');
        Route::get('/send-all', [SubscriptionController::class, 'showSendAllForm'])->name('notify.subscriptions.send-all');
        Route::post('/send-all', [SubscriptionController::class, 'sendToAll'])->name('notify.subscriptions.send-all.post');
    });
    
    // Topics
    Route::prefix('topics')->group(function () {
        Route::get('/', [TopicController::class, 'index'])->name('notify.topics.index');
        Route::get('/create', [TopicController::class, 'create'])->name('notify.topics.create');
        Route::post('/', [TopicController::class, 'store'])->name('notify.topics.store');
        Route::get('/{id}/edit', [TopicController::class, 'edit'])->name('notify.topics.edit');
        Route::put('/{id}', [TopicController::class, 'update'])->name('notify.topics.update');
        Route::delete('/{id}', [TopicController::class, 'destroy'])->name('notify.topics.destroy');
        Route::get('/{id}/send', [TopicController::class, 'showSendForm'])->name('notify.topics.send');
        Route::post('/{id}/send', [TopicController::class, 'sendNotification'])->name('notify.topics.send.post');
    });
    
    // Messages
    Route::prefix('messages')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('notify.messages.index');
        Route::get('/create', [MessageController::class, 'create'])->name('notify.messages.create');
        Route::post('/', [MessageController::class, 'store'])->name('notify.messages.store');
        Route::get('/{id}/edit', [MessageController::class, 'edit'])->name('notify.messages.edit');
        Route::put('/{id}', [MessageController::class, 'update'])->name('notify.messages.update');
        Route::delete('/{id}', [MessageController::class, 'destroy'])->name('notify.messages.destroy');
    });
    
    // Scheduled Notifications
    Route::prefix('scheduled')->group(function () {
        Route::get('/', [ScheduledNotificationController::class, 'index'])->name('notify.scheduled.index');
        Route::get('/create', [ScheduledNotificationController::class, 'create'])->name('notify.scheduled.create');
        Route::post('/', [ScheduledNotificationController::class, 'store'])->name('notify.scheduled.store');
        Route::get('/{id}', [ScheduledNotificationController::class, 'show'])->name('notify.scheduled.show');
        Route::get('/{id}/edit', [ScheduledNotificationController::class, 'edit'])->name('notify.scheduled.edit');
        Route::put('/{id}', [ScheduledNotificationController::class, 'update'])->name('notify.scheduled.update');
        Route::post('/{id}/send-now', [ScheduledNotificationController::class, 'sendNow'])->name('notify.scheduled.send-now');
        Route::delete('/{id}', [ScheduledNotificationController::class, 'cancel'])->name('notify.scheduled.cancel');
    });
});