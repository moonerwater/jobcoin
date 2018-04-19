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
                echo json_encode(array('version' => 1.60));
                break;
        }
    }
}
