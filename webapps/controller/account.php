<?php
include_once($_SERVER["DOCUMENT_ROOT"] . '/app/commonView.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/app/commonService.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/app/login.php');

/**
* Account
* アカウントに関するロジックを管理する
* 以下将来的にではあるが、URLの構想だけ先に決めておく
* GET login : ログイン画面の表示
* POST logine : ログイン処理
* GET forget : パスワード忘れた場合の画面表示
* POST forget : パスワード忘れた場合画面より設定したメールにパスワード設定画面のURLを送信
* GET register : ユーザー登録画面の表示
* POST register : ユーザー登録処理
* GET/POST logout : ログアウト処理
* GET profile : プロフィール画面の表示
* GET profile/edit : プロフィール編集画面の表示
* POST profile/edit : プロフィール編集画面の保存
* GET password/edit : パスワード設定画面の表示
* POST password/edit : パスワード設定の保存
* GET password/setting : パスワード再設定画面の表示
* POST password/setting : パスワード再設定の保存
* GET email/edit : メールアドレス設定画面の表示
* POST email/edit : メールアドレス設定画面の保存
* GET email/edit/comp : メールアドレス設定完了画面の表示
*/
class Account extends Controller
{
	protected $auth_actions = array('profile','profile_edit','password_edit','password_setting','email_edit','logout');
	protected $error_message = "システムエラーが発生しました。管理者へお問い合わせください。";

	/**
	* @param array $params
	* @return json response
	* GET:メールアドレス設定画面の表示
	* POST:メールアドレス設定画面の保存
	*/
	public function email_edit($params)
	{
		$result = execConfigQuery($this->db, 'get_user_by_session', array());
		if(!isset($result) || !isset($result["data"]) || count($result["data"])!==1){
			showPageForbidden();
			exitProc($this->db);
		}
		$user = $result["data"][0];
		$page_code = 'email_edit';
		$mail_code = 'email_edit';
		if ($this->request->isPost()){
			if(!isset($_POST['email'])){
				showPageForbidden();
				exitProc($this->db);
			}
			if (!isCorrectToken()) {
				showPageForbidden();
				exitProc($this->db);
			}
			//認証コードを設定
			$auth_code = createAuthCode($_POST['email']);
			$result = execConfigQuery($this->db, 'auth_code_upd', array(
				'change_email' => $_POST['email'],
				'auth_code'=> $auth_code,
				'login_ip'=> $_SERVER["REMOTE_ADDR"],
			));
			if($result["status"] !== "success"){
				//エラー発生時（内部エラー）
				$this->show_view($page_code, array(
					"message" => $this->error_message
				));
			}
			//メールを送信
			$result = $this->send_user_mail($_POST['email'], $mail_code, array(
				'auth_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/email/edit/comp?key=' . $auth_code,
				'USER_NAME'       => $user["name_last"]." ".$user["name_first"],
			));
			if($result !== true){
				//エラー発生時（内部エラー）
				$this->show_view($page_code, array(
					"message" => $this->error_message
				));
			}
			$this->show_view($page_code, array(
				"email" => $_POST["email"],
				"displaymode" => "success"
			));
		}
		else {
			$this->show_view($page_code, array());
		}
	}

	/**
	* @param array $params
	* @return json response
	* GET:メールアドレス設定完了
	*/
	public function email_edit_comp($params)
	{
		$key = isset($_GET['key']) ? $_GET['key'] : null;
		$page_code = 'email_edit_comp';

		$is_login=true;
		$user = execConfigQuery($this->db, 'get_user_by_auth_code', array('auth_code'=>$key));
		$change_email = "";
		$password = "";
		if(!isset($user) || !isset($user["data"]) || count($user["data"])!==1){
			$message = "URLが無効です。URLが間違っているか、有効期限が切れている可能性があります。再度メールアドレス変更を行うか、サポートまでお問い合わせ下さい。";
			$this->show_view($page_code, array(
				"message" => $message
			));
		}
		else {
			$change_email = $user["data"][0]["change_email"];
			$password = $user["data"][0]["cipher_pw"];
			$mail_check = execConfigQuery($this->db, 'get_user_by_email', array('email'=>$change_email));
			if(isset($mail_check) && isset($mail_check["data"]) && count($mail_check["data"])>0){
				//変更メールがすでに登録されている（内部エラー）
				$this->show_view($page_code, array(
					"message" => $this->error_message
				));
			}
			$result = execConfigQuery($this->db, 'email_edit_comp', array('auth_code'=>$key));
			if($result["status"] !== "success"){
				//エラー発生時（内部エラー）
				$this->show_view($page_code, array(
					"message" => $this->error_message
				));
			}
		}
		$is_login=true;
		$result = execConfigQuery($this->db, 'get_user_by_session', array());
		if(!isset($result) || !isset($result["data"]) || count($result["data"])!==1){
			$is_login=false;
		}
		else {
			if(postLogin($this->db, array("email" => $change_email, "cipher_pw" => $password))===false){
				$is_login=false;
			}
		}
		$this->show_view($page_code, array(
			'is_login' => $is_login
		));
	}

