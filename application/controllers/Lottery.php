<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * Lottery/LTR 抽奖类
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Lottery extends MY_Controller
	{
		/**
		 * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
		 */
		protected $names_to_sort = array(
			'lottery_id', 'name', 'url_name', 'description', 'extra', 'url_image', 'url_audio', 'url_video', 'url_video_thumb', 'max_user_total', 'max_user_daily', 'chance_shared_daily', 'exturl_before', 'exturl_ongoing', 'exturl_after', 'content_css', 'time_start', 'time_end', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 
		);
		
		/**
	     * @var array 可根据最大值筛选的字段名
	     */
	    protected $max_needed = array(
	        'time_create', 'score',
	    );

	    /**
	     * @var array 可根据最小值筛选的字段名
	     */
	    protected $min_needed = array(
	        'time_create', 'score',
	    );
		
		/**
		 * 可作为排序条件的字段名
		 */
		protected $names_to_order = array(
			'lottery_id', 'name', 'url_name', 'description', 'extra', 'url_image', 'url_audio', 'url_video', 'url_video_thumb', 'max_user_total', 'max_user_daily', 'chance_shared_daily', 'exturl_before', 'exturl_ongoing', 'exturl_after', 'content_css', 'time_start', 'time_end', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 
		);

		/**
		 * 可作为查询结果返回的字段名
         *
         * 应删除time_create等需在MY_Controller通过names_return_for_admin等类属性声明的字段名
		 */
		protected $names_to_return = array(
			'lottery_id', 'name', 'url_name', 'description', 'extra', 'url_image', 'url_audio', 'url_video', 'url_video_thumb', 'max_user_total', 'max_user_daily', 'chance_shared_daily', 'exturl_before', 'exturl_ongoing', 'exturl_after', 'content_css', 'time_start', 'time_end', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 
		);

		/**
		 * 创建时必要的字段名
		 */
		protected $names_create_required = array(
			'user_id',
            'lottery_id', 'name', 'url_name', 'description', 'extra', 'url_image', 'url_audio', 'url_video', 'url_video_thumb', 'max_user_total', 'max_user_daily', 'chance_shared_daily', 'exturl_before', 'exturl_ongoing', 'exturl_after', 'content_css', 'time_start', 'time_end', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 
		);

		/**
		 * 可被编辑的字段名
		 */
		protected $names_edit_allowed = array(
			'lottery_id', 'name', 'url_name', 'description', 'extra', 'url_image', 'url_audio', 'url_video', 'url_video_thumb', 'max_user_total', 'max_user_daily', 'chance_shared_daily', 'exturl_before', 'exturl_ongoing', 'exturl_after', 'content_css', 'time_start', 'time_end', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 
		);

		/**
		 * 完整编辑单行时必要的字段名
		 */
		protected $names_edit_required = array(
			'user_id', 'id',
            'lottery_id', 'name', 'url_name', 'description', 'extra', 'url_image', 'url_audio', 'url_video', 'url_video_thumb', 'max_user_total', 'max_user_daily', 'chance_shared_daily', 'exturl_before', 'exturl_ongoing', 'exturl_after', 'content_css', 'time_start', 'time_end', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 
		);

		/**
		 * 编辑单行特定字段时必要的字段名；若与MY_Controller声明的同名类属性相同，可删除此处
		 */
		protected $names_edit_certain_required = array(
			'user_id', 'id', 'name', 'value',
		);

		/**
		 * 编辑多行特定字段时必要的字段名；若与MY_Controller声明的同名类属性相同，可删除此处
		 */
		protected $names_edit_bulk_required = array(
			'user_id', 'ids', 'operation', 'password',
		);

		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'lottery'; // 这里……
			$this->id_name = 'lottery_id'; // 这里……

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		} // end __construct

		/**
		 * 0 计数
		 */
		public function count()
		{
			// 生成筛选条件
			$condition = $this->condition_generate();
            // 类特有筛选项
            $condition = $this->advanced_sorter($condition);

            // 商家仅可操作自己的数据
            if ($this->app_type === 'biz') $condition['biz_id'] = $this->input->post('biz_id');

			// 获取列表；默认可获取已删除项
			$count = $this->basic_model->count($condition);

			if ($count !== FALSE):
				$this->result['status'] = 200;
				$this->result['content']['count'] = $count;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '没有符合条件的数据';

			endif;
		} // end count

		/**
		 * 1 列表/基本搜索
		 */
		public function index()
		{	
			// 检查必要参数是否已传入
			$required_params = array();
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);
				if ( !isset( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 生成筛选条件
			$condition = $this->condition_generate();
            // 类特有筛选项
            $condition = $this->advanced_sorter($condition);

			// 排序条件
			$order_by = NULL;
			foreach ($this->names_to_order as $sorter):
				if ( !empty($this->input->post('orderby_'.$sorter)) )
					$order_by[$sorter] = $this->input->post('orderby_'.$sorter);
			endforeach;

            // 限制可返回的字段
            if ($this->app_type === 'client'):
                $condition['time_delete'] = 'NULL'; // 客户端仅可查看未删除项
            else:
                $this->names_to_return = array_merge($this->names_to_return, $this->names_return_for_admin);
            endif;
            $this->db->select( implode(',', $this->names_to_return) );

			// 获取列表；默认可获取已删除项
            $ids = $this->input->post('ids'); // 可以CSV格式指定需要获取的信息ID们
            if ( empty($ids) ):
                $items = $this->basic_model->select($condition, $order_by);
            else:
                // 限制可返回的字段
                $this->db->select( implode(',', $this->names_to_return) );
                $items = $this->basic_model->select_by_ids($ids);
            endif;

			if ( !empty($items) ):
				$this->result['status'] = 200;
				$this->result['content'] = $items;

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '没有符合条件的数据';
			
			endif;
		} // end index

		/**
		 * 2 详情
		 */
		public function detail()
		{
			// 检查必要参数是否已传入
			$id = $this->input->post('id');
			if ( !isset($id) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

            if ($this->app_type === 'client') $condition['time_delete'] = 'NULL';

			// 限制可返回的字段
			$this->db->select( implode(',', $this->names_to_return) );
			
			// 获取特定项；默认可获取已删除项
			$item = $this->basic_model->select_by_id($id);
			if ( !empty($item) ):
				$this->result['status'] = 200;
				$this->result['content'] = $item;

                // 获取候选项标签信息
                $conditions = array(
                    'lottery_id' => $id,
                );
                $order_by['index_id'] = 'DESC';

                if ($this->app_type === 'client') $conditions['status'] = '正常'; // 客户端仅获取正常状态信息
                $this->result['content']['prizes'] = $this->get_items('lottery_prize', 'prize_id', $conditions, $order_by);

			else:
				$this->result['status'] = 414;
				$this->result['content']['error']['message'] = '没有符合条件的数据';

			endif;
		} // end detail

		/**
		 * 3 创建
		 */
		public function create()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin', 'biz', 'client'); // 客户端类型
			$platform_allowed = array('ios', 'android', 'weapp', 'web'); // 客户端平台
			$min_version = '0.0.1'; // 最低版本要求
			$this->client_check($type_allowed, $platform_allowed, $min_version);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_create_required;
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);
				if ( empty( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
						$this->form_validation->set_rules('lottery_id', '抽奖ID', 'trim|required');
			$this->form_validation->set_rules('name', '名称', 'trim|required');
			$this->form_validation->set_rules('url_name', '自定义URL', 'trim|');
			$this->form_validation->set_rules('description', '描述', 'trim|');
			$this->form_validation->set_rules('extra', '补充描述', 'trim|');
			$this->form_validation->set_rules('url_image', '形象图', 'trim|');
			$this->form_validation->set_rules('url_audio', '背景音乐', 'trim|');
			$this->form_validation->set_rules('url_video', '形象视频', 'trim|');
			$this->form_validation->set_rules('url_video_thumb', '形象视频缩略图', 'trim|');
			$this->form_validation->set_rules('max_user_total', '每用户最高总抽奖数', 'trim|required');
			$this->form_validation->set_rules('max_user_daily', '每用户最高日抽奖数', 'trim|required');
			$this->form_validation->set_rules('chance_shared_daily', '每用户每日分享后额外抽奖数', 'trim|');
			$this->form_validation->set_rules('exturl_before', '活动前相关外链', 'trim|');
			$this->form_validation->set_rules('exturl_ongoing', '活动中相关外链', 'trim|');
			$this->form_validation->set_rules('exturl_after', '活动后相关外链', 'trim|');
			$this->form_validation->set_rules('content_css', '自定义样式', 'trim|');
			$this->form_validation->set_rules('time_start', '开始时间', 'trim|required');
			$this->form_validation->set_rules('time_end', '结束时间', 'trim|required');
			$this->form_validation->set_rules('time_create', '创建时间', 'trim|required');
			$this->form_validation->set_rules('time_delete', '删除时间', 'trim|');
			$this->form_validation->set_rules('time_edit', '最后操作时间', 'trim|required');
			$this->form_validation->set_rules('creator_id', '创建者ID', 'trim|required');
			$this->form_validation->set_rules('operator_id', '最后操作者ID', 'trim|');


			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要创建的数据；逐一赋值需特别处理的字段
				$data_to_create = array(
					'creator_id' => $user_id,

                    //'name' => empty($this->input->post('name'))? NULL: $this->input->post('name'),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'lottery_id', 'name', 'url_name', 'description', 'extra', 'url_image', 'url_audio', 'url_video', 'url_video_thumb', 'max_user_total', 'max_user_daily', 'chance_shared_daily', 'exturl_before', 'exturl_ongoing', 'exturl_after', 'content_css', 'time_start', 'time_end', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 
				);
				foreach ($data_need_no_prepare as $name)
                    $data_to_create[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

				$result = $this->basic_model->create($data_to_create, TRUE);
				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['id'] = $result;
					$this->result['content']['message'] = '创建成功';

				else:
					$this->result['status'] = 424;
					$this->result['content']['error']['message'] = '创建失败';

				endif;
			endif;
		} // end create

		/**
		 * 4 编辑单行数据
		 */
		public function edit()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin', 'biz', 'client'); // 客户端类型
			$platform_allowed = array('ios', 'android', 'weapp', 'web'); // 客户端平台
			$min_version = '0.0.1'; // 最低版本要求
			$this->client_check($type_allowed, $platform_allowed, $min_version);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_edit_required;
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);
				if ( empty( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
						$this->form_validation->set_rules('lottery_id', '抽奖ID', 'trim|required');
			$this->form_validation->set_rules('name', '名称', 'trim|required');
			$this->form_validation->set_rules('url_name', '自定义URL', 'trim|');
			$this->form_validation->set_rules('description', '描述', 'trim|');
			$this->form_validation->set_rules('extra', '补充描述', 'trim|');
			$this->form_validation->set_rules('url_image', '形象图', 'trim|');
			$this->form_validation->set_rules('url_audio', '背景音乐', 'trim|');
			$this->form_validation->set_rules('url_video', '形象视频', 'trim|');
			$this->form_validation->set_rules('url_video_thumb', '形象视频缩略图', 'trim|');
			$this->form_validation->set_rules('max_user_total', '每用户最高总抽奖数', 'trim|required');
			$this->form_validation->set_rules('max_user_daily', '每用户最高日抽奖数', 'trim|required');
			$this->form_validation->set_rules('chance_shared_daily', '每用户每日分享后额外抽奖数', 'trim|');
			$this->form_validation->set_rules('exturl_before', '活动前相关外链', 'trim|');
			$this->form_validation->set_rules('exturl_ongoing', '活动中相关外链', 'trim|');
			$this->form_validation->set_rules('exturl_after', '活动后相关外链', 'trim|');
			$this->form_validation->set_rules('content_css', '自定义样式', 'trim|');
			$this->form_validation->set_rules('time_start', '开始时间', 'trim|required');
			$this->form_validation->set_rules('time_end', '结束时间', 'trim|required');
			$this->form_validation->set_rules('time_create', '创建时间', 'trim|required');
			$this->form_validation->set_rules('time_delete', '删除时间', 'trim|');
			$this->form_validation->set_rules('time_edit', '最后操作时间', 'trim|required');
			$this->form_validation->set_rules('creator_id', '创建者ID', 'trim|required');
			$this->form_validation->set_rules('operator_id', '最后操作者ID', 'trim|');

			// 针对特定条件的验证规则
			if ($this->app_type === '管理员'):
				// ...
			endif;

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit = array(
					'operator_id' => $user_id,

                    //'name' => empty($this->input->post('name'))? NULL: $this->input->post('name'),
				);
				// 自动生成无需特别处理的数据
				$data_need_no_prepare = array(
					'lottery_id', 'name', 'url_name', 'description', 'extra', 'url_image', 'url_audio', 'url_video', 'url_video_thumb', 'max_user_total', 'max_user_daily', 'chance_shared_daily', 'exturl_before', 'exturl_ongoing', 'exturl_after', 'content_css', 'time_start', 'time_end', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id', 
				);
				foreach ($data_need_no_prepare as $name)
                    $data_to_edit[$name] = empty($this->input->post($name))? NULL: $this->input->post($name);

				// 根据客户端类型等条件筛选可操作的字段名
				if ($this->app_type !== 'admin'):
					//unset($data_to_edit['name']);
				endif;

				// 进行修改
				$result = $this->basic_model->edit($id, $data_to_edit);
				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['message'] = '编辑成功';

				else:
					$this->result['status'] = 434;
					$this->result['content']['error']['message'] = '编辑失败';

				endif;
			endif;
		} // end edit
		
		/**
		 * 5 编辑单行数据特定字段
		 *
		 * 修改单行数据的单一字段值
		 */
		public function edit_certain()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin', 'biz', 'client'); // 客户端类型
			$platform_allowed = array('ios', 'android', 'weapp', 'web'); // 客户端平台
			$min_version = '0.0.1'; // 最低版本要求
			$this->client_check($type_allowed, $platform_allowed, $min_version);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

			// 检查必要参数是否已传入
			$required_params = $this->names_edit_certain_required;
			foreach ($required_params as $param):
				${$param} = $this->input->post($param);

                // value 可以为空；必要字段会在字段验证中另行检查
				if ( $param !== 'value' && !isset( ${$param} ) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '必要的请求参数未全部传入';
					exit();
				endif;
			endforeach;

			// 检查目标字段是否可编辑
			if ( ! in_array($name, $this->names_edit_allowed) ):
				$this->result['status'] = 431;
				$this->result['content']['error']['message'] = '该字段不可被修改';
				exit();
			endif;

			// 根据客户端类型检查是否可编辑
			/*
			$names_limited = array(
				'biz' => array('name1', 'name2', 'name3', 'name4'),
				'admin' => array('name1', 'name2', 'name3', 'name4'),
			);
			if ( in_array($name, $names_limited[$this->app_type]) ):
				$this->result['status'] = 432;
				$this->result['content']['error']['message'] = '该字段不可被当前类型的客户端修改';
				exit();
			endif;
			*/

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 动态设置待验证字段名及字段值
			$data_to_validate["{$name}"] = $value;
			$this->form_validation->set_data($data_to_validate);
						$this->form_validation->set_rules('lottery_id', '抽奖ID', 'trim|required');
			$this->form_validation->set_rules('name', '名称', 'trim|required');
			$this->form_validation->set_rules('url_name', '自定义URL', 'trim|');
			$this->form_validation->set_rules('description', '描述', 'trim|');
			$this->form_validation->set_rules('extra', '补充描述', 'trim|');
			$this->form_validation->set_rules('url_image', '形象图', 'trim|');
			$this->form_validation->set_rules('url_audio', '背景音乐', 'trim|');
			$this->form_validation->set_rules('url_video', '形象视频', 'trim|');
			$this->form_validation->set_rules('url_video_thumb', '形象视频缩略图', 'trim|');
			$this->form_validation->set_rules('max_user_total', '每用户最高总抽奖数', 'trim|required');
			$this->form_validation->set_rules('max_user_daily', '每用户最高日抽奖数', 'trim|required');
			$this->form_validation->set_rules('chance_shared_daily', '每用户每日分享后额外抽奖数', 'trim|');
			$this->form_validation->set_rules('exturl_before', '活动前相关外链', 'trim|');
			$this->form_validation->set_rules('exturl_ongoing', '活动中相关外链', 'trim|');
			$this->form_validation->set_rules('exturl_after', '活动后相关外链', 'trim|');
			$this->form_validation->set_rules('content_css', '自定义样式', 'trim|');
			$this->form_validation->set_rules('time_start', '开始时间', 'trim|required');
			$this->form_validation->set_rules('time_end', '结束时间', 'trim|required');
			$this->form_validation->set_rules('time_create', '创建时间', 'trim|required');
			$this->form_validation->set_rules('time_delete', '删除时间', 'trim|');
			$this->form_validation->set_rules('time_edit', '最后操作时间', 'trim|required');
			$this->form_validation->set_rules('creator_id', '创建者ID', 'trim|required');
			$this->form_validation->set_rules('operator_id', '最后操作者ID', 'trim|');


			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();

			else:
				// 需要编辑的数据
				$data_to_edit['operator_id'] = $user_id;
				$data_to_edit[$name] = $value;

				// 获取ID
				$result = $this->basic_model->edit($id, $data_to_edit);

				if ($result !== FALSE):
					$this->result['status'] = 200;
					$this->result['content']['message'] = '编辑成功';

				else:
					$this->result['status'] = 434;
					$this->result['content']['error']['message'] = '编辑失败';

				endif;
			endif;
		} // end edit_certain

		/**
		 * 6 编辑多行数据特定字段
		 *
		 * 修改多行数据的单一字段值
		 */
		public function edit_bulk()
		{
			// 操作可能需要检查客户端及设备信息
			$type_allowed = array('admin', 'biz', 'client'); // 客户端类型
			$platform_allowed = array('ios', 'android', 'weapp', 'web'); // 客户端平台
			$min_version = '0.0.1'; // 最低版本要求
			$this->client_check($type_allowed, $platform_allowed, $min_version);

			// 管理类客户端操作可能需要检查操作权限
			//$role_allowed = array('管理员', '经理'); // 角色要求
			//$min_level = 10; // 级别要求
			//$this->permission_check($role_allowed, $min_level);

            // 此类型方法通用代码块
            $this->common_edit_bulk(TRUE);

			// 验证表单值格式
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			elseif ($this->operator_check() !== TRUE):
				$this->result['status'] = 453;
				$this->result['content']['error']['message'] = '与该ID及类型对应的操作者不存在，或操作密码错误';
				exit();

			else:
				// 需要编辑的数据；逐一赋值需特别处理的字段
				$data_to_edit['operator_id'] = $user_id;

				// 根据待执行的操作赋值待编辑数据
				switch ( $operation ):
					case 'delete':
						$data_to_edit['time_delete'] = date('Y-m-d H:i:s');
						break;
					case 'restore':
						$data_to_edit['time_delete'] = NULL;
						break;
				endswitch;

				// 依次操作数据并输出操作结果
				// 将待操作行ID们的CSV格式字符串，转换为待操作行的ID数组
				$ids = explode(',', $ids);

				// 默认批量处理全部成功，若有任一处理失败则将处理失败行进行记录
				$this->result['status'] = 200;
				foreach ($ids as $id):
					$result = $this->basic_model->edit($id, $data_to_edit);
					if ($result === FALSE):
						$this->result['status'] = 434;
						$this->result['content']['row_failed'][] = $id;
					endif;

				endforeach;

				// 添加全部操作成功后的提示
				if ($this->result['status'] = 200)
					$this->result['content']['message'] = '全部操作成功';

			endif;
		} // end edit_bulk
			
		/**
		 * 以下为工具类方法
		 */

        /**
         * 类特有筛选器
         *
         * @param array $condition 当前筛选条件数组
         * @return array 生成的筛选条件数组
         */
        protected function advanced_sorter($condition = array())
        {
            return $condition;
        } // end advanced_sorter

	} // end class Lottery

/* End of file Lottery.php */
/* Location: ./application/controllers/Lottery.php */
