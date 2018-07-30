<?php

/**
 * DbController
 *
 * Manage errors
 */
class DbController extends ControllerH5
{   
    public function initialize()
    {
        parent::initialize();
    }
    
    public function indexAction() {
        //$this->checkNoUserGoLogin();
        //
        //$userid = $this->userinfo['id'];

        //
        $list = \DbList::find(array('', 'order' => 'id desc'));
        $list = $list->toArray();
        foreach($list as $k => $v){
            $product = \DbProduct::findFirstById($v['product_id']);
            $list[$k]['name'] = $product->name;
            $list[$k]['imgs'] = explode(',', $product->imgs);
            $list[$k]['percent'] = (number_format($v['already_num']/$v['need_num']*100, 2));
        }
        $data['list'] = $list;
        //
        $this->view->setVar('data', $data);
    }

    public function buycountAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        $this->view->disable();

        $list_id = $this->request->get('list_id', 'int');
        $jobcoin = $this->request->get('jobcoin', 'int');
        if (!$list_id) {
            $this->replyFailure('list_id none');
            return '';
        }
        if (!$jobcoin) {
            $this->replyFailure('jobcoin none');
            return '';
        }
        if($jobcoin%50 != 0){
            $this->replyFailure('jobcoin error');
            return '';
        }
        $list = \DbList::findFirstById($list_id);
        if(!$list){
            $this->replyFailure('list_id error');
            return '';
        }
        $list = $list->toArray();
        $has_num = $list['need_num'] - $list['already_num'];
        $count = $jobcoin/50;
        if($has_num - $count < 0){
            $this->replyFailure('count max error');
            return '';
        }
        $user = \User::findFirstById($userid);
        if($user->jobcoin < $jobcoin){
            $this->replyFailure('user joincoin max error');
            return '';
        }
        //
        $list = \DbList::findFirstById($list_id);
        $list->already_num += $count;
        $list->last_time = time();
        $list->save();
        $listuser = new \DbListUser();
        $listuser->list_id = $list_id;
        $listuser->user_id = $userid;
        $listuser->count = $count;
        $listuser->coin_type = 'jobcoin';
        $listuser->num = $jobcoin;
        $listuser->ip = $this->getIp();
        $listuser->create_time = time();
        $listuser->last_time = time();
        $listuser->save();
        for($i =1; $i<=$count; $i++){
            $listuserdetail = \DbListUserDetail::find(array('list_id = '.$list_id, 'order' => 'id desc'));
            if(!$listuserdetail->toArray()){
                $no = 0;
            }
            else{
                $listuserdetail = $listuserdetail->toArray();
                $no = $listuserdetail[0]['no']+1;
            }
            $listuserdetail = new \DbListUserDetail();
            $listuserdetail->list_id = $list_id;
            $listuserdetail->user_id = $userid;
            $listuserdetail->no = $no;
            $listuserdetail->create_time = time();
            $listuserdetail->last_time = time();
            $listuserdetail->save();
        }
        //
        $user = \User::findFirstById($userid);
        $user->jobcoin -= $jobcoin;
        $user->last_time = time();
        $user->save();
        $userjobcoin = new \UserJobcoin();
        $userjobcoin->user_id = $user->id;
        $userjobcoin->type = 'coinget';
        $userjobcoin->jobcoin = $jobcoin;
        $userjobcoin->create_time = time();
        $userjobcoin->last_time = time();
        $userjobcoin->save();
        //
        $list = \DbList::findFirstById($list_id);
        if($list->need_num == $list->already_num){
            $url = 'https://api-prd.eosflare.io/chain/get_info';
            $post_data['grant_type']       = 'client_credentials';
            $o = "";
            foreach ( $post_data as $k => $v )
            {
                $o.= "$k=" . urlencode( $v ). "&" ;
            }
            $post_data = substr($o,0,-1);
            $res = $this->request_post($url, $post_data);
            $result = json_decode($res,true);
            $block_num = $result['result']['blocks'];
            $block_num = $block_num?$block_num:5500500;
            $block_num2 = $block_num+50;
            $md5 = substr(md5($block_num2),25,7);
            $temp10 = hexdec($md5);
            $lucky_num = $temp10 % $list->already_num;


            $list->is_end = 'Y';
            $list->end_time = time();
            $list->block_num = $block_num;
            $list->lucky_num = $lucky_num;
            $list->win_user_id = 0;

            $listuserdetail = \DbListUserDetail::findFirst(" list_id = $list_id and no = $lucky_num");
            if($listuserdetail){
                $list->win_user_id = $listuserdetail->user_id;

                //
                $sysnotice = new \DbSysNotice();
                $sysnotice->type = 'coingetwin';
                $sysnotice->user_id = $listuserdetail->user_id;
                $sysnotice->content = '尊敬的用户，你是'.$list->no.'期的中奖者，请安心等待客服联系你。';
                $sysnotice->url = '/db/detail/'.$list->id;
                $sysnotice->create_time = time();
                $sysnotice->last_time = time();
                $sysnotice->save();
            }
            $list->last_time = time();
            $list->save();

            //挖矿分红奖励
            $listuser = $this->db->fetchAll("SELECT user_id,sum(num) as num FROM db_list_user WHERE list_id= $list_id GROUP by user_id");
            foreach($listuser as $k => $v){
                $user = \User::findFirstById($v['user_id']);
                $user->jobcoin += $v['num']*0.1;
                $user->last_time = time();
                $user->save();
                $userjobcoin = new \UserJobcoin();
                $userjobcoin->user_id = $user->id;
                $userjobcoin->type = 'coinmining';
                $userjobcoin->jobcoin = $v['num']*0.1;
                $userjobcoin->create_time = time();
                $userjobcoin->last_time = time();
                $userjobcoin->save();
            }
        }
        //邀请时的奖励
        $user = \User::findFirstById($userid);
        $code_user = $user->code_user;
        if($code_user){
            $user = \User::findFirst(" code_system = '$code_user'");
            if($user){
                //一级
                $user->jobcoin += $jobcoin*0.1;
                $user->save();

                $userjobcoin = new \UserJobcoin();
                $userjobcoin->user_id = $user->id;
                $userjobcoin->type = 'coingetfor1';
                $userjobcoin->jobcoin = $jobcoin*0.1;
                $userjobcoin->create_time = time();
                $userjobcoin->last_time = time();
                $userjobcoin->save();

                //二级
                $code_user = $user->code_user;
                $user = \User::findFirst(" code_system = '$code_user'");
                if($user){
                    $user->jobcoin += $jobcoin*0.05;
                    $user->save();

                    $userjobcoin = new \UserJobcoin();
                    $userjobcoin->user_id = $user->id;
                    $userjobcoin->type = 'coingetfor2';
                    $userjobcoin->jobcoin = $jobcoin*0.05;
                    $userjobcoin->create_time = time();
                    $userjobcoin->last_time = time();
                    $userjobcoin->save();
                }
            }
        }
        //

