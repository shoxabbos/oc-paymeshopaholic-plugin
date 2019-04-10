<?php 
use Illuminate\Http\Request;
use Shohabbos\Paymeshopaholic\Classes\PaymeHandler;

Route::any('/payme-webhook', function (Request $request) {
    $handler = new PaymeHandler();
    return $handler->listen($request);
});
