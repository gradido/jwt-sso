<?php

/** @noinspection MissedFieldInspection */

use humhub\modules\user\controllers\AuthController;

return [
    'id' => 'jwt-sso',
    'class' => 'humhub\modules\sso\jwt\Module',
    'namespace' => 'humhub\modules\sso\jwt',
    'events' => [
        [AuthController::class, AuthController::EVENT_BEFORE_ACTION, ['humhub\modules\sso\jwt\Module', 'onAuthClientCollectionInit']],
    ],
];
