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
                $jobseeker->jobcoin = 1000;
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

                $name = trim($this->request->get('name'));
                $org_code = trim($this->request->get('org_code'));
                $tax_code = trim($this->request->get('tax_code'));
                $bus_image = trim($this->request->get('bus_image'));
                if(!trim($name)){
                    $this->replyFailure('name is empty');
                    return '';
                }
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
                    $this->reply('tax_code is exist', 0, $result);
                    return '';
                }
                $company = new \Company();
                $company->credit_id = $this->buildCreditId('company');
                $company->credit_score = 600;
                $company->jobcoin = 1000;
                $company->name = $name;
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
                $result->name = $company->name;
                $result->jobcoin = $company->jobcoin;
                $result->comment = "";
                $this->reply('success', 0, $result);
                break;
        }
    }

    public function companybossAction($action) {
        switch($action){
            case 'add':
                $token = trim($this->request->get('token'));
                $partner_id = $this->checkTokenAndGetPartner($token);

                $tax_code = trim($this->request->get('tax_code'));
                $name = trim($this->request->get('name'));
                $idcard = trim($this->request->get('idcard'));
                $phone = trim($this->request->get('phone'));

                if(!trim($tax_code)){
                    $this->replyFailure('tax_code is empty');
                    return '';
                }
                if(!trim($name)){
                    $this->replyFailure('name is empty');
                    return '';
                }
                if(!trim($idcard)){
                    $this->replyFailure('idcard is empty');
                    return '';
                }
                if(!trim($phone)){
                    $this->replyFailure('phone is empty');
                    return '';
                }
                if(!$this->isPhone(trim($phone))){
                    $this->replyFailure('phone format is wrong');
                    return '';
                }
                if(!$this->isIdCard(trim($idcard))){
                    $this->replyFailure('idcard format is wrong');
                    return '';
                }
                $company = \Company::findFirstByTax_code($tax_code);
                if (!$company) {
                    $this->replyFailure('no this tax_code');
                    return '';
                }
                $companyboss =\CompanyBoss::findFirst([' company_id = ?1 and idcard = ?2 ', 'bind'=>[ 1=>$company->id, 2=>$idcard ] ]);
                if ($companyboss) {
                    $result = new stdClass();
                    $result->idcard = $companyboss->idcard;
                    $this->reply('idcard is exist', 0, $result);
                    return '';
                }
                $companyboss = new \CompanyBoss();
                $companyboss->company_id = $company->id;
                $companyboss->name = $name;
                $companyboss->idcard = $idcard;
                $companyboss->phone = $phone;
                $companyboss->partner_id = $partner_id;
                $companyboss->create_time = time();
                $companyboss->last_time = time();
                if(!$companyboss->save()){
                    $this->replyFailure('save is error');
                    return '';
                }
                else{
                    $result = new stdClass();
                    $result->idcard = $companyboss->idcard;
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
                //$companyboss = \CompanyBoss::find(array( 'company_id = ?1', 'bind'=>array(1=>$company->id), 'columns'=>'name, idcard, phone'));
                $companyboss = \CompanyBoss::find(array( 'company_id = :name:', 'bind'=>array('name'=>$company->id), 'columns'=>'name, idcard, phone'));
                $result = $companyboss->toArray();
                $this->reply('success', 0, $result);
                break;
        }
    }

    public function resumeAction($action){
        switch($action) {
            case 'add':
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

                $resume = \Resume::findFirstByJobseeker_id($jobseeker->id);
                if($resume){
                    $result = new stdClass();
                    $result->idcard = $idcard;
                    $this->reply('resume is exist', 0, $result);
                    return '';
                }

                $data = trim($this->request->get('data'));
                $data = json_decode($data);
                if(!is_array($data) || !$data){
                    $this->replyFailure('data is empty');
                    return '';
                }

                foreach($data as $k => $v){
                    if(!trim($v->company_name)){
                        $this->replyFailure('company_name is empty');
                        return '';
                    }
                    if(!trim($v->position)){
                        $this->replyFailure('position is empty');
                        return '';
                    }
                    if(!trim($v->start_time)){
                        $this->replyFailure('start_time is empty');
                        return '';
                    }
                    if(!trim($v->end_time)){
                        $this->replyFailure('end_time is empty');
                        return '';
                    }
                    if(!trim($v->work_content)){
                        $this->replyFailure('work_content is empty');
                        return '';
                    }
                }

                $resume = new \Resume();
                $resume->jobseeker_id = $jobseeker->id;
                $resume->partner_id = $partner_id;
                $resume->exp_attr = json_encode($data, 320);
                $resume->create_time = time();
                $resume->last_time = time();
                if(!$resume->save()){
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

                $resume = \Resume::findFirstByJobseeker_id($jobseeker->id);

                $result = new stdClass();
                $result->credit_id = $jobseeker->credit_id;
                $result->name = $jobseeker->name;
                $result->idcard = $jobseeker->idcard;
                $result->evaluation = "good";
                $exp = json_decode($resume->exp_attr, true);
                $result->exp = $exp ? $exp : array();
                $this->reply('success', 0, $result);
                break;
        }
    }

    public function jobAction($action) {
        switch($action) {
            case 'add':
                $token = trim($this->request->get('token'));
                $partner_id = $this->checkTokenAndGetPartner($token);

                $tax_code = trim($this->request->get('tax_code'));
                $position = trim($this->request->get('position'));
                $work_content = trim($this->request->get('work_content'));
                $need_people = trim($this->request->get('need_people'));
                $work_time = trim($this->request->get('work_time'));
                $salary = trim($this->request->get('salary'));
                $province = trim($this->request->get('province'));
                $city = trim($this->request->get('city'));
                $district = trim($this->request->get('district'));
                $address = trim($this->request->get('address'));

                if(!trim($tax_code)){
                    $this->replyFailure('tax_code is empty');
                    return '';
                }
                $company = \Company::findFirstByTax_code($tax_code);
                if (!$company) {
                    $this->replyFailure('no this tax_code');
                    return '';
                }
                if(!trim($position)){
                    $this->replyFailure('position is empty');
                    return '';
                }
                if(!trim($work_content)){
                    $this->replyFailure('work_content is empty');
                    return '';
                }
                if(!trim($need_people)){
                    $this->replyFailure('need_people is empty');
                    return '';
                }
                if(!trim($work_time)){
                    $this->replyFailure('work_time is empty');
                    return '';
                }
                if(!trim($salary)){
                    $this->replyFailure('salary is empty');
                    return '';
                }
                if(!trim($province)){
                    $this->replyFailure('province is empty');
                    return '';
                }
                if(!trim($city)){
                    $this->replyFailure('city is empty');
                    return '';
                }
                if(!trim($district)){
                    $this->replyFailure('district is empty');
                    return '';
                }
                if(!trim($address)){
                    $this->replyFailure('address is empty');
                    return '';
                }

                if($company->jobcoin < 10){
                    $this->replyFailure('jobcoin is not enough');
                    return '';
                }

                $job = new \Job();
                $job->company_id = $company->id;
                $job->position = $position;
                $job->work_content = $work_content;
                $job->need_people = $need_people;
                $job->work_time = $work_time;
                $job->salary = $salary;
                $job->province = $province;
                $job->city = $city;
                $job->district = $district;
                $job->address = $address;
                $job->partner_id = $partner_id;
                $job->create_time = time();
                $job->last_time = time();
                if(!$job->save()){
                    $this->replyFailure('save is error');
                    return '';
                }
                else{
                    $company->jobcoin = $company->jobcoin - 10;
                    $company->save();

                    $result = new stdClass();
                    $result->job_id = $job->id;
                    $result->credit_id = $company->credit_id;
                    $result->jobcoin = $company->jobcoin;
                    $this->reply('success', 0, $result);
                }
                break;

            case 'list':
                $token = trim($this->request->get('token'));
                $partner_id = $this->checkTokenAndGetPartner($token);

                $page = $this->request->get('page', 'int');
                $pagenum = $this->request->get('pagenum');
                $page = $page ? $page : 1;
                $pagenum = $pagenum ? $pagenum : 10;

                $keyword = trim($this->request->get('keyword'));
                $city = trim($this->request->get('city'));
                $district = trim($this->request->get('district'));


                $where = "";
                if($keyword){
                    $where .= ($where ? 'and': '')." (j.position like :keyword: or c.name like :keyword:) ";
                    $bindArray['keyword'] = "%$keyword%";
                }
                if($city){
                    $where .= ($where ? 'and': '')." (j.city like :city:) ";
                    $bindArray['city'] = "%$city%";
                }
                if($district){
                    $where .= ($where ? 'and': '')." (j.district like :district:) ";
                    $bindArray['district'] = "%$district%";
                }
                $builder = $this->modelsManager->createBuilder()
                    ->addFrom('Job', 'j')
                    ->innerJoin('Company', 'c.id = j.company_id ', 'c')
                    ->columns('j.id as job_id, c.credit_id, c.name as company_name, j.position, j.work_content, j.need_people, j.work_time, j.salary, j.province, j.city, j.district, j.address')
                    ->where($where, $bindArray)
                    ->orderBy('j.id desc ');
                $querypage = $this->queryPage($builder, $page, $pagenum);
                $querypage = $querypage->items->toArray();

                $result = $querypage;
                $this->reply('success', 0, $result);
                break;
        }
    }

    public function applyAction($action){
        switch($action){
            case 'add':
                $token = trim($this->request->get('token'));
                $partner_id = $this->checkTokenAndGetPartner($token);

                $idcard = trim($this->request->get('idcard'));
                $job_id = trim($this->request->get('job_id'));

                if (!$idcard) {
                    $this->replyFailure('idcard is empty');
                    return '';
                }
                if(!trim($job_id)){
                    $this->replyFailure('job_id is empty');
                    return '';
                }

                $jobseeker = \Jobseeker::findFirstByIdcard($idcard);
                if (!$jobseeker) {
                    $this->replyFailure('no this idcard');
                    return '';
                }

                $job = \Job::findFirstById($job_id);
                if (!$job) {
                    $this->replyFailure('no this job');
                    return '';
                }

                $resume = \Resume::findFirstByJobseeker_id($jobseeker->id);
                if (!$resume) {
                    $this->replyFailure('no this idcard resume');
                    return '';
                }

                $apply =\Apply::findFirst([' jobseeker_id = ?1 and job_id = ?2 ', 'bind'=>[ 1=>$jobseeker->id, 2=>$job->id ] ]);
                if ($apply) {
                    $result = new stdClass();
                    $result->idcard = $jobseeker->idcard;
                    $this->reply('apply resume is exist', 0, $result);
                    return '';
                }
                if($jobseeker->jobcoin < 10){
                    $this->replyFailure('jobseeker jobcoin is not enough');
                    return '';
                }

                $apply = new \Apply();
                $apply->jobseeker_id = $jobseeker->id;
                $apply->job_id = $job->id;
                $apply->resume_id = $resume->id;
                $apply->enter = 'N';
                $apply->jobseeker_score = 0;
                $apply->company_score = 0;
                $apply->create_time = time();
                $apply->last_time = time();
                if(!$apply->save()){
                    $this->replyFailure('save is error');
                    return '';
                }
                else{
                    $jobseeker->jobcoin = $jobseeker->jobcoin - 10;
                    $jobseeker->save();

                    $result = new stdClass();
                    $result->idcard = $jobseeker->idcard;
                    $this->reply('success', 0, $result);
                }
                break;

            case 'get':
                $token = trim($this->request->get('token'));
                $partner_id = $this->checkTokenAndGetPartner($token);

                $job_id = trim($this->request->get('job_id'));
                if(!trim($job_id)){
                    $this->replyFailure('job_id is empty');
                    return '';
                }

                $job = \Job::findFirstById($job_id);
                if (!$job) {
                    $this->replyFailure('no this job');
                    return '';
                }
                $apply = \Apply::find(array( 'job_id = :job_id:', 'bind'=>array('job_id'=>$job->id), 'columns'=>'jobseeker_id, resume_id'));
                $result = $apply->toArray();
                foreach($result as $k => $v){
                    $jobseeker = \Jobseeker::findFirstById($v['jobseeker_id']);
                    $result[$k]['credit_id'] = $jobseeker->credit_id;
                    $result[$k]['name'] = $jobseeker->name;
                    $result[$k]['idcard'] = $jobseeker->idcard;
                    $result[$k]['phone'] = $jobseeker->phone;
                    unset($result[$k]['jobseeker_id']);

                    $resume = \Resume::findFirstById($v['resume_id']);
                    $exp = json_decode($resume->exp_attr, true);
                    $result[$k]['exp'] = $exp ? $exp : array();
                    unset($result[$k]['resume_id']);

                }
                $this->reply('success', 0, $result);
                break;

            case 'yes':
                $token = trim($this->request->get('token'));
                $partner_id = $this->checkTokenAndGetPartner($token);

                $idcard = trim($this->request->get('idcard'));
                $job_id = trim($this->request->get('job_id'));

                if (!$idcard) {
                    $this->replyFailure('idcard is empty');
                    return '';
                }
                if(!trim($job_id)){
                    $this->replyFailure('job_id is empty');
                    return '';
                }

                $jobseeker = \Jobseeker::findFirstByIdcard($idcard);
                if (!$jobseeker) {
                    $this->replyFailure('no this idcard');
                    return '';
                }

                $job = \Job::findFirstById($job_id);
                if (!$job) {
                    $this->replyFailure('no this job');
                    return '';
                }

                $apply =\Apply::findFirst([' jobseeker_id = ?1 and job_id = ?2 ', 'bind'=>[ 1=>$jobseeker->id, 2=>$job->id ] ]);
                if (!$apply) {
                    $this->replyFailure('no this apply');
                    return '';
                }
                $apply->enter = 'Y';
                if(!$apply->save()){
                    $this->replyFailure('save is error');
                    return '';
                }
                else{
                    $result = new stdClass();
                    $result->idcard = $jobseeker->idcard;
                    $this->reply('success', 0, $result);
                }
                break;

            case 'score':
                $token = trim($this->request->get('token'));
                $partner_id = $this->checkTokenAndGetPartner($token);

                $idcard = trim($this->request->get('idcard'));
                $job_id = trim($this->request->get('job_id'));
                $jobseeker_score = trim($this->request->get('jobseeker_score','int'));
                $company_score = trim($this->request->get('company_score','int'));

                if (!$idcard) {
                    $this->replyFailure('idcard is empty');
                    return '';
                }
                if(!trim($job_id)){
                    $this->replyFailure('job_id is empty');
                    return '';
                }

                $jobseeker = \Jobseeker::findFirstByIdcard($idcard);
                if (!$jobseeker) {
                    $this->replyFailure('no this idcard');
                    return '';
                }

                $job = \Job::findFirstById($job_id);
                if (!$job) {
                    $this->replyFailure('no this job');
                    return '';
                }

                $apply =\Apply::findFirst([' jobseeker_id = ?1 and job_id = ?2 and enter = ?3 ', 'bind'=>[ 1=>$jobseeker->id, 2=>$job->id, 3=>'Y' ] ]);
                if (!$apply) {
                    $this->replyFailure('this apply not enter');
                    return '';
                }
                if($jobseeker_score){
                    $apply->jobseeker_score = $jobseeker_score;
                }
                if($company_score){
                    $apply->company_score = $company_score;
                }

                if(!$apply->save()){
                    $this->replyFailure('save is error');
                    return '';
                }
                else{
                    $result = new stdClass();
                    $result->idcard = $jobseeker->idcard;
                    $this->reply('success', 0, $result);
                }
                break;
        }

    }
}
