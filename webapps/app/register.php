<?php
include_once($_SERVER["DOCUMENT_ROOT"] . '/app/commonView.php');
/**
 * ユーザー登録ページを表示する
 * ユーザー仮登録メールのＵＲＬより遷移する
 * @param dbconnect $DBCON
 **/
function showAddonPage($DBCON){
	// now available service is main only
	$system = $GLOBALS['gEnvList']['system'];
	if ($system !== 'main') {
		view("notfound.html", array());
		exitProc($DBCON);
	}

	/**
	* GET
	* register
	*/
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$auth_code = isset($_GET['key']) ? $_GET['key'] : "";
		if (!$auth_code) {
			header("Location: index?ERROR=INVALID_AUTH_KEY");
			exitProc($DBCON);
		}

		$res = execConfigQuery($DBCON, 'get_user_by_auth_code', array('auth_code' => $auth_code));
		if (count($res['data']) !== 1 || (int)$res['data'][0]['status'] !== 0) {
			$data['message']     = 'URLの有効期限が切れています。再度アカウント登録を行って下さい。';
			$data['displaymode'] = 'error';
		} else {
			$data = $res['data'][0];
		};
		showQueryView($DBCON, $system, "register_info", $data);
		exitProc($DBCON);
	}

	/**
	* POST
	* pre register
	* TODO 旧システムの仕様にそって email をgetパラメータにのせてるが
	* 個人情報保護の観点からは外したほうがよい
	*/
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		// validate
		$email = isset($_POST['email']) ? trim($_POST['email']) : '';
		if (empty($email) || !isCorrectToken()) {
			view("notfound.html", array());
			exitProc($DBCON);
		}

	  // check exist
		$res = execConfigQuery($DBCON, 'get_user_by_email', array('email' => $email));
		if (count($res['data']) !== 0) {
			$user = $res['data'][0];
			if ((int)$user['status'] !== 0) { // exist registered user
				header('Location: /index?ERROR=EMAIL_ALREADY_EXIST&POS=campaign&err_email=' . $email);
				exitProc($DBCON);
			} else {
				// resend mail
				$auth_code = createAuthCode($email);
				$res = execConfigQuery($DBCON, 'user_pre_upd', array(
			    'email'          => $email,
					'auth_code'      => $auth_code,
			    'login_ip'       => $_SERVER["REMOTE_ADDR"],
				));
				$res = execConfigQuery($DBCON, 'send_mail_pre_register', array(
					'auth_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/register?key=' . $auth_code,
					'email'    => $email,
					'ip'       => $_SERVER["REMOTE_ADDR"],
					'date'     => $GLOBALS['nowDateTime'],
				));
				execConfigMail($DBCON);
				header('Location: /index?ERROR=EMAIL_ALREADY_EXIST_SENT_AGAIN&POS=campaign&err_email=' . $email);
				exitProc($DBCON);
			}
		}

		// create Auth Code
	  $auth_code = createAuthCode($email);
		$auth_url  = 'https://' . $_SERVER['HTTP_HOST'] . '/register?key=' . $auth_code;

		// pre register
		$res = execConfigQuery($DBCON, 'user_pre_ins', array(
	    'email'          => $email,
			'auth_code'      => $auth_code,
	    'login_ip'       => $_SERVER["REMOTE_ADDR"],
		));

		// send mail
		$res = execConfigQuery($DBCON, 'send_mail_pre_register', array(
			'auth_url' => $auth_url,
			'email'    => $email,
			'ip'       => $_SERVER["REMOTE_ADDR"],
			'date'     => $GLOBALS['nowDateTime'],
		));
		execConfigMail($DBCON);
		header('Location: /register_email');
		exitProc($DBCON);
	}
}
