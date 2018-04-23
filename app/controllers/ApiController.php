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

    public function jobseekerAction($action) {
        switch($action) {
            case 'add':
                $token = trim($this->request->get('token'));
                $partnerid = $this->checkAndGetToken($token);

                $data = trim($this->request->get('data'));
                $data = json_decode($data, true);
                print_r($data);
                if(!is_array($data) || !$data){
                    $this->replyFailure('data is empty', 404, null);
                    return '';
                }

                echo '456';

                break;
        }
    }
}
