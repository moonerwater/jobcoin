<?php

use Phalcon\Mvc\Model;

class CompanyBoss extends Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function getSource()
    {
        return "company_boss";
    }
}
