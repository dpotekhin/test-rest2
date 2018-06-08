<?php
namespace app\modules\v1\components;

use common\models\User;
use Yii;
//use app\modules\v1\models\APIUser;
use yii\base\DynamicModel;
use yii\db\Exception;

/**
 * User Controller
 */
class APIUserController extends APIController
{

    public $modelClass = 'app\modules\v1\models\APIUser';

    // keys for user attributes filtration
    public $allowed_user_attributes = [
        'id',
        'username',
        'email',
        'status',
        'email_confirmed'
    ];

    public function actions()
    {
        $actions = parent::actions(); // TODO: Change the autogenerated stub

        // disable the "delete" and "create" actions
        unset(
            $actions['delete'],
            $actions['create']
        );

        return $actions;
    }



    public function behaviors()
    {
        $behaviors = parent::behaviors();

        if( Yii::$app->params['api.authWithToken'] ) {

            $behaviors['authenticator']['except'] = array_merge($behaviors['authenticator']['except'], [
                'reg',
                'confirm-email-send',
                'confirm-email',
                'login',
                'auth',
                'logout',
                'recover-password',
                'reset-password',
            ]);
        }

        return $behaviors;
    }

    public function methods()
    {
        $methods = parent::methods();
        $methods = array_merge( $methods, [
            'reg' => [
                'request' => [
                    'username(string)',
                    'password(string)',
                ],
                "response" => $this->allowed_user_attributes,
            ],
            'confirm-email-send' => [
                'request' => [
                    '-- only for authorized --',
                    'dev_token(string) optional dev!'
                ],
                "response" => [
                    'dev!: email_confirm_token(string)',
                ],
            ],
            'confirm-email' => [
                'request' => [
                    'token(string)',
                ],
                "response" => []
            ],
            'login' => [
                'request' => [
                    'username(string)',
                    'password(string)'
                ],
                "response" => $this->allowed_user_attributes,
            ],
            'auth' => [
                'request' => [
                    '-- only for authorized --',
                ],
                "response" => $this->allowed_user_attributes,
            ],
            'edit' => [
                'request' => [
                    '-- only for authorized --',
                    'username(string)',
                    'email(string)',
                    'password(string)',
                ],
                "response" => $this->allowed_user_attributes,
            ],
            'logout' => [
                'request' => [
                    '-- only for authorized --',
                ],
                "response" => []
            ],
            'recover-password' => [
                'request' => [
                    'email(string)',
                    'dev_token(string) optional dev!',
                ],
                "response" => [
                    'dev!: password_confirm_token(string)',
                ]
            ],
            'reset-password' => [
                'request' => [
                    'password(string)',
                    'token(string)',
                ],
                "response" => []
            ],
        ]);
        return $methods;
    }






    // VVVVVVVVVVVVVVV---   REG   ---VVVVVVVVVVVVVVVV
    public function actionReg()
    {
        $app_params = Yii::$app->params;
        $post = $this->POST;
        $locals = $this->getLocals();
        //

        $model = DynamicModel::validateData([
            'username' => $post['username'],
            'email' => $post['email'],
            'password' => $post['password'],
        ], [
            [['username'], 'required', 'message' => $locals['input_empty:username']],
            [['email'], 'required', 'message' => $locals['input_empty:email']],
            [['password'], 'required', 'message' => $locals['input_empty:password']],
            [['username', 'email'], 'string', 'min' => 3, 'max' => 50, 'tooLong' => $locals['input_string:too_long'], 'tooShort' => $locals['input_string:too_short'] ],
            ['email', 'email', 'message' => $locals['input_email:wrong']],
            [['password'], 'string', 'min' => $app_params['api.passwordMinLength'], 'max' => $app_params['api.passwordMaxLength'], 'tooLong' => $locals['input_string:too_long'], 'tooShort' => $locals['input_string:too_short'] ],
        ]);

        if ($model->hasErrors()) return $this->returnErrors( $model->errors );

        // is the name unique
        if( User::findOne(["username" => $post['username']]) ){
            return $this->returnErrors(['username' => $locals['username:used'] ]);
        }

        // is the email unique
        if( User::findOne(["email" => $post['email']]) ){
            return $this->returnErrors(['email' => $locals['email:used'] ]);
        }

        // Create new User
        $user = new User();
        $user->username = $post['username'];
        $user->email = $post['email'];
        $user->setPassword( $post['password'] );
        $user->generateAuthKey();

        // Setup email confirmation
        if( $app_params['api.confirmEmailAfterReg'] ){
            $user->email_confirm_token = Yii::$app->security->generateRandomString() . '_' . time();
        }

        // Save
        try{
            $user->save(false);
        }catch(Exception $e){
            return $this->returnErrors(['db_error' => $e->errorInfo]);
        }

        $user->refresh();
        $response = [ 'user' => $this->cleanupUserData( $user->attributes ) ];

        // Send Mail
        if( $app_params['mail.sendOnRegister'] || $app_params['api.confirmEmailAfterReg'] ){

            $this->sendMail(
                $user->email,
                'Registration on ' . Yii::$app->name,
                ['html' => 'userRegistered-API-html', 'text' => 'userRegistered-API-text'],
                ['user' => $user ]
            );

            $response = array_merge( $response, [ "message" => $locals['mail:confirm_email_sent'] ] );

        }

        // Auth User // TODO: implement this

        return $this->returnSuccess( $response );
//        return $this->returnSuccess( ['user' => $this->cleanupUserData( $user->attributes ), 'email_confirm_token' => $user->email_confirm_token  ]); // !!! DEBUG

    }
    // ^^^^^^^^^^^^^^^---   REG   ---^^^^^^^^^^^^^^^^





