<?php

use Phalcon\Mvc\Model;

class UserCandy extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "user_candy";
    }
}
