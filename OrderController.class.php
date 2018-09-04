<?php

namespace Backstage\Controller;

use Think\Controller;

class OrderController extends PublicController {

	public function index(){

		// var_dump(2);

		$map=array();

		$orderid=I('orderid');

		$status=I('status');

		$starttime=I('start_time');

		$endtime=I('end_time');

		$userid=I('userid');

		$name=I('name');

		if($status||$status==='0'){

			$map['status']=$status;

		}else{

			$map['status']=array('neq',-1);

		}

		if(!is_array($status)){

			$status=str2arr($status);	

		}

		$this->assign('status', $status);

		if($starttime){

			if($endtime){

				$start=strtotime($starttime." 00:00:00");

				$end=strtotime($endtime." 23:59:59");

				$map['create_time'] = array(array('egt',$start),array('elt',$end),'and');

			}else{

				$start=strtotime($starttime." 00:00:00");

				$map['create_time']=array('egt',$start);

			}

		}else{

			if($end){

				$end=strtotime($endtime." 23:59:59");

				$map['create_time']=array('elt',$end);

			}

		}

		if($name){

			$name_array=M('member')->where('nickname like "%'.$name.'%"')->field('userid')->select();	

			$name_array=array_column($name_array,'userid');

			//dump($name_array);			

			$map['userid']=array('in',implode(',',$name_array));

		}

		if($orderid){

			$map['orderid']=$orderid;

		}

		$this->assign('select_status', $select_status);

		$orderid_list=M('order')->where($map)->field('orderid')->select();

		S('order_where_id',$orderid_list);

		$list = $this->lists('Order', $map ,'create_time desc',true ,false);//分页查询订单

		foreach($list as $k=>$v){

			$goods=json_decode($v['goodsinfo'],true);

			$goods=reset(reset($goods));

			$list[$k]['goods']=M('goods')->where(array('goodsid'=>$goods['goodsid']))->find();			

		}

		$this->assign('_list', $list);

		$count=M('Order')->where($map)->count();

		$this->assign('count', $count);

		/*@高级检索资源*/

		$editAuth=$this->checkAuth(MODULE_NAME.'/'.CONTROLLER_NAME.'/operate');//编辑权限

		$this->assign('_Auth', $editAuth);

		$this->assign('select_member', $select_member);

		

		

		$this->display('order');

	}

	  

	public function operate(){

		$type=I('type');

		if(empty($type)){

			$type=$_GET['type'];

		}

		switch($type){

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

			case 'export':

				$this->export();

			break;	

		}

		}

	  /*@订单详情

	  *

	  */

	protected function edit(){		  

		$orderid=I('id');

		$orderid || $this->ajaxReturn(array('status'=>0,'info'=>'订单号不能为空'));

		$order_info=M('order')->where(array('orderid'=>$orderid))->find();			

		$order_info || $this->ajaxReturn(array('status'=>0,'info'=>'订单不存在'));

		$order_info['username']=get_member_name($order_info['userid']);

		$order_info['create_time']=date('Y-m-d H:i:s',$order_info['create_time']);		

		$goods=json_decode($order_info['goodsinfo'],true);

		$return=array();

		foreach($goods as $k1=>$v1){

			$brand=get_brand_name($k1);

			$good=array();

			foreach($v1 as $k2=>$v2){

				$attr=get_attr($v2['attr']);

				if($attr['key3']){

					$string3=$attr['key3'].":".$attr['value3'];

				}

				if($attr['key2']){

					$string2=$attr['key2'].":".$attr['value2'];

				}

				if($attr['key1']){

					$string1=$attr['key1'].":".$attr['value1'];

				}

				$string=$string1." ".$string2." ".$string3;

				$goodsinfo=M('goods')->where(array('goodsid'=>$v2['goodsid']))->find();

				$goodsinfo['attr']=$string;

				//$goodsinfo['goodsid']=$v2['goodsid'];

				//$goodsinfo=M('goods')->where(array('goodsid'=>$v2['goodsid']))->find()

				//$goodsinfo['goodsname']=$goods_info['goodsname'];

				$goodsinfo['num']=$v2['num'];

				//$goodsinfo['goodsnum']=;

				$good[]=$goodsinfo;

			}

			$t['goods']=$good;

			$t['brand']=$brand;

			$return[]=$t;

		}

		$order_info['goods']=$return;					

		$this->ajaxReturn(array('status'=>1,'order_info'=>$order_info));			

	}

