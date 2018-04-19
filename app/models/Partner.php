<?php

use Phalcon\Mvc\Model;

class Partner extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "partner";
    }
}
