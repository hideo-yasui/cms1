<?php
require_once( $gPathList["lib"]."lib_web.php" ) ;
require_once( $gPathList["lib"]."lib_string.php" ) ;
require_once( $gPathList["lib"]."lib_file.php" ) ;
//s3見に行くと遅くなる
//require_once( $gPathList["lib"]."aws_s3.php" );
require_once( $gPathList["lib"]."authenticate.php" );

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
$nowTime = time() ;

// $nowDateTime = date("Y-m-d H:i:s", $nowTime );
$date = new DateTime();
$nowDateTime = $date->format('Y-m-d H:i:s'); // DateTime に変更
$nowDateOnly = $date->format('Y-m-d'); // Date に変更

$gDebugMsgList = array() ;

$mustErrMsgList = array() ;
$formErrMsgList = array() ;

$ERROR = "";

if (!isset($PLG_dieSimpleJSON)) {
	$PLG_dieSimpleJSON = false;
}


// reload 検出
$PLG_possible_reloaded = false;

//---------------------------------------------------------------------------
// セッション管理      ※$SESSION_FLG = "OFF" がセットされるのは logout.php
//---------------------------------------------------------------------------
if ( !isset( $SESSION_FLG ) ) {
	$SESSION_FLG = "ON" ;
}
if ( $SESSION_FLG == "ON" ) {
	session_cache_limiter("must-revalidate");
	session_cache_expire (0);
	//ここでセッション開始
	session_start();
}


//---------------------------------------------------------------------------
// スーパーグローバル変数の展開
// TODO 本来スーパーグローバル変数は直接操作すべきでない。
// 以下は非常に影響範囲の広い処理だが、修正が必要 2017/03/06 岩田
//---------------------------------------------------------------------------
// 現状、標準設定で行う ---------------------------------------------
__WEBIO_PrmIN( $_POST ) ; // LF の場合 MySql内はエスケープしない
extract($_POST, EXTR_OVERWRITE) ; // POST
__WEBIO_PrmIN( $_GET ) ; // LF の場合 MySql内はエスケープしない
extract($_GET, EXTR_OVERWRITE) ; // GET
__WEBIO_PrmIN( $_FILES ) ; // LF の場合 MySql内はエスケープしない

//---------------------------------------------------------------------------
// DB コネクト
//---------------------------------------------------------------------------
function dbConnect(){
	$DB_PORT = "" ; // default:5432
	$persistent = ""; //持続的接続をする場合 p:を設定する
	$gEnvList = $GLOBALS['gEnvList'];

  $DBCON = mysqli_init();
  $DBCON->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
  $DBCON->real_connect($persistent.$gEnvList["DB_host"], $gEnvList["DB_user"], $gEnvList["DB_pass"], $gEnvList["DB_name"], intval($DB_PORT));

	if ( $DBCON ) {
		mysqli_set_charset($DBCON , "utf8") ;
	}
	else {
		@TXT_LOG("dberr", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "mysqli_connect",  $gEnvList["DB_host"], $gEnvList["DB_user"], "xxxx", $gEnvList["DB_name"], intval($DB_PORT) );
		return false;
	}
	return $DBCON;
}
$DBCON = dbConnect();//@mysqli_connect( $persistent.$gEnvList["DB_host"], $gEnvList["DB_user"], $gEnvList["DB_pass"], $gEnvList["DB_name"], intval($DB_PORT) ) ;

if($DBCON){
	include_once( $gPathList["lib"]."configQuery.php");

	//---------------------------------------------------------------------------
	// リファラ情報のセット
	//---------------------------------------------------------------------------
	if (isset($_SERVER["HTTP_REFERER"])){
		$_SESSION["HTTP_REFERER"] = $_SERVER["HTTP_REFERER"] ;
	}

}


//---------------------------------------------------------------------------
// アタック対応：httpレスポンス分割攻撃
// sample：https://xxxxx.test.domain/index.html/%22;%0D%0A//--%3E%0D%0Aalert%28document.cookie%29;%0D%0A//%3C!--%20HTTP/1.0
//---------------------------------------------------------------------------
if ( $SESSION_FLG == "ON" && isset($_SERVER["REQUEST_URI"])) {
	$request_uri =$_SERVER["REQUEST_URI"] ;
	$chkAttackFlg = 0 ;
	if ( stripos( $request_uri, "%0d" ) || stripos( $request_uri, "%0a" ) ){
		// 強制ログアウト
		//if ( !$gDebugFlg ) include_once( sprintf( "%s/logoff.php", $_SERVER["DOCUMENT_ROOT"] ) ) ;
		authenticate_error( "httpレスポンス分割と思われる挙動を検知しました", "request_uri:[$request_uri]" );
		die();
	}
}
