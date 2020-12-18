<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
 * ============================================================================
 * Author: IT宇宙人      
 * 
 * Date: 2016-03-09
 */

namespace Admin\Controller;
use Think\Page;

class FinanceController extends BaseController {
    
    /*
     * 初始化操作
     */
    public function _initialize() {
       parent::_initialize();
    }    
 
    /**
     *  店家转账汇款记录
     */
    public function store_remittance(){
        $model = M("");
        $store_id = I('store_id');
        $account_bank = I('account_bank');
        $account_name = I('account_name');
        
        $create_time = I('create_time');
        $create_time = str_replace("+"," ",$create_time);
        $create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time3 = explode(' - ',$create_time2);
        $where['sr.create_time'] =  array('between',array(strtotime($create_time3[0]),strtotime($create_time3[1])));
        $store_id && $where['s.store_id'] = $store_id;
        $account_bank && $where['sr.account_bank'] = array('like','%'.$account_bank.'%');
        $account_name && $where['sr.account_name'] = array('like','%'.$account_name.'%');
                        
        $count = $model->table(C('DB_PREFIX').'store_remittance sr')->join('INNER JOIN __STORE__ s ON s.store_id = sr.store_id')->where($where)->count();
        $Page  = new Page($count,10);
        $list = $model->table(C('DB_PREFIX').'store_remittance sr')->join('INNER JOIN __STORE__ s ON s.store_id = sr.store_id')->where($where)->order("sr.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $this->assign('create_time',$create_time2);
        $show  = $Page->show();                 
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();    
    }
    /**
     *  转账汇款记录
     */
    public function remittance(){
        $model = M("");
        $user_id = I('user_id');
        $account_bank = I('account_bank');
        $account_name = I('account_name');
        $create_time = I('create_time');
        $create_time = str_replace("+"," ",$create_time);
        $create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time3 = explode(' - ',$create_time2);
        $where['w.create_time'] =  array('between',array(strtotime($create_time3[0]),strtotime($create_time3[1])));
        $user_id && $where['u.user_id'] = $user_id;
        $account_bank && $where['w.account_bank'] = array('like','%'.$account_bank.'%');
        $account_name && $where['w.account_name'] = array('like','%'.$account_name.'%');
                        
        $count = $model->table(C('DB_PREFIX').'withdrawals w')->join('INNER JOIN __USERS__ u ON u.user_id = w.user_id')->where($where)->count();
        $Page  = new Page($count,16);        
        $list = $model->table(C('DB_PREFIX').'withdrawals w')->join('INNER JOIN __USERS__ u ON u.user_id = w.user_id')->where($where)->order("w.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('create_time',$create_time2);
        $show  = $Page->show();                 
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();    
    }

    /*
     * 订单提成记录
     * */
    public function commission(){
        $model = M("");
        $nickname = I('nickname');
        $create_time = I('change_time');
        $create_time = str_replace("+"," ",$create_time);
        $create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time3 = explode(' - ',$create_time2);
        $where['change_time'] =  array('between',array(strtotime($create_time3[0]),strtotime($create_time3[1])));
        $nickname && $where['nickname'] = array("LIKE", '%' . trim($nickname) . '%');
        I('is_agent') != '' ? $where['a.is_agent'] = I('is_agent') : false;

        if(I('method') == 'select' || I('method') == ""){//按条件查询
            $count = $model->table(C('DB_PREFIX').'account_log a')->join('INNER JOIN __USERS__ u ON u.user_id = a.user_id')->where(array('is_commission'=>1))->where($where)->count();
            $Page  = new Page($count,10);
            $list = $model->table(C('DB_PREFIX').'account_log a')->field('log_id,a.user_id,nickname,a.user_money,order_sn,change_time,desc')->join('INNER JOIN __USERS__ u ON u.user_id = a.user_id')->where(array('is_commission'=>1))->where($where)->order("a.log_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
            $this->assign('create_time',$create_time2);
            $show  = $Page->show();

            //提成总和查询
            $sql = "select SUM(user_money) as commission from ty_account_log WHERE is_commission = 1 and is_agent = 0";
            $commission = M()->query($sql);
            $sql1 = "select SUM(user_money) as agentcommission from ty_account_log WHERE is_commission = 1 and is_agent = 1";
            $agentcommission = M()->query($sql1);

            $this->assign('show',$show);
            $this->assign('list',$list);
            $this->assign('nickname',$nickname);
            $this->assign('amount',number_format($commission[0]['commission']+$agentcommission[0]['agentcommission'],2));
            $this->assign('commission',$commission[0]['commission']);
            $this->assign('agentcommission',$agentcommission[0]['agentcommission']);
            C('TOKEN_ON',false);
            $this->display();
        }
        if(I('method') == 'export'){
            /*导出表*/

            //搜索条件
            $where = 'where 1=1 AND is_commission = 1 ';

            if($nickname){
                $where .= "AND nickname like '%$nickname%' ";
            }
            if($create_time){
                $where .= "AND change_time BETWEEN ".strtotime($create_time3[0])." AND ".strtotime($create_time3[1]);
            }
            if(I('is_agent')){
                $where .= "AND a.is_agent = ".I('is_agent');
            }

            $sql = "select log_id,a.user_id,nickname,a.user_money,order_sn,change_time,a.desc from ty_account_log a INNER JOIN ty_users u ON a.user_id = u.user_id $where ";
            $ordercommission = D()->query($sql);
            $strTable ='<table width="500" border="1">';
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">记录编号</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">用户昵称</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">提成金额</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单编号</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">操作时间</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">备注</td>';
            $strTable .= '</tr>';

            foreach($ordercommission as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['log_id'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nickname'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['user_money'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">&nbsp;'.$val['order_sn'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.date("Y-m-d H:i",$val['change_time']).'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['desc'].'</td>';
                $strTable .= '</tr>';
            }
            $strTable .='</table>';
            unset($ordercommission);
            downloadExcel($strTable,'ordercommission');
            exit();
        }

    }

    /*
     * 导出表
     * */
    /*
     * 订单商品导出
     * */
    public function export_commission()
    {
        //搜索条件
        $where = 'where 1=1 AND is_commission = 1 ';
        $nickname = I('nickname');
        if($nickname){
            $where .= "AND goods_name like '%$nickname%' ";
        }
        $create_time = I('change_time');
        $create_time = str_replace("+"," ",$create_time);
        $create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time3 = explode(' - ',$create_time2);
        if($create_time){
            $where .= "AND change_time BETWEEN ".strtotime($create_time3[0])." AND ".strtotime($create_time3[1]);
        }
        if(I('is_agent')){
            $where .= "AND a.is_agent = ".I('is_agent');
        }

        $sql = "select log_id,a.user_id,nickname,a.user_money,order_sn,change_time,a.desc from ty_account_log a INNER JOIN ty_users u ON a.user_id = u.user_id $where ";
        $ordercommission = D()->query($sql);
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">记录编号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">用户昵称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">提成金额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单编号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">操作时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">备注</td>';
        $strTable .= '</tr>';

        foreach($ordercommission as $k=>$val){
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['log_id'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nickname'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['user_money'].' </td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['order_sn'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.date("Y-m-d H:i",$val['change_time']).'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['desc'].'</td>';
            $strTable .= '</tr>';
        }
        $strTable .='</table>';
        unset($ordercommission);
        downloadExcel($strTable,'ordercommission');
        exit();
    }
    /**
     * 提现申请记录
     */
    public function withdrawals()
    {
        $model = M('');
        $status = I('status');
        $user_id = I('user_id');
        $account_bank = I('account_bank');
        $account_name = I('account_name');
        $create_time = I('create_time');
        $create_time = str_replace("+"," ",$create_time);
        $create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time3 = explode(' - ',$create_time2);

        $where['w.create_time'] =  array('between',array(strtotime($create_time3[0]),strtotime($create_time3[1])));
        if($status === '0' || $status > 0)
            $where['w.status'] = $status;
        $user_id && $where['u.user_id'] = $user_id;
        $account_bank && $where['w.account_bank'] = array('like','%'.$account_bank.'%');
        $account_name && $where['w.account_name'] = array('like','%'.$account_name.'%');

        $count = $model->table(C('DB_PREFIX').'withdrawals w')->join('INNER JOIN __USERS__ u ON u.user_id = w.user_id')->where($where)->count();
        $Page  = new Page($count,10);
        $list = $model->table(C('DB_PREFIX').'withdrawals w')->join('INNER JOIN __USERS__ u ON u.user_id = w.user_id')->where($where)->order("w.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('create_time',$create_time2);
        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();
    }
    /**
     * 商家提现申请记录
     */
    public function store_withdrawals()
    {
        $model = M("store_withdrawals");
        $store_id = I('store_id');
        $account_bank = I('account_bank');
        $account_name = I('account_name');
        $create_time = I('create_time');

        $create_time = str_replace("+"," ",$create_time);
        $create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time3 = explode(' - ',$create_time2);
        $where['sw.create_time'] =  array('between',array(strtotime($create_time3[0]),strtotime($create_time3[1])));
        $store_id && $where['s.store_id'] = $store_id;
        $account_bank && $where['sw.account_bank'] = array('like','%'.$account_bank.'%');
        $account_name && $where['sw.account_name'] = array('like','%'.$account_name.'%');

        $count = $model->table(C('DB_PREFIX').'store_withdrawals sw')->join('INNER JOIN __STORE__ s ON s.store_id = sw.store_id')->where($where)->count();
        $Page  = new Page($count,4);
        $list = $model->table(C('DB_PREFIX').'store_withdrawals sw')->join('INNER JOIN __STORE__ s ON s.store_id = sw.store_id')->where($where)->order("`id` desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('create_time',$create_time2);
        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();
    }
    
    /**
     * 删除申请记录
     */
    public function delStoreWithdrawals()
    {                        
        $model = M("store_withdrawals"); 
        $model->where('id ='.$_GET['id'])->delete(); 
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
        $this->ajaxReturn(json_encode($return_arr));
    }    
        
    /**
     * 修改编辑商家 申请提现
     */
    public  function editStoreWithdrawals(){        
        $id = I('id');
        $model = M("store_withdrawals");
        $withdrawals = $model->find($id);
        $store = M('store')->where("store_id = {$withdrawals[store_id]}")->find();
        
        if(IS_POST)
        {
                $model->create();
                
                // 如果是已经给店家转账 则生成转账流水记录
                if($model->status == 1 && $withdrawals['status'] != 1)
                {                
                    if($store['store_money'] < $withdrawals['money'])
                    {
                        $this->error("店家余额不足{$withdrawals['money']}，不够提现");    
                        exit;
                    }
                    storeAccountLog($withdrawals['store_id'], ($withdrawals['money'] * -1),0,$desc = '平台提现');
                    $remittance = array(
                        'store_id' => $withdrawals['store_id'],
                        'bank_name' => $withdrawals['bank_name'],
                        'account_bank' => $withdrawals['account_bank'],
                        'account_name' => $withdrawals['account_name'],
                        'money' => $withdrawals['money'],
                        'status' => 1,
                        'create_time' => time(),
                        'admin_id' => session('admin_id'),
                        'withdrawals_id' => $withdrawals['id'],
                        'remark'=>$model->remark,
                    );
                    M('store_remittance')->add($remittance);
                }                
                $model->save();                               
                $this->success("操作成功!",U('store_remittance'),3);
                exit;
        }              
       $this->assign('store',$store);
       $this->assign('data',$withdrawals);
       $this->display();           
    }      

    /**
     * 删除申请记录
     */
    public function delWithdrawals()
    {                        
        $model = M("withdrawals"); 
        $model->where('id ='.$_GET['id'])->delete(); 
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
        $this->ajaxReturn(json_encode($return_arr));
    } 
    
    /**
     * 修改编辑 申请提现
     */
    public  function editWithdrawals(){        
        $id = I('id');
        $model = M("withdrawals");
        $withdrawals = $model->find($id);
        $user = M('users')->where("user_id = {$withdrawals[user_id]}")->find();
        
        if(IS_POST)
        {
                $model->create();
                
                // 如果是已经给用户转账 则生成转账流水记录
                if($model->status == 1 && $withdrawals['status'] != 1)
                {                
                    if($user['user_money'] < $withdrawals['money'])
                    {
                        $this->error("用户余额不足{$withdrawals['money']}，不够提现");    
                        exit;
                    }                                       
                    accountLog($withdrawals['user_id'], ($withdrawals['money'] * -1), 0,"平台提现");
                    $remittance = array(
                        'user_id' => $withdrawals['user_id'],
                        'bank_name' => $withdrawals['bank_name'],
                        'account_bank' => $withdrawals['account_bank'],
                        'account_name' => $withdrawals['account_name'],
                        'money' => $withdrawals['money'],
                        'status' => 1,
                        'create_time' => time(),
                        'admin_id' => session('admin_id'),
                        'withdrawals_id' => $withdrawals['id'],
                        'remark'=>$model->remark,
                    );
                    M('remittance')->add($remittance);
                }                
                $model->save();                               
                $this->success("操作成功!",U('remittance'),3);
                exit;
        }       
       if($user['nickname'])        
           $withdrawals['user_name'] = $user['nickname'];
       elseif($user['email'])        
           $withdrawals['user_name'] = $user['email'];
       elseif($user['mobile'])        
           $withdrawals['user_name'] = $user['mobile'];            
       
       $this->assign('user',$user);
       $this->assign('data',$withdrawals);
       $this->display();           
    }      
    
    /**
     *  商家结算记录
     */
    public function order_statis(){
        $model = M("order_statis");
        $store_id = I('store_id');        
        $create_date = I('create_date');
        $create_date = str_replace("+"," ",$create_date);
        $create_date2 = $create_date  ? $create_date  : date('Y-m-d',strtotime('-1 month')).' - '.date('Y-m-d',strtotime('+1 month'));
        $create_date3 = explode(' - ',$create_date2);
        $where = " create_date >= '".strtotime($create_date3[0])."' and create_date <= '".strtotime($create_date3[1])."' ";
        $store_id && $where .= " and store_id = $store_id ";
                        
        $count = $model->where($where)->count();
        $Page  = new Page($count,16);
        $list = $model->join('INNER JOIN __STORE__ ON __STORE__.store_id = __ORDER_STATIS__.store_id')->where($where)->order("`id` desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $this->assign('create_date',$create_date2);
        $show  = $Page->show();                 
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();    
    }
}