<?php

namespace humhub\modules\sso\saml\controllers;

use humhub\modules\sso\saml\authclient\SAML;
use humhub\modules\user\Module;
use Yii;
use yii\web\HttpException;
use humhub\components\Controller;

class LogoutController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        if (!Yii::$app->user->isGuest) {
            $authClient = Yii::$app->user->getCurrentAuthClient();

            if ($authClient !== null && $authClient instanceof SAML) {
                /** @var SAML $authClient */
                $authClient->processSLO(false, Yii::$app->session->get('samlLogoutRequestID', null));
            }
        }

        Yii::$app->user->logout();

        /** @var Module $userModule */
        $userModule = Yii::$app->getModule('user');
        return $this->redirect(($userModule->logoutUrl) ? $userModule->logoutUrl : Yii::$app->homeUrl);
    }

}