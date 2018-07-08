<?php
//---------------------------------------------------------------------------
/*
　基本パス取得・セット（$gPathListのセット）
　環境データセット（env_set.php のinclude）
*/
//---------------------------------------------------------------------------

//---------------------------------------------------------------------------
// 基本となるパス情報のセット
//---------------------------------------------------------------------------
$gPathList = array() ;
//$gPathList["svroot"] = $_SERVER["DOCUMENT_ROOT"] ;
$gPathList["svroot"] = "/vagrant/cms1/webapps";

$gPathList["sv_top"] = substr( $gPathList["svroot"], 0, strrpos( $gPathList["svroot"], "/" ) ) ;
$gPathList["u_home"] = substr( $gPathList["sv_top"], 0, strrpos( $gPathList["sv_top"], "/" ) ) ;
$gPathList["curent"] = dirname( $_SERVER["PHP_SELF"] ) ;

// ディレクトリ構成
$gPathList["page"]       = $gPathList["svroot"] . '/page';
$gPathList["logs"]       = $gPathList["svroot"] . '/../../logs/';
$gPathList["lib"]       = $gPathList["svroot"] . '/../lib/';
$gPathList["upload"]       = $gPathList["svroot"] . '/../../upload/';
// 一時フォルダが存在しない場合はフォルダ作成
if(!file_exists ($gPathList["logs"])){
	mkdir($gPathList["logs"], '0755');
}
if(!file_exists ($gPathList["upload"])){
	mkdir($gPathList["upload"], '0755');
}
include_once(dirname(__FILE__).'/../lib/env_set.php'); // 注意 管理サイトユーザは別
