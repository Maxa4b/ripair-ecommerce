<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\InvoiceController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Account\RmaController as AccountRmaController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RmaController as AdminRmaController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\TransportController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CatalogController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\ProductController;
use App\Http\Controllers\Front\ProRegistrationController;
use App\Http\Controllers\Front\SearchController;
use App\Http\Controllers\Front\StaticPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SAV\RmaFrontController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use App\Models\Legacy\Repair;
use Illuminate\Support\Facades\Route;

Route::bind('repair', function ($value) {
    return Repair::findOrFail($value);
});

Route::middleware(['web'])->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/catalogue', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/catalogue/{category}', [CatalogController::class, 'show'])->name('catalog.show');
    Route::get('/catalogue/{category}/{model?}', [CatalogController::class, 'filter'])->name('catalog.filter');
    Route::get('/produits/{repair}/{slug?}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/recherche', [SearchController::class, 'index'])->name('search');
    Route::get('/recherche/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');

    Route::get('/panier', [CartController::class, 'show'])->name('cart.show');
    Route::post('/panier/articles', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/panier/articles/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/panier/articles/{item}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::post('/panier/promo', [CartController::class, 'applyPromo'])->name('cart.promo');
    Route::delete('/panier/promo', [CartController::class, 'removePromo'])->name('cart.promo.remove');

    Route::prefix('checkout')->name('checkout.')->group(function (): void {
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::post('/identification', [CheckoutController::class, 'identify'])->name('identify');
        Route::post('/adresses', [CheckoutController::class, 'storeAddresses'])->name('addresses');
        Route::post('/livraison', [CheckoutController::class, 'selectShipping'])->name('shipping');
        Route::match(['get', 'post'], '/livraison/options', [CheckoutController::class, 'shippingOptionsAjax'])->name('shipping.options');
        Route::post('/paiement', [CheckoutController::class, 'pay'])->name('payment');
        Route::get('/paypal/success', [CheckoutController::class, 'paypalSuccess'])->name('paypal.success');
        Route::get('/paypal/cancel', [CheckoutController::class, 'paypalCancel'])->name('paypal.cancel');
        Route::get('/confirmation/{order:number}', [CheckoutController::class, 'confirmation'])->name('confirmation');
    });

    Route::get('/pro/inscription', [ProRegistrationController::class, 'create'])->name('pro.apply');
    Route::post('/pro/inscription', [ProRegistrationController::class, 'store'])->name('pro.apply.store');

    Route::middleware('auth')->group(function (): void {
        Route::get('/sav/demande', [RmaFrontController::class, 'create'])->name('sav.request');
        Route::post('/sav/demande', [RmaFrontController::class, 'store'])->name('sav.request.store');
    });

    Route::prefix('mon-compte')
        ->middleware('auth')
        ->name('account.')
        ->group(function (): void {
            Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
            Route::get('/profil', [AccountController::class, 'profile'])->name('profile');
            Route::put('/profil', [AccountController::class, 'updateProfile'])->name('profile.update');

            Route::resource('adresses', AddressController::class)
                ->except(['show', 'create', 'edit'])
                ->parameters(['adresses' => 'address']);
            Route::resource('commandes', AccountOrderController::class)
                ->only(['index', 'show'])
                ->parameters(['commandes' => 'order']);
            Route::resource('factures', InvoiceController::class)
                ->only(['index', 'show'])
                ->parameters(['factures' => 'invoice']);
            Route::get('factures/{invoice}/telecharger', [InvoiceController::class, 'download'])
                ->name('factures.download');
            Route::resource('sav', AccountRmaController::class)
                ->only(['index', 'show'])
                ->parameters(['sav' => 'rmaRequest']);
            Route::post('sav/{rmaRequest}/comment', [AccountRmaController::class, 'comment'])
                ->name('sav.comment');
            Route::get('sav/piece/{attachment}', [AccountRmaController::class, 'downloadAttachment'])
                ->name('sav.attachment');
            Route::post('sav/{rmaRequest}/close', [AccountRmaController::class, 'close'])
                ->name('sav.close');
        });

    Route::prefix('espace-pro')
        ->middleware(['auth', 'pro'])
        ->name('pro.')
        ->group(function (): void {
            Route::get('/', [AccountController::class, 'proDashboard'])->name('dashboard');
            Route::get('/tarifs', [AccountController::class, 'pricing'])->name('pricing');
            Route::get('/exports/commandes', [AccountOrderController::class, 'export'])->name('orders.export');
        });

    Route::prefix('admin')
        ->name('admin.')
        ->middleware(['auth', 'role:super-admin,gestionnaire-catalogue,logistique,sav'])
        ->group(function (): void {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::resource('catalogue-produits', AdminProductController::class)->parameters(['catalogue-produits' => 'product']);
            Route::resource('commandes', AdminOrderController::class)->only(['index', 'show', 'update'])->parameters(['commandes' => 'order']);
            Route::resource('stocks-mouvements', StockController::class)->only(['index', 'store']);
            Route::resource('sav-rma', AdminRmaController::class)->only(['index', 'show', 'update']);
            Route::resource('transporteurs', TransportController::class)->except(['show'])->parameters(['transporteurs' => 'shippingMethod']);
            Route::resource('pro', ProController::class)->only(['index', 'update'])->parameters(['pro' => 'pro']);
            Route::get('contenus/pages', [ContentController::class, 'pages'])->name('content.pages');
            Route::post('contenus/pages', [ContentController::class, 'update'])->name('content.pages.update');
            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        });

    Route::view('/mentions-legales', 'pages.legal')->name('legal.mentions');
    Route::view('/conditions-generales', 'pages.cgv')->name('legal.cgv');
    Route::view('/politique-confidentialite', 'pages.confidentialite')->name('legal.privacy');
    Route::view('/cookies', 'pages.cookies')->name('legal.cookies');
    Route::get('/atelier', [StaticPageController::class, 'workshop'])->name('workshop');
});

Route::get('/dashboard', function () {
    // Remplace l'écran "You're logged in" par un redirect vers l'espace compte.
    return redirect()->route('account.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/stripe/webhook', StripeWebhookController::class)
    ->name('stripe.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

require __DIR__.'/auth.php';
