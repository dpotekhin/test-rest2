<?php

return [

    'adminEmail' => 'admin@example.com',
    'user.passwordResetTokenExpire' => 3600,
//    'user.passwordResetTokenExpire' => 1,
    'supportEmail' => 'support@example.com',


    // API MAIN
    'api.info.dev_token' => 'peppers_rulez',
    'api.authWithToken' => false, // TODO: it needs to be done
    'api.confirmEmailAfterReg' => true, // Need to confirm email after registration
    'api.confirmNeededToLogin' => false, // TODO: it needs to be done


    // SEND MAIL SETTINGS
    'mail.sendOnRegister' => true,


    // LOCALS
//    'mail.password_reseted_title' => ,

];

//function _(){ return 'Password was reseted for ' . \Yii::$app->name; }
