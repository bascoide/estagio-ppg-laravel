<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\UserUploadFinalDocumentController;
use App\Http\Controllers\DocumentValidationController;
use App\Http\Controllers\CoursesController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\ProfessorController;

// Public routes
Route::get('/',  [AuthController::class, 'showLogin']);
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/user-verification', [AuthController::class, 'verifyUser'])->name('user-verification');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated user routes
Route::middleware(['auth.custom'])->group(function () {
    Route::get('/guia-form',   [FormController::class, 'index'])->name('guia-form');
    Route::get('/form',        [FormController::class, 'form'])->name('form');
    Route::get('/get-form',    [FormController::class, 'generateForm'])->name('get-form');
    Route::post('/submit-form', [FormController::class, 'submitForm'])->name('submit-form');

    Route::post('/print-pdf',       [DocumentController::class, 'printDocument'])->name('print-pdf');
    Route::post('/print-document',   [DocumentController::class, 'printDocumentForm'])->name('print-document');
    Route::post('/print-addition',   [DocumentController::class, 'viewAddition'])->name('print-addition');
    Route::post('/download-docx',    [DocumentController::class, 'downloadDocument'])->name('download-docx');

    Route::get('/user-upload-final-document-form',  [UserUploadFinalDocumentController::class, 'index'])->name('user-upload-final-document-form');
    Route::post('/user-upload-final-document',      [UserUploadFinalDocumentController::class, 'uploadFinalDocument'])->name('user-upload-final-document');
});

// Admin routes
Route::middleware(['admin'])->group(function () {
    Route::get('/view-pending-documents',   [AdminPanelController::class, 'viewPendingDocuments'])->name('view-pending-documents');
    Route::get('/need-validation-documents', [AdminPanelController::class, 'viewNeedValidationDocuments'])->name('need-validation-documents');
    Route::get('/view-validation-documents', [AdminPanelController::class, 'viewValidationDocuments'])->name('view-validation-documents');

    Route::match(['GET', 'POST'], '/create-admin', [AdminPanelController::class, 'createAdmin'])->name('create-admin');
    Route::get('/show-users',    [AdminPanelController::class, 'showUsers'])->name('show-users');
    Route::get('/show-documents', [AdminPanelController::class, 'showDocuments'])->name('show-documents');
    Route::get('/user-documents', [AdminPanelController::class, 'viewUserDocuments'])->name('user-documents');
    Route::get('/addition-document', [AdminPanelController::class, 'viewAdditionDocuments'])->name('addition-document');

    Route::get('/upload-document-form',  [DocumentController::class, 'uploadDocumentForm'])->name('upload-document-form');
    Route::post('/upload-document',      [DocumentController::class, 'createNewDocumentAndFields'])->name('upload-document');
    Route::post('/deactivate-document',  [DocumentController::class, 'deactivateDocument'])->name('deactivate-document');
    Route::post('/activate-document',    [DocumentController::class, 'activateDocument'])->name('activate-document');
    Route::post('/print-document-admin', [DocumentController::class, 'printDocumentForm'])->name('print-document-admin');
    Route::post('/view-plan',            [DocumentController::class, 'viewPlan'])->name('view-plan');

    Route::get('/president-upload-final-document-form', [DocumentValidationController::class, 'presidentValidationPage'])->name('president-upload-final-document-form');
    Route::post('/president-final-document',            [DocumentValidationController::class, 'presidentFinalDocument'])->name('president-final-document');
    Route::post('/validate-document',                   [DocumentValidationController::class, 'validateDocument'])->name('validate-document');
    Route::post('/invalidate-document',                 [DocumentValidationController::class, 'invalidateDocument'])->name('invalidate-document');
    Route::match(['GET', 'POST'], '/president-list',    [DocumentValidationController::class, 'listPresidents'])->name('president-list');
    Route::post('/delete-president-email',              [DocumentValidationController::class, 'deletePresidentEmail'])->name('delete-president-email');

    Route::get('/view-final-document',  [AdminPanelController::class, 'viewFinalDocument'])->name('view-final-document');
    Route::get('/view-final-document-admin', [AdminPanelController::class, 'viewFinalDocumentAdmin'])->name('view-final-document-admin');
    Route::post('/edit-document',        [AdminPanelController::class, 'editFinalDocument'])->name('edit-document');
    Route::post('/cancel-document',      [AdminPanelController::class, 'cancelFinalDocument'])->name('cancel-document');

    Route::get('/courses',              [CoursesController::class, 'index'])->name('courses');
    Route::post('/edit-course',         [CoursesController::class, 'editCourseName'])->name('edit-course');
    Route::post('/course/toggle-status', [CoursesController::class, 'toggleStatus'])->name('course.toggle-status');
    Route::post('/add-course',           [CoursesController::class, 'addCourse'])->name('add-course');
    Route::post('/delete-course',        [CoursesController::class, 'deleteCourse'])->name('delete-course');

    Route::get('/professor-search',     [ProfessorController::class, 'index'])->name('professor-search');
    Route::get('/professor-documents',  [ProfessorController::class, 'professorDocuments'])->name('professor-documents');
    Route::post('/create-report',       [ProfessorController::class, 'createReport'])->name('create-report');
    Route::post('/create-status-excel', [ProfessorController::class, 'createStatusExcel'])->name('create-status-excel');

    Route::get('/admin-documentation',  [AdminPanelController::class, 'viewDocumentation'])->name('admin-documentation');
    Route::get('/admin-logs',           [LogsController::class, 'index'])->name('admin-logs');
    Route::get('/set-name',             [AuthController::class, 'showSetName'])->name('set-name');
    Route::post('/set-name',            [AuthController::class, 'setAdminName']);
});
