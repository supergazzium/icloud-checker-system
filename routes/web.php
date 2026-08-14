<?php
use App\Http\Controllers\{DashboardController, CheckController, OrderController, CreditController, LanguageController};
use App\Http\Controllers\Admin\{AdminDashboardController, AdminUserController, AdminServiceController, AdminOrderController, AdminSettingController, AdminBankAccountController, AdminTopupController};
use Illuminate\Support\Facades\Route;

Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');
Route::get('/terms',   fn() => view('legal.terms'))->name('legal.terms');
Route::get('/privacy', fn() => view('legal.privacy'))->name('legal.privacy');

require __DIR__.'/auth.php';

Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [\App\Http\Controllers\SocialAuthController::class, 'callback'])->name('social.callback');

Route::middleware(['auth','active.user','set.locale','password.change'])->group(function () {
    Route::get('/',         [DashboardController::class,'index'])->name('dashboard');
    Route::get('/check',    [CheckController::class,'index'])->name('check.index');
    Route::post('/check',   [CheckController::class,'store'])->middleware('throttle:10,1')->name('check.store');
    Route::get('/orders',         [OrderController::class,'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class,'show'])->name('orders.show');
    Route::get('/credits',  [CreditController::class,'index'])->name('credits.index');
    Route::post('/credits/topup',                 [CreditController::class,'topupStore'])->name('credits.topup.store');
    Route::get('/credits/topup/{topup}',           [CreditController::class,'topupShow'])->name('credits.topup.show');
    Route::get('/credits/topup/{topup}/slip',      [CreditController::class,'topupSlip'])->name('credits.topup.slip');
    Route::get('/credits/{transaction}/receipt',  [CreditController::class,'receipt'])->name('credits.receipt');

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/sessions/other', [\App\Http\Controllers\ProfileController::class, 'destroyOtherSessions'])->name('profile.sessions.destroy-others');
    Route::delete('/profile/sessions/{sessionId}', [\App\Http\Controllers\ProfileController::class, 'destroySession'])->name('profile.sessions.destroy');
});

Route::middleware(['auth','admin','set.locale','password.change','2fa'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/2fa', [\App\Http\Controllers\Admin\TwoFactorController::class,'show'])->name('2fa.show');
    Route::post('/2fa/enable', [\App\Http\Controllers\Admin\TwoFactorController::class,'enable'])->name('2fa.enable');
    Route::delete('/2fa', [\App\Http\Controllers\Admin\TwoFactorController::class,'disable'])->name('2fa.disable');
    Route::get('/',                               [AdminDashboardController::class,'index'])->name('dashboard');
    Route::get('/users',                          [AdminUserController::class,'index'])->name('users.index');
    Route::post('/users',                         [AdminUserController::class,'store'])->name('users.store');
    Route::get('/users/{user}',                   [AdminUserController::class,'show'])->name('users.show');
    Route::post('/users/{user}/topup',            [AdminUserController::class,'topup'])->name('users.topup');
    Route::post('/users/{user}/toggle-active',    [AdminUserController::class,'toggleActive'])->name('users.toggleActive');
    Route::get('/services',                       [AdminServiceController::class,'index'])->name('services.index');
    Route::post('/services',                      [AdminServiceController::class,'store'])->name('services.store');
    Route::put('/services/{service}',             [AdminServiceController::class,'update'])->name('services.update');
    Route::post('/services/{service}/toggle',     [AdminServiceController::class,'toggleActive'])->name('services.toggle');
    Route::get('/orders',                         [AdminOrderController::class,'index'])->name('orders.index');
    Route::get('/orders/export',                  [AdminOrderController::class,'export'])->name('orders.export');
    Route::get('/orders/{order}',                 [AdminOrderController::class,'show'])->name('orders.show');
    Route::post('/orders/{order}/refund',         [AdminOrderController::class,'refund'])->name('orders.refund');
    Route::get('/settings',                       [AdminSettingController::class,'index'])->name('settings.index');
    Route::post('/settings',                      [AdminSettingController::class,'update'])->name('settings.update');
    Route::get('/audit',                           [\App\Http\Controllers\Admin\AdminAuditController::class,'index'])->name('audit.index');

    // Bank accounts (used as topup destinations)
    Route::get('/bank-accounts',                            [AdminBankAccountController::class,'index'])->name('bank-accounts.index');
    Route::post('/bank-accounts',                           [AdminBankAccountController::class,'store'])->name('bank-accounts.store');
    Route::put('/bank-accounts/{bankAccount}',              [AdminBankAccountController::class,'update'])->name('bank-accounts.update');
    Route::post('/bank-accounts/{bankAccount}/toggle',      [AdminBankAccountController::class,'toggleActive'])->name('bank-accounts.toggle');
    Route::delete('/bank-accounts/{bankAccount}',           [AdminBankAccountController::class,'destroy'])->name('bank-accounts.destroy');

    // Topup review
    Route::get('/topups',                          [AdminTopupController::class,'index'])->name('topups.index');
    Route::get('/topups/{topup}',                  [AdminTopupController::class,'show'])->name('topups.show');
    Route::post('/topups/{topup}/approve',         [AdminTopupController::class,'approve'])->name('topups.approve');
    Route::post('/topups/{topup}/reject',          [AdminTopupController::class,'reject'])->name('topups.reject');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/2fa/challenge', [\App\Http\Controllers\Admin\TwoFactorController::class,'challenge'])->name('admin.2fa.challenge');
    Route::post('/admin/2fa/verify',   [\App\Http\Controllers\Admin\TwoFactorController::class,'verify'])->name('admin.2fa.verify');
});
