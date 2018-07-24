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

    public function detailAction()
    {

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
