<?php
/*
 * 生活服务
 * */

namespace Admin\Controller;

use Think\AjaxPage;

class AgentServiceController extends BaseController
{
    /*
     * 服务列表
     * */
    public function serviceList(){
        $service_type = C("SERVICE_TYPE");
        $this->assign('service_type',$service_type);
        $this->display();
    }

    public function ajaxServiceList(){
        $where = ' 1 = 1 '; // 搜索条件
        $type = I('service_type');
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and service_name like '%$key_word%' " ;
        }
        if($type > 0)
        {
            $where .= " and service_type = $type "; // 初始化搜索条件
        }

        $model = M('agent_service');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);
        /**  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
        $Page->parameter[$key]   =   urlencode($val);
        }
         */
        $show = $Page->show();
        $serviceList = $model->where($where)->order('service_id','desc')->limit($Page->firstRow.','.$Page->listRows)->select();

        $service_type = C("SERVICE_TYPE");
        $this->assign('service_type',$service_type);
        $this->assign('serviceList',$serviceList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();

    }

    /*
     * 编辑服务
     * */
    public function addEditAgentService(){
        $id = I('service_id',0);
        //ajax提交验证
        if(IS_POST){     //执行新增操作
            $data = $_POST;

            if($id >0){
                $data['last_update'] = time();
                M('agent_service')->save($data);
                $this->success("操作成功!!!",U('Admin/AgentService/serviceList',array('p'=>$_GET['p'])));
                exit;
            }else{
                $data['on_time'] = time();
                M('agent_service')->add($data);
                $this->success("操作成功!!!",U('Admin/AgentService/serviceList',array('p'=>$_GET['p'])));
                exit;
            }
        }

        $service_type = C("SERVICE_TYPE");
        $this->assign('service_type',$service_type);
        $service = M('agent_service')->where("service_id = {$id}")->find();
        $this->assign('service',$service);
        $this->initEditor(); // 编辑器
        $this->display('_service');
    }

    /*
     * 服务预约列表
     * */
    public function service_appointlist(){
        $service_type = C("SERVICE_TYPE");
        $this->assign('service_type',$service_type);
        $this->display();
    }

    public function ajaxServiceAppointList(){
        $where = ' 1 = 1 '; // 搜索条件
        $type = I('service_type');
        if($type > 0)
        {
            $where .= " and service_type = $type "; // 初始化搜索条件
        }

        $model = M('service_appoint');
        $count = $model->alias('s')->join('join ty_agent_service a on s.service_id = a.service_id ')->where($where)->count();
        $Page  = new AjaxPage($count,10);
        /**  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
        $Page->parameter[$key]   =   urlencode($val);
        }
         */

        $show = $Page->show();
        $appointList = $model->alias('s')->field('id,service_name,user_id,tel_name,tel_phone,first_leader,add_time,is_forward')
            ->join('join ty_agent_service a on s.service_id = a.service_id ')
            ->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('appointList',$appointList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    /*
     * 预约详情
     * */
    public function appoint_detail(){
        $id = I('id');
        $appoint = M('service_appoint')->alias('s')->field('service_name,tel_name,tel_phone,content')
            ->join('join ty_agent_service a on s.service_id = a.service_id ')
            ->where("id = {$id}")->find();

        $this->assign('appoint',$appoint);
        $this->display();
    }

    /**
     * 初始化编辑器链接
     * 本编辑器参考 地址 http://fex.baidu.com/ueditor/
     */
    private function initEditor()
    {
        $this->assign("URL_upload", U('Admin/Ueditor/imageUp',array('savepath'=>'service'))); // 图片上传目录
        $this->assign("URL_imageUp", U('Admin/Ueditor/imageUp',array('savepath'=>'article'))); //  不知道啥图片
        $this->assign("URL_fileUp", U('Admin/Ueditor/fileUp',array('savepath'=>'article'))); // 文件上传s
        $this->assign("URL_scrawlUp", U('Admin/Ueditor/scrawlUp',array('savepath'=>'article')));  //  图片流
        $this->assign("URL_getRemoteImage", U('Admin/Ueditor/getRemoteImage',array('savepath'=>'article'))); // 远程图片管理
        $this->assign("URL_imageManager", U('Admin/Ueditor/imageManager',array('savepath'=>'article'))); // 图片管理
        $this->assign("URL_getMovie", U('Admin/Ueditor/getMovie',array('savepath'=>'article'))); // 视频上传
        $this->assign("URL_Home", "");
    }

    /**
     * 更改指定表的指定字段
     */
    public function updateField(){
        $primary = array(
            'agent_service' => 'service_id',
        );
        $id = I('id',0);
        $field = I('field');
        $value= I('value');
        M($_POST['table'])->where("{$primary[$_POST['table']]} = $id ")->save(array($field =>$value));
        $return_arr = array(
            'status' => 1,
            'msg'   => '操作成功',
            'data'  => array('url'=>U('AgentService/serviceList')),
        );
        $this->ajaxReturn(json_encode($return_arr));
    }
}