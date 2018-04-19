<?php

class ApiController extends ControllerApi
{
    public function initialize()
    {
        parent::initialize();
    }

    public function indexAction()
    {
        echo '123456';
    }

    public function v2Action($action) {
        switch ($action) {
            case 'getversion':
                $parter = \Partner::find();
                print_r($parter->toArray());
                break;
        }
    }
}
