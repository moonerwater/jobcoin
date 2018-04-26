<?php

use Phalcon\Mvc\Model;

class Apply extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "apply";
    }
}
