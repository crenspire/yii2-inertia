<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests\Support;

use yii\base\BaseObject;
use yii\web\IdentityInterface;

class TestIdentity extends BaseObject implements IdentityInterface
{
    public int $id = 1;

    public static function findIdentity($id): ?self
    {
        return (int) $id === 1 ? new self() : null;
    }

    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        return null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAuthKey(): ?string
    {
        return null;
    }

    public function validateAuthKey($authKey): bool
    {
        return false;
    }
}
