<?php

use Phalcon\Mvc\Model;

class Company extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "company";
    }
}
