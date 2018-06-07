<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */

$confirmLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->email_confirm_token]);

?>
*** MAIL FROM API ***

Hello <?= $user->username ?>,

You are successfully registered.

<?php if( $user->email_confirm_token ): ?>
    You need to follow the link to complete your registration: <?= Html::a(Html::encode($confirmLink), $confirmLink) ?>
<?php endif; ?>
