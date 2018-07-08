<?php
include_once($_SERVER["DOCUMENT_ROOT"] . '/app/commonView.php');
// functions ===================================================================
/**
 * ログインページを表示するもしくは、ログイン認証を行いtopページに遷移する
 * @param dbconnect $DBCON
 **/
function showAddonPage($DBCON){
    $nowTime = time() ;
    $date = new DateTime();
    $nowDateTime = $date->format('Y-m-d H:i:s'); // DateTime に変更
    $nowDateOnly = $date->format('Y-m-d'); // Date に変更
    $system   = $GLOBALS['gEnvList']['system'];
    $login_id = "";

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      if(isset($_GET["error"])){
        sendErrorResponse($DBCON, 1,  $system, $login_id);
      }
      sendErrorResponse($DBCON, 9,  $system, $login_id);
    }

    /**
    * POST
    * ログイン処理
    */
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!isCorrectToken()) {
            //CSRF token不一致
            sendErrorResponse($DBCON, 9, $system, $_POST['login_id']);
        }

        // ログイン処理
        $login = postLogin($DBCON, $_POST);

        // ログイン成功
        if ($login["status"] === "success") {
            // show logined page
            header( "Location: /top" ) ;
            exitProc($DBCON);
        }

        // ログイン失敗時
        switch($login["message"]){
            case "E_EXIST_NODATA":
                sendErrorResponse($DBCON, 3,  $system, $login_id);
                break;
            case "E_BAD_REQUEST":
            case "ERROR":
                sendErrorResponse($DBCON, 2,  $system, $login_id);
                break;
            case "E_ACCOUNT_LOCK":
                sendErrorResponse($DBCON, 4,  $system, $login_id);
                break;
            default:
                sendErrorResponse($DBCON, 9,  $system, $login_id);
                break;
        }
    }
}
/**
 * ログイン認証を行い認証結果を返却する
 * @param dbconnect $DBCON
 **/
