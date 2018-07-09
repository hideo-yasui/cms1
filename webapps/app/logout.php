<?php
/**
 * ログアウト
 * @param dbconnect $DBCON
 **/
function showAddonPage($DBCON) {
  $loginPage = "/login";

	// 未ログイン時の場合
	if (empty($_SESSION["sLoginID"])) {
		header("Location: ".$loginPage);
		exitProc($DBCON);
	}

 // @INS_SQL_LOG("auth", "logout", $_SESSION["sLoginID"]."/ret=[".$loginPage."]");
  sessionDestroy();
  if (isset($_GET["error"])) {
    $loginPage = "sessionTimeout";
  }
  header("Location: ".$loginPage);
  exitProc($DBCON);
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
