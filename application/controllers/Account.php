<?php
	defined('BASEPATH') OR exit('此文件不可被直接访问');

	/**
	 * ACT 账户类
	 *
	 * 以API服务形式返回数据列表、详情、创建、单行编辑、单/多行编辑（删除、恢复）等功能提供了常见功能的示例代码
	 * CodeIgniter官方网站 https://www.codeigniter.com/user_guide/
	 *
	 * @version 1.0.0
	 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
	 * @copyright ICBG <www.bingshankeji.com>
	 */
	class Account extends MY_Controller
	{
		public function __construct()
		{
			parent::__construct();

			// 设置主要数据库信息
			$this->table_name = 'user'; // 这里……
			$this->id_name = 'user_id'; // 这里……
			$this->names_to_return[] = 'user_id'; // 还有这里，OK，这就可以了

			// 主要数据库信息到基础模型类
			$this->basic_model->table_name = $this->table_name;
			$this->basic_model->id_name = $this->id_name;
		}

		/**
		 * ACT1 短信登录/注册
		 */
		public function login_sms()
		{
			// 验证短信正确性
			$this->verify_sms();

			// 准备最后登录信息
			$login_info['last_login_ip'] = empty($this->input->post('user_ip'))? $this->input->ip_address(): $this->input->post('user_ip'); // 优先检查请求是否来自APP
			$login_info['last_login_timestamp'] = time();

			// 获取用户/检查用户是否存在
			$user_info = $this->check_mobile( $this->input->post('mobile') );

			// 若用户存在，返回用户信息；若用户不存在，创建用户。
			if ( !empty($user_info) ):
				// 更新最后登录信息
				@$this->basic_model->edit($user_info['user_id'], $login_info);

				// 不返回真实密码信息
				if ( !empty($user_info['password']) ) $user_info['password'] = 'set';

				$this->result['status'] = 200;
				$this->result['content'] = array_merge($user_info, $login_info);

			else:
				// 创建用户
				$data_to_create['mobile'] = $this->input->post('mobile');
				$data_to_create = array_merge($data_to_create, $login_info);
				$result = $this->user_create($data_to_create);
				if ( !empty($result) ):
					// 获取用户信息
					$item = $this->basic_model->select_by_id($result);
					// 不返回真实密码信息
					if ( !empty($item['password']) ) $item['password'] = 'set';

					$this->result['status'] = 200;
					$this->result['content'] = $item;

				else:
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '用户创建失败';

				endif;

			endif;
		} // end login_sms
		
		/**
		 * ACT2 密码设置
		 */
		public function password_set()
		{
			// 检查必要参数是否已传入
			$required_params = array('user_id', 'password', 'password_confirm');
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
			// 待验证的表单项
			$this->form_validation->set_rules('user_id', '用户ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');
			$this->form_validation->set_rules('password_confirm', '确认密码', 'trim|required|matches[password]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
				// 获取用户/检查用户是否存在
				$user_info = $this->basic_model->select_by_id($user_id);

				if ( empty($user_info) ):
					// 若已设置密码，则进行提示
					$this->result['status'] = 414;
					$this->result['content']['error']['message'] = '用户不存在';

				elseif ( !empty($user_info['password']) ):
					// 若已设置密码，则进行提示
					$this->result['status'] = 411;
					$this->result['content']['error']['message'] = '该用户已设置过密码，如需修改应通过“密码修改”进行操作';

				else:
					// 设置密码并更新登录信息
					$data_to_edit = array(
						'password' => sha1($password),
						'last_login_timestamp' => time(),
						'last_login_ip' => empty($this->input->post('user_ip'))? $this->input->ip_address(): $this->input->post('user_ip'),
					);
					
					// 更新资料
					$result = $this->basic_model->edit($user_id, $data_to_edit);
					if ($result !== FALSE):
						$this->result['status'] = 200;
						$this->result['content'] = '密码设置成功';

					else:
						$this->result['status'] = 434;
						$this->result['content']['error']['message'] = '密码设置失败';

					endif;
				endif;
				
			endif;
		} // end password_set

		/**
		 * ACT3 密码修改
		 */
		public function password_change()
		{
			// 检查必要参数是否已传入
			$required_params = array('user_id', 'password_current', 'password', 'password_confirm');
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
			// 待验证的表单项
			$this->form_validation->set_rules('user_id', '用户ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('password_current', '原密码', 'trim|required|min_length[6]|max_length[20]');
			$this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');
			$this->form_validation->set_rules('password_confirm', '确认密码', 'trim|required|matches[password]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
				// 获取用户/检查用户是否存在
				$user_info = $this->basic_model->select_by_id($user_id);

				if ( empty($user_info) ):
					// 若已设置密码，则进行提示
					$this->result['status'] = 414;
					$this->result['content']['error']['message'] = '用户不存在';

				elseif ( empty($user_info['password']) ):
					// 若未设置密码，则进行提示
					$this->result['status'] = 411;
					$this->result['content']['error']['message'] = '该用户未设置过密码，如需设置应通过“密码设置”进行操作';
					
				elseif ( $user_info['password'] !== SHA1($password_current) ):
					// 若原密码输入错误，则进行提示
					$this->result['status'] = 401;
					$this->result['content']['error']['message'] = '原密码错误';
				
				elseif ( $user_info['password'] === SHA1($password) ):
					// 若新密码与原密码相同，则进行提示
					$this->result['status'] = 401;
					$this->result['content']['error']['message'] = '新密码应与原密码不同';

				else:
					// 设置密码并更新登录信息
					$data_to_edit = array(
						'password' => sha1($password),
						'last_login_timestamp' => time(),
						'last_login_ip' => empty($this->input->post('user_ip'))? $this->input->ip_address(): $this->input->post('user_ip'),
					);

					// 更新资料
					$result = $this->basic_model->edit($user_id, $data_to_edit);
					if ($result !== FALSE):
						$this->result['status'] = 200;
						$this->result['content'] = '密码修改成功';

					else:
						$this->result['status'] = 434;
						$this->result['content']['error']['message'] = '密码修改失败';

					endif;
				endif;
				
			endif;
		} // end password_change
		
		/**
		 * ACT4 密码登录
		 *
		 * @params string $password 登录密码
		 * @params string $mobile 手机号
		 */
		public function login()
		{
			// 检查必要参数是否已传入
			$password = $this->input->post('password');
			if ( empty($password) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '必要的请求参数未传入';
				exit();
			endif;

			// 手机号及Email须至少传入一项
			$mobile = $this->input->post('mobile');
			$email = $this->input->post('email');
			if ( empty($mobile) && empty($email) ):
				$this->result['status'] = 400;
				$this->result['content']['error']['message'] = '手机号及Email须至少传入一项';
				exit();
			endif;

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 待验证的表单项
			$this->form_validation->set_rules('mobile', '手机号', 'trim|exact_length[11]|is_natural_no_zero');
			$this->form_validation->set_rules('email', 'Email', 'trim|max_length[40]|valid_email');
			$this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
				// 获取用户/检查用户是否存在
				if ( !empty($mobile) ):
					$user_info = $this->check_mobile($mobile);
				else:
					$user_info = $this->check_email($email);
				endif;

				// 若用户存在，检查密码正确性。
				if ( !empty($user_info) ):
					if ($user_info['password'] === sha1($password)):
						// 更新最后登录信息
						$login_info['last_login_ip'] = empty($this->input->post('user_ip'))? $this->input->ip_address(): $this->input->post('user_ip'); // 优先检查请求是否来自APP
						$login_info['last_login_timestamp'] = time();
						@$this->basic_model->edit($user_info['user_id'], $login_info);

						// 不返回真实密码信息
						if ( !empty($user_info['password']) ) $user_info['password'] = 'set';
						
						$this->result['status'] = 200;
						$this->result['content'] = array_merge($user_info, $login_info);

					else:
						$this->result['status'] = 401;
						$this->result['content']['error']['message'] = '密码错误';

					endif;

				else:
					$this->result['status'] = 414;
					$this->result['content']['error']['message'] = '用户不存在';

				endif;

			endif;
		} // end login

		/**
		 * ACT5 密码重置
		 */
		public function password_reset()
		{
			// 验证短信正确性
			$this->verify_sms();

			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 待验证的表单项
			$this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');
			$this->form_validation->set_rules('password_confirm', '确认密码', 'trim|required|matches[password]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
				// 准备最后登录信息
				$login_info['last_login_ip'] = empty($this->input->post('user_ip'))? $this->input->ip_address(): $this->input->post('user_ip'); // 优先检查请求是否来自APP
				$login_info['last_login_timestamp'] = time();

				// 获取用户/检查用户是否存在
				$user_info = $this->check_mobile( $this->input->post('mobile') );

				// 若用户存在，更新用户密码；若用户不存在，创建用户。
				// 同时更新最后登录信息
				if ( !empty($user_info) ):
					$login_info['password'] = sha1($this->input->post('password'));

					$result = $this->basic_model->edit($user_info['user_id'], $login_info);
					if ($result !== FALSE):
						$this->result['status'] = 200;
						$this->result['content'] = '密码重置成功';
					else:
						$this->result['status'] = 434;
						$this->result['content'] = '密码重置失败';
					endif;

				else:
					// 创建用户
					$data_to_create = array(
						'mobile' => $this->input->post('mobile'),
						'password' => sha1($this->input->post('password')),
					);
					$data_to_create = array_merge($data_to_create, $login_info);

					$result = $this->user_create($data_to_create);
					if ( !empty($result) ):
						$this->result['status'] = 200;
						$this->result['content'] = '用户创建成功，请使用该手机号及密码登录';
					else:
						$this->result['status'] = 414;
						$this->result['content']['error']['message'] = '该手机号未注册为用户，请直接用手机号登录';
					endif;

				endif;

			endif;
		} // end password_reset

		/**
		 * 短信验证
		 *
		 * TODO 改为实例化并调用Sms::verify()
		 */
		private function verify_sms()
		{
			// 检查必要参数是否已传入
			$required_params = array('mobile', 'captcha', 'sms_id');
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
			// 动态设置待验证字段名及字段值
			$this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]');
			$this->form_validation->set_rules('sms_id', '短信ID', 'trim|required|is_natural_no_zero');
			$this->form_validation->set_rules('captcha', '短信验证码', 'trim|required|exact_length[6]|is_natural_no_zero');
			
			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
				// 筛选条件
				$condition = array(
					'type' => '1', // 仅限验证码类短信
					'sms_id' => $sms_id,
					'mobile' => $mobile,
					'captcha' => $captcha,
					'time_expire >=' => time(),
				);

				// 获取列表；默认不获取已删除项
				$this->basic_model->table_name = 'sms'; // 更改当前表名
				$this->basic_model->id_name = 'sms_id'; // 更改当前表主键名
				$items = $this->basic_model->select($condition, NULL, FALSE, FALSE);
				$this->basic_model->table_name = 'user'; // 还原当前表名
				$this->basic_model->id_name = 'user_id'; // 还原当前表主键名

				if ( empty($items) ):
					$this->result['status'] = 400;
					$this->result['content']['error']['message'] = '验证码错误或已过期';
					exit();

				endif;

			endif;
		}

		/**
		 * 创建用户
		 * TODO 改为实例化并调用User::create()
		 *
		 * @params $data_to_create 待创建的用户信息
		 * @params $name 用于创建用户的资料类型；手机号mobile，电子邮箱Email，默认mobile
		 */
		private function user_create($data_to_create, $name = 'mobile')
		{
			// 初始化并配置表单验证库
			$this->load->library('form_validation');
			$this->form_validation->set_error_delimiters('', '');
			// 动态设置待验证字段名及字段值
			$data_to_validate["{$name}"] = $data_to_create[$name];
			$this->form_validation->set_data($data_to_validate);
			$this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]|is_natural|is_unique[user.mobile]');
			//$this->form_validation->set_rules('email', 'Email', 'trim|required|max_length[40]|valid_email|is_unique[user.email]');

			// 若表单提交不成功
			if ($this->form_validation->run() === FALSE):
				$this->result['status'] = 401;
				$this->result['content']['error']['message'] = validation_errors();
				exit();

			else:
				// 创建并返回行ID
				return $this->basic_model->create($data_to_create, TRUE);

			endif;
		}

		/**
		 * 检查是否已经有以相应手机号注册的账户
		 *
		 * @params string $mobile 需要检查的手机号
		 * @params boolean $return_boolean 是否需要以布尔值形式返回
		 */
		private function check_mobile($mobile, $return_boolean = FALSE)
		{
			$data_to_search['mobile'] = $mobile;
			$result = $this->basic_model->match($data_to_search);

			if ($return_boolean === FALSE):
				return $result;
			else:
				return ( empty($result) )? FALSE: TRUE;
			endif;
		}

		/**
		 * 检查是否已经有以相应Email注册的账户
		 *
		 * @params string $email 需要检查的Email
		 * @params boolean $return_boolean 是否需要以布尔值形式返回
		 */
		private function check_email($email, $return_boolean = FALSE)
		{
			$data_to_search['email'] = $email;
			$result = $this->basic_model->match($data_to_search);

			if ($return_boolean === FALSE):
				return $result;
			else:
				return ( empty($result) )? FALSE: TRUE;
			endif;
		}
	}

/* End of file Account.php */
/* Location: ./application/controllers/Account.php */