    // VVVVVVVVVVVVVVV---   CONFIRM EMAIL SEND   ---VVVVVVVVVVVVVVVV
    public function actionConfirmEmailSend()
    {
        $app_params = Yii::$app->params;
        $post = $this->POST;
        $locals = $this->getLocals();
        //

        // USER
        if(  !($user = $this->getUser()) ) return $this->returnErrors();

        // is confirmation required
        if( !$app_params['api.confirmEmailAfterReg'] ){
            return $this->returnErrors(['email_confirm_is_not_required' => $locals['email:confirm_not_required'] ]);
        }

        // is user already confirm his email
        if( $user->email_confirmed ) {
            return $this->returnErrors(['email_is_confirmed' => $locals['email:is_confirmed_already'] ]);
        }

        // token send timeout
        if( !$this->canSendToken( $user->email_confirm_token ) ){
            return $this->returnErrors(['token_send_timeout' => $locals['token:send_timeout'] ]);
        }

        // Setup email confirmation
        $user->email_confirm_token = $this->generateToken();

        // Save
        try{
            $user->save(false);
        }catch(Exception $e){
            return $this->returnErrors( $this->getDBError( $e->errorInfo ) );
        }

        $user->refresh();

        $response = [
            'token_send_timeout' => $app_params['api.tokenSendTimeout'],
        ];

        // Send Mail
        if( $app_params['mail.sendOnRegister'] || $app_params['api.confirmEmailAfterReg'] ){

            if( $this->sendMail(
                    $user->email,
                    'Registration on ' . Yii::$app->name,
                    ['html' => 'userRegistered-API-html', 'text' => 'userRegistered-API-text'],
                    ['user' => $user ]
                )
            ){

                if( $this->HAS_DEV_TOKEN ) // IF DEV TOKEN
                    $response = array_merge( $response, ["email_confirm_token" => $user->email_confirm_token] );

                return $this->returnSuccess( array_merge( $response, [ 'message' => str_replace( '{email}', $user->email,  $locals['mail:confirm_email_sent'] ) ] ) );
            }

            return $this->returnErrors([ 'mail_send_error' => $locals['mail:send_error'] ]);
        }

        return $this->returnSuccess( $response );

    }
    // ^^^^^^^^^^^^^^^---   CONFIRM EMAIL SEND   ---^^^^^^^^^^^^^^^^