function postLogin($DBCON, $params){
    $status = "success";
    $description = "";
    $message = "";
    $nowTime = time() ;
    $date = new DateTime();
    $nowDateTime = $date->format('Y-m-d H:i:s'); // DateTime に変更
    $nowDateOnly = $date->format('Y-m-d'); // Date に変更
    $system   = $GLOBALS['gEnvList']['system'];
    $lockStatus = "unlocked"; // アカウントロック locked|released|unlocked

    // get user
    @INS_SQL_LOG("auth", "login start", $params["login_id"]);
    $user = null;
    if(!isset($params["login_id"]) && isset($params["user_id"])){
        //login_idをuser_idで補完
        $params["login_id"] = $params["user_id"];
    }
    if(!isset($params["login_id"]) && isset($params["email"])){
        //login_idをemailで補完
        $params["login_id"] = $params["email"];
    }
    if(!isset($params["password"]) && isset($params["cipher_pw"])){
        //パスワードが、ハッシュ化済みの場合
        $params["password"] = STR_wordFormatter($params["cipher_pw"]);
    }
    else if(isset($params["password"]) && !isset($params["cipher_pw"])){
        //パスワードが、ハッシュ化されてない場合
        $params["password"] = LC_COM_getPWencode(STR_wordFormatter($params["password"]));
    }

    if(isset($params["login_id"]) && isset($params["password"])){
        //login_id, passwordを利用したログイン
        $user = getUserByLoginParams($DBCON, $system
                    , STR_wordFormatter(str_replace(' ', '+', $params["login_id"]))
                    , $params["password"]);
    }

    // アカウントロックステータスを取得する
    $lockStatus = getLockStatusByLoginId($DBCON, $system, $params["login_id"]);

    // アカウントロックの場合
    if ($lockStatus === "locked") {
        // アカウントロック状態でのログインアクセスは、全て失敗ログインとして取り扱う
        updateAccountLock($DBCON, $system, $params["login_id"]);
        return [
            "status"      => "failed",
            "message"     => "E_ACCOUNT_LOCK",
            "description" => ""
        ];
    }
    // アカウントロック解除の場合
    else if ($lockStatus === "released") {
        // アカウントロックを解除
        resetAcountLock($DBCON, $system, $params["login_id"]);
    }

    if(!isset($user) || $user["status"] !== "success"){
        $status = "failed";
        $message = "E_BAD_REQUEST";
        $description = "paramater error:".$user["status"];
    }
    else if(count($user["data"]) !== 1) {
        $status = "failed";
        $message = "E_EXIST_NODATA";
        $description = "";
    }

    // ログイン失敗ならば、ログイン失敗の値を更新する
    if ($status !== "success") {
        updateAccountLock($DBCON, $system, $params["login_id"]);
    }

    if($status === "success") {
        $gLoginUsrInfo = $user["data"][0];

        //------------------------------------------------------------------------------------
        // ログインキー $login_key の発行・セット
        //------------------------------------------------------------------------------------
        // ログインキーを生成する
        // 万一、同一IP・同時刻でアクセスされ、なおかつログインキーが重複した場合、
        // ログインキーを100000回まで取得することで、完全な重複をなくす
        // 通常は１回の取得で十分である
        $login_ip = $_SERVER["REMOTE_ADDR"];
        for ($cnt=0; $cnt < 100000; $cnt++) {
            $login_key = COM_getRandomKey(12) ;
            $result = execConfigQuery($DBCON, getQueryCodeBySystem($system, 'login_key'));
            if (isset($result['data'][0]["COUNT"]) && $result['data'][0]["COUNT"] == 0) {
                break ;
            }
        }

        //------------------------------------------------------------------------------------
        // ログイン情報の更新
        //------------------------------------------------------------------------------------
        // 更新内容のセット
        $upList = array() ;
        $upList["login_ip"]  = $_SERVER["REMOTE_ADDR"] ;
        $upList["login_key"] = $login_key ;

        //テーブルによっては使わない可能性があるフィールド(rid / ID問題を解消したい）
        if (!isset($gLoginUsrInfo["init_login_date"])) $gLoginUsrInfo["init_login_date"]=$nowDateTime;
        if (!isset($gLoginUsrInfo["now_login_date"])) $gLoginUsrInfo["now_login_date"]=$nowDateTime;
        if (!isset($gLoginUsrInfo["login_ip"])) $gLoginUsrInfo["login_ip"]=$_SERVER["REMOTE_ADDR"] ;
        if (!isset($gLoginUsrInfo["login_id"]) && isset($gLoginUsrInfo["email"])) $gLoginUsrInfo["login_id"] = $gLoginUsrInfo["email"];

        if (isset($gLoginUsrInfo["ID"]) && !isset($gLoginUsrInfo["rid"])) $gLoginUsrInfo["rid"]=$gLoginUsrInfo["ID"];
        else if (isset($gLoginUsrInfo["rid"]) && !isset($gLoginUsrInfo["ID"])) $gLoginUsrInfo["ID"]=$gLoginUsrInfo["rid"];

        if ( $gLoginUsrInfo["init_login_date"] === '0000-00-00 00:00:00' ) {
            $upList["init_login_date"] = $nowDateTime ;
        }
        $upList["last_login_date"] = $gLoginUsrInfo["now_login_date"] ;
        $upList["now_login_date"] = $nowDateTime ;
        $upList["login_ip_prev"] = $gLoginUsrInfo["login_ip"] ;
        $upList["rid"] = $gLoginUsrInfo["rid"] ;
        $upList["ID"] = $gLoginUsrInfo["ID"] ;

        // 更新条件のセット
        $result = execConfigQuery($DBCON, getQueryCodeBySystem($system, 'upd_login_key'), $upList);
        if ($result["status"] !== "success") {
            $status = "failed";
            $message = "ERROR";
        }
        else {
            // set user infromation
            $gLoginUsrInfo["login_ip"] = $upList["login_ip"] ;
            $gLoginUsrInfo["login_key"] = $upList["login_key"] ;
            $gLoginUsrInfo["last_login_date"] = $upList["last_login_date"] ;
            $gLoginUsrInfo["now_login_date"] = $upList["now_login_date"] ;
        }
        @INS_SQL_LOG("auth", "login end", $login_id."\nsystem=[".$system."]id=[".$gLoginUsrInfo["ID"]."]");

        session_regenerate_id(true) ;
        $_SESSION["sLoginSystem"] = $GLOBALS['gEnvList']['system'];
        $_SESSION["sUsrID"] = $gLoginUsrInfo["ID"] ;
        $_SESSION["sLoginID"] = isset($gLoginUsrInfo["login_id"]) ? $gLoginUsrInfo["login_id"] : null;
        $_SESSION["sEmail"] = $gLoginUsrInfo["email"] ;
        $_SESSION["sLoginKey"] = $gLoginUsrInfo["login_key"];
        $_SESSION["nickname"] = $gLoginUsrInfo["name_1"] ;
        $_SESSION["sAccessTime"] = $nowTime ;
        $_SESSION["sCheckTime"] = 300;    //5分に１回authenticate実行
        $_SESSION["sLimitTime"] = 3600; //1時間経過でセッション破棄
        $_SESSION["sCOMPANY_CODE"]="";
        $_SESSION["sCOMPANY_NAME"]="";
        $_SESSION["sORGANIZATION_NAME"]="";
        if (!isset($gLoginUsrInfo["name_1"]) && isset($gLoginUsrInfo["NAME"])) $_SESSION["nickname"]=$gLoginUsrInfo["NAME"];
        if (!isset($gLoginUsrInfo["login_id"]) && isset($gLoginUsrInfo["LOGIN_ID"])) $_SESSION["sLoginID"]=$gLoginUsrInfo["LOGIN_ID"];
        if (isset($gLoginUsrInfo["COMPANY_CODE"])) $_SESSION["sCOMPANY_CODE"]=$gLoginUsrInfo["COMPANY_CODE"];
        if (isset($gLoginUsrInfo["COMPANY_NAME"])) $_SESSION["sCOMPANY_NAME"]=$gLoginUsrInfo["COMPANY_NAME"];
        if (isset($gLoginUsrInfo["ORGANIZATION_NAME"])) $_SESSION["sORGANIZATION_NAME"]=$gLoginUsrInfo["ORGANIZATION_NAME"];
    }
    $result = array(
        'status'      => $status,
        'message'     => $message,
        'description' => $description
    );
	return $result;
}
/**
* エラーレスポンスを返却し、スクリプトを終了する
* @return void
*/
function sendErrorResponse($DBCON, $num, $system, $login_id = null) {
    $message = "";
    switch($num){
        case 1:
            $message = "セッションタイムアウトしました。再度ログインをしてください。";
            break;
        case 2:
            $message = "サーバー内エラーが発生しました。管理者にお問い合わせ下さい。";
            break;
        case 3:
            $message = "ログイン可能な利用者情報が見つかりませんでした。";
            break;
        case 4:
            $message = "このアカウントは、一時的にロックされています。しばらく時間をおいてから再度ログインしてください。";
            break;
        default:
            header( "Location: /logout" ) ;
            exitProc($DBCON);
    }
    $data = array(
      "status"      => "failed",
      "message"     => $message,
      "description" => $num
    );
    if(!empty($message)){
        @INS_SQL_LOG("err", "login err", $login_id."\nsystem=[".$system."]id=[".$login_id."]no=[$num]message=[$message]");
    }

    showQueryView($DBCON, $system, "login", $data);
    exitProc($DBCON);
}
/**
* ログインIDとパスワードでユーザ情報を取得
* @return Array
*/
function getUserByLoginParams($DBCON, $system, $login_id, $password) {
  $data = array(
    'login_id'  => $login_id,
    'password' => $password
  );
  return execConfigQuery($DBCON, getQueryCodeBySystem($system, 'login'), $data);
}

