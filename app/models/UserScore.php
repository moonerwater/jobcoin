<?php

use Phalcon\Mvc\Model;

class UserScore extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "user_score";
    }
}
