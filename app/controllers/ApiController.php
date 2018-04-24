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
                $partner_id = $this->checkTokenAndGetPartner($token);

                $data = trim($this->request->get('data'));
                $data = json_decode($data);
                if(!is_object($data)){
                    $this->replyFailure('data is empty');
                    return '';
                }
                if(!trim($data->name)){
                    $this->replyFailure('name is empty');
                    return '';
                }
                if(!trim($data->idcard)){
                    $this->replyFailure('idcard is empty');
                    return '';
                }
                if(!trim($data->phone)){
                    $this->replyFailure('phone is empty');
                    return '';
                }

                if(!$this->isPhone(trim($data->phone))){
                    $this->replyFailure('phone format is wrong');
                    return '';
                }
                if(!$this->isIdCard(trim($data->idcard))){
                    $this->replyFailure('idcard format is wrong');
                    return '';
                }
                $jobseeker = \Jobseeker::findFirstByIdcard(trim($data->idcard));
                if ($jobseeker) {
                    $result = new stdClass();
                    $result->credit_id = $jobseeker->credit_id;
                    $this->reply('idcard is exist', 0, $result);
                    return '';
                }

                $jobseeker = new \Jobseeker();
                $jobseeker->credit_id = $this->buildCreditId('jobseeker');
                $jobseeker->credit_score = 600;
                $jobseeker->jobcoin = 0;
                $jobseeker->name = $data->name;
                $jobseeker->idcard = $data->idcard;
                $jobseeker->phone = $data->phone;
                $jobseeker->experience_score = 0;
                $jobseeker->ability_score = 0;
                $jobseeker->partner_id = $partner_id;
                unset($data->name, $data->idcard, $data->phone);
                $jobseeker->attr = json_encode($data, 320);
                $jobseeker->create_time = time();
                $jobseeker->last_time = time();
                if(!$jobseeker->save()){
                    $this->replyFailure('save is error');
                    return '';
                }
                else{
                    $result = new stdClass();
                    $result->credit_id = $jobseeker->credit_id;
                    $this->reply('success', 0, $result);
                }

                break;

            case 'get':
                $token = trim($this->request->get('token'));
                $partner_id = $this->checkTokenAndGetPartner($token);

                $idcard = trim($this->request->get('idcard'));
                if(!trim($idcard)){
                    $this->replyFailure('idcard is empty');
                    return '';
                }
                $jobseeker = \Jobseeker::findFirstByIdcard($idcard);
                if (!$jobseeker) {
                    $this->replyFailure('no this idcard');
                    return '';
                }
                $result = new stdClass();
                $result->credit_id = $jobseeker->credit_id;
                $result->credit_score = $jobseeker->credit_score;
                $result->work_count = 0;
                $result->jobcoin = $jobseeker->jobcoin;
                $result->ability_score = $jobseeker->ability_score;
                $result->experience_score = $jobseeker->experience_score;
                $this->reply('success', 0, $result);
                break;
        }
    }

    public function companyAction($action) {
        switch($action){
            case 'add':
                $token = trim($this->request->get('token'));
                $partner_id = $this->checkTokenAndGetPartner($token);

                $org_code = trim($this->request->get('org_code'));
                $tax_code = trim($this->request->get('tax_code'));
                $bus_image = trim($this->request->get('bus_image'));
                if(!trim($org_code)){
                    $this->replyFailure('org_code is empty');
                    return '';
                }
                if(!trim($tax_code)){
                    $this->replyFailure('tax_code is empty');
                    return '';
                }
                if(!trim($bus_image)){
                    $this->replyFailure('bus_image is empty');
                    return '';
                }
                $company = \Company::findFirstByTax_code(trim($tax_code));
                if ($company) {
                    $result = new stdClass();
                    $result->credit_id = $company->credit_id;
                    $this->reply('credit_id is exist', 0, $result);
                    return '';
                }
                $company = new \Company();
                $company->credit_id = $this->buildCreditId('company');
                $company->credit_score = 600;
                $company->org_code = $org_code;
                $company->tax_code = $tax_code;
                $company->bus_image = $bus_image;
                $company->partner_id = $partner_id;
                $company->create_time = time();
                $company->last_time = time();
                if(!$company->save()){
                    $this->replyFailure('save is error');
                    return '';
                }
                else{
                    $result = new stdClass();
                    $result->credit_id = $company->credit_id;
                    $this->reply('success', 0, $result);
                }

                break;

            case 'get':
                $token = trim($this->request->get('token'));
                $partner_id = $this->checkTokenAndGetPartner($token);

                $tax_code = trim($this->request->get('tax_code'));
                if(!trim($tax_code)){
                    $this->replyFailure('tax_code is empty');
                    return '';
                }
                $company = \Company::findFirstByTax_code($tax_code);
                if (!$company) {
                    $this->replyFailure('no this tax_code');
                    return '';
                }
                $result = new stdClass();
                $result->credit_id = $company->credit_id;
                $result->credit_score = $company->credit_score;
                $result->comment = "";
                $this->reply('success', 0, $result);
                break;
        }
    }

    public function companybossAction($action) {
        switch($action){
            case 'add':

                break;
        }
    }
}
