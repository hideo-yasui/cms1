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

  @INS_SQL_LOG("auth", "logout", $_SESSION["sLoginID"]."/ret=[".$loginPage."]");
  sessionDestroy();
  if (isset($_GET["error"])) {
    $loginPage = "sessionTimeout";
  }
  header("Location: ".$loginPage);
  exitProc($DBCON);
}
