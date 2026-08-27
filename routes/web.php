<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Pages\ApiManagement\ApiBalanceController;
use App\Http\Controllers\Pages\ApiManagement\ApiClientController;
use App\Http\Controllers\Pages\ApiManagement\ApiCredentialController;
use App\Http\Controllers\Pages\DashboardController;
use App\Http\Controllers\Pages\Finance\ExpenseController;
use App\Http\Controllers\Pages\Finance\FinanceCategoryController;
use App\Http\Controllers\Pages\Finance\IncomeController;
use App\Http\Controllers\Pages\RoleController;
use App\Http\Controllers\Pages\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('post.login');

Route::middleware(['auth'])->group(function () {
    // logout
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

    // dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/seed-dummy', [DashboardController::class, 'seedDummyData'])->name('dashboard.seed-dummy');

    // data role
    Route::prefix('role')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('role.index')->can('lihat level');
        Route::post('/store', [RoleController::class, 'store'])->name('role.store')->can('tambah level');
        Route::get('/{id}/permission', [RoleController::class, 'permission'])->name('role.permission')->can('edit level');
        Route::put('/{id}/savePermission', [RoleController::class, 'savePermission'])->name('role.savePermission')->can('edit level');
        Route::get('/{id}/show', [RoleController::class, 'show'])->name('role.show')->can('lihat level');
        Route::put('/{id}/update', [RoleController::class, 'update'])->name('role.update')->can('edit level');
        Route::delete('/{id}/destroy', [RoleController::class, 'destroy'])->name('role.destroy')->can('hapus level');
    });

    // data users
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('user.index')->can('lihat user');
        Route::get('/create', [UserController::class, 'create'])->name('user.create')->can('buat user');
        Route::post('/store', [UserController::class, 'store'])->name('user.store')->can('buat user');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('user.edit')->can('ubah user');
        Route::put('/{id}/update', [UserController::class, 'update'])->name('user.update')->can('ubah user');
        Route::delete('/{id}/destroy', [UserController::class, 'destroy'])->name('user.destroy')->can('hapus user');
    });

    // finance internal
    Route::prefix('pemasukan')->group(function () {
        Route::get('/', [IncomeController::class, 'index'])->name('income.index')->can('lihat pemasukan');
        Route::get('/create', [IncomeController::class, 'create'])->name('income.create')->can('tambah pemasukan');
        Route::post('/store', [IncomeController::class, 'store'])->name('income.store')->can('tambah pemasukan');
        Route::get('/{id}/edit', [IncomeController::class, 'edit'])->name('income.edit')->can('edit pemasukan');
        Route::put('/{id}/update', [IncomeController::class, 'update'])->name('income.update')->can('edit pemasukan');
        Route::delete('/{id}/destroy', [IncomeController::class, 'destroy'])->name('income.destroy')->can('hapus pemasukan');
    });

    Route::prefix('pengeluaran')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('expense.index')->can('lihat pengeluaran');
        Route::get('/create', [ExpenseController::class, 'create'])->name('expense.create')->can('tambah pengeluaran');
        Route::post('/store', [ExpenseController::class, 'store'])->name('expense.store')->can('tambah pengeluaran');
        Route::get('/{id}/edit', [ExpenseController::class, 'edit'])->name('expense.edit')->can('edit pengeluaran');
        Route::put('/{id}/update', [ExpenseController::class, 'update'])->name('expense.update')->can('edit pengeluaran');
        Route::delete('/{id}/destroy', [ExpenseController::class, 'destroy'])->name('expense.destroy')->can('hapus pengeluaran');
    });

    Route::prefix('kategori-keuangan')->group(function () {
        Route::get('/', [FinanceCategoryController::class, 'index'])->name('finance-category.index')->can('lihat kategori keuangan');
        Route::post('/store', [FinanceCategoryController::class, 'store'])->name('finance-category.store')->can('tambah kategori keuangan');
        Route::put('/{id}/update', [FinanceCategoryController::class, 'update'])->name('finance-category.update')->can('edit kategori keuangan');
        Route::delete('/{id}/destroy', [FinanceCategoryController::class, 'destroy'])->name('finance-category.destroy')->can('hapus kategori keuangan');
    });

    // api management
    Route::prefix('api-management')->middleware('can:Api Management')->group(function () {
        Route::get('/', [ApiClientController::class, 'index'])->name('api-management.index');
        Route::get('/create', [ApiClientController::class, 'create'])->name('api-management.create');
        Route::post('/store', [ApiClientController::class, 'store'])->name('api-management.store');
        Route::get('/{id}', [ApiClientController::class, 'show'])->name('api-management.show');
        Route::get('/{id}/provisioned', [ApiClientController::class, 'provisioned'])->name('api-management.provisioned');
        Route::get('/{id}/edit', [ApiClientController::class, 'edit'])->name('api-management.edit');
        Route::put('/{id}/update', [ApiClientController::class, 'update'])->name('api-management.update');
        Route::post('/{id}/disable', [ApiClientController::class, 'disable'])->name('api-management.disable');
        Route::post('/{id}/enable', [ApiClientController::class, 'enable'])->name('api-management.enable');
        Route::post('/{id}/revoke', [ApiClientController::class, 'revoke'])->name('api-management.revoke');

        Route::post('/{id}/credentials/rotate', [ApiCredentialController::class, 'rotate'])->name('api-management.credentials.rotate');
        Route::post('/credentials/{credentialId}/revoke', [ApiCredentialController::class, 'revoke'])->name('api-management.credentials.revoke');
    });

    // pengaturan saldo website
    Route::prefix('saldo-website')->group(function () {
        Route::get('/', [ApiBalanceController::class, 'index'])->name('saldo-website.index')->can('lihat saldo');
        Route::post('/{id}/adjust', [ApiBalanceController::class, 'adjust'])->name('saldo-website.adjust')->can('buat saldo');
    });
});
