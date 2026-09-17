<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests\Support;

use yii\web\Session;

/**
 * In-memory session that keeps $_SESSION across simulated requests without sending headers.
 */
class ArraySession extends Session
{
    private bool $active = false;

    public function init(): void
    {
    }

    public function open(): void
    {
        if ($this->active) {
            return;
        }
        $this->active = true;
        $_SESSION ??= [];
        $this->updateFlashCounters();
    }

    public function close(): void
    {
        $this->active = false;
    }

    public function destroy(): void
    {
        $_SESSION = [];
        $this->active = false;
    }

    public function getIsActive(): bool
    {
        return $this->active;
    }

    public function getHasSessionId(): bool
    {
        return $this->active || !empty($_SESSION);
    }

    public function regenerateID($deleteOldSession = false): void
    {
    }
}
