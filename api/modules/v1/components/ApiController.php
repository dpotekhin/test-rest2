<?php
namespace app\modules\v1\components;

use Yii;
use yii\filters\auth\QueryParamAuth;
use yii\filters\Cors;
//use yii\rest\ActiveController;
use yii\rest\Controller;
//use yii\web\Response;

/**
 * API Base Controller
 * All controllers within API app must extend this controller!
 */
class APIController extends Controller
{

    public $REQUEST;
    public $POST;
    public $HAS_DEV_TOKEN;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        if( Yii::$app->params['api.authWithToken'] ) {
            // add CORS filter
            $behaviors['corsFilter'] = [
                'class' => Cors::className(),
            ];

            // add QueryParamAuth for authentication
            $behaviors['authenticator'] = [
                'class' => QueryParamAuth::className(),
            ];

            // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
            $behaviors['authenticator']['except'] = ['options'];
        }

        return $behaviors;
    }

    public function beforeAction($action)
    {
        // your custom code here, if you want the code to run before action filters,
        // which are triggered on the [[EVENT_BEFORE_ACTION]] event, e.g. PageCache or AccessControl

        if (!parent::beforeAction($action)) {
            return false;
        }

        // other custom code here
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        //
        $this->REQUEST = \Yii::$app->request;
        $this->POST = $this->REQUEST->post();

        $dev_token = $this->POST['dev_token'];
        $this->HAS_DEV_TOKEN = $dev_token && $dev_token === Yii::$app->params['api.info.dev_token'];

        return true; // or false to not run the action
    }



    // CUSTOM

    public function methods(){
        return [
            'info' => [
                'request' => [
                    'dev_token(string) optional dev!',
                ],
                'response' => [
                    'dev!: methods'
                ]
            ],
        ];
    }


    // VVVVVVVVVVVVVVV---   INFO   ---VVVVVVVVVVVVVVVV
    public function actionInfo()
    {

        // DEV TOKEN REQUIRED !
        if( !$this->HAS_DEV_TOKEN ) return $this->returnErrors(['required:dev_token' => 'developer token is required']);
        /*
        if( $post['dev_token'] != Yii::$app->params['api.info.dev_token'] ){
            return $this->returnErrors([ 'required:dev_token' => 'developer token is required' ]);
        }
        */

        return $this->returnSuccess([ 'methods' => $this->methods() ]);
    }
    // ^^^^^^^^^^^^^^^---   INFO   ---^^^^^^^^^^^^^^^^




    // VVVVVVVVVVVVVVV---   ERRORS   ---VVVVVVVVVVVVVVVV
    public $errors = array();

    public function addError( $key, $message ){
//        array_push( $this->errors, [ "{$key}" => $message] );
//        array_push( $this->errors, [ $key => $message] );
        $this->errors[$key] = $message;
    }

    public function returnErrors( $messages = null ){

//        if( $messages ) $this->errors = Utils::merge_associative_arrays( $this->errors, $messages );
        if( $messages ) $this->errors = array_merge( $this->errors, $messages );

        if( count( $this->errors ) ){
            return [
                "error" => true,
                "error_messages" => $this->errors,
            ];
        }else{
            return $this->returnSuccess();
        }
    }

    public function getDBError( $error ){
        $app_params = Yii::$app->params;
        $locals = $this->getLocals();
        return ['db_error' => ($app_params['api.sendDetailsOnDBError'] ? $error : $locals['db:error'] ) ];
    }
    // ^^^^^^^^^^^^^^^---   ERRORS   ---^^^^^^^^^^^^^^^^




    // RETURN SUCCESS
    public function returnSuccess( $answer = null ){
        if( isset($answer) ) return array_merge( ['success' => true ], $answer );
        return ['success' => true ];
    }

    // -------------------- SUPPORT --------------------

    public function getUser(){

        $user = Yii::$app->user->identity;

        if( $user ) return $user;

        $locals = $this->getLocals();
        $this->addError( self::USER_NOT_LOGGED_IN, $locals['user:not_logged_in'] );
    }

    public function getLocals(){
        return Yii::$app->params['locals'];
    }

    public function generateToken(){
        return Yii::$app->security->generateRandomString() . '_' . time();
    }

    // -------------------- SUPPORT --------------------

}