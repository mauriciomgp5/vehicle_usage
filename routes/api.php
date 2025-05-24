<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvolutionWebhookController;

// ... existing code ...

Route::post('/webhook/evolution', [EvolutionWebhookController::class, 'handleWebhook']); 