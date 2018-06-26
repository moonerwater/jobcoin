<?php

use Phalcon\Mvc\Model;

class UserDataIdcard extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "user_data_idcard";
    }
}
