<?php

namespace humhub\modules\sso\saml\controllers;

use humhub\components\Response;
use humhub\modules\sso\saml\authclient\SAML;
use humhub\modules\sso\saml\Module;
use OneLogin\Saml2\Error;
use Yii;
use yii\web\HttpException;
use humhub\components\Controller;

class MetadataController extends Controller
{
    /**
     * @var SAML
     */
    public $authClient;

    /**
     * @inheritDoc
     */
    public function beforeAction($action)
    {
        $authClientId = Yii::$app->request->get('authclient', 'saml');

        if (!Yii::$app->authClientCollection->hasClient($authClientId)) {
            /** @var Module $module */
            $module = Yii::$app->getModule('saml-sso');
            $settings = $module->getSettingsModel();

            if (empty($settings->entityId)) {
                $settings->entityId = 'https://localhost/-not-configured-';
            }
            if (empty($settings->x509cert)) {
                $settings->x509cert = '-not-configured-';
            }
            if (empty($settings->singleSignOnServiceUrl)) {
                $settings->singleSignOnServiceUrl = 'https://localhost/-not-configured-';
            }
            $settings->addAuthClient(null, $authClientId);
        }

        $this->authClient = Yii::$app->authClientCollection->getClient($authClientId);
        return parent::beforeAction($action);
    }

    /**
     * **Required**
     *
     * @return void
     * @throws HttpException
     */
    public function actionIndex()
    {
        // REQUIRED: For Old EntityID URL
        return $this->actionDownload();
    }


    public function actionDownload()
    {
        $metadata = '';
        try {
            $settings = $this->authClient->getSamlAuth()->getSettings();
            $metadata = $settings->getSPMetadata();

            $errors = $settings->validateMetadata($metadata);
            if (!empty($errors)) {
                throw new Error(
                    'Invalid SP metadata: ' . implode(', ', $errors),
                    Error::METADATA_SP_INVALID
                );
            }

        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage());
        }

        #Yii::$app->response->format = Response::FORMAT_XML;

        Yii::$app->response->sendContentAsFile($metadata, 'metadata.xml', [
            'mimetype' => 'application/xml',
        ]);
    }

}