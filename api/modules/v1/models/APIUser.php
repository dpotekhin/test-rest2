<?php
namespace app\modules\v1\models;

use \yii\db\ActiveRecord;

/**
 * User Model
 */
class APIUser extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public static function primaryKey()
    {
        return ['id'];
    }

    /**
     * Define rules for validation
     */
    public function rules()
    {
        return [
            [['username', 'email', 'password_hash'], 'required']
        ];
    }

    public function fields(){
        return [
            'id',
            'name' => 'username',
            'email' => function($model,$field){
                return "мыло вот оно: " . $model[ $field ];
            },
        ];
    }
}