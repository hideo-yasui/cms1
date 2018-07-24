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
$gPathList["_cache"]       = $gPathList["svroot"] . '/../../_cache/';
// 一時フォルダが存在しない場合はフォルダ作成
if(!file_exists ($gPathList["logs"])){
	mkdir($gPathList["logs"], '0755');
}
if(!file_exists ($gPathList["upload"])){
	mkdir($gPathList["upload"], '0755');
}
if(!file_exists ($gPathList["_cache"])){
	mkdir($gPathList["_cache"], '0755');
}

$gEnvList = array();
$gEnvList["version"] 	= '1.0.0.2' ;

// Slack WebHock End Point
$gEnvList["SLACK_endpoint"]		= "";
$gEnvList["SLACK_log_channel"]		= "system_log";
$gEnvList["SLACK_template"]		= array();
$gEnvList["SLACK_template"]["INFO"] = array("name" => "INFO", "icon" => ":speech_balloon:");
$gEnvList["SLACK_template"]["WARNING"] = array("name" => "WARNING", "icon" => ":warning:");
$gEnvList["SLACK_template"]["ERROR"] = array("name" => "ERROR", "icon" => ":no_entry:");


//----------------------------------------------------------------------------------
//テキストログ出力/DBログ出力を無効にしたい場合に、query_codeを追加する
$gEnvList["ignorelog_query_code"] = array();
//$gEnvList["ignorelog_query_code"][] = "get_page";
$gEnvList["ignorelog_query_code"][] = "get_treemenu";
$gEnvList["ignorelog_query_code"][] = "get_group_code_enc";
$gEnvList["ignorelog_query_code"][] = "t_logs_ins";
$gEnvList["ignorelog_query_code"][] = "t_logs";

//次のpost変数は、秘匿する
$gEnvList["hidden_post_paramater"] = array();
$gEnvList["hidden_post_paramater"][] = "password_old";
$gEnvList["hidden_post_paramater"][] = "password";
$gEnvList["hidden_post_paramater"][] = "password_confirm";

//次のquery_codeは、認証処理を省略する
$gEnvList["authenticate_skip"] = array();
$gEnvList["authenticate_skip"][] = "get_group_code_enc";
$gEnvList["authenticate_skip"][] = "get_taxon_all";
$gEnvList["authenticate_skip"][] = "get_taxon_by_id";
$gEnvList["authenticate_skip"][] = "user_register_upd";
$gEnvList["authenticate_skip"][] = "t_temp_users_ins";
$gEnvList["authenticate_skip"][] = "check_age_is_mature";
$gEnvList["authenticate_skip"][] = "check_is_user_by_email";


require_once( $gPathList["lib"]."lib_web.php" ) ;
require_once( $gPathList["lib"]."lib_string.php" ) ;
require_once( $gPathList["lib"]."lib_file.php" ) ;
//s3見に行くと遅くなる
//require_once( $gPathList["lib"]."aws_s3.php" );

