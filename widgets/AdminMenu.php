<?php

namespace humhub\modules\sso\saml\widgets;

use Yii;
use yii\helpers\Url;

class AdminMenu extends \humhub\widgets\BaseMenu
{

    public $template = "@humhub/widgets/views/tabMenu";

    public function init()
    {
        $this->addItem([
            'label' => Yii::t('SamlSsoModule.base', 'Configuration'),
            'url' => Url::to(['/saml-sso/admin']),
            'sortOrder' => 50,
            'isActive' => (Yii::$app->controller->action->id === 'index'),
        ]);

        $this->addItem([
            'label' => Yii::t('SamlSsoModule.base', 'Information'),
            'url' => Url::to(['/saml-sso/admin/info']),
            'sortOrder' => 100,
            'isActive' => (Yii::$app->controller->action->id === 'info'),
        ]);

        parent::init();
    }

}
