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
        'input_empty:first_name' => '#{attribute} cannot be blank.',
        'input_empty:last_name' => '#{attribute} cannot be blank.',
        'input_empty:personal_data_agreement' => '#{attribute} cannot be blank.',
        'input_empty:password' => '#{attribute} cannot be blank.',
        'input_empty:password' => '#{attribute} cannot be blank.',
        'input_string:too_short' => '#{attribute} should contain at least {min} characters.',
        'input_string:too_long' => '#{attribute} should contain at most {max} characters.',
        'input_email:wrong' => '#{attribute} is not a valid email address.',

        // CUSTOM ERRORS
        'input:personal_data_agreement' => 'It is necessary to confirm the agreement on the processing of personal data.',
        'input_empty:token' => '#Token cannot be blank.',
        'input:wrong_auth' => '#Wrong login or password.',

        'db:error' => '#DB error.',

        'username:used' => '#Username is used already.',
        'email:used' => '#Email is used already.',

        'user:not_logged_in' => '#User is not logged in.',
        'user:not_found' => '#User is not found.',
        'user:not_active' => '#User is not activated.',
        'email:confirm_not_required' => '#Email confirmation is not required.',
        'email:is_confirmed_already' => '#Email is confirmed already.',

        // TOKEN
        'token:expired' => '#Token is expired.',
        'token:send_timeout' => '#Token send timeout is not completed yet.',

        // EMAIL
        'mail:confirm_email_sent' => '#The letter with the email confirmation link was sent to {email}.',
        'mail:password_reset_sent' => '#The letter with the password reset link was sent to {email}.',
        'mail:password_changed_sent' => '#The notification of password changing was sent to {email}.',
        'mail:send_error' => '#Mail sending error.',
        'mail:not_sent' => '#Mail is not sended.',
    ],

];

//function _(){ return 'Password was reseted for ' . \Yii::$app->name; }
