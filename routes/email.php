<?php

use Illuminate\Support\Facades\Route;

//use App\Mail\TestEmail;

Route::get('/dev/test-email', function () {

    abort_unless(app()->environment('local'), 403);

    Mail::raw('Laravel Mercury test email.', function ($message) {

        $message->to('test@localhost')
            ->subject('Development Test Email');

    });

    return 'Email sent.';
});