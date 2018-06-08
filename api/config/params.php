<?php

return [

    'adminEmail' => 'admin@example.com',
    'api.tokenSendTimeout' => 10,
    'api.tokenResetExpire' => 3600,
//    'user.passwordResetTokenExpire' => 1,
    'supportEmail' => 'support@example.com',


    // API MAIN
//    'api.info.dev_token' => 'peppers_rulez',
    'api.sendDetailsOnDBError' => true,
    'api.authWithToken' => false, // TODO: it needs to be done
    'api.confirmEmailAfterReg' => true, // Need to confirm email after registration
    'api.confirmNeededToLogin' => false, // TODO: it needs to be done

    'api.passwordMinLength' => 6, //
    'api.passwordMaxLength' => 20, //


    // SEND MAIL SETTINGS
    'mail.sendOnRegister' => true,


    // LOCALS
    'locals' => [

        // NATIVE ERRORS
        'input_empty:username' => '#{attribute} cannot be blank.',
        'input_empty:email' => '#{attribute} cannot be blank.',
        'input_empty:password' => '#{attribute} cannot be blank.',
        'input_string:too_short' => '#{attribute} should contain at least {min} characters.',
        'input_string:too_long' => '#{attribute} should contain at most {max} characters.',
        'input_email:wrong' => '#{attribute} is not a valid email address.',

        // CUSTOM ERRORS
        'input_empty:token' => '#Token cannot be blank.',
        'input:wrong_auth' => '#Wrong login or password.',

        'db:error' => '#DB error.',

        'username:used' => '#Username is used already.',
        'email:used' => '#Email is used already.',
        'mail:confirm_email_sended' => '#The letter with email confirmation link sended to {email}.',

        'user:not_logged_in' => '#User is not logged in.',
        'user:not_found' => '#User is not found.',
        'user:not_active' => '#User is not activated.',
        'email:confirm_not_required' => '#Email confirmation is not required.',
        'email:is_confirmed_already' => '#Email is confirmed already.',

        'token:expired' => '#Token is expired.',
        'token:send_timeout' => '#Token send timeout is not completed yet.',
    ],

];

//function _(){ return 'Password was reseted for ' . \Yii::$app->name; }
