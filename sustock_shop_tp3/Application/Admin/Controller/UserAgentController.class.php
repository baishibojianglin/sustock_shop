<?php
/**
 * Author: Yan
 * Date: 2020-11-16
 */

namespace Admin\Controller;

use Admin\Logic\UsersLogic;
use Think\AjaxPage;
use Think\Page;

class UserAgentController extends BaseController {

    public function index(){
        $this->display();
    }

    /**
     * 会员列表
     */
    public function ajaxindex(){
        // 搜索条件
        $condition = array();
        I('mobile') ? $condition['mobile'] = I('mobile') : false;
        I('email') ? $condition['email'] = I('email') : false;
        $condition['is_agent'] = 1;
        $sort_order = I('order_by').' '.I('sort');
               
        $model = M('users');
        $count = $model->where($condition)->count();
        $Page  = new AjaxPage($count,10);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        
        $userList = $model->where($condition)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
                
        $user_id_arr = get_arr_column($userList, 'user_id');
        if(!empty($user_id_arr))
        {
            /*$userAllList = $model->where($condition)->select();
            foreach ($userList as $key => $value) {
                //$userList[$key]['total_agent_leader'] = count(array_unique($this->getTree($userAllList, $value['first_agent_leader'])));
                $userList[$key]['total_agent_leader'] = $this->getTree($userAllList, $value['first_agent_leader']);
            }
            file_put_contents('./test3.txt', json_encode($userList));*/

            $first_agent_leader = M('users')->query("select first_agent_leader,count(1) as count  from __PREFIX__users where first_agent_leader in(".  implode(',', $user_id_arr).")  group by first_agent_leader");
            $first_agent_leader = convert_arr_key($first_agent_leader, 'first_agent_leader');

            $second_agent_leader = M('users')->query("select second_agent_leader,count(1) as count  from __PREFIX__users where second_agent_leader in(".  implode(',', $user_id_arr).")  group by second_agent_leader");
            $second_agent_leader = convert_arr_key($second_agent_leader,'second_agent_leader');
        }
        $this->assign('first_agent_leader',$first_agent_leader);
        $this->assign('second_agent_leader',$second_agent_leader);
                                
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('level',M('user_level')->getField('level_id,level_name'));
        $this->display();
    }

    /**
     * PHP递归获取所有下级：无层次，仅返回id
     * @param $data
     * @param int $parent_id
     * @param bool|true $is_first_time
     * @return array
     */
    public function getTree($data, $parent_id = 0, $is_first_time = true)
    {
        //static $arr = [];
        $arr = [];
        if ($is_first_time) {
            $arr = [];
        }
        foreach ($data as $key => $val) {
            if ($val['first_agent_leader'] == $parent_id) {
                $arr[] = $val['user_id'];
                $this->getTree($data, $val['user_id'], false);
            }
        }
        return $arr;
    }
}