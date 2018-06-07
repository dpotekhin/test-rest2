<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */

$confirmLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->email_confirm_token]);

?>
<div class="password-reset">

    <h1>*** MAIL FROM API ***</h1>

    <p>Hello <?= Html::encode($user->username) ?>,</p>

    <p>You are successfully registered.</p>

    <?php if( $user->email_confirm_token ): ?>
        <p>You need to follow the link to complete your registration: <?= Html::a(Html::encode($confirmLink), $confirmLink) ?></p>
    <?php endif; ?>

</div>
