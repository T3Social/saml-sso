<?php

/* @var $model AllowedLanguages */

use humhub\libs\Html;
use humhub\modules\ui\form\widgets\ActiveForm;

?>

<?php $this->beginContent('@saml-sso/views/admin/layout.php') ?>
    <div class="panel-body">

        
        <?php $form = ActiveForm::begin(['enableClientValidation' => false, 'enableAjaxValidation' => false]); ?>

        <?= $form->field($model, 'isEnabled')->checkbox(); ?>
        <?= $form->field($model, 'entityId'); ?>
        <?= $form->field($model, 'singleSignOnServiceUrl'); ?>
        <?= $form->field($model, 'singleLogoutServiceUrl'); ?>
        <?= $form->field($model, 'x509cert')->textarea(['rows' => 10]); ?>

        <?= $form->beginCollapsibleFields('Attribute Settings'); ?>
        <?= $form->field($model, 'logAvailableAttributes')->checkbox(); ?><br/>
        <?= $form->field($model, 'attributeMapping')->textarea(['rows' => 6]); ?>
        <?= $form->endCollapsibleFields(); ?>

        <?= $form->beginCollapsibleFields('Login Form'); ?>
        <?= $form->field($model, 'displayName'); ?>
        <?= $form->field($model, 'forceSamlAuth')->checkbox(); ?><br/>
        <?= $form->endCollapsibleFields(); ?>

        <?= $form->beginCollapsibleFields('SP settings'); ?>
        <?= $form->field($model, 'spX509cert')->textarea(['rows' => 2]); ?>
        <?= $form->field($model, 'spPrivateKey')->textarea(['rows' => 2]); ?>
        <?= $form->field($model, 'spUseDeprecatedEntityId')->checkbox(); ?>
        <?= $form->endCollapsibleFields(); ?>
        <br/>
        <br/>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('base', 'Save'), ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
<?php $this->endContent() ?>