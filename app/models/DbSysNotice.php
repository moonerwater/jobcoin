<?php

use Phalcon\Mvc\Model;

class DbSysNotice extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "db_sys_notice";
    }
}
