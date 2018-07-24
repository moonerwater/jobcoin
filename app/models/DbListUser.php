<?php

use Phalcon\Mvc\Model;

class DbListUser extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "db_list_user";
    }
}
