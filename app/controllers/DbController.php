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
        $data['list'] = $list;

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