	protected function edit_ajax(){

		$orderid=I('orderid');

		$orderid || $this->ajaxReturn(array('status'=>0,'info'=>'订单号不存在'));

		$check=M('order')->where(array('orderid'=>$orderid))->find();

		if($check['status']==1){

			if(I('name')&&I('address')&&I('mobile')){			

				$data['name']=I('name');

				$data['address']=I('address');

				$data['mobile']=I('mobile');

				$data['postage']=I('postage');

				$data['update_time']=time();

				$data['remark'] = I('remark');

			}else{

				$this->ajaxReturn(array('status'=>0,'info'=>'请填写完整收货人信息'));

			}

			

		}elseif($check['status']==2){

			if(I('tans_no')&&I('logistics')){		

				$data['tans_no']=I('tans_no');

				$data['logistics']=I('logistics');

				$data['remark'] = I('remark');

			}else{

				$this->ajaxReturn(array('status'=>0,'info'=>'请填写物流单号和物流公司'));

			}		

		}elseif ($check['status']==3||$check['status']==4) {
			$data['remark'] = I('remark');
		}
		
		$return=M('order')->where(array('orderid'=>$orderid))->setField($data);

		$this->success('保存成功');		

	}

	

	/*删除订单*/ 

	protected function status(){

	$id =   I('orderid');

	$map['status']=I('get.status')?I('get.status'):-1;

	switch($map['status']){

		case '2':

		$check=M('Order')->where(array('orderid'=>$id,'status'=>1))->find();

		if(!$check){

			$this->error('待付款订单不存在');

		}

		$status=M('order')->where(array('orderid'=>$id,'status'=>1))->setField('status',2);

		break;

		case '3':

		$check=M('Order')->where(array('orderid'=>$id,'status'=>2))->find();

		if(!$check){

			$this->error('待发货订单不存在');

		}elseif(!$check['tans_no']){

			$this->error('请先填入物流单号并保存');

		}

		$status=M('order')->where(array('orderid'=>$id,'status'=>2))->setField('status',3);

		break;

		case '4':

		$check=M('Order')->where(array('orderid'=>$id,'status'=>3))->find();

		if(!$check){

			$this->error('待收货订单不存在');

		}

		$status=M('order')->where(array('orderid'=>$id,'status'=>3))->setField('status',4);

		distribution_end($id);

		break;

	}

	if($status>0){

		$this->success('修改状态成功');

	}else{

		$this->error('修改状态失败');

	}

	}

	protected function export(){
	    
		$map['orderid']=array('in',implode(',',array_column(S('order_where_id'),'orderid')));

		$filename ="订单列表".date('YmdHis').'.csv';

		header("Content-type:text/csv");

		header("Content-Disposition:attachment;filename=".$filename);

		header('Cache-Control:must-revalidate,post-check=0,pre-check=0');

		header('Expires:0');

		header('Pragma:public');

		$headArr='订单ID,订单状态,下单人微信昵称,收货人,收货人手机,收货人地址,商品信息,总金额,物流公司,物流单号'."\n";

		$data=iconv('utf-8','gb2312',$headArr);

		$list=M('order')->where($map)->select();

		foreach ($list as $k=>$v){

			$array=array();

			$array['orderid']=$v['orderid'];

			$array['status']=get_order_status($v['status']);

			$member = M('member')->where(array('userid'=>$v['userid']))->find();

			$array['nickname'] = $member['nickname'];

            $array['name']=$v['name'];

			$array['mobile']=$v['mobile'];

			$array['address']=$v['address'];

			$goods=json_decode($v['goodsinfo'],true);

			// var_dump($goods);exit;

			$return=array();

			$array['goodsinfo']='';

			foreach($goods as $k1=>$v1){

				foreach($v1 as $k2=>$v2){

					$attr=get_attr($v2['attr']);

					if($attr['key3']){

						$string3=$attr['key3'].":".$attr['value3'];

					}

					if($attr['key2']){

						$string2=$attr['key2'].":".$attr['value2'];

					}

					if($attr['key1']){

						$string1=$attr['key1'].":".$attr['value1'];

					}

					$goodsinfo=M('Goods')->where('goodsid = '.$v2['goodsid'])->find();

					$string=$string1." ".$string2." ".$string3;

					$goodsinfo['attr']=$string;

					$goodsinfo['goodsid']=$v2['goodsid'];

					//$goodsinfo['goodsname']=get_goods_goodsname($v2['goodsid']); 

					//$goodsinfo['goodsnum'] = 

						//var_dump($goodsinfo);exit;

					$goodsinfo['num']=$v2['num'];

					$array['goodsinfo'].='货号:'.$goodsinfo['goodsnum'].'|商品名称:'.$goodsinfo['goodsname'].'|属性:'.$goodsinfo['attr'].'|数量:'.$goodsinfo['num'].';';

			// var_dump($array);



				}

			}

			$array['money']=$v['money'];

			$array['logistics']=$v['logistics'];

			$array['tans_no']=$v['tans_no'];

			foreach($array as $key=>$vo){

				$array[$key]=iconv('utf-8','gb2312//IGNORE',$vo);

			}	

			// var_dump($array);

			$data.=implode(',',$array)."\n";

		}

		echo $data;

	}

}