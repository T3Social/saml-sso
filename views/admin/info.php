<?php


use humhub\libs\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;

?>



<?php $this->beginContent('@saml-sso/views/admin/layout.php') ?>

<div class="panel-body">
    <div class="form-group">
        <?= Html::a(\humhub\modules\ui\icon\widgets\Icon::get('download') . '&nbsp;&nbsp;' . Yii::t('SamlSsoModule.base', 'Download SP metadata (XML)'), ['/saml-sso/metadata', 'authclient' => 'saml'], ['class' => 'btn btn-success', 'target' => '_blank']) ?>
    </div>

    <div class="form-group">
        <label class="control-label" for="entityid">Entity Id</label>
        <?= Html::textInput('entityid', $entityId, ['class' => 'form-control', 'readonly' => true]); ?>
        <!--<p class="help-block">Must be an URL. IDP</p>-->
    </div>

    <div class="form-group">
        <label class="control-label" for="entityid">Name ID Format</label>
        <?= Html::textInput('entityid', "urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified", ['class' => 'form-control', 'readonly' => true]); ?>
        <p class="help-block">Must be an URL. IDP</p>
    </div>

    <br/>
    <h1>Single Login (SSO)</h1>

    <div class="form-group">
        <label class="control-label" for="sso">Location</label>
        <?= Html::textInput('sso', Url::to(['/user/auth/external', 'authclient' => $authclientId, 'handleAcs' => 1], true), ['class' => 'form-control', 'readonly' => true]); ?>
    </div>
    <div class="form-group">
        <label class="control-label" for="ssob">Binding</label>
        <?= Html::textInput('ssob', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect', ['class' => 'form-control', 'readonly' => true]); ?>
    </div>

    <br/>
    <h1>Single Logout (SLO)</h1>

    <div class="form-group">
        <label class="control-label" for="slo">Location</label>
        <?= Html::textInput('slo', Url::to(['/saml-sso/logout', 'authclient' => $authclientId], true), ['class' => 'form-control', 'readonly' => true]); ?>
    </div>
    <div class="form-group">
        <label class="control-label" for="slob">Binding</label>
        <?= Html::textInput('slob', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST', ['class' => 'form-control', 'readonly' => true]); ?>
    </div>


    <br/>
    <br/>

</div>

<?php $this->endContent() ?>
