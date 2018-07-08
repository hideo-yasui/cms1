<?php
require_once( $gPathList["lib"]."vendor/aws.phar" );
/**
* Controller.
*/
class S3
{
	protected $default_backet = 'mykinso-upload';
	protected $region = 'ap-northeast-1';
	protected $version = 'latest';
	protected $sdk;
	protected $client;
	/**
	* コンストラクタ
	* aws configureにて、設定したIAMを使う
	*/
	public function __construct($set_backet='', $set_profile='default')
	{
		if(!empty($set_backet)){
			//s3のバケット名
			$this->default_backet = $set_backet;
		}
		try {

			$aws_credential = array(
				'version' => $this->version,
				'region'  => $this->region
			);
			$key = getenv('AWS_S3_ACCESS_KEY');
			$secret = getenv('AWS_S3_SECRET_KEY');
			if(!empty($key) && !empty($secret)){
				$aws_credential['credentials'] = array('key' => $key, 'secret' => $secret);
			}
			else {
				$aws_credential['profile'] = 'default';
			}
			// S3に接続するためのクライアント設定
			$this->sdk = new Aws\Sdk($aws_credential);
			$this->client = $this->sdk->createS3();
		}
		catch (Exception $e){
			echo $e->getMessage();
			@TXT_LOG("error",$_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, $e->getMessage());
		}
	}
	/**
	* ファイルアップロード
	* @param string $file_path 物理ファイルパス
	* @param string $s3_save_file s3側のファイルパス
	* @param string $file_name metadata.nameに登録する
	* @return array
	*/
	public function putfile($file_path, $s3_save_file='', $file_name='')
	{
		$values = array(
			'status'      => "success",
			'data'     => "",
			'message'     => "",
			'description' => ""
		);
		if(empty($file_path) || empty(basename($file_path))
			|| !file_exists($file_path)){
			$values["status"] = "failed";
			$values["message"] = "ERROR";
			$values["description"] = "PARAMATER ERROR" ;
			return $values;
		}
		if(empty($s3_save_file)){
			//保存ファイル名の指定がない場合は、対象ファイルの名前を使う
			$s3_save_file = basename($file_path);
		}
		$mimetype = mime_content_type($file_path);

		$metadata = array();
		if(!empty($file_name)){
			$metadata['name'] = $file_name;
		}
		$putdata = array(
			'Bucket' => $this->default_backet,
			'Key' => $s3_save_file,
			'SourceFile'=> $file_path,
			'Metadata'=> $metadata,
			'ServerSideEncryption' => 'AES256'
		);
		if(empty($mimetype)){
			$putdata['ContentType'] = $mimetype;
		}
		try {
			$result = $this->client->putObject($putdata);
		}
		catch(Exception $e){
			$values["status"] = "failed";
			$values["message"] = "ERROR";
			$values["description"] = "UPLOAD FAILED:".$e->getMessage();
			return $values;
		}
		return $values;
	}
	/**
	* ファイルダウンロード
	*
	* @param string $s3_save_name s3側のファイルパス
	* @param string $s3_save_file 物理ファイルを保存するパス
	* @param boolean $is_force_save trueの場合上書き保存する
	* @return array
	*/
	public function getfile($s3_save_name, $save_file, $is_force_save=true)
	{
		$values = array(
			'status'      => "success",
			'data'     => "",
			'message'     => "",
			'description' => ""
		);
		if(empty($s3_save_name)){
			$values["status"] = "failed";
			$values["message"] = "ERROR";
			$values["description"] = "PARAMATER ERROR" ;
			return $values;
		}
		if($is_force_save===false	&& file_exists($save_file)){
			//上書き保存しない、かつ、ファイルが存在する場合
			$values["status"] = "failed";
			$values["message"] = "ERROR";
			$values["description"] = "FILE ALREADY EXIST" ;
			return $values;
		}
		try {
			$result = $this->client->getObject([
				'Bucket' => $this->default_backet,
				'Key' => $s3_save_name
			]);
			if(!empty($save_file)){
				//保存するパスの設定があれば物理ファイルを保存する
				$fp = fopen($save_file, 'w');
				fwrite($fp, $result['Body']);
			}
		}
		catch(Exception $e){
			$values["status"] = "failed";
			$values["message"] = "ERROR";
			$values["description"] = "DOWNLOAD FAILED:".$e->getMessage();
			return $values;
		}
		$values["data"] = $result;
		return $values;
	}
}
