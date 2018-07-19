<?php
/**
* 配列データファイル出力
* @param string $file_prefix 出力ファイル接頭子
* @param array $input_array 対象配列データ
* @param string $encode 出力文字コード
* @return array
*/
function array_output_file($file_prefix, $input_array, $file_type="csv", $encode=""){
	if (!isset($encode) || empty($encode)){
		//encode指定がない場合、デフォルト：utf-8
		$encode = "UTF-8";
		if ($file_type === "csv"){
			//csv出力時は、sjis相当
			if(preg_match('/Windows/', $_SERVER['HTTP_USER_AGENT'])){
				$encode = "SJIS-WIN";
			}
			else {
				$encode = "CP932";
			}
		}
	}

	//日付とクエリコードより、fileidを生成
	$todate = date("Ymd_His");
	$fileid = $todate."_".getSecureKey(6);
	$file_name = $file_prefix."_".$fileid.".".$file_type;
	$delimita = ",";
	$ex = getFileExtend($file_name);
	if($ex=="tsv") $delimita = "\t";

	//DBから取得したデータを出力するtextに変換
	$convert = conv_array_to_text($input_array, $delimita, $encode);
	if($convert===""){
		return $convert;
	}
	//ファイル出力
	$text = $convert["result"];
	$path = $GLOBALS['gPathList']["upload"].$fileid;
	if(!filewrite($path, $text)){
		//ファイル出力エラー
		return null;
	}
	$file = array(
		'fileid' => $fileid,
		'filename' => $file_name,
		'filesize' => filesize($path),
		'filetype' => mime_content_type($path),
		'status' => "success",
		'message' => "",
		'description' => ""
	);
	return $file;
}

/**
* ファイル書き込み
* @param string $path 保存ファイルパス
* @param string $text 保存テキスト
* @return boolean
*/
function filewrite($path, $text){
	if(file_exists($path) ) {
		unlink($path);
	}
	touch($path);
	chmod($path, 0755);
	$fp = @fopen( $path, "w" );
	$res = 0;
	if(flock($fp, LOCK_EX)){
		//fputs($fp, mb_convert_encoding($text, "UTF-8", "auto"));
		@TXT_LOG("post",$_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "filewrite", $path."/".$text );
		fputs($fp, $text);
		flock($fp, LOCK_UN);
	}
	else {
		$res = 1;
	}
	if($res == 0){
		fclose($fp);
		if(is_file($path)){
			return true;
		}
	}
	return false;
}
/**
* ファイル拡張子取得
* @param string $path 対象ファイルパス
* @return string
*/
function getFileExtend($path){
	$split = explode('.', $path);
	return $split[count($split)-1];
}
/**
* サブフォルダ作成
* @param string $path 対象ファイルパス
* @param string $current カレントファイルパス
* @return string
*/
function mksubdir($path, $current){
	$paths = explode('/', $path);
	$current = $current;
	for($i=0;$i<count($paths)-1;$i++){
		$folder = $paths[$i];
		if(empty($folder)) continue;
		$current .= $folder;
		if(!file_exists($current)){
			mkdir($current, '0755');
		}
		$current .='/';
	}
}
function delfiles($dir){
	TXT_LOG("request", $dir);
	if ($dirHandle = opendir ($dir)) {
		while (false !== ($fileName = readdir($dirHandle))) {
			if ( $fileName != "." && $fileName != ".." ) {
				unlink ($dir.$fileName);
			}
		}
		closedir ( $dirHandle );
	}
}
/**
* zipファイル解凍
* @param string $zip_path 対象zipファイル
* @param string $unzip_dir 保存フォルダ（uploadフォルダ）
* @param string $file_mod 保存ファイルの権限
* @return array
*/
function file_unzip($zip_path, $unzip_dir, $file_mod = 0755) {
	$zip = new ZipArchive();
	$files = [];
	if ($zip->open($zip_path) !== true){
		@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "file_unzip",$zip_path) ;
	}
	if ($zip->extractTo($unzip_dir) !== true) {
		$zip->close();
		return false;
	}
	for ($i = 0; $i < $zip->numFiles; $i++) {
		$files[] = $zip->getNameIndex($i);
		$path = $unzip_dir.$zip->getNameIndex($i);
		if(file_exists($path)){
			chmod($path, $file_mod);
		}
	}
	$zip->close();
	return $files;
}
function json_file_write($savefile, $data){
	$json = fopen($savefile,  'w+b');
	fwrite($json, json_encode($data));
	fclose($json);
}
function json_file_read($savefile){
	if(!file_exists($savefile)){
		return null;
	}
	$json = file_get_contents($savefile);
	$json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
	$obj = json_decode($json,true);
	return $obj;
}