    // VVVVVVVVVVVVVVV---   CONFIRM EMAIL    ---VVVVVVVVVVVVVVVV
    public function actionConfirmEmail()
    {
        $app_params = Yii::$app->params;
        $post = $this->POST;
        $locals = $this->getLocals();

        if( !$post['token'] ) $this->addError( 'token', $locals['input_empty:token'] );

        if( count($this->errors) ) return $this->returnErrors();

        $user = User::findOne([
            "email_confirm_token" => $post['token'], // users with email confirm token
            'status' => User::STATUS_ACTIVE, // active users only
            'email_confirmed' => false, // without confirmed email
        ]);

        if( !$user ){
            return $this->returnErrors( [APIController::USER_NOT_FOUND => $locals['user:not_found']] );
        }

        if( !$this->isTokenNotExpired( $post['token'] ) ){
            return $this->returnErrors( ['token_expired' => $locals['token:expired']] );
        }

        $user->email_confirm_token = null;
        $user->email_confirmed = true;

        if( !$user->save() ){
            return $this->returnErrors( $this->getDBError( $e->errorInfo ) );
        }

        // send mail
//        $this->senMail(
//            $user->email,
//            'Password is resetted for ' . Yii::$app->name,
//            ['html' => 'passwordResetted-API-html', 'text' => 'passwordResetted-API-text'],
//            ['user' => $user]
//        );

//        return $this->returnSuccess(['user' => array($user->attributes)]);// !!! DEBUG ONLY
        return $this->returnSuccess();
    }
    // ^^^^^^^^^^^^^^^---   CONFIRM EMAIL   ---^^^^^^^^^^^^^^^^





    // VVVVVVVVVVVVVVV---   LOGIN   ---VVVVVVVVVVVVVVVV
    public function actionLogin()
    {
        $app_params = Yii::$app->params;
        $post = $this->POST;
        $locals = $this->getLocals();
        //

        $model = DynamicModel::validateData([
            'username' => $post['username'],
            'password' => $post['password'],
        ], [
            [['username'], 'required', 'message' => $locals['input_empty:username']],
            [['password'], 'required', 'message' => $locals['input_empty:password']],
            [['username', 'password'], 'string' ],
        ]);

        if ($model->hasErrors()) return $this->returnErrors( $model->errors );

        $user = User::findOne([
            'username' => $post['username'],
        ]);

        if( !$user ){
            return $this->returnErrors([ APIController::USER_NOT_FOUND => $locals['user:not_found'] ]);
        }

        // Check password
        if ( !Yii::$app->getSecurity()->validatePassword( $post['password'], $user['password_hash'] )) {
            return $this->returnErrors(['wrong_login_or_password' => $locals['input:wrong_auth']]);
        }

        // Check user status
        if($user->status !== User::STATUS_ACTIVE){
            return $this->returnErrors([ APIController::USER_NOT_ACTIVE => $locals['user:not_active'] ]);
        }

        Yii::$app->user->login($user);

        return $this->returnSuccess( ['user' => $this->getUserData()] );

    }
    // ^^^^^^^^^^^^^^^---   LOGIN   ---^^^^^^^^^^^^^^^^





    // VVVVVVVVVVVVVVV---   AUTH   ---VVVVVVVVVVVVVVVV
    public function actionAuth()
    {
//        $app_params = Yii::$app->params;
//        $post = $this->POST;
//        $locals = $this->getLocals();

        if(  !($user = $this->getUser()) ) return $this->returnErrors();

        return $this->returnSuccess(['user' => $this->getUserData() ]);
    }
    // ^^^^^^^^^^^^^^^---   AUTH   ---^^^^^^^^^^^^^^^^



    // VVVVVVVVVVVVVVV---   LOGOUT   ---VVVVVVVVVVVVVVVV
    public function actionLogout()
    {
//        $app_params = Yii::$app->params;
//        $post = $this->POST;
//        $locals = $this->getLocals();

        if(  !($user = $this->getUser()) ) return $this->returnErrors();

        Yii::$app->user->logout();
        return $this->returnSuccess();
    }
    // ^^^^^^^^^^^^^^^---   LOGOUT   ---^^^^^^^^^^^^^^^^




