<?php 
use Illuminate\Http\Request;
use Shohabbos\Paymeshopaholic\Classes\PaymeHandler;

Route::any('/shohabbos/paymeshopaholic/webhook', function (Request $request) {
    $handler = new PaymeHandler();
    return $handler->listen($request);
});
