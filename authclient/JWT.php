<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\sso\jwt\authclient;

use humhub\modules\user\authclient\BaseClient;
use humhub\modules\user\services\AuthClientUserService;
use Yii;
use humhub\modules\user\authclient\interfaces\StandaloneAuthClient;
use humhub\modules\user\models\User;

/**
 * JWT Authclient
 */
class JWT extends BaseClient implements StandaloneAuthClient
{
    /**
     * @var string url of the JWT provider
     */
    public $url = '';

    /**
     * @var string shared key
     */
    public $sharedKey = '';

    /**
     * @var array a list of supported jwt verification algorithms Supported algorithms are 'HS256', 'HS384', 'HS512' and 'RS256'
     */
    public $supportedAlgorithms = array(['HS256']);

    /**
     * @var string attribute to match user tables with (email, username, id, guid)
     */
    public $idAttribute = 'email';

    /**
     * @var int token time leeway
     */
    public $leeway = 60;

    /**
     * @var array the list of IPs that are allowed to use JWT.
     * Each array element represents a single IP filter which can be either an IP address
     * or an address with wildcard (e.g. 192.168.0.*) to represent a network segment.
     */
    public $allowedIPs = [];

    /**
     * @var bool enable automatic login of 'allowed ips'.
     */
    public $autoLogin = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        Yii::setAlias('@Firebase/JWT', '@jwt-sso/vendors/php-jwt/src');
    }

    /**
     * @inheritdoc
     */
    public function authAction($authAction)
    {
        $token = Yii::$app->request->get('jwt');

        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }

        if ($token == '') {
            return $this->redirectToBroker();
        }

        try {
            \Firebase\JWT\JWT::$leeway = $this->leeway;
            $decodedJWT = \Firebase\JWT\JWT::decode($token, $this->sharedKey, $this->supportedAlgorithms);
        } catch (\Exception $ex) {
            Yii::$app->session->setFlash('error', Yii::t('JwtSsoModule.jwt', $ex->getMessage()));
            return Yii::$app->getResponse()->redirect(['/user/auth/login']);
        }

        $this->setUserAttributes((array)$decodedJWT);
        Yii::$app->session->set('loginRememberMe', true);
        $this->autoStoreAuthClient();


        $defaultRedirect = $authAction->authSuccess(client: $this);

        if (isset($decodedJWT->redirectLink)) {
            Yii::$app->getResponse()->redirect($decodedJWT->redirectLink);
        }
        return $defaultRedirect;
    }

    /**
     * @inheritdoc
     */
    public function setUserAttributes($userAttributes)
    {
        // Remove JWT Attributes
        unset($userAttributes['iss']);
        unset($userAttributes['jti']);
        unset($userAttributes['iat']);

        if (!isset($userAttributes['id'])) {
            if ($this->idAttribute == 'email' && isset($userAttributes['email'])) {
                $userAttributes['id'] = $userAttributes['email'];
            } else if ($this->idAttribute == 'guid' && isset($userAttributes['guid'])) {
                $userAttributes['id'] = $userAttributes['guid'];
            } else if ($this->idAttribute == 'username' && isset($userAttributes['username'])) {
                $userAttributes['id'] = $userAttributes['username'];
            }
        }

        return parent::setUserAttributes($userAttributes);
    }

    public function redirectToBroker()
    {
        return Yii::$app->getResponse()->redirect($this->url);
    }

    /**
     * @inheritdoc
     */
    protected function defaultViewOptions()
    {
        return [
            'cssIcon' => 'fa fa-fast-forward',
            'buttonBackgroundColor' => '#4078C0',
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defaultName()
    {
        return 'jwt';
    }

    /**
     * @inheritdoc
     */
    protected function defaultTitle()
    {
        return 'JWT SSO';
    }

    /**
     * Automatically stores this auth client to a found user.
     * So the user doesn't needs to login and manually set this authclient
     */
    protected function autoStoreAuthClient()
    {
        $user = $this->getUserByAttributes();
        if ($user !== null) {
            (new AuthClientUserService($user))->add($this);
        }
    }

    /**
     * @return User|null
     */
    protected function getUserByAttributes()
    {
        $attributes = $this->getUserAttributes();
        if (isset($attributes['email'])) {
            return User::findOne(['email' => $attributes['email']]);
        } else if(isset($attributes['username'])) {
            return User::findOne(['username' => $attributes['username']]);
        } else if(isset($attributes['id'])) {
            return User::findOne(['id' => $attributes['id']]);
        } else if(isset($attributes['guid'])) {
            return User::findByGuid($attributes['guid']);
        }

        return null;
    }

    public function checkIPAccess()
    {
        if (empty($this->allowedIPs)) {
            return true;
        }

        $ip = Yii::$app->getRequest()->getUserIP();
        foreach ($this->allowedIPs as $filter) {
            if ($filter === '*' || $filter === $ip || (($pos = strpos($filter, '*')) !== false && !strncmp($ip, $filter, $pos))) {
                return true;
            }
        }
        return false;
    }

}
