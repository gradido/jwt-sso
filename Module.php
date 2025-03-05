<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\sso\jwt;

use humhub\components\Event;
use Yii;
use yii\helpers\Url;

class Module extends \humhub\components\Module
{
    public $resourcesPath = 'resources';

    /**
     * JWT Handling on login page
     *
     * @param Event $event
     * @return void
     * @throws \yii\base\InvalidConfigException
     * @since 1.1
     */
    public static function onAuthClientCollectionInit($event)
    {
        if (!Yii::$app->user->isGuest) {
            return;
        }

        if (Yii::$app->authClientCollection->hasClient('jwt')) {
            $jwtAuth = Yii::$app->authClientCollection->getClient('jwt');

            // direct redirect
            if (!Yii::$app->request->get('jwt') && $jwtAuth->url) {
                $event->sender->redirect($jwtAuth->url);
            }
            
            if ($jwtAuth->checkIPAccess()) {
                if ($jwtAuth->autoLogin && $event->action->id == 'login' && empty(Yii::$app->request->get('noJwt'))) {
                    $event->isValid = false;
                    return $jwtAuth->redirectToBroker();
                }
            } else {
                // Not allowed, remove authClient
                Yii::$app->authClientCollection->removeClient('jwt');
            }
        }
    }
}
