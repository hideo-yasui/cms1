<?php
// ===========================================================
/**
* index.php
 * フロントコントローラー
 * @return void
 */
include_once(dirname(__FILE__).'/../lib/define.php'); // 共通
include_once($gPathList["lib"].'init.php'); // 注意 管理サイトユーザは別
require_once($gPathList["lib"].'application.php');
require_once(dirname(__FILE__) . '/app/commonApplication.php');
if($DBCON){
	$app = new commonApplication($DBCON, true);
	$app->run();
}
