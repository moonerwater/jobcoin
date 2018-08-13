<?php

class MjobController extends ControllerH5
{
    public function initialize()
    {
        parent::initialize();
    }

    public function indexAction() {
        $invitecode = $this->request->get('invitecode', 'string');
        $this->view->setVar('invitecode', $invitecode);
    }

    public function logoutAction() {
        $this->session->remove('userinfo');
        $this->response->redirect('mjob/index');
    }

    public function getphonecodeAction() {
        $this->view->disable();
        $mobile = $this->request->get('mobile', 'string');
        $tempSms = $this->cache->get('sms_'.date("Ymd").'_'.$mobile);
        $tempSms = $tempSms ? $tempSms : array();
        $tempSmsLast = $tempSms[count($tempSms)-1];
        if (!$this->isPhone($mobile)) {
            $this->replyFailure('手机号码格式不正确');
            return '';
        }
        elseif (count($tempSms) >= 3) {
            $this->replyFailure('相同手机号码一天内只能发送三次短信');
            return '';
        }
        elseif ($tempSmsLast) {
            if (time() - $tempSmsLast['time'] < 60) {
                $this->replyFailure('60秒后才能发送');
                return '';
            }
        }
        $code = rand(1000,9999);
        $sms = new \Sms();
        if (!$sms->sendSms($mobile, 'userSign', array('code' => $code))) {
            $this->replyFailure('发送失败');
            return '';
        }
        $tempSms[] = array('code' => $code, 'time' => time());
        $this->cache->save('sms_'.date("Ymd").'_'.$mobile, $tempSms);

        $user = \User::findFirstByPhone($mobile);
        if ($user) {
            $result = new stdClass();
            $result->user_type = 'old';
        }
        else{
            $result = new stdClass();
            $result->user_type = 'new';
        }

        $this->reply(true, 0, $result);
    }

    public function checkphonecodeAction() {
        $this->view->disable();
        $mobile = $this->request->get('mobile', 'string');
        $vcode = $this->request->get('code', 'alphanum');
        $code_user = $this->request->get('code_user');
        $tempSms = $this->cache->get('sms_'.date("Ymd").'_'.$mobile);
        $tempSms = $tempSms ? $tempSms : array();
        $tempSmsLast = $tempSms[count($tempSms)-1];
        if (!$this->isPhone($mobile)) {
            $this->replyFailure('手机号码格式不正确');
            return '';
        }

        if (!($tempSmsLast['code'] && $tempSmsLast['code'] == $vcode)) {
            $this->replyFailure('验证码错误');
            return '';
        }
        else {
            $user = \User::findFirstByPhone($mobile);
            if ($user) {
                $result = new stdClass();
                $result->user_type = 'old';

                $userid = $user->id;
            }
            else{
                if($code_user){
                    $usercode = \User::findFirst(" code_system = '$code_user'");
                    if(!$usercode){
                        $usercode = \User::findFirst(" phone = '$code_user'");
                    }
                }
                if($usercode){
                    $code_user = $usercode->code_system;
                }
                else{
                    $code_user = null;
                }

                $user = new \User();
                $user->score = 30;
                $user->jobcoin = 50;
                $user->code_system = $this->buildCode('user', 6);
                $user->phone = $mobile;
                $user->code_user = $code_user;
                $user->create_time = time();
                $user->last_time = time();
                $user->save();
                $userid = $user->id;

                $userscore = new \UserScore();
                $userscore->user_id = $user->id;
                $userscore->type = 'reg';
                $userscore->score = 30;
                $userscore->create_time = time();
                $userscore->last_time = time();
                $userscore->save();

                $userjobcoin = new \UserJobcoin();
                $userjobcoin->user_id = $user->id;
                $userjobcoin->type = 'reg';
                $userjobcoin->jobcoin = 50;
                $userjobcoin->create_time = time();
                $userjobcoin->last_time = time();
                $userjobcoin->save();

                $result = new stdClass();
                $result->user_type = 'new';
                $result->reg_score = 30;

                //邀请时的奖励
                if($code_user){
                    $user = \User::findFirst(" code_system = '$code_user'");
                    if($user){
                        //一级
                        $user->jobcoin += 20;
                        $user->candy += 10;
                        $user->save();

                        $userjobcoin = new \UserJobcoin();
                        $userjobcoin->user_id = $user->id;
                        $userjobcoin->type = 'regfor1';
                        $userjobcoin->jobcoin = 20;
                        $userjobcoin->create_time = time();
                        $userjobcoin->last_time = time();
                        $userjobcoin->save();

                        $usercandy = new \UserCandy();
                        $usercandy->user_id = $user->id;
                        $usercandy->type = 'regfor1';
                        $usercandy->candy = 10;
                        $usercandy->create_time = time();
                        $usercandy->last_time = time();
                        $usercandy->save();

                        //二级
                        $code_user = $user->code_user;
                        $user = \User::findFirst(" code_system = '$code_user'");
                        if($user){
                            $user->jobcoin += 10;
                            $user->candy += 5;
                            $user->save();

                            $userjobcoin = new \UserJobcoin();
                            $userjobcoin->user_id = $user->id;
                            $userjobcoin->type = 'regfor2';
                            $userjobcoin->jobcoin = 10;
                            $userjobcoin->create_time = time();
                            $userjobcoin->last_time = time();
                            $userjobcoin->save();

                            $usercandy = new \UserCandy();
                            $usercandy->user_id = $user->id;
                            $usercandy->type = 'regfor2';
                            $usercandy->candy = 5;
                            $usercandy->create_time = time();
                            $usercandy->last_time = time();
                            $usercandy->save();
                        }

                    }
                }

            }
            $userLogin = \User::findFirst(array(
                sprintf(" id = ".$userid),
                "columns" => "id, name"
            ));
            $this->session->set('userinfo', $userLogin->toArray());

            $this->reply('success', 0, $result);
        }
    }

