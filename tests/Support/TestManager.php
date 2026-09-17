<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests\Support;

use Crenspire\Yii2Inertia\Manager;
use yii\base\Event;
use yii\web\Request;

class TestManager extends Manager
{
    /** @var array<string, string> */
    public array $sentCookies = [];

    public int $beforeSendCalls = 0;

    public function handleBeforeSend(Event $event): void
    {
        $this->beforeSendCalls++;
        parent::handleBeforeSend($event);
    }

    protected function sendCsrfCookie(string $token, Request $request): void
    {
        $this->sentCookies[$this->csrfCookieName] = $token;
    }
}
