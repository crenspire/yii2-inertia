<?php

declare(strict_types=1);

namespace app\models;

use yii\base\Model;

class ContactForm extends Model
{
    public ?string $name = null;
    public ?string $email = null;
    public ?string $message = null;

    public function rules(): array
    {
        return [
            [['name', 'email', 'message'], 'required'],
            ['email', 'email'],
            ['message', 'string', 'min' => 10],
        ];
    }
}
