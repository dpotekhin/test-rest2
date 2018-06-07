<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */
?>
<div class="password-reset">

    <h1>*** MAIL FROM API ***</h1>

    <p>Hello <?= Html::encode($user->username) ?>,</p>

    <p>Your password is resetted successfuly.</p>

</div>
