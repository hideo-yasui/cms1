<?php
include_once($_SERVER["DOCUMENT_ROOT"] . '/app/commonView.php');
/**
 * パスワードを忘れた場合に利用するページを表示する
 * 指定したメールアドレスにパスワード再設定ページへのＵＲＬを送信する
 * @param dbconnect $DBCON
 **/
function showAddonPage($DBCON){

    $system   = $GLOBALS['gEnvList']['system'];
    $contents = "forget";

    /**
     * GET
     * パスワード再発行メールの送信フォームを表示
     */
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      showQueryView($DBCON, $system, $contents);
      exitProc($DBCON);
    }

    /**
     * POST
     * パスワード再発行メールの送信処理を実行
     */
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        @INS_SQL_LOG("auth", "password forget start", $contents);
        if(!isCorrectToken()){
        //CSRF token不一致
        sendErrorResponse($DBCON, $system, $contents, 9);
      }

      // init
      $email  = isset($_POST['email']) ? $_POST['email'] : null;
      if (empty($email)) {
          sendErrorResponse($DBCON, $system, $contents, 1);
      }
    	// validate
    	$query = 'get_user_by_email'; // main
    	if ($system === 'pro') {
        $query = 'get_hospital_user_by_email';
    	}
      $return_url = isset($_POST['return_url']) ? $_POST['return_url'] : '';
      $user = execConfigQuery($DBCON, $query,  array('email' => $email ));

      // 非アクティブユーザの場合
    	if (isset($user['data'][0]['active']) && $user['data'][0]['active'] != 1) {
    		sendErrorResponse($DBCON, $system, $contents, 5);
    	}
      // 仮登録ユーザの場合
      if (isset($user['data'][0]['status']) && $user['data'][0]['status'] == 0) {
        sendErrorResponse($DBCON, $system, $contents, 5);
      }

      $result = execConfigQuery($DBCON, 'password_setting_upd',  array(
        'system' => $system,
        'email'  => $email,
        // MEMO ? '  ' がスペース2つなのは、execConfigQueryがスペースをnullに変えてしまうバグがあり、それを回避するため。
        'return_url' => empty($return_url) ? '  ' : '&return_url=' . $return_url,
        'url'    => 'https://' . $_SERVER['HTTP_HOST'].'/recover/password/change?key=',
      ));
      if ($result["status"] !== "success") {
          sendErrorResponse($DBCON, $system, $contents, 2);
      }
      else if (!isset($_SESSION['sInsertId']) || $_SESSION['sInsertId']<1) {
        sendErrorResponse($DBCON, $system, $contents, 3);
      }
      @INS_SQL_LOG("auth", "password forget end", $contents);
      //送信完了画面の表示
      $data["email"] = $email;
      $data["displaymode"] = "confirm";
      execConfigMail($DBCON);
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
function sendErrorResponse($DBCON, $system, $contents, $num) {
    $message = "";
    switch($num){
        case 1:
        $message = "メールアドレスが入力されていません";
        break;
        case 9:
        case 2:
        $message = "サーバー内エラーが発生しました。管理者にお問い合わせ下さい";
        break;
        case 3:
        $message = "登録されているユーザーが見つかりません";
        break;
        case 4:
        $message = "メール送信処理に失敗しました";
        break;
				case 5:
        $message = "ご入力いただいたメールアドレスのユーザーは現在システムを利用することができません。詳しくはサポートまでお問い合わせ下さい。";
        break;
    }
    @INS_SQL_LOG("err", "password change error No=[$num] Message=[$message]", $contents);
    $data = array("message" => $message,
          "description" => $num,
          "status" => "failed",
          "displaymode" => "");
    showQueryView($DBCON, $system, $contents, $data);
    exitProc($DBCON);
}
