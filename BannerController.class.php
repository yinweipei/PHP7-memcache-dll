<?php

namespace Backstage\Controller;

use Think\Controller;

use Think\Hook;

class BannerController extends PublicController {

    public function index(){

		$map=array();

		$title=I('title');

		$type=I('type');

		$status=I('status');

		if($status){

			$map['status']=$status;

		}else{

			$map['status']=1;			

		}				

		if($type){

			$map['type']=$type;

		}else{

			$map['type']=array('in','1,2,3');

		}		

		if($title){

			$map['title']=array('LIKE',"%".$title."%");

			$select_status=1;

		}		

		$list = $this->lists('banner', $map ,'sort,create_time desc',true ,false);//分页查询订单

		$this->assign('_list', $list);

		$count=M('banner')->where($map)->count();

		$this->assign('count', $count);

		/*@高级检索资源*/

		$editAuth=$this->checkAuth(MODULE_NAME.'/'.CONTROLLER_NAME.'/operate');//编辑权限

		$this->assign('_Auth', $editAuth);

		

		$this->display('banner');

	  }

	  public function operate(){

		$type=I('get.type');

		switch($type){

			case 'add' :

				if(IS_POST){

					$this->add_ajax();

				}else{

					$this->add();

				}

			break;

			case 'edit' :

				if(IS_POST){

					$this->edit_ajax();

				}else{

					$this->edit();

				}

			break;			

			case 'status':

				$this->status();

			break;	

		}

			

	  }

	  /*@详情

	  *

	  */

	protected function edit_ajax(){

		$id=I('get.id');

		$id || $this->error("缺失必要参数:ID");

		$main_data['title']=I('title');		

		$main_data['update_time']=time();									

		$main_data['status']=1;

		$main_data['description']=I('description');

		$main_data['type']=I('type');

		$main_data['sort']=I('sort');

		$main_data['img_path']=I('img_path');

		$main_data['goodsid']=implode('',I('goodsid'));

		$main_data['link'] = I('link');

		$save=M('banner')->where(array('id'=>$id))->setField($main_data);

		$this->success('保存成功！'); 

	}

	protected function edit(){

		$id=I('get.id');	

		$id || $this->ajaxReturn(array('status'=>0,'info'=>'id不能为空'));

		$info=M('banner')->where(array('id'=>$id))->find();		

		$info || $this->ajaxReturn(array('status'=>0,'info'=>'轮播不存在'));

		$goods_info=M('goods')->where(array('goodsid'=>$info['goodsid']))->field('goodsid,goodsname,goodsnum')->select();

		if($goods_info){

			$info['goods']=$goods_info;

		}else{

			$info['goods']=array();

		}

		$this->ajaxReturn(array('status'=>1,'info'=>$info));					

	}

	   /*@详情

	  *

	  */

	protected function add(){

			$card=M('card')->where(array('status'=>1))->select();

			$this->assign('card', $card); 

			$this->display('banner_edit');

			

	}

	protected function add_ajax(){		

		$main_data['title']=I('title');

		$main_data['create_time']=time();									

		$main_data['status']=1;

		$main_data['description']=I('description');

		$main_data['type']=I('type');

		$main_data['sort']=I('sort');

		$main_data['img_path']=I('img_path');

		$main_data['goodsid']=implode(',',I('goodsid'));

		$save=M('banner')->add($main_data);

		if($save){

			$this->success('新增成功!'); 

		}

		$this->error("新增失败！"); 

		

	}

/*修改状态*/

	protected function status(){

		$id = array_unique((array)I('id',0));

		$id = is_array($id) ? implode(',',$id) : $id;

		if ( empty($id) ) {

			$this->error('请选择要操作的数据!');

		}

		$map['id'] =   array('in',$id);

		$map['status']=I('get.status')?I('get.status'):-1;

		$status=M('banner')->save($map);

		if($status>0){

			$this->success('修改状态成功');

		}

		$this->error('修改状态失败');

	}

}