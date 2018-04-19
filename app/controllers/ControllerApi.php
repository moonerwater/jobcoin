<?php

class ControllerApi extends ControllerBase
{

    protected function initialize()
    {
        parent::initialize();
        $this->view->setVar('versionNum', date('Ymd'));
    }

}