    // VVVVVVVVVVVVVVV---   RECOVER PASSWORD   ---VVVVVVVVVVVVVVVV
    public function actionRecoverPassword()
    {
        $app_params = Yii::$app->params;
        $post = $this->POST;
        $locals = $this->getLocals();
        //

        $model = DynamicModel::validateData([
            'email' => $post['email'],
        ], [
            [['email'], 'required', 'message' => $locals['input_empty:email']],
            ['email', 'email'],
        ]);

        if ($model->hasErrors()) return $this->returnErrors( $model->errors );

        $user = User::findOne([
//            'email' => $post['email']
            'email' => $model->email
        ]);

        if( ! $this->isUserActive( $user ) ) return $this->returnErrors();

        // token send timeout
        if( !$this->canSendToken( $user->password_reset_token ) ){
            return $this->returnErrors(['token_send_timeout' => $locals['token:send_timeout'] ]);
        }

        // reset token

//        if ( !User::isPasswordResetTokenValid($user->password_reset_token)) {
            $user->generatePasswordResetToken();
            if (!$user->save()) {
                return $this->returnErrors( $this->getDBError( $e->errorInfo ) );
            }
//        }

        $user->refresh();

        // send mail
        if( $this->sendMail(
            $model->email,
           'Password reset for ' . Yii::$app->name,
           ['html' => 'passwordResetToken-API-html', 'text' => 'passwordResetToken-API-text'],
           ['user' => $user]
        )
       ){
//            return $this->returnSuccess(["user" => array($user->attributes)]); // !!! DEBUG ONLY
            $response = [ "message" => str_replace( '{email}', $user->email,  $locals['mail:confirm_email_sent'] ) ];

            if( $this->HAS_DEV_TOKEN ) // IF DEV TOKEN
                $response = array_merge( $response, [ "password_confirm_token" => $user->password_reset_token ] );

            return $this->returnSuccess( $response );
        }

        return $this->returnErrors(["sendmail:error" => 'mailer error']);

    }
    // ^^^^^^^^^^^^^^^^^^---   RECOVER PASSWORD   ---^^^^^^^^^^^^^^^^^^




    // VVVVVVVVVVVVVVV---   PASSWORD RESET   ---VVVVVVVVVVVVVVVV
    public function actionResetPassword()
    {
        $app_params = Yii::$app->params;
        $post = $this->POST;
        $locals = $this->getLocals();
//
//        if( !$post['password'] ) $this->addError( 'required:password', 'password is required' );
//        if( !$post['token'] ) $this->addError( 'required:token', 'token is required' );
//
//        if( count($this->errors) ) return $this->returnErrors();


        $model = DynamicModel::validateData([
            'token' => $post['token'],
            'password' => $post['password'],
        ], [
            [['token'], 'required', 'message' => $locals['input_empty:token']],
            [['password'], 'required', 'message' => $locals['input_empty:password']],
            [['token', 'password'], 'string' ],
        ]);

        if ($model->hasErrors()) return $this->returnErrors( $model->errors );

        $user = User::findOne([
            "password_reset_token" => $post['token'],
        ]);

        if( ! $this->isUserActive( $user ) ) return $this->returnErrors();

        if( !$this->isTokenNotExpired( $post['token'] ) ){
            return $this->returnErrors( ['token_expired' => $locals['token:expired']] );
        }

        $user->password_reset_token = null;
        $user->setPassword( $post['password'] );
        $user->generateAuthKey();
        if( !$user->save() ){
            return $this->returnErrors( $this->getDBError( $e->errorInfo ) );
        }

        $response = [];

        // send mail
        if(
            $this->sendMail(
                $user->email,
                'Password is resetted for ' . Yii::$app->name,
                ['html' => 'passwordResetted-API-html', 'text' => 'passwordResetted-API-text'],
                ['user' => $user]
            )
        ){
//            $response = array_merge( $response, [ 'message' => $locals[''] ] );
            $response = [ 'message' => $locals['mail:password_changed_sent'] ];
        }

//        return $this->returnSuccess(['user' => array($user->attributes)]);// !!! DEBUG ONLY
        return $this->returnSuccess( $response );

    }
    // ^^^^^^^^^^^^^^^---   PASSWORD RESET   ---^^^^^^^^^^^^^^^^




    // VVVVVVVVVVVVVVV---   SUPPORT METHODS   ---VVVVVVVVVVVVVVVV
    public function canSendToken($token)
    {

        if (empty($token)) {
            return true;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['api.tokenSendTimeout'];
        return $timestamp + $expire < time();
    }

    public function isTokenNotExpired($token)
    {

        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['api.tokenResetExpire'];
        return $timestamp + $expire >= time();
    }

    public function getUserData(){
        if( Yii::$app->user->identity ) {
            $user = (array)Yii::$app->user->identity->attributes;
            $user = $this->cleanupUserData( $user );
            return $user;
        }
        return null;
    }

    public function cleanupUserData( $userdata ){
        if( !$userdata ) return null;
        if( $this->allowed_user_attributes ) return Utils::array_filter_key( $userdata, $this->allowed_user_attributes );
        return $userdata;
    }

    // SEND MAIL
    public function sendMail($to, $subject, $template, $data ){
        return Yii::$app
            ->mailer
            ->compose( $template, $data )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo( $to )
            ->setSubject( $subject )
            ->send();
    }


}