        $this->reply('success', 0, $result2);

    }

    public function detailAction($id) {
        //$this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
        $userid = $userid?$userid:0;

        //
        $list = \DbList::findFirstById($id);
        if(!$list){
            $this->replyFailure('id error');
            return '';
        }
        $list = $list->toArray();
        $product = \DbProduct::findFirstById($list['product_id']);
        $list['name'] = $product->name;
        $temp = explode(',', $product->imgs);
        $list['imgs2'][0] = $temp[count($temp)-1];
        $list['imgs2'][1] = $temp[0];
        $list['imgs'] = explode(',', $product->imgs);
        $list['percent'] = (number_format($list['already_num']/$list['need_num']*100, 2));
        $list['has_num'] = $list['need_num'] - $list['already_num'];
        if($list['is_end'] == 'Y'){
            $list['end_time'] = date('Y-m-d H:i:s', $list['end_time']);
            $user = \User::findFirstById($list['win_user_id']);
            $list['phone'] = $this->getDisablePhone($user->phone);
            $list['username'] = $user->name;
        }
        $user = \User::findFirstById($userid);
        $list['jobcoin'] = $user->jobcoin;
        $data['list'] = $list;
        //
        $listuserdetail = \DbListUserDetail::find(array('user_id = '.$userid.' and list_id = '.$id, 'order' => 'id asc'));
        foreach($listuserdetail as $k => $v){
            $userno[] = $v->no;
        }
        $data['userno'] = implode(',', $userno);
        $data['listusertotal'] = count($listuserdetail);
        $listuser = \DbListUser::find(array('list_id = '.$id, 'order' => 'id desc'));
        $listuser = $listuser->toArray();
        foreach($listuser as $k => $v){
            $listuser[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $listuser[$k]['ip'] = $this->getDisableIp($v['ip']);
            $user = \User::findFirstById($v['user_id']);
            $listuser[$k]['phone'] = $this->getDisablePhone($user->phone);
            $listuser[$k]['username'] = $user->name;
        }
        $data['listuser'] = $listuser;
        //
        $listusercomment = \DbListUserComment::find(array('product_id = '.$list['product_id'], 'order' => 'id desc'));
        $listusercomment = $listusercomment->toArray();
        foreach($listusercomment as $k => $v){
            $listusercomment[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $listusercomment[$k]['comment'] = nl2br($v['comment']);
            $listusercomment[$k]['imgs'] = explode(',', $v['imgs']);
            if(!$listusercomment[$k]['imgs'][0]){
                $listusercomment[$k]['imgs'] = array();
            }
            $user = \User::findFirstById($v['user_id']);
            $listusercomment[$k]['phone'] = $this->getDisablePhone($user->phone);
            $listusercomment[$k]['username'] = $user->name;
            $product = \DbProduct::findFirstById($v['product_id']);
            $listusercomment[$k]['pname'] = $product->name;

        }
        $data['listusercomment'] = $listusercomment;
        //
        $this->view->setVar('data', $data);
    }

    public function luckynumAction($id) {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];

        //
        $list = \DbList::findFirstById($id);
        if(!$list){
            $this->replyFailure('id error');
            return '';
        }
        $list = $list->toArray();
        $list['end_time'] = date('Y-m-d H:i:s', $list['end_time']);
        $list['block_num2'] = $list['block_num']+50;
        $list['md5'] = substr(md5($list['block_num2']),25,7);
        $list['temp10'] = hexdec($list['md5']);
        $list['lucky_num'] = $list['temp10'] % $list['already_num'];
        $this->view->setVar('data', $list);
    }

    public function shareorderAction() {
        //
        $listusercomment = \DbListUserComment::find(array('', 'order' => 'id desc'));
        $listusercomment = $listusercomment->toArray();
        foreach($listusercomment as $k => $v){
            $listusercomment[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $listusercomment[$k]['comment'] = nl2br($v['comment']);
            $listusercomment[$k]['imgs'] = explode(',', $v['imgs']);
            if(!$listusercomment[$k]['imgs'][0]){
                $listusercomment[$k]['imgs'] = array();
            }
            $user = \User::findFirstById($v['user_id']);
            $listusercomment[$k]['phone'] = $this->getDisablePhone($user->phone);
            $listusercomment[$k]['username'] = $user->name;
            $product = \DbProduct::findFirstById($v['product_id']);
            $listusercomment[$k]['pname'] = $product->name;

        }
        $data['listusercomment'] = $listusercomment;
        //
        $this->view->setVar('data', $data);
    }

    public function paysuccessAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];
    }

    public function messageAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];

        $sysnotice = \DbSysNotice::find(array('user_id = '.$userid, 'order' => 'id desc'));
        $sysnotice = $sysnotice->toArray();
        foreach($sysnotice as $k => $v){
            $sysnotice[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);

        }
        $data['sysnotice'] = $sysnotice;
        //
        $this->view->setVar('data', $data);
    }

    public function joinrecordAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];

        $listuser = $this->db->fetchAll("SELECT list_id,user_id,sum(count) as count FROM db_list_user WHERE user_id = $userid GROUP by list_id ORDER by list_id DESC ");
        foreach($listuser as $k => $v){
            $list = \DbList::findFirstById($v['list_id']);
            $list = $list->toArray();
            $listuser[$k]['no'] = $list['no'];
            $listuser[$k]['is_end'] = $list['is_end'];
            $listuser[$k]['is_win'] = 'N';
            if($list['win_user_id'] == $userid){
                $listuser[$k]['is_win'] = 'Y';
            }
            $listuser[$k]['is_comment'] = 'N';
            $listusercomment = \DbListUserComment::findFirst(" list_id =  ".$v['list_id']." and user_id = $userid ");
            if($listusercomment) {
                $listuser[$k]['is_comment'] = 'Y';
            }
            $listuser[$k]['percent'] = (number_format($list['already_num']/$list['need_num']*100, 2));

            $product = \DbProduct::findFirstById($list['product_id']);
            $listuser[$k]['pname'] = $product->name;
            $listuser[$k]['imgs'] = explode(',', $product->imgs);
        }
        $data['listuser'] = $listuser;
        //
        $this->view->setVar('data', $data);
    }

    public function commentAction($id) {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];

        $list = \DbList::findFirst(" win_user_id = $userid and id = $id ");
        if(!$list){
            $this->replyFailure('id error');
            return '';
        }
        $list = $list->toArray();
        $product = \DbProduct::findFirstById($list['product_id']);
        $list['pname'] = $product->name;
        $list['imgs'] = explode(',', $product->imgs);
        //
        $this->view->setVar('data', $list);
    }

    public function commentaddAction() {
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];

        $list_id = $this->request->get('list_id', 'int');
        $comment = $this->request->get('comment');
        $base64 = $this->request->get('base64');
        if (!$list_id) {
            $this->replyFailure('list_id none');
            return '';
        }
        if (!$comment) {
            $this->replyFailure('comment none');
            return '';
        }
        $list = \DbList::findFirst(" win_user_id = $userid and id = $list_id ");
        if(!$list){
            $this->replyFailure('list_id error');
            return '';
        }
        $list = $list->toArray();

        $listusercomment = \DbListUserComment::findFirst(" list_id = $list_id ");
        if($listusercomment){
            $this->replyFailure('list_id commented');
            return '';
        }

        $listusercomment = new DbListUserComment();
        $listusercomment->list_id = $list_id;
        $listusercomment->product_id = $list['product_id'];
        $listusercomment->user_id = $userid;
        $listusercomment->comment = $comment;
        $imgs = array();
        if($base64){
            foreach($base64 as $k => $v){
                $file = $this->base64_image_content($v,'/upload/dbcomment');
                if($file){
                    $imgs[] = $file;
                }
            }
        }
        $listusercomment->imgs = implode(',',$imgs);
        $listusercomment->create_time = time();
        $listusercomment->last_time = time();
        $listusercomment->save();
        //晒单奖励
        $user = \User::findFirstById($userid);
        $user->jobcoin += 10;
        $user->last_time = time();
        $user->save();
        $userjobcoin = new \UserJobcoin();
        $userjobcoin->user_id = $user->id;
        $userjobcoin->type = 'comment';
        $userjobcoin->jobcoin = 10;
        $userjobcoin->create_time = time();
        $userjobcoin->last_time = time();
        $userjobcoin->save();
        //

        $this->reply('success', 0, $result2);
    }

    public function strategyAction()
    {

    }



}
