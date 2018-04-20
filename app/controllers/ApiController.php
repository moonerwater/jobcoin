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

    public function sysAction($action) {
        switch ($action) {
            case 'getversion':
                $parter = \Partner::find();
                //print_r($parter->toArray());
                $jobseeker = \Jobseeker::query()->columns('json_extract(attr, "$.name") as name2')->where(' json_extract(attr, "$.name") = "david" ')->orderBy('id asc')->execute();
                print_r($jobseeker->toArray());
                break;
        }
    }
}