    public function checktodayloginAction(){
        $this->checkNoUserGoLogin();
        $userid = $this->userinfo['id'];

        $user = \User::findFirstById($userid);

        $login_score = 0;
        if(date('Ymd') != date('Ymd', $user->last_time)){
            $login_score = 1;
            $user->score += 1;

            $userscore = new \UserScore();
            $userscore->user_id = $user->id;
            $userscore->type = 'login';
            $userscore->score = 1;
            $userscore->create_time = time();
            $userscore->last_time = time();
            $userscore->save();
        }
        $user->last_time = time();
        $user->save();


        $result = new stdClass();
        $result->login_score = $login_score;

        $this->reply('success', 0, $result);
    }

    public function getjobcoinnoAction() {
        $this->checkNoUserGoLogin();
        $userid = $this->userinfo['id'];
        $total_score = $this->getTotalScore();

        $canget = number_format(100*$this->userinfo['score']/$total_score, 2);
        $stime = strtotime('-3 days');
        $etime = time();
        $scorelist = \UserScoreList::query()->columns('create_time')->where(' user_id = '.$userid)->orderBy('create_time desc')->execute()->toArray();
        if($scorelist){
            if($scorelist[0]['create_time'] > $stime){
                $stime = $scorelist[0]['create_time'];
            }
        }
        if($this->userinfo['create_time'] > $stime){
            $stime = $this->userinfo['create_time'];
        }
        //echo $stime;

        //8小时放一次，每次放6个晶体，每8小时放出100个job币。
        for($mytime = $stime+3600*8; $mytime<=$etime; $mytime+=3600*8){
            //
            $money_total=($canget < 0.1) ? 0.1 : $canget;
            $personal_num=6;
            $min_money=0.01;
            $money_right=$money_total;
            $randMoney=[];
            for($i=1;$i<=$personal_num;$i++){
                if($i== $personal_num){
                    $money=$money_right;
                }else{
                    $max=$money_right*100 - ($personal_num - $i ) * $min_money *100;
                    $money= rand($min_money*100,$max) /100;
                    $money=sprintf("%.2f",$money);
                }
                $randMoney[]=$money;
                $money_right=$money_right - $money;
                $money_right=sprintf("%.2f",$money_right);
            }
            shuffle($randMoney);
            //
            foreach($randMoney as $k => $v){
                $userscorelist = new \UserScoreList();
                $userscorelist->user_id = $userid;
                $userscorelist->jobcoinno = uniqid().$this->randomString(4);
                $userscorelist->jobcoin = $v;
                $userscorelist->create_time = $mytime;
                $userscorelist->last_time = $mytime;
                $userscorelist->save();
            }
        }
        //
        $userscorelist = \UserScoreList::find(array( "user_id = :user_id: and is_get = 'N' and create_time >= ".strtotime('-3 days'), 'bind'=>array('user_id'=>$userid), 'columns'=>'jobcoinno, jobcoin'));
        $result = $userscorelist->toArray();
        $this->reply('success', 0, $result);
    }

