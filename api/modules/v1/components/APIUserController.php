<?php
namespace app\modules\v1\components;

use common\models\User;
use Yii;
//use app\modules\v1\models\APIUser;
use yii\db\Exception;

/**
 * User Controller
 */
class APIUserController extends APIController
{
    public $modelClass = 'app\modules\v1\models\APIUser';

    public $allowed_user_attributes = ['id','username', 'email', 'status']; // keys for user attributes filtration
    public $auth_after_reg = !false;

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

        $behaviors['authenticator']['except'] = array_merge($behaviors['authenticator']['except'], ['login', 'logout', 'auth', 'reg']);

        return $behaviors;
    }




    //
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




    // REG
    public function actionReg()
    {
        $request = \Yii::$app->request;
        $post = $request->post();

//        return $post['username'];

        if (!$post) {
            $this->addError('request', 'no registration data');
            return $this->returnErrors();
        }

        if( !$post['username'] ) $this->addError( 'username', 'username is required' );
        if( !$post['email'] ) $this->addError( 'email', 'email is required' );
        if( !$post['password'] ) $this->addError( 'password', 'password is required' );

        if( count($this->errors) ){
            return $this->returnErrors();
        }

        // is the name unique
        if( User::findOne(["username" => $post['username']]) ){
            $this->addError('username', 'username is used already');
            return $this->returnErrors();
        }

        // is the email unique
        if( User::findOne(["email" => $post['email']]) ){
            $this->addError('email', 'email is used already');
            return $this->returnErrors();
        }

        $user = new User();
        $user->username = $post['username'];
        $user->email = $post['email'];
        $user->setPassword( $post['password'] );
        $user->generateAuthKey();
//        $user->password_hash = Yii::$app->getSecurity()->generatePasswordHash($post['password']);

        try{
            $user->save(false);
        }catch(Exception $e){
//            throw new \yii\web\HttpException(405, 'Error saving model');
//            $this->addError('request', 'DataBase error');
//            return $this->getErrors();
            $this->addError( 'db', $e->errorInfo );
            return $this->returnErrors();
        }

        // Auth User
        if( $auth_after_reg ){

        }

        return $this->returnSuccess( ['user' => $this->cleanupUserData( $user->attributes ) ]);

    }





    // LOGIN
    public function actionLogin()
    {
        $request = \Yii::$app->request;
        $post = $request->post();

//        return $post['username'];

        if( !$post ) {
            $this->addError( 'request', 'no login data' );
            return $this->returnErrors();
        }

        if( !$post['username'] ) $this->addError( 'username', 'username is required' );
//        if( !$post['email'] ) $this->addError( 'email', 'email is required' );
        if( !$post['password'] ) $this->addError( 'password', 'password is required' );

        if( count($this->errors) ){
            return $this->returnErrors();
        }

        $identity = User::findOne(['username' => $post['username'] ]);

        if( !$identity ){
            $this->addError( 'request', 'user not found' );
            return $this->returnErrors();
        }


//        return [ 'password' => $post['password'], "hash" =>  $identity['password_hash'] ];

        if ( Yii::$app->getSecurity()->validatePassword( $post['password'], $identity['password_hash'] )) {
            Yii::$app->user->login($identity);
            return $this->returnSuccess( ['user' => $this->getUserData()] );
        }

        $this->addError( 'request', 'wrong login or password' );
        return $this->returnErrors();

    }






    // AUTH
    public function actionAuth()
    {
        $identity = Yii::$app->user->identity;

        if( !$identity ){
            $this->addError( 'request', 'user is not logged in' );
            return $this->returnErrors();
        }

        return ['success' => true, 'user' => $this->getUserData() ];
    }





    // LOGOUT
    public function actionLogout()
    {

        $identity = Yii::$app->user->identity;

        if( !$identity ){
            $this->addError( 'request', 'user is not logged in' );
            return $this->returnErrors();
        }

        Yii::$app->user->logout();
        return $this->returnSuccess();
    }




}