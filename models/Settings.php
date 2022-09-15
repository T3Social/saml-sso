<?php


namespace humhub\modules\sso\saml\models;

use humhub\modules\sso\saml\authclient\SAML;
use humhub\modules\sso\saml\Events;
use humhub\modules\sso\saml\Module;
use yii\base\Model;
use yii\helpers\Url;
use Yii;

class Settings extends Model
{

    public $isEnabled;
    public $displayName;
    public $entityId;
    public $singleSignOnServiceUrl;
    public $singleLogoutServiceUrl;
    public $x509cert;
    public $attributeMapping;
    public $forceSamlAuth;
    public $logAvailableAttributes;
    public $spX509cert;
    public $spPrivateKey;
    public $spUseDeprecatedEntityId;

    /**
     * Name/Id of SAML authclient configured via settings (web interface)
     */
    const SETTINGS_SAML_ID = 'saml';
    const AUTHCLIENT_CLASS = SAML::class;


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['entityId', 'singleSignOnServiceUrl', 'x509cert'], 'required'],
            [['singleSignOnServiceUrl', 'singleLogoutServiceUrl', 'entityId'], 'url'],
            [['x509cert', 'displayName', 'attributeMapping', 'spX509cert', 'spPrivateKey'], 'safe'],
            [['isEnabled', 'forceSamlAuth', 'logAvailableAttributes', 'spUseDeprecatedEntityId'], 'boolean']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'isEnabled' => Yii::t('SamlSsoModule.base', 'Enable the SAML authentication method'),
            'entityId' => Yii::t('SamlSsoModule.base', 'Entity Id'),
            'singleSignOnServiceUrl' => Yii::t('SamlSsoModule.base', 'SingleSignOnService Url'),
            'singleLogoutServiceUrl' => Yii::t('SamlSsoModule.base', 'SingleLogoutService Url'),
            'x509cert' => Yii::t('SamlSsoModule.base', 'X.509'),
            'displayName' => Yii::t('SamlSsoModule.base', 'Display name'),
            'attributeMapping' => Yii::t('SamlSsoModule.base', 'Attribute mapping'),
            'forceSamlAuth' => Yii::t('SamlSsoModule.base', 'Always use SAML Auth'),
            'logAvailableAttributes' => Yii::t('SamlSsoModule.base', 'Log available SAML attributes'),
            'spX509cert' => Yii::t('SamlSsoModule.base', 'SP: X.509 certificate'),
            'spPrivateKey' => Yii::t('SamlSsoModule.base', 'SP: Private key'),
            'spUseDeprecatedEntityId' => Yii::t('SamlSsoModule.base', 'SP: Use deprecated entity ID'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return [
            'entityId' => Yii::t('SamlSsoModule.base', 'Must be an URL. IDP'),
            'singleSignOnServiceUrl' => Yii::t('SamlSsoModule.base', 'URL target to send the Authentication Request Message.'),
            'singleLogoutServiceUrl' => Yii::t('SamlSsoModule.base', 'URL target to send the SLO Request.'),
            'x509cert' => Yii::t('SamlSsoModule.base', 'Plublic X.509 of the IdP'),
            'displayName' => Yii::t('SamlSsoModule.base', 'Optional'),
            'attributeMapping' => Yii::t('SamlSsoModule.base', 'One mapping per line. Format: humhub-attribute-name=saml-attribute-name'),
            'forceSamlAuth' => Yii::t('SamlSsoModule.base', 'Warning: Make sure to configure at least one   administrative user that can access HumHub via SSO. You can bypass SAML authentication via following URL: {url}', ['url' => Url::to(['/user/auth/login', 'noSaml' => 1], true)]),
            'logAvailableAttributes' => Yii::t('SamlSsoModule.base', 'For testing purposes: Write provided SAML attributes into log'),
            'spX509cert' => Yii::t('SamlSsoModule.base', 'Optional. Required to encrypt requests.'),
            'spPrivateKey' => Yii::t('SamlSsoModule.base', 'Optional. Required to encrypt requests.'),
            'spUseDeprecatedEntityId' => Yii::t('SamlSsoModule.base', 'This changes the SP Entity ID!'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function loadSettings()
    {
        $module = $this->getModule();

        $this->isEnabled = $module->settings->get('isEnabled');
        $this->entityId = $module->settings->get('entityId');
        $this->singleSignOnServiceUrl = $module->settings->get('singleSignOnServiceUrl');
        $this->singleLogoutServiceUrl = $module->settings->get('singleLogoutServiceUrl');
        $this->x509cert = $module->settings->get('x509cert');
        $this->displayName = $module->settings->get('displayName');
        $this->attributeMapping = $module->settings->get('attributeMapping');
        $this->logAvailableAttributes = $module->settings->get('logAvailableAttributes');
        $this->forceSamlAuth = $module->settings->get('forceSamlAuth');
        $this->spX509cert = $module->settings->get('spX509cert');
        $this->spPrivateKey = $module->settings->get('spPrivateKey');
        $this->spUseDeprecatedEntityId = (boolean)$module->settings->get('spUseDeprecatedEntityId');

        $this->setDefaultMapping();
    }

    private function setDefaultMapping()
    {
        if (empty($this->attributeMapping)) {
            $this->attributeMapping = '';
            foreach (SAML::$defaultAttributeMap as $f => $v) {
                $this->attributeMapping .= $f . '=' . $v . "\n";
            }
        }
    }

    /**
     * @return array
     */
    public function getAttributeMappingArray()
    {
        if (empty($this->attributeMapping)) {
            return Saml::$defaultAttributeMap;
        }
        $mapping = [];
        foreach (explode("\n", $this->attributeMapping) as $l) {
            $p = explode("=", $l, 2);
            if (isset($p[0]) && isset($p[1])) {
                $mapping[trim($p[0])] = trim($p[1]);
            }
        }
        return $mapping;
    }

    /**
     * Returns the AuthClient config array, by the current settings
     */
    public function addAuthClient($collection = null, $authClientId = null)
    {
        if ($collection === null) {
            $collection = Yii::$app->authClientCollection;
        }

        if ($authClientId === null) {
            $authClientId = static::SETTINGS_SAML_ID;
        }

        $module = $this->getModule();

        $config = [
            'class' => static::AUTHCLIENT_CLASS,
            'name' => $authClientId,
            'title' => empty($this->displayName) ? Yii::t('SamlSsoModule.base', 'SAML') : $this->displayName,
            'entityId' => $this->entityId,
            'singleSignOnServiceUrl' => $this->singleSignOnServiceUrl,
            'singleLogoutServiceUrl' => $this->singleLogoutServiceUrl,
            'x509cert' => $this->x509cert,
            'normalizeUserAttributeMap' => $this->getAttributeMappingArray(),
            'spPrivateKey' => $this->spPrivateKey,
            'spX509cert' => $this->spX509cert,
            'spUseDeprecatedEntityId' => $this->spUseDeprecatedEntityId,
            'advancedSettings' => $module->advancedSettings
        ];

        $collection->setClient($authClientId, $config);
    }


    /**
     * {@inheritdoc}
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $this->setDefaultMapping();

        $module = $this->getModule();
        $module->settings->set('isEnabled', $this->isEnabled);
        $module->settings->set('entityId', $this->entityId);
        $module->settings->set('singleSignOnServiceUrl', $this->singleSignOnServiceUrl);
        $module->settings->set('singleLogoutServiceUrl', $this->singleLogoutServiceUrl);
        $module->settings->set('x509cert', $this->x509cert);
        $module->settings->set('displayName', $this->displayName);
        $module->settings->set('attributeMapping', $this->attributeMapping);
        $module->settings->set('forceSamlAuth', $this->forceSamlAuth);
        $module->settings->set('logAvailableAttributes', $this->logAvailableAttributes);
        $module->settings->set('spX509cert', $this->spX509cert);
        $module->settings->set('spPrivateKey', $this->spPrivateKey);
        $module->settings->set('spUseDeprecatedEntityId', $this->spUseDeprecatedEntityId);
    }

    /**
     * @return Module
     */
    protected function getModule()
    {
        return Yii::$app->getModule('saml-sso');
    }

}