    public function editjobcoinnoAction() {
        $this->checkNoUserGoLogin();
        $userid = $this->userinfo['id'];
        $jobcoinno = trim($this->request->get('jobcoinno'));

        $userscorelist =\UserScoreList::findFirst([" user_id = ?1 and jobcoinno = ?2 and is_get = 'N' ", 'bind'=>[ 1=>$userid, 2=>$jobcoinno ] ]);
        if (!$userscorelist) {
            $this->replyFailure('no this jobcoinno');
            return '';
        }
        $userscorelist->is_get = 'Y';
        $userscorelist->last_time = time();
        if(!$userscorelist->save()){
            $this->replyFailure('save is error');
            return '';
        }
        else{
            $user = \User::findFirstById($userid);
            $user->jobcoin += $userscorelist->jobcoin;
            $user->last_time = time();
            $user->save();


            $userjobcoin = new \UserJobcoin();
            $userjobcoin->user_id = $userid;
            $userjobcoin->type = 'mining';
            $userjobcoin->jobcoin = $userscorelist->jobcoin;
            $userjobcoin->create_time = time();
            $userjobcoin->last_time = time();
            $userjobcoin->save();

            //
            $result = new stdClass();
            $result->jobcoinno = $jobcoinno;
            $this->reply('success', 0, $result);
        }
    }

    public function addchsiuserAction() {
        $userid = $this->request->get('userid');
        //
        $username = $this->request->get('username', 'string');
        $password = $this->request->get('password', 'string');

        if (!$userid) {
            $this->replyFailure('没有用户id');
            return '';
        }

        if (!$username) {
            $this->replyFailure('没有用户名');
            return '';
        }

        if (!$password) {
            $this->replyFailure('没有密码');
            return '';
        }
        putenv("PHANTOMJS_EXECUTABLE=/usr/local/bin/phantomjs");//引入phantomjs
        exec("/usr/local/bin/casperjs /var/www/html/casperjs/chsi.js $username $password $userid");//此处我使用的都是绝对路径

        $result = new stdClass();
        $result->userid = $userid;

        $this->reply('success', 0, $result);

    }

    public function addchsicodeAction() {
        $this->checkNoUserGoLogin();
        $userid = $this->userinfo['id'];
        //
        $code = $this->request->get('code', 'string');
        if (!$code) {
            $this->replyFailure('没有验证码');
            return '';
        }
        $file_path = '/var/www/html/casperjs/chsi/result_'.$userid.'.txt';
        if(file_exists($file_path)){
            $str = file_get_contents($file_path);
            $str = str_replace("\r\n","<br />",$str);
            $str = json_decode($str);
            $str->code = $code;
            $strcode = json_encode($str, 320);

            $myfile = fopen($file_path, "w");
            fwrite($myfile, $strcode);
            fclose($myfile);

            $result = new stdClass();
            $result->code = $code;

            $this->reply('success', 0, $result);
        }
        else{
            $this->replyFailure('error');
            return '';
        }
    }

