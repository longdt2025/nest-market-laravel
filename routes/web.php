<?php
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/',[ShopController::class,'home'])->name('shop.home');
Route::get('/san-pham',[ShopController::class,'index'])->name('shop.index');
Route::get('/san-pham/{product:slug}',[ShopController::class,'show'])->name('shop.show');
Route::get('/gio-hang',[CartController::class,'index'])->name('cart.index');
Route::post('/gio-hang/{product}',[CartController::class,'store'])->name('cart.store');
Route::patch('/gio-hang/{product}',[CartController::class,'update'])->name('cart.update');
Route::delete('/gio-hang/{product}',[CartController::class,'destroy'])->name('cart.destroy');

Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap',[AuthController::class,'loginForm'])->name('login');
    Route::post('/dang-nhap',[AuthController::class,'login'])->name('login.submit')->middleware('throttle:10,1');
    Route::get('/dang-ky',[AuthController::class,'registerForm'])->name('register');
    Route::post('/dang-ky',[AuthController::class,'register'])->name('register.submit')->middleware('throttle:10,1');
});
Route::post('/dang-xuat',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::middleware(['auth','customer'])->group(function () {
    Route::get('/tinh-thanh',[ProvinceController::class,'index'])->name('location.provinces')->middleware('throttle:30,1');
    Route::get('/thanh-toan',[CheckoutController::class,'create'])->name('checkout.create');
    Route::post('/thanh-toan',[CheckoutController::class,'store'])->name('checkout.store')->middleware('throttle:10,1');
    Route::get('/don-hang',[OrderController::class,'index'])->name('orders.index');
    Route::get('/don-hang/{order}',[OrderController::class,'show'])->name('orders.show');
});
Route::prefix('admin')->name('admin.')->middleware(['auth','admin'])->group(function () {
    Route::get('/',[DashboardController::class,'index'])->name('dashboard');
    Route::resource('categories',AdminCategoryController::class)->except('show');
    Route::resource('products',AdminProductController::class)->except('show');
    Route::get('orders',[AdminOrderController::class,'index'])->name('orders.index');
    Route::get('orders/{order}',[AdminOrderController::class,'show'])->name('orders.show');
    Route::patch('orders/{order}/status',[AdminOrderController::class,'update'])->name('orders.update');
});