$errCode = 0;
function TXT_LOG(){
	$gPathList = $GLOBALS['gPathList'];
	$now = date("Ym");
	$message = "";
	$argc = func_num_args();
	for($i=0;$i<$argc;$i++){
		$arg = func_get_arg($i);
		if($i==0) $filepath = $gPathList["logs"].$now."_".$arg.".log";
		else $message = $message.$arg."\t";
	}
	$message = str_replace("\r\n"," ",$message);
	$message = str_replace("\r"," ",$message);
	$message = str_replace("\n"," ",$message);

	@logWrite($filepath, $message);
}
function logWrite($filepath, $msg){
	$arrTime = explode('.',microtime(true));
	$now = date('Y-m-d H:i:s', $arrTime[0]) . '.' .$arrTime[1];
	$dat = $now.":".$msg."\n";
	chmod($filepath, 0666);
	file_put_contents($filepath, $dat, FILE_APPEND);
}
//====================================================================================
// ログイン PW を暗号化ルーチンに従い、暗号化する
// $pw：パスワード
// 戻り値：暗号化されたパスワード
//------------------------------------------------------------------------------------
function LC_COM_getPWencode( $pw )
{
	$pw_md5 = md5( $pw ) ;
	$pw_sha1 = sha1( $pw ) ;
	$randomKey = sprintf( "bX15XTTn-Cykinso-Mykinso-5LU5ZGXI" ) ;
	$key_sh1 = sha1( $randomKey ) ;

	$pw_md_top = substr( $pw_md5, 0, 16 ) ;
	$pw_md_bak = substr( $pw_md5, 16 ) ;
	$pw_sh_top = substr( $pw_sha1, 0, 20 ) ;
	$pw_sh_bak = substr( $pw_sha1, 20 ) ;
	$key_top = substr( $key_sh1, 0, 20 ) ;
	$key_bak = substr( $key_sh1, 20 ) ;

	/***
	$retVal = sprintf( "%s / %s / %s / %s / %s / %s", $pw_md_top, $pw_md_bak, $pw_sh_top, $pw_sh_bak, $key_top, $key_bak ) ;
	printf( "pw_md5 : [%s]<br>\n", $pw_md5 ) ;
	printf( "pw_sha1 : [%s]<br>\n", $pw_sha1 ) ;
	printf( "key_sh1 : [%s]<br>\n", $key_sh1 ) ;
	printf( "retVal : [%s]<br>\n", $retVal ) ;
	***/
	$retVal = sprintf( "%s%s%s%s%s%s", $key_bak, $pw_sh_bak, $pw_md_bak, $key_top, $pw_md_top, $pw_sh_top ) ;

	return( $retVal ) ;
}
//========================================================================================================
// 指定されたリストから指定桁数分ランダムで文字列を生成
// $pLst：生成するランダム文字列のリスト
// $keta：生成するランダム文字列の桁数
// 戻り値：生成したランダム文字列
//--------------------------------------------------------------------------------------------------------
function COM_getRandomString( $pLst, $keta )
{
	$maxRan = ( count( $pLst ) - 1 ) ;
	srand((double)microtime()*1000000);
	$retVal = "" ;
	for ( $cnt = 0; $cnt < $keta; $cnt++ ) {
		$gPos = rand( 0, $maxRan ) ;
		$retVal = sprintf( "%s%s", $retVal, $pLst[$gPos] ) ;
	}
	return( $retVal ) ;
}
//========================================================================================================
// ランダムなアルファベットと数字でパスワードを生成
// $keta：生成するパスワードの桁数
// 戻り値：生成したメンバーパスワード
//--------------------------------------------------------------------------------------------------------
function COM_getRandomKey( $keta )
{
	$pLst = array("A","B","C","D","E","F","G","H","J","K","L","M","N","P","Q","R","S","T","U","V","W","X","Y","Z","a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","0","1","2","3","4","5","6","7","8","9","_") ;
	$retVal = COM_getRandomString( $pLst, $keta ) ;
	return( $retVal ) ;
}

//---------------------------------------------------------------------------
// デフォルトパラメータのセット
//---------------------------------------------------------------------------

$ERROR = "";

if (!isset($PLG_dieSimpleJSON)) {
	$PLG_dieSimpleJSON = false;
}


// reload 検出
$PLG_possible_reloaded = false;

session_cache_limiter("must-revalidate");
session_cache_expire (0);
//ここでセッション開始
session_start();



//---------------------------------------------------------------------------
// リファラ情報のセット
//---------------------------------------------------------------------------
if (isset($_SERVER["HTTP_REFERER"])){
	$_SESSION["HTTP_REFERER"] = $_SERVER["HTTP_REFERER"] ;
}


//---------------------------------------------------------------------------
// アタック対応：httpレスポンス分割攻撃
// sample：https://xxxxx.test.domain/index.html/%22;%0D%0A//--%3E%0D%0Aalert%28document.cookie%29;%0D%0A//%3C!--%20HTTP/1.0
//---------------------------------------------------------------------------
if (isset($_SERVER["REQUEST_URI"])) {
	$request_uri =$_SERVER["REQUEST_URI"] ;
	$chkAttackFlg = 0 ;
	if ( stripos( $request_uri, "%0d" ) || stripos( $request_uri, "%0a" ) ){
		header( "Location: /logouot" ) ;
		exit;
	}
}
