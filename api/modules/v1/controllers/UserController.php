<?php
/**
 * Created by PhpStorm.
 * User: dpotekhin
 * Date: 07.06.2018
 * Time: 11:54
 */

namespace app\modules\v1\controllers;

use Yii;
use app\modules\v1\components\APIUserController;

class UserController extends APIUserController
{

//    public $allowed_user_attributes = null; // disable user attributes filtering

    public function returnErrors($messages = null)
    {
        $answer = parent::returnErrors($messages); // TODO: Change the autogenerated stub
//        if( $answer ) $answer["add"] = "add";
        return $answer;
    }



    public function returnSuccess($answer = null)
    {
        $answer = parent::returnSuccess($answer); // TODO: Change the autogenerated stub
//        if( $answer ) $answer["user"]['action'] = Yii::$app->controller->action->id;
        return $answer;
    }

}