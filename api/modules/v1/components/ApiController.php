<?php
namespace app\modules\v1\components;

use yii\filters\auth\QueryParamAuth;
use yii\filters\Cors;
use yii\rest\ActiveController;
use yii\web\Response;

/**
 * API Base Controller
 * All controllers within API app must extend this controller!
 */
class APIController extends ActiveController
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();

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

        return true; // or false to not run the action
    }


    // RETURN ERRORS
    public $errors = array();

    public function addError( $key, $message ){
//        array_push( $this->errors, [ "{$key}" => $message] );
//        array_push( $this->errors, [ $key => $message] );
        $this->errors[$key] = $message;
    }

    public function returnErrors( $messages = null ){

        $this->errors = Utils::merge_associative_arrays( $this->errors, $messages );

        if( count( $this->errors ) ){
            return [
                "error" => true,
                "error_messages" => $this->errors,
            ];
        }else{
            return $this->returnSuccess();
        }
    }

    // RETURN SUCCESS
    public function returnSuccess( $answer = null ){
        if( isset($answer) ) return array_merge( ['success' => true ], $answer );
        return ['success' => true ];
    }


}