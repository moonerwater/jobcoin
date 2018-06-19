<?php

use Phalcon\Mvc\Model;

class User extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "user";
    }
}
