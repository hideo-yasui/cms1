<?php
include_once($_SERVER["DOCUMENT_ROOT"] . '/app/commonView.php');
/**
 * パスワードを変更するページを表示する
 * パスワード再設定メールのＵＲＬより遷移する
 * @param dbconnect $DBCON
 **/
function showAddonPage($DBCON){
	$system = $GLOBALS['gEnvList']['system'];
	$data   = array();

	/**
		* GET
		* リクエストの場合の処理
		*/
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		// password_setting扱い
		$contents = "password_setting";
		$retrieve_code = isset($_GET['key']) ? $_GET['key'] : null;
		$request = array("system" => $system, 'retrieve_code' => $retrieve_code);
		$user = execConfigQuery($DBCON, 'get_password_change_user',  $request);
		if ($user["status"] !== "success" || count($user["data"]) !== 1) {
			sendErrorResponse($DBCON, $system, $contents, 1, $retrieve_code);
		}
		$data["key"] = $retrieve_code;
		$data["displaymode"] = "";
		showQueryView($DBCON, $system, $contents, $data);
		exitProc($DBCON);
	}

	/**
	* POST
	* リクエストの場合の処理
	*/
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$csrfCheck = isCorrectToken();
		// password_setting,passowrd_edit 2パターンある
		$retrieve_code = "";
		if(isset($_POST['password_old']) && isset($_SESSION['sUsrID'])){
			$contents = "password_edit";
			$password_old = STR_wordFormatter($_POST['password_old']);
			@INS_SQL_LOG("auth", "password change start", $contents);
			if($csrfCheck === false){
				sendErrorResponse($DBCON, $system, $contents, 9, $retrieve_code);
			}
			else if (!STR_passwordValidator($password_old)) {
				sendErrorResponse($DBCON, $system, $contents, 2, $retrieve_code);
			}
		}
		else if(isset($_POST['key'])){
			$contents = "password_setting";
			$retrieve_code    = $_POST['key'];
			@INS_SQL_LOG("auth", "password change start", $contents);
			if($csrfCheck === false){
				sendErrorResponse($DBCON, $system, $contents, 9, $retrieve_code);
			}
			else if (!$retrieve_code) {
				sendErrorResponse($DBCON, $system, $contents, 1, $retrieve_code);
			}
		}
		else {
			//入力情報不足エラー
			sendErrorResponse($DBCON, $system, $contents, 6, $retrieve_code);
		}

		if(!isCorrectToken()){
			//CSRF token不一致
			sendErrorResponse($DBCON, $system, $contents, 1, $retrieve_code);
		}
		//更新パスワードチェック
		$password         = STR_wordFormatter($_POST['password']);
		$password_confirm = STR_wordFormatter($_POST['password_confirm']);

		if (!STR_passwordValidator($password)) {
			//パスワードの形式が一致しない
			sendErrorResponse($DBCON, $system, $contents, 2, $retrieve_code);
		}
		else if ($password !== $password_confirm) {
			//確認パスワードと一致しない
			sendErrorResponse($DBCON, $system, $contents, 3, $retrieve_code);
		}

		$request = array("system" => $system);
		if($contents === "password_setting"){
			//retrieve_codeでユーザーを取得する
			$request["retrieve_code"] = $retrieve_code;
		}
		else  {
			//現行パスワードとSESSION sUsrIDでユーザーを取得する
			$request["password_old"] = $password_old;
		}

		// パスワード更新する対象ユーザーを取得
		$user = execConfigQuery($DBCON, 'get_password_change_user', $request);
		if ($user["status"] !== "success" || count($user["data"]) !== 1) {
			//取得できない場合
			if($contents === "password_setting") {
				//URL 期限切れエラー
				sendErrorResponse($DBCON, $system, $contents, 1, $retrieve_code);
			}
			else {
				//現在のパスワードと不一致エラー
					sendErrorResponse($DBCON, $system, $contents, 5, $retrieve_code);
			}
		}
		// パスワード更新
		$request["password"] = $password;
		$result = execConfigQuery($DBCON, 'upd_password_change_user',  $request);
		if ($result["status"] !== "success") {
			//パスワード更新失敗（システムエラー）
	        sendErrorResponse($DBCON, $system, $contents, 4, $retrieve_code);
	    }
	     else if (!isset($result["data"]["result"]) || count($result["data"]['result'])<1 || !isset($result["data"]['result'][0]["count"]) || $result["data"]['result'][0]["count"]<1 ) {
			 //パスワード更新件数０件（競合により更新済みの可能性が高い）
	         sendErrorResponse($DBCON, $system, $contents, 4, $retrieve_code);
	    }
		if($contents === "password_setting"){
			$data["key"] = $retrieve_code;
		}
		$data["displaymode"] = "success";
		@INS_SQL_LOG("auth", "password change end", $contents);

		showQueryView($DBCON, $system, $contents, $data);
		exitProc($DBCON);
	}
}
/**
 * sendErrorResponse
 * 番号に応じてレスポンスを返却する
 * @param  レスポンス番号
 * @return show view and exit script
 */
function sendErrorResponse($DBCON, $system, $contents, $num, $retrieve_code) {
 	$message = "";
	$displaymode = "";
 	switch($num){
		case 9:
		case 1:
		$message = "URLが無効です。URLが間違っているか、有効期限が切れている可能性があります。再度パスワード再発行を行うか、サポートまでお問い合わせ下さい。";
		$displaymode = "error";
		break;
		case 2:
		$message = "パスワードの形式が正しくありません。入力内容をご確認ください。";
		break;
		case 3:
		$message = "新しいパスワードを確認する必要があります。";
		break;
		case 4:
		$message = "パスワード更新に失敗しましたサポートまでお問い合わせ下さい。";
		break;
		case 5:
		$message = "現在のパスワードが一致しません。";
		break;
		case 6:
		$message = "入力情報に不足があります。";
		break;
	}
	$data = array(
		"message"     => $message,
		"description" => $num,
		"status"      => "failed",
		"key"         => $retrieve_code,
		"displaymode" => $displaymode
	);
	@INS_SQL_LOG("err", "password change error No=[$num] Message=[$message]", $contents);
	showQueryView($DBCON, $system, $contents, $data);
	exitProc($DBCON);
}