	/**
	* ユーザーメール送信
	* @param  String mail_code
	* @param  Array mail_param
	* @return Array
	*/
	private function show_view($page_code , $param){
		$system=$this->application->system;
		showQueryView($this->db, 'main', $page_code, $param);
		exitProc($this->db);
	}
	/**
	* ユーザーメール送信
	* @param  String mail_code
	* @param  Array mail_param
	* @return Array
	*/
	private function send_user_mail($email, $mail_code, $mail_param){
		//メールテンプレート取得
		$mail_template = $this->get_mailtext_by_code($mail_code);
		if(empty($mail_template)){
			return false;
		}
		$mail_param["TO_ADDRESS"] = $email;
		if(!$this->post_maildata($mail_template, $mail_param)){
			return false;
		}
		execConfigMail($this->db);
		return true;
	}
	/**
	* config.t_mailにinsertする
	* @param  String mail_code
	* @return Array
	*/
	private function post_maildata($mail_template, $mail_param){
		if(empty($mail_template)) return false;
		if(empty($mail_param)) return false;
		if(empty($mail_template["FROM_ADDRESS"])) return false;
		if(empty($mail_template['TITLE'])) return false;
		if(empty($mail_template['BODY'])) return false;
		if(empty($mail_param["TO_ADDRESS"])) return false;

		//メール本文のパラメータ部分の置換
		$mail_body = parseWords($mail_template["BODY"], $mail_param);

		$data = array(
			'FROM_ADDRESS' => $mail_template['FROM_ADDRESS'],
			'TO_ADDRESS'   => $mail_param['TO_ADDRESS'],
			'CC_ADDRESS'   => $mail_template['CC_ADDRESS'],
			'BCC_ADDRESS'   => $mail_template['BCC_ADDRESS'],
			'TITLE'        => $mail_template['TITLE'],
			'BODY'         => $mail_body,
			'MAIL_CODE' => $mail_template['MAIL_CODE'],
			'MAIL_TYPE'    => 'text',
			'SEND_STATUS'  => 'ready'
		);

		$result = execConfigQuery($this->db, 't_mail_ins', $data);
		if($result["status"] !== "success"){
			return false;
		}
		return true;
	}
	/**
	* mail_codeから、mail件名・本文を取得
	* @param  String mail_code
	* @return Array
	*/
	private function get_mailtext_by_code($mail_code){
		if(empty($mail_code)) return null;
		//メール用パラメータの取得m_code/GEOUP_VAL = SYS_CONFIG or MAIL_PARAM or MAIL_CODE
		$result = execConfigQuery($this->db, "get_mailparam");
		if($result["status"] !== "success" || count($result["data"]) === 0){
			return null;
		}
		$mail_param = array();
		$mail_title = "";
		$mail_body = "";
		$from_address = "";
		$cc_address = "";
		$data_array = $result["data"];
		for($i=0;$i<count($data_array);$i++){
			$data = $data_array[$i];
			if($data["GROUP_VAL"] === "SYS_CONFIG"){
				$mail_param[$data["CODE_VAL"]] = $data["CODE_NAME"];
				if($data["CODE_VAL"]==="MAIL_SUPPORTDESK"){
					//通知メールFROM
					$from_address = $data["CODE_NAME"];
				}
				else if($data["CODE_VAL"]==="MAIL_NOTIFY"){
					//通知メールCC
					$cc_address = $data["CODE_NAME"];
				}
			}
			else if($data["GROUP_VAL"] === "MAIL_PARAM"){
				$mail_param[$data["CODE_VAL"]] = $data["CODE_REMARK"];
			}
			else if($data["GROUP_VAL"] === "MAIL_CODE"){
				if($mail_code === $data["CODE_VAL"]){
					//codeより件名・本文を取得
					$mail_title = $data["CODE_NAME"];
					$mail_body = __WEBIO_return($data["CODE_REMARK"]);
				}
			}
		}
		if(empty($from_address)) return null;
		if(empty($mail_title)) return null;
		if(empty($mail_body)) return null;
		//メール本文に共通パラメータをマッピング
		$mail_body = parseWords($mail_body, $mail_param);
		return array(
			'MAIL_CODE' => $mail_code,
			'FROM_ADDRESS' => $from_address,
			'CC_ADDRESS' => $cc_address,
			'BCC_ADDRESS' => '',
			'TITLE' => $mail_title,
			'BODY' => $mail_body
		);
	}
	/**
	* slack通知チャンネルを取得
	* @param  String channel_code 代替コード
	* @return Array
	*/
	private function get_slack_channel($channel_code){
		//メール用パラメータの取得m_code/GEOUP_VAL = SYS_CONFIG or MAIL_PARAM or MAIL_CODE
		$result = execConfigQuery($this->db, "get_mailparam");
		if($result["status"] !== "success" || count($result["data"]) === 0){
			return null;
		}
		$slack_channel = "";
		$data_array = $result["data"];
		for($i=0;$i<count($data_array);$i++){
			$data = $data_array[$i];
			if($data["GROUP_VAL"] === "SYS_CONFIG"){
				$mail_param[$data["CODE_VAL"]] = $data["CODE_NAME"];
				if($data["CODE_VAL"]===$channel_code){
					$slack_channel = $data["CODE_NAME"];
				}
			}
		}
		if(empty($slack_channel)) return null;
		return str_replace('#', '', $slack_channel);
	}
}
