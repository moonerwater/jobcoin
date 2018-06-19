<?php

use Phalcon\Mvc\Model;

class UserJobcoin extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "user_jobcoin";
    }
}
