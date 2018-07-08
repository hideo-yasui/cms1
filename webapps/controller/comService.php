<?php

class comService extends Controller
{
	protected $auth_actions = array('');

	public function search($params){
		$result = $this->badRequestResponce();
		if(isset($params['query_code'])){
			if ($this->request->isGet()) {
				$result = $this->application->dbi->execConfigSearch($params['query_code'], $_GET);
			}
		}
		return $result;
	}
	public function query($params){
		$result = $this->badRequestResponce();
		if(isset($params['query_code'])){
			if (!empty($params["method"])) $params['query_code'] .= $params['method'];
			if ($this->request->isGet()) {
				$params = array_merge($params, $_GET);
			}
			else if ($this->request->isPost()) {
				$params = array_merge($params, $_POST);
			}
			$result = $this->application->dbi->execConfigQuery($params['query_code'], $params);
		}
		return $result;
	}
	public function download($params){
		if ($this->request->isGet()) {
			if($this->_getfile($params, false) === true){
				$this->exitProc();
			}
		}
		return $this->notFoundResponce();
	}
	public function getfile($params){
		if ($this->request->isGet()) {
			if($this->_getfile($params, true) === true){
				$this->exitProc();
			}
		}
		view("notfound.html", array());
		$this->exitProc();
	}
	private function _getfile($params, $is_inline){
		global $gPathList;
		$values = $this->application->dbi->execConfigQuery("get_file", $params);
		if($values["status"] =="success" && count($values["data"]) > 0){
			$save_file_path = $gPathList["upload"].$values["data"][0]["fileid"];
			$fileid = $values["data"][0]['fileid'];
			$filename = $values["data"][0]['filename'];
			$values = $this->application->dbi->downloadfile($fileid, $filename, $save_file_path, $is_inline);
			return true;
		}
		return false;
	}
	public function upload($params){
		global $gPathList;
		$result = $this->badRequestResponce();
		if ($this->request->isPost()) {
			$todate = date("Ymd_His");
			$fileid = $todate."_".getSecureKey(6);
			$result = $this->uploadfile($_POST["formid"], $gPathList["upload"], $fileid);
			if($result["status"] ==="success"){
				$values = $this->application->dbi->addConfigFile($values["data"], $_POST["remark"]);
				$result["status"] = $values["status"];
				$result["message"] = $values["message"];
				$result["description"] = $values["description"];
			}
		}
		return $result;
	}
	public function export($params){
		$result = $this->badRequestResponce();
		if(isset($params['query_code'])){
			if ($this->request->isGet()) {
				$outputfile = "csv";
				if (!empty($_GET["outputfile"])) $outputfile = $_GET["outputfile"];
				$encode = "";
				if (!empty($_GET["encode"])) $encode = $_GET["encode"];
				$result = $this->application->dbi->exportFile($query_code, $_GET, $outputfile, $encode);
			}
		}
		return $result;
	}
	public function import($params){
		$result = $this->upload($params);
		if($result["status"] ==="success"){
			$path = $gPathList["upload"].$fileid;
			$values = $this->application->dbi->importFile($query_code, $path, $_POST);
			$result["status"] = $values["status"];
			$result["message"] = $values["message"];
			$result["description"] = $values["description"];
		}
		return $result;
	}
	public function downloadfile($fileid, $filename, $savefile, $is_inline=false){
		$values = array(
			'status'      => "success",
			'data'     => "",
			'message'     => "",
			'description' => ""
		);
		if (!file_exists($savefile)) {
			@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "aws_s3->getfile",$values["message"], $values["description"]) ;
			return $values;
		}

		$isStream = 0;
		$filename = ' filename*=UTF-8\'\''.rawurlencode($filename);
		if($is_inline === false) $filename = "attachment; ".$filename;
		if($isStream == 1){
			header("Content-Disposition: inline; ".$filename );
			header("Content-Type: application/octet-stream");
		}
		else {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$filetype = $finfo->file($savefile);
			header("Content-Disposition: ".$filename );
			header("Content-Type: ".$filetype);
		}
		$filesize = filesize($savefile);
		header("Content-Length: ".$filesize);
		readfile($savefile);
		return $values;
	}
	public function uploadfile($formid,$folder,$fileid){
		$result = $this->save_uploadfile($formid,$folder,$fileid);
		if($result["status"]!=="success"){
			@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "save_uploadfile",$result["message"], $result["description"]) ;
			return $result;
		}
		return $result;
	}
	private function save_uploadfile($formid,$folder,$fileid){
		$values = array(
			'status'      => "success",
			'data'     => array(),
			'message'     => "",
			'description' => ""
		);

		if (!is_uploaded_file($_FILES[$formid]["tmp_name"])) {
			$values["status"] = "failed";
			$values["message"] = "ERROR" ;
			$values["description"] = "NO FILE" ;
			return $values;
		}
		//uploadしたファイルを保存
		$path = $folder.$fileid;
		if (!move_uploaded_file($_FILES[$formid]["tmp_name"], $path)) {
			$values["status"] = "failed";
			$values["message"] = "ERROR" ;
			$values["description"] = "UPLOADFILE MOVE FAILED" ;
			return $values;
		}
		$filename = basename($_FILES[$formid]["name"]);
		if($values["status"]==="success"){
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$filetype = $finfo->file($path);
			$values["data"] = array(
				"fileid"=>$fileid,
				"filename"=>$filename,
				"filesize"=>filesize($path),
				"filetype"=>$filetype
			);
		}
		else {
			@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "aws_s3->putfile",$values["message"], $values["description"]) ;
		}
		return $values;
	}
	public function exitProc(){
		exitProc($this->application->dbi->db);
	}
}
