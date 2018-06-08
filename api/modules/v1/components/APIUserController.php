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


        $model = DynamicModel::validateData([
            'username' => $post['username'],
            'email' => $post['email'],
            'password' => $post['password'],
        ], [
            [['username', 'email', 'password'], 'required'],
            [['username', 'email'], 'string', 'min' => 3, 'max' => 128],
            ['email', 'email'],
            [['password'], 'string', 'min' => $app_params['api.passwordMinLength'], 'max' => $app_params['api.passwordMaxLength'] ],
        ]);

        if ($model->hasErrors()) {
            return $this->returnErrors( $model->errors );
        }

        // is the name unique
        if( User::findOne(["username" => $post['username']]) ){
            return $this->returnErrors(['is_used:username' => 'username is used already']);
        }

        // is the email unique
        if( User::findOne(["email" => $post['email']]) ){
            return $this->returnErrors(['is_used:email' => 'email is used already']);
        }

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
//            throw new \yii\web\HttpException(405, 'Error saving model');
//            $this->addError('request', 'DataBase error');
//            return $this->getErrors();
            return $this->returnErrors(['db_error' => $e->errorInfo]);
        }

        // Send Mail
        if( $app_params['mail.sendOnRegister'] || $app_params['api.confirmEmailAfterReg'] ){

            $this->sendMail(
                $user->email,
                'Registration on ' . Yii::$app->name,
                ['html' => 'userRegistered-API-html', 'text' => 'userRegistered-API-text'],
                ['user' => $user ]
            );

        }

        // Auth User // TODO: implement this
//        if( Yii::$app->params['mail.sendOnRegister'] ){
//
//        }

        return $this->returnSuccess( ['user' => $this->cleanupUserData( $user->attributes ) ]);
