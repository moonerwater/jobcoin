<?php

use Phalcon\Mvc\Model;

class DbListUserDetail extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "db_list_user_detail";
    }
}