/**
* ログインIDのアカウントロックステータスを取得する
* @return String
*/
function getLockStatusByLoginId($DBCON, $system, $login_id) {
    // Validate
    if (empty($login_id)) return "unlocked";
    $failureCntLimit = 5; // ログイン失敗回数の上限
    $failureMinuteLimit = 1; // アカウントロックの時間: 1分
    $userData = getActiveUserByLoginId($DBCON, $system, $login_id); // ユーザ情報

    // データが存在しない場合 or ユーザーが重複している場合
    if (empty($userData) || count($userData["data"]) === 0) return "unlocked";

    $userInfo = $userData["data"][0];
    $failureCnt = (int)$userInfo["login_failure_cnt"];
    $failureDate = new DateTime($userInfo["login_failure_date"]);
    $now = new DateTime();
    $diffMiniutes = (int)($now->getTimestamp() - $failureDate->getTimestamp()) / 60;

    // アカウントロック判定
    if ($failureCnt >= $failureCntLimit) {
        // 直近のログイン失敗が1分以内の場合は、アカウントロック中
        if ($diffMiniutes < $failureMinuteLimit) {
            return "locked";
        }
        // 1分経過している場合は、ロック解除
        else {
            return "released";
        }
    }

    // それ以外の場合は、アンロック
    return "unlocked";
}
/**
* アカウントロック情報の更新
* @return  Array
*/
function updateAccountLock($DBCON, $system, $login_id) {
    // Validate
    if (empty($login_id)) return [];
    // Main & Pro & API 環境以外の場合
    if (!in_array($system, ["main", "pro", "api"])) return [];
    $data = array(
        "login_id"           => $login_id,
        "increase_cnt"       => 1, // 指定の数だけカウントアップ
        "login_failure_date" => date("Y-m-d H:i:s"),
        "login_failure_ip"   => $_SERVER["REMOTE_ADDR"]
    );
    return execConfigQuery($DBCON, getQueryCodeBySystem($system, "upd_account_lock"), $data);
}
/**
* アカウントロックの値のリセット
* @return  Array
*/
function resetAcountLock($DBCON, $system, $login_id) {
    // Validate
    if (empty($login_id)) return [];
    // Main & Pro & API 環境以外の場合
    if (!in_array($system, ["main", "pro", "api"])) return [];
    $data = array(
        "login_id" => $login_id,
    );
    return execConfigQuery($DBCON, getQueryCodeBySystem($system, "reset_account_lock"), $data);
}
/**
* ログインID(Email)で有効なユーザ情報を取得する
* @return Array
*/
function getActiveUserByLoginId($DBCON, $system, $login_id) {
    // Validate
    if (empty($login_id)) return [];
    // Main & Pro & API 環境以外の場合
    if (!in_array($system, ["main", "pro", "api"])) return [];
    $data = array(
        'login_id' => $login_id
    );
    return execConfigQuery($DBCON, getQueryCodeBySystem($system, "get_active_user_by_login_id"), $data);
}
/**
 * サービスとタイプに応じて必要なクエリコードを返却します
 * （文字列結合で生成すると、grepできなくなるのでリテラルな形式にしました）
 * // TODO 共通のクエリを呼び出すようにして、クエリ内部のSQLで処理を分けるようにするべき 2017/02/08 岩田
 */
