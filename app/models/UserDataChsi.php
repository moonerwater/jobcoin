<?php

use Phalcon\Mvc\Model;

class UserDataChsi extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "user_data_chsi";
    }
}
