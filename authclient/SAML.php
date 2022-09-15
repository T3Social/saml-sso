<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\sso\saml\authclient;

use humhub\modules\sso\saml\Module;
use humhub\modules\user\authclient\AuthClientHelpers;
use humhub\modules\user\authclient\BaseClient;
use humhub\modules\user\authclient\interfaces\ApprovalBypass;
use humhub\modules\user\authclient\interfaces\SyncAttributes;
use humhub\modules\user\models\User;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Utils;
use Yii;
use humhub\modules\user\authclient\interfaces\StandaloneAuthClient;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * SAML AuthClient
 */
class SAML extends BaseClient implements StandaloneAuthClient, ApprovalBypass, SyncAttributes
{

    /**
     * @var Auth
     */
    private $auth;

    public $entityId;
    public $singleSignOnServiceUrl;
    public $singleLogoutServiceUrl;
    public $x509cert;

    public $spX509cert;
    public $spPrivateKey;
    public $spUseDeprecatedEntityId;

    /**
     * @var array
     * @see https://github.com/onelogin/php-saml/blob/master/advanced_settings_example.php
     */
    public $advancedSettings = [];

    public static $defaultAttributeMap = [
        'id' => 'uidNumber',
        'email' => 'urn:oid:0.9.2342.19200300.100.1.3',
        'username' => 'urn:oid:0.9.2342.19200300.100.1.1',
        'firstname' => 'urn:oid:2.5.4.42',
        'lastname' => 'urn:oid:2.5.4.4',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        require_once Yii::getAlias('@saml-sso/vendor/autoload.php');

        if (!Yii::$app->request->isConsoleRequest && Yii::$app->request->isSecureConnection) {
            Utils::setSelfProtocol('https');
        }

        $this->auth = new Auth($this->getSettingsAsArray());
    }

    /**
     * @inheritdoc
     */
    protected function defaultNormalizeUserAttributeMap()
    {
        return static::$defaultAttributeMap;
    }

    /**
     * @return Auth
     */
    public function getSamlAuth()
    {
        return $this->auth;
    }

    /**
     * @return array
     */
    private function getSettingsAsArray()
    {
        $spEntityId = Url::to(['/saml-sso/metadata/download', 'authclient' => $this->getName()], true);
        if ($this->spUseDeprecatedEntityId === true) {
            $spEntityId = Url::to(['/saml-sso/metadata', 'authclient' => $this->getName()], true);
        }

        $settings = [
            'security' => [
                'requestedAuthnContext' => false,
            ],
            'sp' => [
                'entityId' => $spEntityId,
                'assertionConsumerService' => [
                    'url' => Url::to(['/user/auth/external', 'authclient' => $this->getName(), 'handleAcs' => 1], true),
                ],
                'singleLogoutService' => [
                    'url' => Url::to(['/saml-sso/logout', 'authclient' => $this->getName()], true),
                ],
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',

            ],
            'idp' => [
                'entityId' => $this->entityId,
                'singleSignOnService' => [
                    'url' => $this->singleSignOnServiceUrl,
                    // SAML protocol binding to be used when returning the <Response>
                    // message. OneLogin Toolkit supports the HTTP-Redirect binding
                    // only for this endpoint.
                    // 'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'singleLogoutService' => [
                    'url' => $this->singleLogoutServiceUrl,
                ],
                'x509cert' => $this->x509cert,
            ],
        ];

        if (!empty($this->spX509cert)) {
            $settings['sp']['x509cert'] = $this->spX509cert;
        }

        if (!empty($this->spPrivateKey)) {
            $settings['sp']['privateKey'] = $this->spPrivateKey;
        }

        return ArrayHelper::merge($settings, $this->advancedSettings);
    }