    public function getchsiresultAction() {
        $this->checkNoUserGoLogin();
        $userid = $this->userinfo['id'];
        //

        $file_path = '/var/www/html/casperjs/chsi/result_'.$userid.'.txt';
        if(file_exists($file_path)){
            $str = file_get_contents($file_path);
            $str = str_replace("\r\n","<br />",$str);
            $str = json_decode($str);
            //
            if($str->data_url) {
                $str->imgbase64 = $this->fileToBase64($str->data_url);
            }
            //
            $this->reply('success', 0, $str);

            if($str->status == 'login success'){
                $str->status = 'login success ed';
                $img_base64 = $str->imgbase64;
                unset($str->imgbase64);
                $strcode = json_encode($str, 320);

                $myfile = fopen($file_path, "w");
                fwrite($myfile, $strcode);
                fclose($myfile);

                //
                $user = \User::findFirstById($userid);
                $user->check_chsi = 'Y';
                $user->score += 40;
                $user->last_time = time();
                $user->save();

                $userscore = new \UserScore();
                $userscore->user_id = $user->id;
                $userscore->type = 'chsi';
                $userscore->score = 40;
                $userscore->create_time = time();
                $userscore->last_time = time();
                $userscore->save();

                $userdatachsi = new \UserDataChsi();
                $userdatachsi->user_id = $user->id;
                $userdatachsi->img_base64 = $img_base64;
                $userdatachsi->create_time = time();
                $userdatachsi->last_time = time();
                $userdatachsi->save();

            }
        }
        else{
            $this->replyFailure('error');
            return '';
        }

    }

    public function loginAction() {
        $this->checkUserGoMain();
        $invitecode = $this->request->get('invitecode', 'string');
        $this->view->setVar('invitecode', $invitecode);

        $backurl = $this->request->get('backurl', 'string');
        $this->view->setVar('backurl', $backurl);
    }

    public function mainAction() {
        $this->checkNoUserGoLogin();
        //
        //$this->view->disable();
        $userid = $this->userinfo['id'];

        //
        $data = array();
        $data['score'] = $this->userinfo['score'];
        //
        $user = \User::find(array('', 'order' => 'score desc, id asc'));
        foreach($user as $k => $v){
            if($v->id == $userid){
                $data['scorerank'] = $k+1;
            }
        }
        //
        $todaycount = \UserScoreList::find("is_get = 'Y' and last_time >= ".strtotime(date("Y-m-d")))->count();
        $totalcount = \UserScoreList::find("is_get = 'Y'")->count();
        $data['todaycount'] = $todaycount;
        $data['totalcount'] = $totalcount;
        //
        $data['time'] = date("Y-m-d H:i");
        //
        $user = \User::find(array('', 'order' => 'score desc, id asc', 'limit'=>10));
        $data['userlist'] = $user->toArray();
        foreach($data['userlist'] as $k => $v){
            $data['userlist'][$k]['phone'] = $this->getDisablePhone($v['phone']);
        }
        //
        $totaluser = \User::find('')->count();
        $data['totaluser'] = $totaluser;
        $data['totalvip'] = $data['totaluser'] <=1000 ? $data['totaluser'] : 1000;
        //
        $user = \User::find(array('', 'order' => 'id asc'));
        foreach($user as $k => $v){
            if($v->id == $userid){
                $data['userrank'] = $k+1;
            }
        }
        //
        $data['username'] = $this->userinfo['name'];
        $data['username'] = $data['username'] ? $data['username'] : $this->userinfo['phone'];

        //print_r($data);
        $this->view->setVar('data', $data);
    }

