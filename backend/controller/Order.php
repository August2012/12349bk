<?php

/**
 * 订单模块管理
 */
class Order {

	private static $order_status = array(
		'0' => '待审核',
		'1' => '已审核',
		'2' => '打回',
		'3' => '无效',
		'4' => '已分配',
		'5' => '待支付',
		'6' => '已支付',
		'7' => '已回访',
	);

	/**
	 * 待审核订单
	 *
	 * @return page
	 * @author zhaozl
	 * @since  2015-07-01
	 */
	public static function waitorder() {

		Flight::jsrender('/public/js/order/waitorder.js');

		Flight::render('order/waitorder');

	}

	/**
	 * 获取服务类型
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-07
	 */
	public static function getItemsType() {

		$db = Flight::get('db');
		$items = $db->select('lk_service_item', array('item_id', 'item_name', 'ser_id'), array('is_use' => 1));
		// 此处获取类型
		$services = $db->select('lk_service', array('ser_id', 'ser_name'), array('is_use' => 1));
		$sers = array();
		foreach ($services as $ser) {
			$sers[$ser['ser_id']] = $ser['ser_name'];
		}

		$types = array();
		foreach ($items as $item) {
			$types[$item['item_id']] = $sers[$item['ser_id']]. '·' . $item['item_name'];
		}

		Flight::json($types);
	}


	/**
	 * 获取订单
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-06
	 */
	public static function getOrders() {

		$req = Flight::request()->query;

		$search = isset($req->sSearch)?$req->sSearch:'';
		$sSearch_1 = isset($req->sSearch_1)?$req->sSearch_1:'';
		$sSearch_2 = isset($req->sSearch_2)?$req->sSearch_2:'';
		$sSearch_3 = isset($req->sSearch_3)?$req->sSearch_3:'';
		$sSearch_4 = isset($req->sSearch_4)?$req->sSearch_4:'';

		$type = isset($req->type)?$req->type:'';
		$length = isset($req->iDisplayLength)?$req->iDisplayLength:'';
		$start = isset($req->iDisplayStart)?$req->iDisplayStart:'';
		$sort_index = isset($req->iSortCol_0)?$req->iSortCol_0:'0';

		$sort_item = $req['mDataProp_'.$sort_index];
		$sort_sort = strtoupper($req['sSortDir_0']);

		$db = Flight::get('db');
		$sql = "SELECT
			lk_order.*, lk_order_service.service_name,
			lk_order_service.item_name,
			lk_order_service.item_id,
			lk_order_service.service_id,
			lk_order_address.contact,
			lk_order_address.region_id,
			lk_order_address.region_name,
			lk_order_address.address,
			lk_order_address.phone_mob,
			lk_order_address.phone_tel,
			lk_staff_comment. degree,
			lk_staff_comment.`comment`,
			lk_shopstaff.phone_mob AS staff_phone
		FROM
			lk_order
		LEFT JOIN lk_order_service ON lk_order.order_sn = lk_order_service.order_sn
		LEFT JOIN lk_order_address ON lk_order.order_sn = lk_order_address.order_sn
		LEFT JOIN lk_staff_comment ON lk_order.order_sn = lk_staff_comment.order_sn
		LEFT JOIN lk_shopstaff ON lk_order.staff_id = lk_shopstaff.staff_id
		WHERE 1=1";

		switch ($type) {
			case '1':
			// 待审核订单
				$sql .= " AND lk_order.status = 0";
				break;
			case '2':
			// 带接单订单
				$sql .= " AND lk_order.status = 1";
				break;
			case '3':
			// 投诉订单
				$sql .= " AND lk_staff_comment.degree = -1";

				if($sSearch_2) {
					$sql .= " AND lk_order.add_time >= {$sSearch_2}";
				}

				if($sSearch_3) {
					$sql .= " AND lk_order.add_time >= {$sSearch_3}";
				}
				break;
			case '4':
				$sql .= " AND lk_order.status in ('4', '5')";
				break;
			case '5':
				$sql .= " AND lk_order.status = 6";
				break;
			default:
				if($sSearch_2) {
					$sql .= " AND lk_order.add_time >= {$sSearch_2}";
				}

				if($sSearch_3) {
					$sql .= " AND lk_order.add_time >= {$sSearch_3}";
				}
			// 全部订单
				break;
		}

		if($sSearch_4 && $sSearch_4 > 0) {
			// 今日工单
			if($sSearch_4 == 1) {
				$todayStart = strtotime(date('Y-m-d'));
				$todayEnd = strtotime(date('Y-m-d')) + 86399;

				$sql .= " AND lk_order.work_time >= {$todayStart} AND lk_order.work_time <= {$todayEnd} ";

			}else if($sSearch_4 == 2) {
				// 明日工单
				$towStart = strtotime(date('Y-m-d',strtotime('+1 day')));
				$towEnd = $towStart + 86399;

				$sql .= " AND lk_order.work_time >= {$towStart} AND lk_order.work_time <= {$towEnd} ";
			}
		}
		
		if($sSearch_1 > 0) {
			$sql .= " AND lk_order_service.item_id = {$sSearch_1}";
		}

		if($search) {
			$sql .= " AND (lk_order.order_sn LIKE '%{$search}%' 
				OR lk_order_address.contact LIKE '%{$search}%' 
				OR lk_order_address.phone_mob LIKE '%{$search}%'
				OR lk_order.shop_name LIKE '%{$search}%'
				OR lk_order.staff_name LIKE '%{$search}%'
				OR lk_shopstaff.phone_mob LIKE '%{$search}%'
				OR lk_order_address.region_name LIKE '%{$search}%'
				OR lk_order_address.address LIKE '%{$search}%'
				)";
		}

		$countSql = $sql;
		$sql .= " ORDER BY lk_order.order_id DESC LIMIT {$start},{$length}";

		$res = $db->query($sql)->fetchAll();

		$result = array();
		if($res){
			foreach ($res as $key => $value) {

				if($value['staff_name'] && $value['staff_phone']) {
					$staffinfo =  $value['staff_name'].'【'.$value['staff_phone'].'】';
				}

				if($value['contact'] && ($value['phone_mob'] || $value['phone_tel'])) {
					$userinfo =  $value['contact'].'【'.(isset($value['phone_mob']) && $value['phone_mob']?$value['phone_mob']:$value['phone_tel']).'】';
				}

				$result[] = array(
					'order_sn' => $value['order_sn'],
					'type_name' => $value['service_name']. '·'. $value['item_name'],
					'status' => self::$order_status[$value['status']],
					'shop_name' => $value['shop_name'],
					'statuscode' => $value['status'],
					'staff_name' => $value['staff_name'],
					'staff' => isset($staffinfo)?$staffinfo:'',
					'contact' => $value['contact'],
					'userinfo' => isset($userinfo)?$userinfo:'',
					'degree' => $value['degree'],
					'amount' => $value['amount'],
					'source' => $value['source'],
					'source1' => $value['source'],
					'phone_mob' => isset($value['phone_mob']) && $value['phone_mob']?$value['phone_mob']:$value['phone_tel'],
					'address_detail' => $value['region_name'].$value['address'],
					'need_time' => $value['need_time'] > 0?date('Y-m-d H:i:s', $value['need_time']):'尽快',
					'add_time' => $value['add_time'] > 0?date('Y-m-d H:i:s', $value['add_time']):'',
					'work_time' => $value['work_time'] > 0?date('Y-m-d H:i:s', $value['work_time']):'',
					'remark' => $value['remark'],
					'order_id' => $value['order_id'],
					'comment' => $value['comment'],
					'staff_phone' => $value['staff_phone'],
					'admin_name' => $value['admin_name'],
					'item_id' => $value['item_id'],
					'service_id' => $value['service_id'],
				);
			}
		}