    /**
     * @inheritdoc
     */
    public function authAction($authAction)
    {
        $authAction->controller->enableCsrfValidation = false;

        /** @var Module $module */
        $module = Yii::$app->getModule('saml-sso');
        $settings = $module->getSettingsModel();

        if (Yii::$app->request->get('handleAcs')) {

            //Yii::error('Process ACS ' . print_r($_REQUEST, 1), 'saml');

            // Handle acs request
            $this->auth->processResponse(Yii::$app->session->get('samlAuthNRequestID'));

            $errors = $this->auth->getErrors();

            if (!empty($errors)) {
                Yii::$app->session->setFlash('error', implode(', ', $errors));
                return Yii::$app->getResponse()->redirect(['/user/auth/login']);
            }

            if (!$this->auth->isAuthenticated()) {
                Yii::$app->session->setFlash('error', 'SAML auth failed!');
                return Yii::$app->getResponse()->redirect(['/user/auth/login']);
            }

            Yii::$app->session->remove('samlAuthNRequestID');

            $samlAttributes = $this->loadExtraAttributes($this->auth->getAttributes());
            if (!empty($samlAttributes['errorMessage'])) {
                Yii::$app->session->setFlash('error', $samlAttributes['errorMessage']);
                return Yii::$app->getResponse()->redirect(['/user/auth/login']);
            }

            if ($settings->logAvailableAttributes) {
                Yii::error("Got SAML Attributes" . print_r($samlAttributes, 1), 'saml-sso');
            }

            $state = [
                'nameId' => $this->auth->getNameId(),
                'nameIdFormat' => $this->auth->getNameIdFormat(),
                'nameIdNameQualifier' => $this->auth->getNameIdNameQualifier(),
                'nameIdSPNameQualifier' => $this->auth->getNameIdSPNameQualifier(),
                'sessionIndex' => $this->auth->getSessionIndex()
            ];
            Yii::$app->session->set('samlState', $state);

            $this->setUserAttributes($samlAttributes);
            $this->autoStoreAuthClient();

            return $authAction->authSuccess($this);
        } else {
            if (!Yii::$app->user->isGuest) {
                Yii::$app->user->logout();
            }

            Yii::$app->session->set('samlAuthNRequestID', $this->auth->getLastRequestID());
            return $this->auth->login();
        }
    }


    protected function loadExtraAttributes($attributes)
    {
        return $attributes;
    }


    public function logout()
    {
        $state = Yii::$app->session->get('samlState');

        $nameId = null;
        $nameIdFormat = null;
        $nameIdNameQualifier = null;
        $nameIdSPNameQualifier = null;
        $sessionIndex = null;

        if (isset($state['nameId'])) {
            $nameId = $state['nameId'];
        }
        if (isset($state['nameIdFormat'])) {
            $nameIdFormat = $state['nameIdFormat'];
        }
        if (isset($state['nameIdNameQualifier'])) {
            $nameIdNameQualifier = $state['nameIdNameQualifier'];
        }
        if (isset($state['nameIdSPNameQualifier'])) {
            $nameIdSPNameQualifier = $state['nameIdSPNameQualifier'];
        }
        if (isset($state['sessionIndex'])) {
            $sessionIndex = $state['sessionIndex'];
        }

        Yii::$app->session->set('samlLogoutRequestID', $this->auth->getLastRequestID());

        $this->auth->logout(
            null,
            [],
            $nameId,
            $sessionIndex,
            false,
            $nameIdFormat,
            $nameIdNameQualifier,
            $nameIdSPNameQualifier
        );
    }

    public function processSLO($requestId = null)
    {

        if (isset($_REQUEST['SAMLResponse'])) {
            $_GET['SAMLResponse'] = $_REQUEST['SAMLResponse'];
        }
        if (isset($_REQUEST['SAMLRequest'])) {
            $_GET['SAMLRequest'] = $_REQUEST['SAMLRequest'];
        }

        $this->auth->processSLO(false, $requestId);

        $errors = $this->auth->getErrors();
        if (empty($errors)) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getSyncAttributes()
    {
        return ['lastname', 'firstname', 'email'];
    }

    /**
     * Automatically stores this auth client to a found user.
     * So the user doesn't needs to login and manually set this authclient
     */
    private function autoStoreAuthClient()
    {
        $attributes = $this->getUserAttributes();
        if (isset($attributes['email'])) {
            $user = User::findOne(['email' => $attributes['email']]);
            if ($user !== null) {
                AuthClientHelpers::storeAuthClientForUser($this, $user);
            }
        } else {
            Yii::error("Could not auto store auth client. 'email' attribute was not provided. Attributes: " . print_r($attributes, 1), 'saml-sso');
        }
    }

    /**
     * @inheritdoc
     */
    protected function normalizeUserAttributes($attributes)
    {
        // Remove array values
        foreach ($attributes as $name => $value) {
            if (is_array($value) && !empty($value)) {
                $attributes[$name] = $value[0];
            }
        }

        return parent::normalizeUserAttributes($attributes);
    }

    /**
     * @inheritdoc
     */
    public function setUserAttributes($userAttributes)
    {
        if (!isset($userAttributes['id'])) {
            $userAttributes['id'] = $this->auth->getNameId();
        }

        if (isset($userAttributes['id']) && !isset($userAttributes['email'])) {
            $validator = new \yii\validators\EmailValidator();
            if ($validator->validate($userAttributes['id'], $error)) {
                $userAttributes['email'] = $userAttributes['id'];
            }
        }

        parent::setUserAttributes($userAttributes);
    }

    /**
     * @inheritdoc
     */
    protected function defaultViewOptions()
    {
        return [
            'cssIcon' => 'fa fa-sign-in',
            'buttonBackgroundColor' => '#4078C0',
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defaultName()
    {
        return 'saml';
    }

    /**
     * @inheritdoc
     */
    protected function defaultTitle()
    {
        return 'SAML';
    }

}