function getQueryCodeBySystem($system, $type) {
  if ($type === 'login') {
    switch ($system) {
      case 'api':
      return 'api_mykinso_login';
      case 'main':
      return 'mykinso_login';
      case 'pro':
      return 'hospital_login';
      default:
      return 'gestion_login';
    }
  }
  if ($type === 'login_key') {
    switch ($system) {
      case 'api':
      case 'main':
      return 'mykinso_login_key';
      case 'pro':
      return 'hospital_login_key';
      default:
      return 'gestion_login_key';
    }
  }
  if ($type === 'upd_login_key') {
    switch ($system) {
      case 'api':
      case 'main':
      return 'upd_mykinso_login_key';
      case 'pro':
      return 'upd_hospital_login_key';
      default:
      return 'upd_gestion_login_key';
    }
  }
  if ($type === "get_active_user_by_login_id") {
      switch ($system) {
          case 'api':
          case 'main':
          return 'get_mykinso_active_user_by_login_id';
          case 'pro':
          return 'get_hospital_active_user_by_login_id';
          default:
          return 'get_mykinso_active_user_by_login_id';
      }
  }
  if ($type === "upd_account_lock") {
      switch ($system) {
          case 'api':
          case 'main':
          return 'upd_mykinso_account_lock';
          case 'pro':
          return 'upd_hospital_account_lock';
          default:
          return 'upd_mykinso_account_lock';
      }
  }
  if ($type === "reset_account_lock") {
      switch ($system) {
          case 'api':
          case 'main':
          return 'reset_mykinso_account_lock';
          case 'pro':
          return 'reset_hospital_account_lock';
          default:
          return 'reset_mykinso_account_lock';
      }
  }
}
