<?php

namespace humhub\modules\sso\saml\controllers;

use Yii;
use humhub\modules\admin\components\Controller;
use humhub\modules\sso\saml\models\Settings;
use yii\base\BaseObject;
use yii\helpers\Url;

class AdminController extends Controller
{
    public function actionIndex()
    {
        $model = new Settings();
        $model->loadSettings();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
            return $this->redirect(['index']);
        }

        return $this->render('index', ['model' => $model]);
    }


    public function actionInfo()
    {
        $model = new Settings();
        $model->loadSettings();

        $spEntityId = Url::to(['/saml-sso/metadata/download', 'authclient' => 'saml'], true);
        if ($model->spUseDeprecatedEntityId === true) {
            $spEntityId = Url::to(['/saml-sso/metadata', 'authclient' => 'saml'], true);
        }


        return $this->render('info', [
            'authclientId' => 'saml',
            'entityId' => $spEntityId,
        ]);
    }

}