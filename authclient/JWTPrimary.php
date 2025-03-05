<?php

namespace humhub\modules\sso\jwt\authclient;

use humhub\modules\user\authclient\interfaces\PrimaryClient;
use humhub\modules\user\authclient\interfaces\AutoSyncUsers;
use humhub\modules\user\authclient\interfaces\SyncAttributes;

class JWTPrimary extends JWT implements PrimaryClient, AutoSyncUsers, SyncAttributes
{
    public $syncAttributes = ['email', 'username'];

    public function getUser()
    {
        return $this->getUserByAttributes();
    }

    /**
     * @inheritdoc
     */
    public function syncUsers()
    {
        // Users needs to be synced manually. e.g. via REST
        return null;
    }

    public function getSyncAttributes()
    {
        return $this->syncAttributes;
    }
}
