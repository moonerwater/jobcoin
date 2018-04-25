<?php

use Phalcon\Mvc\Model;

class Job extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "job";
    }
}