//        return $this->returnSuccess( ['user' => $this->cleanupUserData( $user->attributes ), 'email_confirm_token' => $user->email_confirm_token  ]); // !!! DEBUG

    }
    // ^^^^^^^^^^^^^^^---   REG   ---^^^^^^^^^^^^^^^^




    // VVVVVVVVVVVVVVV---   CONFIRM EMAIL SEND   ---VVVVVVVVVVVVVVVV
    public function actionConfirmEmailSend()
    {
        $app_params = Yii::$app->params;

        $post = $this->POST;

        // USER
        $identity = Yii::$app->user->identity;

        if( !$identity ){
            return $this->returnErrors(['user_not_logged_in' => 'user not logged in']);
        }

        // is confirmation required
        if( !$app_params['api.confirmEmailAfterReg'] ){
            return $this->returnErrors(['email_confirm_is_not_required' => 'email confirmation is not required']);
        }

        // is user already confirm his email
        if( $identity->email_confirmed ) {
            return $this->returnErrors(['email_is_confirmed' => 'email is confirmed already']);
        }

        // Setup email confirmation
        $identity->email_confirm_token = Yii::$app->security->generateRandomString() . '_' . time();

        // Save
        try{
            $identity->save(false);
        }catch(Exception $e){
            return $this->returnErrors(['db_error' => $e->errorInfo]);
        }

        // Send Mail
        if( $app_params['mail.sendOnRegister'] || $app_params['api.confirmEmailAfterReg'] ){
            $this->sendMail(
                $identity->email,
                'Registration on ' . Yii::$app->name,
                ['html' => 'userRegistered-API-html', 'text' => 'userRegistered-API-text'],
                ['user' => $identity ]
            );
        }

        $response = [ 'message' => 'email confirmation sended to ' . $identity->email ];

        if( $this->HAS_DEV_TOKEN ) // IF DEV TOKEN
            $response = array_merge( $response, ["email_confirm_token" => $identity->email_confirm_token] );

        return $this->returnSuccess( $response );
    }
    // ^^^^^^^^^^^^^^^---   CONFIRM EMAIL SEND   ---^^^^^^^^^^^^^^^^





    // VVVVVVVVVVVVVVV---   CONFIRM EMAIL    ---VVVVVVVVVVVVVVVV
    public function actionConfirmEmail()
    {
        $post = $this->POST;

        if( !$post['token'] ) $this->addError( 'required:token', 'token is required' );

        if( count($this->errors) ) return $this->returnErrors();

        $user = User::findOne([
            "email_confirm_token" => $post['token'], // users with email confirm token
            'status' => User::STATUS_ACTIVE, // active users only
            'email_confirmed' => false, // without confirmed email
        ]);

        if( !$user ){
            return $this->returnErrors( ['not_found:user' => 'user is not found' ] );
        }

        if( !$this->isTokenNotExpired( $post['token'] ) ){
            return $this->returnErrors( ['token_expired' => 'token expired' ] );
        }

        $user->email_confirm_token = null;
        $user->email_confirmed = true;
        if( !$user->save() ){
            return $this->returnErrors([ 'db_error' => $e->errorInfo ]);
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
        $post = $this->POST;

//        return $post['username'];

//        if( !$post ) {
//            $this->addError('required:params', 'params is reqiured');
//            return $this->returnErrors();
//        }

        if( !$post['username'] ) $this->addError( 'required:username', 'username is required' );
//        if( !$post['email'] ) $this->addError( 'email', 'email is required' );
        if( !$post['password'] ) $this->addError( 'required:password', 'password is required' );

        if( count($this->errors) ){
            return $this->returnErrors();
        }

        $identity = User::findOne([
            'username' => $post['username'],
        ]);

        if( !$identity ){
            return $this->returnErrors(['not_found:user' => 'user is not found']);
        }

        // Check password
        if ( !Yii::$app->getSecurity()->validatePassword( $post['password'], $identity['password_hash'] )) {
            return $this->returnErrors(['wrong_login_or_password' => 'wrong login or password']);
        }

        // Check user status
        if($identity->status !== User::STATUS_ACTIVE){
            return $this->returnErrors([ 'user:not_active' => 'user is inactive' ]);
        }

        Yii::$app->user->login($identity);

        return $this->returnSuccess( ['user' => $this->getUserData()] );

    }
    // ^^^^^^^^^^^^^^^---   LOGIN   ---^^^^^^^^^^^^^^^^





    // VVVVVVVVVVVVVVV---   AUTH   ---VVVVVVVVVVVVVVVV
    public function actionAuth()
    {
        $identity = Yii::$app->user->identity;

        if( !$identity ){
            return $this->returnErrors(['user_not_logged_in' => 'user not logged in']);
        }

        return $this->returnSuccess(['user' => $this->getUserData() ]);
    }
    // ^^^^^^^^^^^^^^^---   AUTH   ---^^^^^^^^^^^^^^^^



    // VVVVVVVVVVVVVVV---   LOGOUT   ---VVVVVVVVVVVVVVVV
    public function actionLogout()
    {

        $identity = Yii::$app->user->identity;

        // !!! DEBUG !!!
//        $this->senMail(
//            $user->email,
//            (Yii::$app->params['mail.password_reseted'])(),
//            ['html' => 'passwordResetToken-API-html', 'text' => 'passwordResetToken-API-text'],
//            ['user' => [] ]
//        );
        // !!! DEBUG !!!

        if( !$identity ){
            return $this->returnErrors(['user_not_logged_in' => 'user not logged in']);
        }

        Yii::$app->user->logout();
        return $this->returnSuccess();
    }
    // ^^^^^^^^^^^^^^^---   LOGOUT   ---^^^^^^^^^^^^^^^^




    // VVVVVVVVVVVVVVV---   RECOVER PASSWORD   ---VVVVVVVVVVVVVVVV
    public function actionRecoverPassword()
    {
        $post = $this->POST;

        if( !$post['email'] ) $this->addError( 'required:email', 'email is required' );

        if( count($this->errors) ){
            return $this->returnErrors();
        }

        $user = User::findOne([
            'email' => $post['email'],
            'status' => User::STATUS_ACTIVE
        ]);

        if( !$user ){
            return $this->returnErrors(['not_found:user' => "user with email: " . $post['email'] . " is not found"]);
        }

        // generate reset token
        if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
            $user->generatePasswordResetToken();
            if (!$user->save()) {
                return $this->returnErrors([ 'db_error' => $e->errorInfo ]);
            }
        }

        // send mail
        if( $this->sendMail(
           $post['email'],
           'Password reset for ' . Yii::$app->name,
           ['html' => 'passwordResetToken-API-html', 'text' => 'passwordResetToken-API-text'],
           ['user' => $user]
        )
       ){
//            return $this->returnSuccess(["user" => array($user->attributes)]); // !!! DEBUG ONLY


            if( $this->HAS_DEV_TOKEN ) // IF DEV TOKEN
                return $this->returnSuccess(["password_confirm_token" => $user->password_reset_token] );
            else return $this->returnSuccess();
        }

        return $this->returnErrors(["sendmail:error" => 'mailer error']);

    }
    // ^^^^^^^^^^^^^^^^^^---   RECOVER PASSWORD   ---^^^^^^^^^^^^^^^^^^




    // VVVVVVVVVVVVVVV---   PASSWORD RESET   ---VVVVVVVVVVVVVVVV
    public function actionResetPassword()
    {
        $post = $this->POST;

        if( !$post['password'] ) $this->addError( 'required:password', 'password is required' );
        if( !$post['token'] ) $this->addError( 'required:token', 'token is required' );

        if( count($this->errors) ) return $this->returnErrors();

//        return $this->returnSuccess([ "password" => $post['password'], "token" => $post['token'] ]);

        $user = User::findOne([
            "password_reset_token" => $post['token'],
            'status' => User::STATUS_ACTIVE,
        ]);

        if( !$user ){
            return $this->returnErrors( ['not_found:user' => 'user is not found' ] );
        }

        if( !$this->isTokenNotExpired( $post['token'] ) ){
            return $this->returnErrors( ['token_expired' => 'token expired' ] );
        }

        $user->password_reset_token = null;
        $user->setPassword( $post['password'] );
        $user->generateAuthKey();
        if( !$user->save() ){
            return $this->returnErrors([ 'db_error' => $e->errorInfo ]);
        }

        // send mail
        $this->sendMail(
            $user->email,
            'Password is resetted for ' . Yii::$app->name,
            ['html' => 'passwordResetted-API-html', 'text' => 'passwordResetted-API-text'],
            ['user' => $user]
        );

//        return $this->returnSuccess(['user' => array($user->attributes)]);// !!! DEBUG ONLY
        return $this->returnSuccess();

    }


    public function isTokenNotExpired($token)
    {

        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }
    // ^^^^^^^^^^^^^^^---   PASSWORD RESET   ---^^^^^^^^^^^^^^^^




    // VVVVVVVVVVVVVVV---   SUPPORT METHODS   ---VVVVVVVVVVVVVVVV
    // USER DATA
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