    public function assetsAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        //
        $data = array();
        $data['jobcoin'] = $this->userinfo['jobcoin'];
        //
        $this->view->setVar('data', $data);
    }

    public function iaeAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        //
        $userjobcoin = \UserJobcoin::find(array('user_id ='.$userid, 'order' => 'create_time desc'));
        $userjobcoin = $userjobcoin->toArray();
        foreach($userjobcoin as $k => $v){
            $userjobcoin[$k]['time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        $data['userjobcoin'] = $userjobcoin;
        //
        $this->view->setVar('data', $data);
    }

    public function promoteAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        $data = array();
        $data['score'] = $this->userinfo['score'];
        //
        $user = \User::find(array('', 'order' => 'score desc, id asc'));
        foreach($user as $k => $v){
            if($v->id == $userid){
                $data['scorerank'] = $k+1;
            }
        }
        $data['user'] = $this->userinfo;
        //
        $this->view->setVar('data', $data);
    }

    public function strategyAction() {
        $this->checkNoUserGoLogin();
    }
    
    public function recordAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        $data = array();
        $data['score'] = $this->userinfo['score'];
        //
        $userscore = \UserScore::find(array('user_id ='.$userid, 'order' => 'create_time desc'));
        $userscore = $userscore->toArray();
        foreach($userscore as $k => $v){
            $userscore[$k]['time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        $data['userscore'] = $userscore;
        //
        $this->view->setVar('data', $data);
    }

    public function inviteAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        $data = array();
        $data['code_system'] = $this->userinfo['code_system'];
        //
        $user = \User::find("code_user = '".$data['code_system']."'");
        $user = $user->toArray();
        $data['count1'] = count($user);
        $data['count2'] = 0;
        foreach($user as $k => $v){
            $usercount = \User::find("code_user = '".$v['code_system']."'")->count();
            $data['count2'] += $usercount;
        }
        //
        $data['jobcoin'] = 0;
        $userjobcoin = \UserJobcoin::find(array('user_id ='.$userid." and (type = 'regfor1' or type = 'regfor2')", 'order' => 'create_time desc'));
        $userjobcoin = $userjobcoin->toArray();
        foreach($userjobcoin as $k => $v){
            $data['jobcoin'] += $v['jobcoin'];
        }

        $data['candy'] = 0;
        $usercandy = \UserCandy::find(array('user_id ='.$userid." and (type = 'regfor1' or type = 'regfor2')", 'order' => 'create_time desc'));
        $usercandy = $usercandy->toArray();
        foreach($usercandy as $k => $v){
            $data['candy'] += $v['candy'];
        }

        //
        $this->view->setVar('data', $data);
    }

    public function invitecardAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        $data = array();
        $data['code_system'] = $this->userinfo['code_system'];
        //
        $user = \User::find(array('', 'order' => 'id asc'));
        foreach($user as $k => $v){
            if($v->id == $userid){
                $data['userrank'] = $k+1;
            }
        }
        //
        $data['username'] = $this->userinfo['name'];
        $data['username'] = $data['username'] ? $data['username'] : $this->userinfo['phone'];

        //
        $this->view->setVar('data', $data);
    }

    public function chsiAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        if($this->userinfo['check_chsi'] == 'Y'){
            $this->response->redirect('mjob/index');
        }
    }

    public function settingAction() {
        $this->checkNoUserGoLogin();
    }

    public function identityAction() {
        $this->checkNoUserGoLogin();
    }

    public function checkidcardimgAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        $imgbase64 = $this->request->get('imgbase64', 'string');

        if (!$imgbase64) {
            $this->replyFailure('没有图像');
            return '';
        }

        $idcard = new \Idcard();
        $result = $idcard->sendIdcard($imgbase64);
        if($result['error_code'] == 0 && $result['result']['realname'] && $result['result']['idcard']){
            //
            $user = \User::findFirstById($userid);
            $user->check_idcard = 'Y';
            $user->name = $result['result']['realname'];
            $user->idcard = $result['result']['idcard'];
            $user->score += 30;
            $user->last_time = time();
            $user->save();

            $userscore = new \UserScore();
            $userscore->user_id = $user->id;
            $userscore->type = 'idcard';
            $userscore->score = 30;
            $userscore->create_time = time();
            $userscore->last_time = time();
            $userscore->save();

            $userdataidcard = new \UserDataIdcard();
            $userdataidcard->user_id = $user->id;
            $userdataidcard->attr = json_encode($result['result'], 320);
            $userdataidcard->create_time = time();
            $userdataidcard->last_time = time();
            $userdataidcard->save();

            $this->reply(true, 0, $result);

        }
        else{
            $this->replyFailure($result['reason']);
        }

    }

    /*public function checkidcardimgAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        $imgbase64 = $this->request->get('imgbase64', 'string');

        if (!$imgbase64) {
            $this->replyFailure('没有图像');
            return '';
        }

        $url = 'https://aip.baidubce.com/oauth/2.0/token';
        $post_data['grant_type']       = 'client_credentials';
        $post_data['client_id']      = 'iokoqaKX1nGWi7VSstR3RSMu';
        $post_data['client_secret'] = '3KckyUS1yVZrVUlQFx8DUchuDk31QnOI';
        $o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);

        $res = $this->request_post($url, $post_data);
        $result = json_decode($res,true);

        $token = $result['access_token'];
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/idcard?access_token=' . $token;
        $img = $imgbase64;
        $bodys = array(
            "detect_direction" => true,
            "id_card_side" => 'front',
            "detect_risk" => false,
            "image" => $img
        );
        $res = $this->request_post($url, $bodys);
        $result = json_decode($res,true);

        if($result['error_code'] == 0 && $result['words_result']['姓名']['words'] && $result['words_result']['公民身份号码']['words']){
            //
            $user = \User::findFirstById($userid);
            $user->check_idcard = 'Y';
            $user->name = $result['words_result']['姓名']['words'];
            $user->idcard = $result['words_result']['公民身份号码']['words'];
            $user->score += 30;
            $user->last_time = time();
            $user->save();

            $userscore = new \UserScore();
            $userscore->user_id = $user->id;
            $userscore->type = 'idcard';
            $userscore->score = 30;
            $userscore->create_time = time();
            $userscore->last_time = time();
            $userscore->save();

            $userdataidcard = new \UserDataIdcard();
            $userdataidcard->user_id = $user->id;
            $userdataidcard->attr = json_encode($result['words_result'], 320);
            $userdataidcard->create_time = time();
            $userdataidcard->last_time = time();
            $userdataidcard->save();

            $this->reply(true, 0, $result);

        }
        else{
            $this->replyFailure($result['error_msg']);
        }

    }*/

    public function checkfaceAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        $imgbase64 = $this->request->get('imgbase64', 'string');

        if (!$imgbase64) {
            $this->replyFailure('没有图像');
            return '';
        }

        $url = 'https://aip.baidubce.com/oauth/2.0/token';
        $post_data['grant_type']       = 'client_credentials';
        $post_data['client_id']      = 'Dru3IW8xggMHfhNiDwwQCELn';
        $post_data['client_secret'] = 'rYxCjoxEdfIcTuD9S4MHplt1EVHAiAw8';
        $o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);

        $res = $this->request_post($url, $post_data);
        $result = json_decode($res,true);

        $token = $result['access_token'];
        $url = 'https://aip.baidubce.com/rest/2.0/face/v3/detect?access_token=' . $token;
        $img = $imgbase64;
        $bodys = array(
            'image_type' => "BASE64",
            'image' => $img
        );
        $res = $this->request_post($url, $bodys);
        $result = json_decode($res,true);

        if($result['error_code'] == 0 && $result['result']){
            //
            $user = \User::findFirstById($userid);
            $user->check_face = 'Y';
            $user->score += 50;
            $user->last_time = time();
            $user->save();

            $userscore = new \UserScore();
            $userscore->user_id = $user->id;
            $userscore->type = 'face';
            $userscore->score = 50;
            $userscore->create_time = time();
            $userscore->last_time = time();
            $userscore->save();

            $userdataface = new \UserDataFace();
            $userdataface->user_id = $user->id;
            $userdataface->img_base64 = $img;
            $userdataface->create_time = time();
            $userdataface->last_time = time();
            $userdataface->save();

            $this->reply(true, 0, $result);
        }
        else{
            $this->replyFailure($result['error_msg']);
        }
    }

    public function faceverifyAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        if($this->userinfo['check_face'] == 'Y'){
            $this->response->redirect('mjob/index');
        }
    }

    public function followwechatAction()
    {
        
    }

    public function thirdpartyAction()
    {
        
    }

    public function warrantAction()
    {
        
    }

    public function dataplateAction()
    {
        
    }

    public function aboutusAction()
    {
        
    }

    public function buyorderAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];

        $list = \PayinList::findFirst(array('user_id = '.$userid, 'order' => 'id desc'));
        $this->view->setVar('data', $list);

    }

    public function buycoinAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];

        $paytype = $this->request->get('paytype');
        $name = $this->request->get('name');
        $bank_name = $this->request->get('bank_name');
        $bank_no = $this->request->get('bank_no');
        $money = $this->request->get('money');

        if (!$paytype) {
            $this->replyFailure('paytype none');
            return '';
        }
        if (!$name) {
            $this->replyFailure('name none');
            return '';
        }
        /*if (!$bank_name) {
            $this->replyFailure('bank_name none');
            return '';
        }
        if (!$bank_no) {
            $this->replyFailure('bank_no none');
            return '';
        }*/
        if (!$money) {
            $this->replyFailure('money none');
            return '';
        }

        if($paytype == 'bank'){
            $agentlist = \PayinAgent::find(array("can_bank = 'Y' ", 'order' => 'id asc'))->toArray();
        }
        elseif($paytype == 'alipay'){
            $agentlist = \PayinAgent::find(array("can_alipay = 'Y' ", 'order' => 'id asc'))->toArray();
        }
        elseif($paytype == 'weixin'){
            $agentlist = \PayinAgent::find(array("can_weixin = 'Y' ", 'order' => 'id asc'))->toArray();
        }
        else{
            $this->replyFailure('pay method error');
            return '';
        }

        $agent_id = rand(0,count($agentlist)-1);
        $list = new \PayinList();
        $list->user_id = $userid;
        $list->pay_method = $paytype;
        $list->agent_id = $agentlist[$agent_id]['id'];
        $list->money = $money;
        $list->coin = $money*10;
        $list->name = $name;
        $list->bank_name = $bank_name;
        $list->bank_no = $bank_no;
        $list->create_time = time();
        $list->last_time = time();
        $list->save();


        $this->reply('success', 0, $agentlist[$agent_id]);
    }

    public function ordersAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];

        $where = " user_id = $userid ";
        if($this->checkCanAdmin($userid)){
            $where = '';
        }

        $list = \PayinList::find(array($where, 'order' => 'id desc'))->toArray();
        foreach($list as $k => $v) {
            $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $agent = \PayinAgent::findFirstById($v['agent_id']);
            $list[$k]['agent_name'] = $agent->name;
        }
        //
        $this->view->setVar('data', $list);

    }

    public function orderstatAction($id) {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];

        $where = " user_id = $userid and id = $id";
        if($this->checkCanAdmin($userid)){
            $where = "id = $id";
        }
        $data['list'] = \PayinList::findFirst($where);
        $data['agent'] = \PayinAgent::findFirstById($data['list']->agent_id);
        //
        $this->view->setVar('data', $data);
        $this->view->setVar('canadmin', $this->checkCanAdmin($userid));
    }

    public function orderdoneAction($id) {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];

        $where = " user_id = $userid and id = $id";
        if($this->checkCanAdmin($userid)){
            $where = "id = $id";
        }
        $data['list'] = \PayinList::findFirst($where);
        $data['agent'] = \PayinAgent::findFirstById($data['list']->agent_id);
        //
        $this->view->setVar('data', $data);
    }

    public function orderfailedAction($id) {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];

        $where = " user_id = $userid and id = $id";
        if($this->checkCanAdmin($userid)){
            $where = "id = $id";
        }
        $data['list'] = \PayinList::findFirst($where);
        $data['agent'] = \PayinAgent::findFirstById($data['list']->agent_id);
        //
        $this->view->setVar('data', $data);
    }

    public function changeorderAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];

        if(!$this->checkCanAdmin($userid)){
            $this->replyFailure('no power');
            return '';
        }

        $id = $this->request->get('id', 'int');
        $type = $this->request->get('type');
        if (!$id) {
            $this->replyFailure('id none');
            return '';
        }
        if (!$type) {
            $this->replyFailure('type none');
            return '';
        }

        $list =  \PayinList::findFirst(" id = $id and pay_type = 'Wait' ");
        if(!$list){
            $this->replyFailure('list none');
            return '';
        }
        if($type == 'N'){
            $list->pay_type = 'N';
            $list->pay_time = time();
            $list->save();
        }
        elseif($type == 'Y'){
            $list->pay_type = 'Y';
            $list->pay_time = time();
            $list->save();

            //充值
            $list = $list->toArray();
            $user = \User::findFirstById($list['user_id']);
            $user->jobcoin += $list['coin'];
            $user->last_time = time();
            $user->save();
            $userjobcoin = new \UserJobcoin();
            $userjobcoin->user_id = $list['user_id'];
            $userjobcoin->type = 'payin';
            $userjobcoin->jobcoin = $list['coin'];
            $userjobcoin->create_time = time();
            $userjobcoin->last_time = time();
            $userjobcoin->save();
            //

        }
        else{
            $this->replyFailure('type error');
            return '';
        }

        $this->reply('success', 0, $result2);
    }


}
