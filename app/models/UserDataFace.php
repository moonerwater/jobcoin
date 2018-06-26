<?php

use Phalcon\Mvc\Model;

class UserDataFace extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "user_data_face";
    }
}
