<?php
/**
 * 認証処理を行い、結果を配列で返す
 * @param  Object : DB Connection
 * @return Array
 */
function getAuthenticateResponse($DBCON) {
    $response = array(
        'status'      => "success",
        'message'     => "",
        'description' => ""
    );
    // validate
    if (!isset($_SESSION["sLoginKey"])
    || !isset($_SESSION["sAccessTime"])
    || !isset($_SESSION["sLoginSystem"])
    || !isset($_SESSION["sCheckTime"])
    || !isset($_SESSION["sLimitTime"])) {
        $response = array(
        'status'      => "failed",
        'message'     => "E_AUTH_FAIL",
        'description' => "undefined index: required"
        );
        return $response;
    }

    // init
    $checkList = array() ;
    $checkList["login_ip"]  = $_SERVER["REMOTE_ADDR"];
    $checkList["login_id"]  = $_SESSION["sLoginID"];
    $checkList["login_key"] = $_SESSION["sLoginKey"];
    $chkTIME    = (time() - $_SESSION["sAccessTime"]);
    $loginInfo  =  "login_ip=".$checkList["login_ip"];
    $loginInfo .= "/login_id=".$checkList["login_id"];
    $loginInfo .= "/login_key=".$checkList["login_key"];

    // check authenticate by the sCheckTime
    if ($chkTIME > $_SESSION["sCheckTime"]
        && $chkTIME > $_SESSION["sLimitTime"]) {
            $response = array(
            'status'      => "failed",
            'message'     => "E_AUTH_TIMEOUT",
            'description' => "chkTime(".$chkTIME.")>lmtTIME(".$_SESSION["sLimitTime"].")"
            );
            return $response;
    }
    //check database authenticate
    $result = execConfigQuery($DBCON, $_SESSION["sLoginSystem"]."_authenticate", $checkList);
    @INS_SQL_LOG("auth", "authenticate start", 'login id=['.$checkList["login_id"].'],'.'key=['.$checkList["login_key"].']['.$result["status"].']');
    if ($result["status"] !== "success" || count($result["data"]) === 0) {
        $response = array(
        'status'      => "failed",
        'message'     => "E_AUTH_FAIL",
        'description' => "[".$loginInfo."]/".$result["status"]."/".count($result["data"])
        );
        return $response;
}
  if ($_SESSION["sLoginSystem"] !== $GLOBALS['gEnvList']['system']) {
      $response = array(
      'status'      => "failed",
      'message'     => "E_AUTH_FAIL",
      'description' => "[".$loginInfo."]/".$result["status"]."/".count($result["data"])
      );
      return $response;
  }
  // Authenticate sceess
  $_SESSION["sAccessTime"] = time(); // 最後にアクセスした時間をセット（タイムアウトに使用する）
  return $response;
}
/**
 * セッション破棄する
 * @return void
 */
function sessionDestroy(){
    session_cache_limiter("nocache");
    session_cache_expire (0);
    session_regenerate_id( true ) ;

    $_SESSION = array() ;
    $sesName = session_name() ;
    if ( isset( $_COOKIE[$sesName] ) ) {
    	$setExpTime = ( time() - ( 60 * 60 * 24 * 365 ) ) ; // 有効期限を１年前にセット
    	setcookie( $sesName, "", $setExpTime, "/" ) ;
    }
    session_destroy();
}
