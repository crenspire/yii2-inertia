<?php

declare(strict_types=1);

namespace app\models;

use yii\base\Model;

class User extends Model
{
    public $id;
    public $username;
    public $email;
}

