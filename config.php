<?php

/** @noinspection MissedFieldInspection */

use humhub\modules\user\authclient\Collection;
use humhub\modules\user\controllers\AuthController;

return [
    'id' => 'saml-sso',
    'class' => 'humhub\modules\sso\saml\Module',
    'namespace' => 'humhub\modules\sso\saml',
    'urlManagerRules' => [
        'saml-sso/metadata/<authclient>' => 'saml-sso/metadata/download',
    ],
    'events' => [
        [Collection::class, Collection::EVENT_AFTER_CLIENTS_SET, ['humhub\modules\sso\saml\Events', 'onAuthClientCollectionInit']],
        [AuthController::class, AuthController::EVENT_BEFORE_ACTION, ['humhub\modules\sso\saml\Events', 'onAuthControllerBeforeAction']],
    ]
];
?>