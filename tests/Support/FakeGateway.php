<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests\Support;

use Crenspire\Yii2Inertia\Ssr\Gateway;
use Crenspire\Yii2Inertia\Ssr\SsrResponse;

class FakeGateway implements Gateway
{
    /** @var array<string, mixed>|null */
    public ?array $dispatched = null;

    public function __construct(private readonly ?SsrResponse $response)
    {
    }

    public function dispatch(array $page): ?SsrResponse
    {
        $this->dispatched = $page;

        return $this->response;
    }
}
