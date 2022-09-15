<div class="panel panel-default">
    <div class="panel-heading">
        <?= Yii::t('SamlSsoModule.base', '<strong>SAML</strong> SSO module - Administration'); ?>
    </div>
    <?= humhub\modules\sso\saml\widgets\AdminMenu::widget(); ?>
    
    <?= $content; ?>
</div>