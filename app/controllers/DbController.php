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
        $this->checkNoUserGoLogin();
        //
        $userid = $this->userinfo['id'];

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

    public function detailAction($id) {
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
        $product = \DbProduct::findFirstById($list['product_id']);
        $list['name'] = $product->name;
        $temp = explode(',', $product->imgs);
        $list['imgs2'][0] = $temp[count($temp)-1];
        $list['imgs2'][1] = $temp[0];
        $list['imgs'] = explode(',', $product->imgs);
        $list['percent'] = (number_format($list['already_num']/$list['need_num']*100, 2));
        $list['has_num'] = $list['need_num'] - $list['already_num'];
        if($list['is_end'] == 'Y'){
            $list['end_time'] = date('Y-m-d H:i:s', $list['create_time']);
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
        $data['userno'] = implode('、', $userno);
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
            $user = \User::findFirstById($v['user_id']);
            $listusercomment[$k]['phone'] = $this->getDisablePhone($user->phone);
            $listusercomment[$k]['username'] = $user->name;

        }
        $data['listusercomment'] = $listusercomment;
        //
        $this->view->setVar('data', $data);
    }

    public function paysuccessAction()
    {

    }

    public function luckynumAction()
    {

    }

    public function shareorderAction()
    {

    }
}
