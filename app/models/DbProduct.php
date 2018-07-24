<?php

use Phalcon\Mvc\Model;

class DbProduct extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "db_product";
    }
}
