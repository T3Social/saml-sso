<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\sso\saml;

use humhub\modules\sso\saml\authclient\SAML;
use humhub\modules\sso\saml\models\Settings;
use humhub\modules\user\authclient\Collection;
use humhub\modules\user\controllers\AuthController;
use Yii;
use yii\base\ActionEvent;

/**
 * Class Events
 *
 * @package humhub\modules\sso\saml
 */
class Events
{


    /**
     * Automatically add configured SAML AuthClient to collection
     *
     * @param \humhub\components\Event $event
     * @return void
     */
    public static function onAuthClientCollectionInit($event)
    {
        /** @var Collection $collection */
        $collection = $event->sender;

        /** @var Module $module */
        $module = Yii::$app->getModule('saml-sso');
        $settings = $module->getSettingsModel();

        if ($settings->isEnabled) {
            $settings->addAuthClient($collection);
        }
    }

    /**
     * @param $event ActionEvent
     */
    public static function onAuthControllerBeforeAction($event)
    {
        /** @var AuthController $controller */
        $controller = $event->sender;

        /** @var Module $module */
        $module = Yii::$app->getModule('saml-sso');
        $settings = $module->getSettingsModel();

        // Workaround to disable client validation on POST ACS Requests
        if ($event->action->id === 'external' && Yii::$app->request->get('handleAcs')) {
            $controller->enableCsrfValidation = false;
        } elseif ($event->action->id === 'login' && Yii::$app->request->isGet && $settings->forceSamlAuth && empty(Yii::$app->request->get('noSaml'))) {
            $event->isValid = false;
            Yii::$app->response->redirect(['/user/auth/external', 'authclient' => 'saml']);
        } elseif ($event->action->id === 'logout' && empty(Yii::$app->request->get('saml-callback')) && !Yii::$app->user->isGuest) {
            $authClient = Yii::$app->user->getCurrentAuthClient();
            if ($authClient !== null && $authClient instanceof SAML && !empty($settings->singleLogoutServiceUrl)) {
                $event->isValid = false;
                $authClient->logout();
            }
        }
    }
}