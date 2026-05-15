<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\socialAuthController;
use App\Http\Controllers\UsersContoller;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\Api\ReviewController;

use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport;

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



// Public Routes
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/passowrd', 'sendResetLink');
    Route::post('/reset-password', 'reset');
});

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/category/{id}', [CategoryController::class, 'show']);
Route::post('/search-category', [CategoryController::class, 'search']);
Route::get('/category/{id}/products', [CategoryController::class, 'getProducts']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/product/{id}', [ProductController::class, 'show']);
Route::get('/latest-sale', [ProductController::class, 'getLastSaleProducts']);
Route::get('/latest', [ProductController::class, 'getLatest']);
Route::get('/top-rated', [ProductController::class, 'getTopRated']);

Route::post('/cart/check', [CartController::class, 'check']);


Route::get('/login-google', [socialAuthController::class, 'redirectToProvider']);
Route::get('/auth/google/callback', [socialAuthController::class, 'handleCallback']);

Route::post('/create-checkout-session', [PaymentController::class, 'createCheckoutSession']);
Route::post('/stripe-webhook', [PaymentController::class, 'handleWebhook']);

Route::post('/contact', [ContactController::class, 'store']);

Route::get('/reviews/{productId}', [ReviewController::class, 'getProductReviews']);
Route::get('/latest-reviews', [ReviewController::class, 'getLatestReviews']);

// Protected Routes
Route::middleware('auth:api')->group(function () {
    // User
    Route::get('/user', [UsersContoller::class, 'authUser']);
    Route::post('/user/profile/edit', [UsersContoller::class, 'editProfile']);
    // Stats
    Route::get('/dashboard/stats', [OrderController::class, 'getDashboardStats']);
    Route::post('/checkout', [OrderController::class, 'store']);
    Route::get('/export-orders', [OrderController::class, 'export']);
    Route::get('/my-orders', [OrderController::class, 'myOrders']);
    Route::put('/user/order-cancel/{id}', [OrderController::class, 'userCancelOrder']);
    // Favorites
    Route::post('/favorite/toggle/{id}', [FavoriteController::class, 'toggleFavorite']);
    Route::get('/favorites', [FavoriteController::class, 'index']);
    // reviews
    Route::post('/reviews', [ReviewController::class, 'store']);

    Route::middleware('checkAdmin')->controller(UsersContoller::class)->group(function () {
        // Users
        Route::get('/users', 'GetUsers');
        Route::get('/user/{id}', 'getUser');
        Route::post('/search-user', 'search');
        Route::post('/user/edit/{id}', 'editUser');
        Route::post('/user/add', 'addUser');
        Route::delete('/user/{id}', 'destroy');
        // Orders
        Route::put('/orders/{id}/status', [OrderController::class, 'changeStatus']);
        Route::get('/orders', [OrderController::class, 'allOrdersAdmin']);
        Route::post('/orders/update-status', [OrderController::class, 'bulkUpdateStatus']);
        Route::post('/search-order', [OrderController::class, 'search']);
        // Messages Contacts
        Route::get('/messages-contacts', [App\Http\Controllers\ContactController::class, 'index']);
        Route::post('/contacts/{id}/reply', [ContactController::class, 'sendReply']);
        Route::delete('/contact/{id}', [ContactController::class, 'destroy']);
    });
    // Product Manger
    Route::middleware('checkProductManager')->controller(CategoryController::class)->group(function () {
        Route::post('/category/edit/{id}', 'edit');
        Route::post('/category/add', 'store');
        Route::delete('/category/{id}', 'destroy');
    });

    Route::middleware('checkProductManager')->controller(ProductController::class)->group(function () {
        // Products
        Route::post('/search-product',  'search');
        Route::post('/product/edit/{id}', 'update');
        Route::post('/product/add', 'store');
        Route::delete('/product/{id}', 'destroy');
    });
    Route::middleware('checkProductManager')->controller(ProductImageController::class)->group(function () {
        Route::post('/product-img/add', 'store');
        Route::delete('/product-img/{id}', 'destroy');
    });

    Route::post('/cart', [CartController::class, 'store']);
    Route::get('/cart', [CartController::class, 'index']);


    // Checkout



    // Auth
    Route::get('/logout', [AuthController::class, 'logout']);
});
