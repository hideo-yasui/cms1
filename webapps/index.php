<?php
// ===========================================================
/**
* index.php
 * フロントコントローラー
 * @return void
 */
include_once(dirname(__FILE__).'/../lib/init.php'); // 共通
require_once($gPathList["lib"].'application.php');
require_once(dirname(__FILE__) . '/controller/commonApplication.php');
$app = new commonApplication(true);
$app->run();