		$iFilteredTotal = count($db->query($countSql)->fetchAll());

		$iTotalRecords = $db->count('lk_order');
		Flight::json(array(
			'aaData' => $result, 
			"sEcho" => intval($req->sEcho),
			"iTotalRecords" => $iTotalRecords,
			"iTotalDisplayRecords" => $iFilteredTotal,
		));

	}

	/**
	 * 审核订单
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-07
	 */
	public static function check_order() {

		$req = Flight::request()->data;
		$order_id = isset($req['order_id'])?$req['order_id']:'';
		$type = isset($req['type'])?$req['type']:'';

		if($order_id) {

			// 判断订单是否存在并有效
			$db = Flight::get('db');
			$hasOrder = $db->has('lk_order',array('AND' => array('order_id' => $order_id, 'status' => 0)) );
			if($hasOrder) {

				$admin_id = Session::get('admin_id');
				$time = time();

				if($type == 1) {

					// 更改订单表审核信息
					$db->update('lk_order', array(
						'status' => 1,
						'verify_time' => $time,
					), array('order_id' => $order_id));

					// 添加操作记录
					$order_sn = $db->get('lk_order', 'order_sn', array('order_id' => $order_id));
					$db->insert('lk_order_action', array(
						'order_sn' => $order_sn,
						'admin_id' => $admin_id,
						'action_id' => 1,
						'action_result' => 1,
						'comment' => '审核通过',
						'action_time' => $time
					));

					Flight::json(array('success' => true, 'msg' => '审核订单成功'));
				}else{

					$reason = isset($req['reason'])?$req['reason']:'';
					// 更改订单表审核信息
					$db->update('lk_order', array(
						'status' => 2,
						'verify_time' => $time,
					), array('order_id' => $order_id));

					// 添加操作记录
					$order_sn = $db->get('lk_order', 'order_sn', array('order_id' => $order_id));
					$db->insert('lk_order_action', array(
						'order_sn' => $order_sn,
						'admin_id' => $admin_id,
						'action_id' => 1,
						'action_result' => 0,
						'comment' => $reason,
						'action_time' => $time
					));

					Flight::json(array('success' => true, 'msg' => '打回订单成功'));
				}

			}else{
				Flight::json(array('success' => false, 'msg' => '找不到符合条件的订单'));
			}

		}else{
			Flight::json(array('success' => false, 'msg' => '传入的数据不正确'));
		}

	}

	/**
	 * 审核成功待接单订单
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-07
	 */
	public static function processorder() {
		Flight::cssrender('/public/css/select2.min.css');
		Flight::cssrender('/public/css/plugins/jquery.loadmask.spin.css');
		Flight::jsrender('/public/js/plugins/jquery.validate.min.js');
		Flight::jsrender('/public/js/plugins/jquery.tagsinput.min.js');
		Flight::jsrender('/public/js/plugins/select2.min.js');
		Flight::jsrender('/public/js/plugins/spin.min.js');
		Flight::jsrender('/public/js/plugins/jquery.loadmask.spin.js');
		Flight::jsrender('/public/js/order/processorder.js');

		Flight::render('order/processorder');

	}

	/**
	 * 投诉订单
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-07
	 */
	public static function complainorder() {
		Flight::cssrender('/public/css/plugins/jquery.loadmask.spin.css');
		Flight::jsrender('/public/js/plugins/spin.min.js');
		Flight::jsrender('/public/js/plugins/jquery.loadmask.spin.js');
		Flight::jsrender('/public/js/order/complainorder.js');

		Flight::render('order/complainorder');
	}

	/**
	 * 全部订单
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-07
	 */
	public static function allorder() {
		Flight::cssrender('/public/css/plugins/jquery.loadmask.spin.css');
		Flight::jsrender('/public/js/plugins/spin.min.js');
		Flight::jsrender('/public/js/plugins/jquery.loadmask.spin.js');
		Flight::jsrender('/public/js/order/allorder.js');

		Flight::render('order/allorder');
	}


	/**
	 * 审核成功待接单订单
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-07
	 */
	public static function view_order() {

		$req = Flight::request()->data;
		$order_id = isset($req['order_id'])?$req['order_id']:'';

		$db = Flight::get('db');
		$order_info = $db->get('lk_order', "*", array('order_id' => $order_id));
		if($order_info) {

			$order_action = $db->select('lk_order_action', "*", array('order_sn' => $order_info['order_sn']));

			if($order_action) {
				$strHtml = "<div id='dialog' title='订单跟踪信息'><table class='stdtable' cellspacing='0' width='100%'><thead>
				<tr>
					<th class='head0'>#</th>
					<th class='head1'>订单记录</th>
					<th class='head0'>操作人</th>
					<th class='head1'>时间</th>
				</tr></thead><tbody>";

				foreach ($order_action as $key => $value) {
					
					if(in_array($value['action_id'], array(4,5,6))) {
						$action_name = '【用户】'.$db->get("lk_user", "user_name", array("user_id" => $value['admin_id']));
					}else{
						$action_name = '【管理员】'.$db->get("lk_admin", "admin_name", array("admin_id" => $value['admin_id']));
					}

					if($value['action_id'] == 6) {
						$value['comment'] = '微信客户端付款';
					}

					$strHtml .= '<tr><td class="head0">'.($key+1).'</td><td class="head1">'.$value['comment'].'</td><td class="head0">'.$action_name.'</td><td class="head0">'.date('Y-m-d H:i:s', $value['action_time']).'</td></tr>';

				}

				$strHtml .= '</tbody></table></div>';

				echo $strHtml;

			}else{
				echo "无订单操作记录";
			}
		}else{
			echo "找不到相关订单信息";
		}

	}

	/**
	 * 后台下单功能
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-14
	 */
	public static function addorder() {

		if(IS_POST) {
			$data = Flight::request()->data;

			$item_id = isset($data["item_id"])?trim($data["item_id"]):'';
			if(!$item_id) {
				Handle::result("缺少关键数据", "请选择一个服务类型" );
			}

			// 获取金额
			$need_time = isset($data['need_time'])?strtotime($data['need_time']):'';
			if(!$need_time) {
				Handle::result("缺少关键数据", "请选择约定服务时间" );
			}

			$contact = isset($data['contact'])?trim($data['contact']):'';
			if(!$contact) {
				Handle::result("缺少关键数据", "请选择约定服务时间" );
			}
			$phone_mob = isset($data['phone_mob'])?trim($data['phone_mob']):'';
			if(!$phone_mob) {
				Handle::result("缺少关键数据", "请填写手机号或电话" );
			}

			// 判断是手机号码还是电话
			if(preg_match("/1[3578]{1}\d{9}$/",$phone_mob)){  
			    $is_phone = true;
			}else{  
			    $is_phone = false;
			}  

			$region_id = isset($data['region_id'])?$data['region_id']:'';
			if(!$region_id) {
				Handle::result("缺少关键数据", "请选择区域" );
			}

			$address = isset($data['address'])?$data['address']:'';
			if(!$address) {
				Handle::result("缺少关键数据", "请输入服务地址" );
			}
			$remark = isset($data['remark'])?$data['remark']:'';
			$user_name = isset($data['user_name'])?$data['user_name']:'';

			$db = Flight::get('db');
			if($user_name) {
				$user = $db->get("lk_user", array("user_id","user_name"), array("OR" => array("user_name" => $user_name, "phone_mob" => $user_name)));
				if(!$user) {
					Handle::result("错误", "找不到用户，请确认" );
				}

			}

			$order_sn = self::_generate_order_sn();
			$order_data = array(
				'order_sn' => $order_sn,
				'type' => 'service',
				'buyer_id' => isset($user['user_id'])?$user['user_id']:'',
				'buyer_name' => isset($user['user_name'])?$user['user_name']:'匿名',
				'add_time' => time(),
				'need_time' => $need_time,
				'source' => 1,
				'status' => 1, // 后台不需要审核
				'remark' => $remark,
				'admin_id' => Session::get('admin_id'),
				'admin_name' => Session::get('admin_name'),
			);

			$db->pdo->beginTransaction();
			$order_id = $db->insert("lk_order", $order_data);

			if($order_id) {

				$region_array = Shop::getRegions(2);

				$address_data = array(
					'order_sn' => $order_sn,
					'contact' => $contact,
					'region_id' => $region_id,
					'region_name' => isset($region_array[$region_id])?$region_array[$region_id]:'',
					'address' => $address,
					'phone_mob' => $is_phone?$phone_mob:'',
					'phone_tel' => $is_phone?'':$phone_mob,
				);
				$order_address_id = $db->insert("lk_order_address", $address_data);

				if(!$order_address_id) {
					$db->pdo->rollBack();
					Handle::result("错误", "插入订单地址失败" );
				}

				$item = $db->get("lk_service_item", array("ser_id", "item_name"), array("item_id" => $item_id));
				$service_name = $db->get("lk_service", "ser_name", array("ser_id" => $item['ser_id']));

				$order_service = array(
					'order_sn' => $order_sn,
					'service_id' => $item['ser_id'],
					'service_name' => $service_name,
					'item_id' => $item_id,
					'item_name' => $item['item_name'],
				);

				$order_service = $db->insert("lk_order_service", $order_service);
				if(!$order_service) {
					$db->pdo->rollBack();
					Handle::result("错误", "插入订单服务项失败" );
				}

				$action_data = array(
					'order_sn' => $order_sn,
					'admin_id' => Session::get('admin_id'),
					'action_id' => '0',
					'action_result' => '1',
					'comment' => '后台下单',
					'action_time' => time(),
				);
				$action_id = $db->insert("lk_order_action", $action_data);

				$db->pdo->commit();
				// 短信通知用户下单成功
				if($is_phone) {
					sendMsg($phone_mob, "尊敬的{$contact}: 您预约的【{$service_name}-{$item['item_name']}】服务,已经下单成功，我们会尽快安排服务。");
				}
				Handle::result("成功", "添加成功", '../order/processorder', "点击继续添加", '../order/addorder' );

			}else{
				$db->pdo->rollBack();
				Handle::result("错误", "插入订单失败" );
			}


		}else{

			$serItems = Shop::getServiceItems();
			$regions = Shop::getRegions();

			Flight::jsrender('/public/js/plugins/jquery.validate.min.js');
			Flight::jsrender('/public/js/plugins/jquery.tagsinput.min.js');
			Flight::jsrender('/public/js/plugins/chosen.jquery.min.js');
			Flight::jsrender('/public/js/order/addorder.js');
			Flight::render('order/addorder', array("ser_items" => $serItems, "regions" => $regions));
			
		}

	}


	/**
	 * 后台下单功能
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-14
	 */
	public static function addcouponorder() {

		if(IS_POST) {
			$data = Flight::request()->data;

			$coup_id = isset($data["coup_id"])?trim($data["coup_id"]):'';
			if(!$coup_id) {
				Flight::json(array("success" => false, "msg" => "请选择优惠券"));
				die;
			}

			$db = Flight::get('db');
			$item_id = $db->get("lk_coup_list", "item_id", array("id" => $coup_id));

			// 获取金额
			$need_time = isset($data['need_time'])?strtotime($data['need_time']):'';
			if(!$need_time) {
				Flight::json(array("success" => false, "msg" => "请选择约定服务时间"));
				die;
			}

			$contact = isset($data['contact'])?trim($data['contact']):'';
			if(!$contact) {
				Flight::json(array("success" => false, "msg" => "请填写联系人"));
				die;
			}
			$phone_mob = isset($data['phone_mob'])?trim($data['phone_mob']):'';
			if(!$phone_mob) {
				Flight::json(array("success" => false, "msg" => "请填写手机号或电话"));
				die;
			}

			// 判断是手机号码还是电话
			if(preg_match("/1[3578]{1}\d{9}$/",$phone_mob)){  
			    $is_phone = true;
			}else{  
			    $is_phone = false;
			}  

			$region_id = isset($data['region_id'])?$data['region_id']:'';
			if(!$region_id) {
				Flight::json(array("success" => false, "msg" => "请选择区域"));
				die;
			}

			$address = isset($data['address'])?$data['address']:'';
			if(!$address) {
				Flight::json(array("success" => false, "msg" => "请输入服务地址"));
				die;
			}
			$remark = isset($data['remark'])?$data['remark']:'';
			$phone = isset($data['phone'])?$data['phone']:'';

			if($phone) {
				$user = $db->get("lk_user", array("user_id","user_name"), array( "phone_mob" => $phone ));
			}

			$order_sn = self::_generate_order_sn();
			$order_data = array(
				'order_sn' => $order_sn,
				'type' => 'service',
				'buyer_id' => isset($user['user_id'])?$user['user_id']:'',
				'buyer_name' => isset($user['user_name'])?$user['user_name']:'匿名',
				'add_time' => time(),
				'need_time' => $need_time,
				'source' => 1,
				'status' => 1, // 后台不需要审核
				'remark' => $remark,
				'admin_id' => Session::get('admin_id'),
				'admin_name' => Session::get('admin_name'),
			);

			$db->pdo->beginTransaction();
			$order_id = $db->insert("lk_order", $order_data);

			if($order_id) {

				$db->update("lk_coup_list", array("order_sn" => $order_sn, "ischecked" => 1, "usedtime" => date('Y-m-d H:i:s')), array("id" => $coup_id));

				$region_array = Shop::getRegions(2);

				$address_data = array(
					'order_sn' => $order_sn,
					'contact' => $contact,
					'region_id' => $region_id,
					'region_name' => isset($region_array[$region_id])?$region_array[$region_id]:'',
					'address' => $address,
					'phone_mob' => $is_phone?$phone_mob:'',
					'phone_tel' => $is_phone?'':$phone_mob,
				);
				$order_address_id = $db->insert("lk_order_address", $address_data);

				if(!$order_address_id) {
					$db->pdo->rollBack();
					Flight::json(array("success" => false, "msg" => "插入订单地址失败"));
					die;
				}

				$item = $db->get("lk_service_item", array("ser_id", "item_name"), array("item_id" => $item_id));
				$service_name = $db->get("lk_service", "ser_name", array("ser_id" => $item['ser_id']));

				$order_service = array(
					'order_sn' => $order_sn,
					'service_id' => $item['ser_id'],
					'service_name' => $service_name,
					'item_id' => $item_id,
					'item_name' => $item['item_name'],
				);

				$order_service = $db->insert("lk_order_service", $order_service);
				if(!$order_service) {
					$db->pdo->rollBack();
					Flight::json(array("success" => false, "msg" => "插入订单服务项失败"));
					die;
				}

				$action_data = array(
					'order_sn' => $order_sn,
					'admin_id' => Session::get('admin_id'),
					'action_id' => '0',
					'action_result' => '1',
					'comment' => '后台下单',
					'action_time' => time(),
				);
				$action_id = $db->insert("lk_order_action", $action_data);

				$db->pdo->commit();

				// 短信通知用户下单成功
				if($is_phone) {
					sendMsg($phone_mob, "尊敬的{$contact}: 您预约的【{$service_name}-{$item['item_name']}】服务,已经下单成功，我们会尽快安排服务。");
				}

				Flight::json(array("success" => true, "msg" => "下单成功"));

			}else{
				$db->pdo->rollBack();
				Flight::json(array("success" => false, "msg" => "插入订单失败"));
				die;
			}


		}else{

			$items = Shop::getCouponServiceItems();
			$regions = Shop::getRegions();

			Flight::cssrender('/public/css/select2.min.css');
			Flight::cssrender('/public/css/plugins/jquery.loadmask.spin.css');
			Flight::jsrender('/public/js/plugins/jquery.validate.min.js');
			Flight::jsrender('/public/js/plugins/jquery.tagsinput.min.js');
			Flight::jsrender('/public/js/plugins/select2.min.js');
			Flight::jsrender('/public/js/plugins/spin.min.js');
			Flight::jsrender('/public/js/plugins/jquery.loadmask.spin.js');
			Flight::jsrender('/public/js/order/addcouponorder.js');
			Flight::render('order/addcouponorder', array("items" => $items, "regions" => $regions));
			
		}

	}

	/**
	 * 获取我的可用优惠券
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-20
	 */
	public static function getMyCoupon() {

		$data = Flight::request()->data;
		$phone_mob = isset($data->phone)?$data->phone:'';

		if($phone_mob) {
			$db = Flight::get('db');

			$curTime = date("Y-m-d H:i:s");
			$coupons = $db->select("lk_coup_list", array(
				"[>]lk_service_item" => "item_id"
			), array(
				"id", 
				"lk_coup_list.commoncode",
				"lk_service_item.ser_id", 
				"lk_service_item.item_name", 
				"lk_coup_list.order_sn", 
				"lk_coup_list.ischecked",
				"lk_coup_list.usedtime",
				), array("AND" => array(
				"begintime[<]" => $curTime,
				"endtime[>]" => $curTime,
				"phone_mob" => $phone_mob,
				"isuse" => 1,
				"coupon_id" => 1
			)));

			if($coupons) {
				Flight::json(array("success" => true, "data" => $coupons));

			}else{
				Flight::json(array("success" => false, "msg" => "没有可用优惠券"));
			}

		}else{
			Flight::json(array("success" => false, "msg" => "请输入手机号"));
		}


	}

	/**
	 * 生成订单号
	 *
	 * @param  string     $_pre [description]
	 * @return [type]           [description]
	 * @author zhaozl
	 * @since  2015-07-14
	 */
	public static function _generate_order_sn($_pre = ''){
        //'年月日时分+5位随机数'
        $seq_no = date('ymd').'-'.rand(1000,9999);
        $atrOrderSNMain = $_pre.$seq_no;

        if(Flight::get('db')->get("lk_order", "order_sn", array("order_sn" => $atrOrderSNMain))) {
        	return self::_generate_order_sn($_pre);
        }
        return $atrOrderSNMain;

    }

    /**
     * 获取商家根据订单号
     *
     * @return [type]     [description]
     * @author zhaozl
     * @since  2015-07-14
     */
    public static function getShopByOrderId() {

    	$order_id = Flight::request()->data->order_id;

    	if($order_id && $order_sn = Flight::get('db')->get("lk_order", "order_sn", array("order_id" => $order_id))) {

    		$item_id = Flight::get('db')->get("lk_order_service", "item_id", array("order_sn" => $order_sn));
    		if($item_id) {

    			$shops = Flight::get('db')->query("SELECT shop_id, shop_name FROM lk_shop WHERE FIND_IN_SET('{$item_id}', `item_ids`)")->fetchAll();
    			if($shops) {

					Flight::json(array("success" => true, "data" => $shops));    		

    			}else{
					Flight::json(array("success" => false, "msg" => "查无能服务此订单的商家"));    		
    			}

    		}else{
				Flight::json(array("success" => false, "msg" => "订单缺少关键数据"));    		
    		}

    	}else{
			Flight::json(array("success" => false, "msg" => "订单号无效"));    		
    	}

    }

    /**
     * 获取服务人员
     *
     * @return [type]     [description]
     * @author zhaozl
     * @since  2015-07-15
     */
    public static function getShopStaffByShopId() {
    	$shop_id = Flight::request()->data->shop_id;

    	if($shop_id && $staff = Flight::get('db')->select("lk_shopstaff", array("staff_id", "staff_name"), 
    		array("AND" => array("shop_id" => $shop_id, "is_use" => 1, "status" => 1)))) {

			Flight::json(array("success" => true, "staff" => $staff));    		

    	}else{
			Flight::json(array("success" => false, "msg" => "商家下不存在有效的服务人员"));    		
    	}
    }

    /**
     * 给订单安排人
     *
     * @return [type]     [description]
     * @author zhaozl
     * @since  2015-07-15
     */
    public static function assignWorker() {

    	$data = Flight::request()->data;

    	$order_id = isset($data['order_id'])?$data['order_id']:'';
    	$shop_id = isset($data['shop'])?$data['shop']:'';
    	$staff_id = isset($data['staff'])?$data['staff']:'';

    	if($order_id && $shop_id && $staff_id) {

    		$shop_name = Flight::get('db')->get("lk_shop", "shop_name", array("shop_id" => $shop_id));
    		$staff_info = Flight::get('db')->get("lk_shopstaff", array("phone_mob", "staff_name"), array("staff_id" => $staff_id));

    		$order_info = Flight::get('db')->get("lk_order", "*", array("order_id" => $order_id));
    		$order_address = Flight::get('db')->get("lk_order_address", "*", array("order_sn" => $order_info['order_sn']));

    		//订单的自服务ID
    		$order_item = Flight::get('db')->get("lk_order_service", array("item_id", "item_name", "service_name"), array("order_sn" => $order_info['order_sn']));
    		$shop_rate = Flight::get('db')->get("lk_shop_commission", "rate", array("AND" =>  array("shop_id" => $shop_id, "item_id" => $order_item['item_id'], "status" => 1)));
    		
    		if(!$shop_rate && $order_item['item_id'] <= 30) {
    			Flight::json(array("success" => false, "msg" => "请先设定改商家的佣金比例"));
				die;
    		}else{
    			$shop_rate = $shop_rate?$shop_rate:0;
    		}
    		Flight::get('db')->update("lk_order_service", array("rate" => $shop_rate), array("order_sn" => $order_info['order_sn']));

    		Flight::get('db')->update("lk_order", array(
    			'staff_id' => $staff_id,
    			'staff_name' => $staff_info['staff_name'],
    			'shop_id' => $shop_id,
    			'shop_name' => $shop_name,
    			'status' => 4,
    			'work_time' => time(),
    		), array("order_id" => $order_id));

    		$action_data = array(
    			'order_sn' => $order_info['order_sn'],
				'admin_id' => Session::get('admin_id'),
				'action_id' => 3,
				'action_result' => 1,
				'comment' => "分配商家服务人员",
				'action_time' => time(),
    		);
    		$action_id = Flight::get('db')->insert("lk_order_action", $action_data);

    		$contact_phone = isset($order_address['phone_mob']) && $order_address['phone_mob']?$order_address['phone_mob']:$order_address['phone_tel'];
    		// 短信通知
    		$need_time = date('Y-m-d H:i', $order_info['need_time']);
    		$msg = "尊敬的{$staff_info['staff_name']}：您有新工单{$order_info['order_sn']},服务内容：{$order_item['service_name']}-{$order_item['item_name']}，服务对象：{$order_address['contact']}，联系方式：{$contact_phone}，服务地址：{$order_address['region_name']}{$order_address['address']},预约时间：{$need_time}";
    		sendMsg($staff_info['phone_mob'], $msg);

    		Flight::json(array("success" => true, "msg" => "订单指派成功"));
			die;

    	}else{
    		Flight::json(array("success" => false, "msg" => "数据缺失"));
			die;
    	}


    }

    /**
     * 待支付订单
     *
     * @return [type]     [description]
     * @author zhaozl
     * @since  2015-07-17
     */
    public static function weitpayprder() {
    	Flight::cssrender('/public/css/plugins/jquery.loadmask.spin.css');
		Flight::jsrender('/public/js/plugins/spin.min.js');
		Flight::jsrender('/public/js/plugins/jquery.loadmask.spin.js');

    	Flight::jsrender('/public/js/plugins/jquery.validate.min.js');
		Flight::jsrender('/public/js/plugins/jquery.tagsinput.min.js');

    	Flight::jsrender('/public/js/order/weitpayprder.js');
		Flight::render('order/weitpayprder');
    }

    /**
     * 完成订单
     *
     * @return [type]     [description]
     * @author zhaozl
     * @since  2015-07-17
     */
    public static function finishorder() {
    	Flight::cssrender('/public/css/plugins/jquery.loadmask.spin.css');
    	Flight::jsrender('/public/js/plugins/jquery.validate.min.js');
		Flight::jsrender('/public/js/plugins/jquery.tagsinput.min.js');

		Flight::jsrender('/public/js/plugins/spin.min.js');
		Flight::jsrender('/public/js/plugins/jquery.loadmask.spin.js');
    	Flight::jsrender('/public/js/order/finishorder.js');
		Flight::render('order/finishorder');
    }

    /**
     * 后台支付订单
     *
     * @return [type]     [description]
     * @author zhaozl
     * @since  2015-07-18
     */
	public static function pay_order() {

		if(IS_POST) {
			$data = Flight::request()->data;

			$order_id = isset($data['order_id'])?$data['order_id']:'';
			$order_amount = isset($data['order_amount']) && $data['order_amount'] > 0?sprintf("%.2f", $data['order_amount']):0;
			$commoncode = isset($data['commoncode'])?trim($data['commoncode']):'';
			$codepassword = isset($data['codepassword'])?trim($data['codepassword']):'';

			if(!$order_id) {
				Flight::json(array("success" => false, "msg" => "未获取到订单号"));
				die;
			}

			if( ($order_amount != 0 && !$order_amount) && (!$commoncode || !$codepassword)) {
				Flight::json(array("success" => false, "msg" => "订单金额错误"));
				die;
			}

			$db = Flight::get("db");
			$order_data = $db->get("lk_order", "*", array("AND" => array("order_id" => $order_id, "status[~]" => array("4", "5"))));
			if(!$order_data) {
				Flight::json(array("success" => false, "msg" => "订单状态不符合条件"));
				die;
			}
			// 现金支付
			$order_coupon = $db->get("lk_coup_list", array("id", "commoncode", "codepassword", "item_id"), array("order_sn" => $order_data['order_sn']));

			// 若是有优惠券
			if($order_coupon) {
				if($commoncode && $codepassword && $order_coupon['commoncode'] == $commoncode && $order_coupon['codepassword'] == $codepassword) {
					$coupon_value = $db->get("lk_service_item", "item_price", array("item_id" => $order_coupon['item_id']));

					$db->pdo->beginTransaction();
					if($order_amount > 0) {
						$pay_res = $db->insert("lk_order_pay", array(
							"order_sn" => $order_data['order_sn'],
							"pay_id" => 3,
							"pay_name" => "现金支付",
							"money" => $order_amount,
							"pay_time" => time(),
							"status" => 1,
							"pay_message" => "后台支付混合(现金)支付BY(".Session::get("admin_id").")".Session::get("admin_name"),
						));

						$act_res = $db->insert("lk_order_action", array(
							"order_sn" => $order_data['order_sn'],
							"admin_id" => Session::get("admin_id"),
							"action_id" => 7,
							"action_result" => 1,
							"comment" => "后台支付混合(现金)支付BY(".Session::get("admin_id").")".Session::get("admin_name"),
							"action_time" => time()
						));
						
					}else{
						$order_amount = 0;
						$pay_res = 1;
						$act_res = 1;
					}

					// 优惠券部分
					$order_res1 = $db->update("lk_order", array(
						"amount" => $order_amount + $coupon_value,
						"discount" => $coupon_value,
						"order_amount" => $order_amount,
						"use_coupon_value" => $coupon_value,
						"use_coupon_no" => $commoncode,
						"pay_time" => time(),
						"pay_type" => 3,
						"status" => 6,
						), array("order_id"=> $order_id));

					$pay_res1 = $db->insert("lk_order_pay", array(
						"order_sn" => $order_data['order_sn'],
						"pay_id" => 1,
						"pay_name" => "优惠券抵消支付",
						"money" => $coupon_value,
						"pay_time" => time(),
						"status" => 1,
						"pay_message" => "后台支付优惠券抵消BY(".Session::get("admin_id").")".Session::get("admin_name"),
						));

					$act_res1 = $db->insert("lk_order_action", array(
						"order_sn" => $order_data['order_sn'],
						"admin_id" => Session::get("admin_id"),
						"action_id" => 7,
						"action_result" => 1,
						"comment" => "后台支付优惠券抵消BY(".Session::get("admin_id").")".Session::get("admin_name"),
						"action_time" => time()
						));

					$coup_res = $db->update("lk_coup_list", array("pay_confirm" => 1, "order_sn" => $order_data['order_sn']), array("id" => $order_coupon['id']));
					if($order_res1 && $pay_res1 && $act_res1 && $pay_res && $act_res && $coup_res) {
						$db->pdo->commit();
						Flight::json(array("success" => true, "msg" => "支付成功"));
						die;
					}else{
						$db->pdo->rollBack();
						Flight::json(array("success" => false, "msg" => "支付更新数据失败"));
						die;
					}
	
				}else{
					Flight::json(array("success" => false, "msg" => "优惠券与下单选择的优惠券不符"));
					die;
				}
			}else{
				//现金支付
				$db->pdo->beginTransaction();

				$order_res = $db->update("lk_order", array(
					"amount" => $order_amount,
					"order_amount" => $order_amount,
					"pay_time" => time(),
					"pay_type" => 1,
					"status" => 6,
				), array("order_id"=> $order_id));

				$pay_res = $db->insert("lk_order_pay", array(
					"order_sn" => $order_data['order_sn'],
					"pay_id" => 3,
					"pay_name" => "现金支付",
					"money" => $order_amount,
					"pay_time" => time(),
					"status" => 1,
					"pay_message" => "后台支付现金支付BY(".Session::get("admin_id").")".Session::get("admin_name"),
				));

				$act_res = $db->insert("lk_order_action", array(
					"order_sn" => $order_data['order_sn'],
					"admin_id" => Session::get("admin_id"),
					"action_id" => 7,
					"action_result" => 1,
					"comment" => "后台支付现金支付BY(".Session::get("admin_id").")".Session::get("admin_name"),
					"action_time" => time()
				));

				if($order_res && $pay_res && $act_res) {
					$db->pdo->commit();
					Flight::json(array("success" => true, "msg" => "支付成功"));
					die;
				}else{
					$db->pdo->rollBack();
					Flight::json(array("success" => false, "msg" => "支付更新数据失败"));
					die;
				}

			}

		}else{

			$order_id = Flight::request()->query->order_id;

			Flight::jsrender('/public/js/plugins/jquery.validate.min.js');
			Flight::jsrender('/public/js/plugins/jquery.tagsinput.min.js');
			Flight::jsrender('/public/js/plugins/chosen.jquery.min.js');
			Flight::jsrender('/public/js/order/pay_order.js');
			Flight::render('order/pay_order', array("order_id" => $order_id));
		}

	}

	/**
     * 回访订单
     *
     * @return [type]     [description]
     * @author zhaozl
     * @since  2015-07-18
     */
	public static function visit_order() {

		$data = Flight::request()->data;
		$order_id = isset($data['order_id'])?$data['order_id']:'';

		if($order_id) {

			$db = Flight::get('db');
			$order_info = $db->get("lk_order", "*", array("order_id" => $order_id));

			if($order_info) {
				$order_pay = $db->select("lk_order_pay", "*", array("AND" => array("order_sn" => $order_info['order_sn'], "status" => 1)));
				$order_address = $db->get("lk_order_address", "*", array("order_sn" => $order_info['order_sn']));
				$order_ser = $db->get("lk_order_service", "*", array("order_sn" => $order_info['order_sn']));

				$pay_info = "";
				if($order_pay) {
					$totalMoney = 0;
					foreach ($order_pay as $pay) {
						$pay_info .= "【{$pay['pay_name']}】{$pay['money']}元<br>";
						$totalMoney += $pay['money'];
					}
					$pay_info .= "总支付金额{$totalMoney}元";
				}

				$strHtml = '<div id="dialog" title="订单回访"><form class="backform stdform stdform2 formtable" action="" method="post">';
				$strHtml .= '<input type="hidden" name="order_id" id="order_id" value="'.$order_id.'"/>';
				$strHtml .= '<table width="100%" cellspacing="0">
                                <tr>
                                    <td>
                                        <p>
                                            <label>服务内容</label>
                                            <span class="field">'.$order_ser['service_name'].'【'.$order_ser['item_name'].'】'.'</span>
                                        </p>
                                    </td>
                                    <td>
                                        <p>
                                            <label>服务时间</label>
                                            <span class="field">'.date('Y-m-d H:i:s', $order_info['work_time']).'</span>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p>
                                            <label>联系人</label>
                                            <span class="field">'.$order_address['contact'].'【'.$order_address['phone_mob'].'】'.'</span>
                                        </p>
                                    </td>
                                    <td>
                                        <p>
                                            <label>地址</label>
                                            <span class="field">'.$order_address['region_name'].$order_address['address'].'</span>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <p>
                                            <label for="">服务商家:</label>
                                            <span class="field">'.$order_info['shop_name'].'</span>
                                        </p>
                                    </td>
                                    <td>
                                        <p>
                                            <label for="">服务人员:</label>
                                            <span class="field">
                                                '.$order_info['staff_name'].'
                                            </span>
                                        </p>
                                    </td>
                                </tr>
								<tr>
									<td>
										<p>
											<label for="">订单总金额</label>
											<span class="field">'.$order_info['amount'].'</span>
										</p>
									</td>
									<td>
										<p>
											<label for="">支付详情</label>
											<span class="field">'.$pay_info.'</span>
										</p>
									</td>
								</tr>
								<tr>
									<td>
										<p>
											<label for="">补差金额(没有请填0)</label>
											<span class="field">
												<input type="text" name="money" id="money" class="smallinput" placeholder="补差金额" style="width: 200px;"/>
											</span>
										</p>
									</td>
									<td>
										<p>
											<label for="">服务评价</label>
											<span class="field">
												<select name="degree" id="degree">
								            		<option value="5">完美服务</option>
								            		<option value="4">服务较好</option>
								            		<option value="3">服务一般</option>
								            		<option value="1">服务很差</option>
								            		<option value="-1">投诉</option>
								            	</select>
											</span>
										</p>
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<p>
											<label>回访评论记录：</label>
											<span class="field">
						                    	<textarea name="comment" id="comment" cols="30" rows="5"></textarea>
						                    </span>
										</p>
									</td>
								</tr>
                            </table></form></div>';
            	echo $strHtml;

			}else{
				echo "找不到订单信息";
			}

		}else{
			echo "请传入订单";
		}

	}

	/**
	 * 回访订单信息
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-23
	 */
	public static function finish_visit() {

		if(IS_POST) {
			$data = Flight::request()->data;

			$order_id = isset($data['order_id'])?$data['order_id']:'';
			$money = isset($data['money']) && $data['money'] > 0?sprintf("%.2f",$data['money']):0;
			$comment = isset($data['comment'])?$data['comment']:'';
			$degree = isset($data['degree'])?$data['degree']:'';

			if(!$order_id) {
				Flight::json(array("success" => false, "msg" => "未获取到订单号"));
				die;
			}

			if(!$comment) {
				Flight::json(array("success" => false, "msg" => "请填写回访记录"));
				die;
			}

			$db = Flight::get("db");
			$order_data = $db->get("lk_order", "*", array("AND" => array("order_id" => $order_id, "status" => array("6"))));
			if(!$order_data) {
				Flight::json(array("success" => false, "msg" => "订单状态不符合条件"));
				die;
			}
			
			$db->pdo->beginTransaction();
			// 如果额外金额
			if($money > 0) {
				$pay_order_ex = $db->update("lk_order", array("amount[+]" => $money, "order_amount[+]" => $money), array("order_id" => $order_id));
				// 生成支付单
				$pay_order_info = $db->insert("lk_order_pay", array(
					"order_sn" => $order_data['order_sn'],
					"pay_id" => 3,
					"pay_name" => "现金支付(回访)",
					"money" => $money,
					"pay_time" => time(),
					"status" => 1,
					"pay_message" => "后台支付回访支付BY(".Session::get("admin_id").")".Session::get("admin_name"),
				));

				$act_res1 = $db->insert("lk_order_action", array(
					"order_sn" => $order_data['order_sn'],
					"admin_id" => Session::get("admin_id"),
					"action_id" => 7,
					"action_result" => 1,
					"comment" => "后台支付回访支付BY(".Session::get("admin_id").")".Session::get("admin_name"),
					"action_time" => time()
				));

			}else{
				$pay_order_ex = 1;
				$pay_order_info = 1;
				$act_res1 = 1;
			}

			$order_res = $db->update("lk_order", array("status" => 7, "finish_time" => time()), array("order_id" => $order_id));

			$act_res = $db->insert("lk_order_action", array(
				"order_sn" => $order_data['order_sn'],
				"admin_id" => Session::get("admin_id"),
				"action_id" => 8,
				"action_result" => 1,
				"comment" => "回访记录(".Session::get("admin_id").")".Session::get("admin_name")."--".$comment,
				"action_time" => time()
				));

			$comment_id = $db->insert("lk_staff_comment", array(
				"order_sn" => $order_data['order_sn'],
				"staff_id" => $order_data['staff_id'],
				"user_id" => $order_data['buyer_id'],
				"degree" => $degree,
				"comment" => $comment,
				"add_time" => time()
			));

			if($order_res && $act_res && $comment_id && $pay_order_ex && $pay_order_info && $act_res1) {
				$db->pdo->commit();
				Flight::json(array("success" => true, "msg" => "回访成功"));
				die;
			}else{
				$db->pdo->rollBack();
				Flight::json(array("success" => false, "msg" => "更新数据失败"));
				die;
			}

		}else{
			
			echo "非法访问";
			
		}
	}

	/**
	 * 限单功能
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-21
	 */	
	public static function limitOrder() {

		$data = Flight::request()->data;
		$coup_id = isset($data['coup_id'])?$data['coup_id']:'';
		$item_id = isset($data['item_id'])?$data['item_id']:'';
		$need_time = isset($data['need_time'])?$data['need_time']:'';
		if(!$need_time) {
			Flight::json(array("success" => false, "msg" => "缺少预约时间"));
			die;
		}

		if($coup_id || $item_id) {
			$db = Flight::get("db");

			if($coup_id) {
				$item_id = $db->get("lk_coup_list", "item_id", array("AND" => array("id" => $coup_id, "isuse" => 1, "ischecked" => 0, "order_sn" => "")));
			}

			$startTime = date("Y-m-d", strtotime($need_time)).' 00:00:00';
			$endTime = date("Y-m-d", strtotime($need_time)).' 23:59:59';

			// 限单功能
			$limit_order = $db->get("lk_service_item", "limit_order", array("item_id" => $item_id));
			if($limit_order > 0) {
				// 当天已经下单量
				$hasOrder = $db->count("lk_order", array("[>]lk_order_service" => "order_sn"), "order_id", array("AND" =>array(
					"item_id" => $item_id,
					"need_time[>]" => $startTime,
					"need_time[<]" => $endTime,
					)));
				if($hasOrder >= $limit_order) {
					Flight::json( array( "success" => false, "msg" => "无法下单，预约时间当天订单已达到限单量" ));
					die;
				}
			}
			
			Flight::json(array("success" => true, "msg" => "当日剩余单量".($limit_order - $hasOrder)));

		}else{
			Flight::json(array("success" => false, "msg" => "缺少数据无法检测限单量"));
		}

	}

	/**
	 * 取消分配
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-21
	 */
	public static function cancelAssign() {

		$data = Flight::request()->data;
    	$order_id = isset($data['order_id'])?$data['order_id']:'';
    	$reason = isset($data['reason'])?$data['reason']:'';

    	if($order_id) {
    		$db = Flight::get("db");
    		$order_info = $db->get("lk_order", array("staff_id", "order_sn"), array("order_id" => $order_id));
    		if(isset($order_info['staff_id']) && $order_info['staff_id']) {
    			// 更新订单状态
    			$db->update("lk_order", array(
    				"staff_id" => 0,
    				"staff_name" => "",
    				"shop_id" => 0,
    				"shop_name" => "",
    				"status" => 1,
    			), array("order_id" => $order_id));
    			// 添加操作记录
    			$db->insert("lk_order", array(
    				"order_sn" => $order_info['order_sn'],
    				"admin_id" => Session::get('admin_id'),
    				"action_id" => 9,
    				"action_result" => 1,
    				"comment" => trim($reason),
    				"action_time" => time(),
    			));

    			// 短信通知取消分派
    			$staff_info = $db->get("lk_shopstaff", array("staff_name", "phone_mob"), array("staff_id" => $order_info['staff_id']));

    			if($staff_info && $staff_info['phone_mob'] && preg_match("/1[3578]{1}\d{9}$/",$staff_info['phone_mob'])) {
  					sendMsg($staff_info['phone_mob'], "尊敬的{$staff_info['staff_name']}: 之前分派给您的工单{$order_info['order_sn']}已重新指派，无需跟进，谢谢!");
    			}

    			Flight::json(array("success" => true, "msg" => "操作成功"));

    		}else{
    			Flight::json(array("success" => false, "msg" => "找不到订单号"));
    		}

    	}else{
    		Flight::json(array("success" => false, "msg" => "缺少订单号"));
    	}

	}

	/**
	 * 取消订单
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-21
	 */
	public static function cancelOrder() {

		$data = Flight::request()->data;
    	$order_id = isset($data['order_id'])?$data['order_id']:'';
    	$reason = isset($data['reason'])?$data['reason']:'';

    	if($order_id) {
    		$db = Flight::get("db");
    		$order_info = $db->get("lk_order", array("staff_id", "order_sn"), array("order_id" => $order_id));
    		if(isset($order_info['order_sn']) && $order_info['order_sn']) {
    			// 更新订单状态
    			$db->update("lk_order", array(
    				"status" => 3,
    			), array("order_id" => $order_id));
    			// 添加操作记录
    			$db->insert("lk_order_action", array(
    				"order_sn" => $order_info['order_sn'],
    				"admin_id" => Session::get('admin_id'),
    				"action_id" => 10,
    				"action_result" => 1,
    				"comment" => trim($reason),
    				"action_time" => time(),
    			));

    			// 返回之前的优惠券
    			$db->update("lk_coup_list", array("ischecked" => 0, "usedtime" => NULL, "order_sn" => ''), array("order_sn" => $order_info['order_sn']));

    			// 短信通知取消分派
    			$staff_info = $db->get("lk_shopstaff", array("staff_name", "phone_mob"), array("staff_id" => $order_info['staff_id']));

    			if($staff_info && $staff_info['phone_mob'] && preg_match("/1[3578]{1}\d{9}$/",$staff_info['phone_mob'])) {
  					sendMsg($staff_info['phone_mob'], "尊敬的{$staff_info['staff_name']}: 之前分派给您的工单{$order_info['order_sn']}已取消，无需跟进，谢谢!");
    			}

    			Flight::json(array("success" => true, "msg" => "操作成功"));

    		}else{
    			Flight::json(array("success" => false, "msg" => "找不到订单号"));
    		}

    	}else{
    		Flight::json(array("success" => false, "msg" => "缺少订单号"));
    	}

	}

	/**
	 * 获取订单优惠券，来确认支付方式
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-21
	 */
	public static function getOrderCoupon() {

		$data = Flight::request()->data;
    	$order_id = isset($data['order_id'])?$data['order_id']:'';

    	if($order_id) {
    		$db = Flight::get("db");
    		$order_info = $db->get("lk_order", array("staff_id", "order_sn"), array("order_id" => $order_id));

    		if(isset($order_info['order_sn']) && $order_info['order_sn']) {

    			// 返回之前的优惠券
    			$code = $db->get("lk_coup_list", array("id","commoncode", "codepassword"), array("order_sn" => $order_info['order_sn']));

    			if($code) {
    				Flight::json(array("success" => true, "hascoupon" => true, "commoncode" => $code['commoncode'], 
    					"codepassword" => $code['codepassword'], "coup_id" => $code['id']));
    			}else{
    				Flight::json(array("success" => true, "hascoupon" => false));
    			}
    		}else{
    			Flight::json(array("success" => false, "msg" => "找不到订单号"));
    		}
    	}else{
    		Flight::json(array("success" => false, "msg" => "缺少订单号"));
    	}
	}


	/**
	 * 更改操作时间
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-07-22
	 */
	public static function change_worktime() {

		$data = Flight::request()->data;
    	$order_id = isset($data['order_id'])?$data['order_id']:'';
    	$work_time = isset($data['work_time'])?strtotime($data['work_time']):'';
    	$comment = isset($data['comment'])?$data['comment']:'';

    	if($order_id) {
    		$db = Flight::get("db");
    		$order_sn = $db->get("lk_order", "order_sn", array("order_id" => $order_id));

    		if($order_sn) {

    			// 返回之前的优惠券
    			$work = $db->update("lk_order", array("work_time" => $work_time), array("order_sn" => $order_sn));

    			if($work) {

    				$db->insert("lk_order_action", array(
    					"order_sn" => $order_sn,
    					"admin_id" => Session::get('admin_id'),
    					"action_id" => 11,
    					"action_result" => 1,
    					"comment" => $comment,
    					"action_time" => time(),
    				));

    				Flight::json(array("success" => true, "msg" => "操作成功"));
    			}else{
    				Flight::json(array("success" => true, "msg" => "操作失败"));
    			}
    		}else{
    			Flight::json(array("success" => false, "msg" => "找不到订单号"));
    		}
    	}else{
    		Flight::json(array("success" => false, "msg" => "缺少订单号"));
    	}

	}

	/**
	 * 获取最近一个月的订单
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-08-03
	 */
	public static function getRecentOrder() {
		
		$data = Flight::request()->data;

		$phone = isset($data['phone'])?$data['phone']:'';
		if($phone) {
			$endtime = time();
			$starttime = time() - 30*24*60*60;

			$sql = "SELECT A.order_sn, A.add_time, A.need_time, A.status, B.phone_mob, B.contact, B.address, B.region_name, C.service_name, C.item_name FROM lk_order A 
			LEFT JOIN lk_order_address B USING(`order_sn`) 
			LEFT JOIN lk_order_service C USING(`order_sn`) 
			LEFT JOIN lk_user D  ON A.buyer_id = D.user_id 
			WHERE (B.phone_mob = '{$phone}' OR B.phone_tel = '{$phone}' OR D.phone_mob = '{$phone}') 
			AND A.add_time > $starttime AND A.add_time < $endtime ORDER BY A.add_time DESC";

			$res = Flight::get('db')->query($sql)->fetchAll();

			if($res) {
				$result = array();
				foreach ($res as $key => $value) {
					$res[$key]['status'] = self::$order_status[$value['status']];
					$res[$key]['add_time'] = date("Y-m-d H:i:s", $value['add_time']);
					$res[$key]['need_time'] = date("Y-m-d H:i:s", $value['need_time']);
				}

				Flight::json(array("success" => true, "data" => $res));
			}else{
				Flight::json(array("success" => false, "msg" => "找不到相关订单"));
			}
		}else{
			Flight::json(array("success" => false, "msg" => "为获取到手机号码"));
		}

	}

	/**
	 * 已完成订单
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-08-04
	 */
	public static function completeorder() {
		Flight::cssrender('/public/css/jquery.datagrid.css');
		Flight::jsrender('/public/js/plugins/jquery.dataGrid.js');
		Flight::jsrender('/public/js/order/completeorder.js');

		Flight::render('order/completeorder');
	}

	/**
	 * 获取订单
	 *
	 * @return [type]     [description]
	 * @author zhaozl
	 * @since  2015-08-06
	 */
	public static function getOrder() {

		$data = Flight::request()->query;

		$start = isset($data['start'])?$data['start']:0;
		$length = isset($data['length'])?$data['length']:20;

		$search = isset($data['search'])?$data['search']:'';
		$type = isset($data['type'])?$data['type']:'';

		$serType = isset($data->serType)?$data->serType:'';
		$startTime = isset($data->startTime)?$data->startTime:'';
		$endTime = isset($data->endTime)?$data->endTime:'';
		$orderSource = isset($data->orderSource)?$data->orderSource:'';

		$db = Flight::get('db');
		$sql = "SELECT
			lk_order.*, lk_order_service.service_name,
			lk_order_service.item_name,
			lk_order_service.item_id,
			lk_order_service.service_id,
			lk_order_address.contact,
			lk_order_address.region_id,
			lk_order_address.region_name,
			lk_order_address.address,
			lk_order_address.phone_mob,
			lk_order_address.phone_tel,
			lk_staff_comment. degree,
			lk_staff_comment.`comment`,
			lk_shopstaff.phone_mob AS staff_phone
		FROM
			lk_order
		LEFT JOIN lk_order_service ON lk_order.order_sn = lk_order_service.order_sn
		LEFT JOIN lk_order_address ON lk_order.order_sn = lk_order_address.order_sn
		LEFT JOIN lk_staff_comment ON lk_order.order_sn = lk_staff_comment.order_sn
		LEFT JOIN lk_shopstaff ON lk_order.staff_id = lk_shopstaff.staff_id
		WHERE 1=1";

		switch ($type) {
			case '1':
			// 待审核订单
				$sql .= " AND lk_order.status = 0";
				break;
			case '2':
			// 带接单订单
				$sql .= " AND lk_order.status = 1";
				break;
			case '3':
			// 投诉订单
				$sql .= " AND lk_staff_comment.degree = -1";

				if($startTime) {
					$sql .= " AND lk_order.add_time >= {$startTime}";
				}

				if($endTime) {
					$sql .= " AND lk_order.add_time >= {$endTime}";
				}
				break;
			case '4':
				$sql .= " AND lk_order.status in ('4', '5')";
				break;
			case '5':
				$sql .= " AND lk_order.status = 6";
				break;
			case '6':
				$sql .= " AND lk_order.status = 7";
			default:
				if($startTime) {
					$sql .= " AND lk_order.add_time >= {$startTime}";
				}

				if($endTime) {
					$sql .= " AND lk_order.add_time >= {$endTime}";
				}
			// 全部订单
				break;
		}

		if($orderSource && $orderSource > 0) {
			// 今日工单
			if($orderSource == 1) {
				$todayStart = strtotime(date('Y-m-d'));
				$todayEnd = strtotime(date('Y-m-d')) + 86399;

				$sql .= " AND lk_order.work_time >= {$todayStart} AND lk_order.work_time <= {$todayEnd} ";

			}else if($orderSource == 2) {
				// 明日工单
				$towStart = strtotime(date('Y-m-d',strtotime('+1 day')));
				$towEnd = $towStart + 86399;

				$sql .= " AND lk_order.work_time >= {$towStart} AND lk_order.work_time <= {$towEnd} ";
			}
		}
		
		if($serType > 0) {
			$sql .= " AND lk_order_service.item_id = {$serType}";
		}

		if($search) {
			$sql .= " AND (lk_order.order_sn LIKE '%{$search}%' 
				OR lk_order_address.contact LIKE '%{$search}%' 
				OR lk_order_address.phone_mob LIKE '%{$search}%'
				OR lk_order.shop_name LIKE '%{$search}%'
				OR lk_order.staff_name LIKE '%{$search}%'
				OR lk_shopstaff.phone_mob LIKE '%{$search}%'
				OR lk_order_address.region_name LIKE '%{$search}%'
				OR lk_order_address.address LIKE '%{$search}%'
				)";
		}

		$countSql = $sql;
		$rectotal = count($db->query($countSql)->fetchAll());

		while($start > $rectotal) {
			$start -= $length;
		}

		$sql .= " ORDER BY lk_order.order_id DESC LIMIT {$start},{$length}";

		$res = $db->query($sql)->fetchAll();

		$result = array();
		if($res){
			foreach ($res as $key => $value) {

				if($value['staff_name'] && $value['staff_phone']) {
					$staffinfo =  $value['staff_name'].'【'.$value['staff_phone'].'】';
				}

				if($value['contact'] && ($value['phone_mob'] || $value['phone_tel'])) {
					$userinfo =  $value['contact'].'【'.(isset($value['phone_mob']) && $value['phone_mob']?$value['phone_mob']:$value['phone_tel']).'】';
				}

				$result[] = array(
					'order_sn' => $value['order_sn'],
					'type_name' => $value['service_name']. '·'. $value['item_name'],
					'status' => self::$order_status[$value['status']],
					'shop_name' => $value['shop_name'],
					'statuscode' => $value['status'],
					'staff_name' => $value['staff_name'],
					'staff' => isset($staffinfo)?$staffinfo:'',
					'contact' => $value['contact'],
					'userinfo' => isset($userinfo)?$userinfo:'',
					'degree' => $value['degree'],
					'amount' => $value['amount'],
					'source' => $value['source'],
					'source1' => $value['source'],
					'phone_mob' => isset($value['phone_mob']) && $value['phone_mob']?$value['phone_mob']:$value['phone_tel'],
					'address_detail' => $value['region_name'].$value['address'],
					'need_time' => $value['need_time'] > 0?date('Y-m-d H:i:s', $value['need_time']):'尽快',
					'add_time' => $value['add_time'] > 0?date('Y-m-d H:i:s', $value['add_time']):'',
					'work_time' => $value['work_time'] > 0?date('Y-m-d H:i:s', $value['work_time']):'',
					'remark' => $value['remark'],
					'order_id' => $value['order_id'],
					'comment' => $value['comment'],
					'staff_phone' => $value['staff_phone'],
					'admin_name' => $value['admin_name'],
					'item_id' => $value['item_id'],
					'service_id' => $value['service_id'],
				);
			}
		}


		$iTotalRecords = $db->count('lk_order');
		Flight::json(array(
			'data' => $result, 
			"start" => $start,
			"total" => $rectotal
		));

	}

}