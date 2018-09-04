<?php
include_once($_SERVER["DOCUMENT_ROOT"] . '/app/login.php');

/**
 * app
 * アプリ共通のロジックを管理する
 */
class App extends Controller
{
    protected $auth_actions = array('users','logout');
    protected $token_user;
    protected $isValidToken;
    protected $device_user;
    protected $passcode_user;
    protected $email_user;

    function __construct($application, $DBCON) {
      parent::__construct($application, $DBCON);

      // set user by auth token
      if (isset($_SERVER['HTTP_API_TOKEN'])) {
        $result = $this->get_user_by_token();
        if ($result["status"] === "success"
          && isset($result["data"]) && count($result["data"]) > 0) {
          $this->token_user = $result["data"];
          $this->isValidToken = true;
          @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "isset(token_user):".isset($this->token_user)) ;
        }
      }
    }

    // Override For API
    /**
     * アクションを実行
     *
     * @param string $action
     * @param array $params
     * @return array
     */
    public function run($action, $params = array())
    {
        if (!method_exists($this, $action)) {
            return $this->notFoundResponce();
        }

        // check authenticate
        if ($this->needsAuthentication($action)) {
          if(!$this->isValidToken){
                authenticate_error('E_INVALID_API_TOKEN', '');
                return $this->forbiddenResponce();
            }
        }

        $response = $this->$action($params);

        if (!$response) {
          $response = $this->badRequestResponce();
        }

        sendJSONResponse($response);
        exitProc($this->db);
    }

    // API End Points
    /**
    * @param array $params
    * @return json response
    * GET:ログイン済みのユーザーデータを返却する
    * POST:ユーザーデータを登録する
    */
    public function users($params)
    {
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "---------------------users start--------------------------") ;

		$result = $this->badRequestResponce();
        if ($this->request->isPost()){
            $this->_init($_POST);
            $result = $this->post_users($_POST);
        }
        else {
            @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "param[userid]=".$params["user_id"] , "token_user[userid]=".$token_user["user_id"]) ;
            $result = $this->token_user;
        }
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "---------------------users end--------------------------") ;
        $this->sendJSONResponse($result);
    }

    /**
    * パラメータに応じてログイン処理を行い、結果を返却する
    * 初期処理にて、アプリユーザーが存在しなければ追加
    * @param array $params
    * @return json response
    */
    public function login($params)
    {
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "---------------------login start--------------------------") ;
        $result = $this->badRequestResponce();
        if ($this->request->isPost()) {
            $result = $this->post_login($_POST);
        }
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "---------------------login end--------------------------") ;
        $this->sendJSONResponse($result);
    }

  // Services

  /**
  * ユーザー情報取得の初期処理
	* @access protected
	* @param array [$params]
	* @return void
	*/
    protected function _init($params = array())
    {
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "---------------------_init start--------------------------") ;
        if(isset($this->token_user) && isset($this->token_user["device_id"])
                && !empty($this->token_user["device_id"])
                && (!isset($params["device_id"]) || empty($params["device_id"]))){
            //device_idがパラメータになければ、token_user から渡す
            $params["device_id"] = $this->token_user["device_id"];
        }
        if(isset($this->token_user) && isset($this->token_user["email"])
                && !empty($this->token_user["email"])
                && $this->token_user["hospital_id"] == 0
                && (!isset($params["email"]) || empty($params["email"]))){
            //emailがパラメータになければ、token_user から渡す
            $params["email"] = $this->token_user["email"];
        }
        if(isset($this->token_user) && isset($this->token_user["passcode"])
                && !empty($this->token_user["passcode"])
                && $this->token_user["hospital_id"] >= 0
                && (!isset($params["passcode"]) || empty($params["passcode"]))){
            //passcodeがパラメータになければ、token_user から渡す
            $params["passcode"] = $this->token_user["passcode"];
        }

        //デバイスIDでユーザーを取得
        $result = $this->get_user_by_deviceid($params);
        if($result["status"] === "success"
            && isset($result["data"]) && count($result["data"])==1){
            $this->device_user = $result["data"][0];
            @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "isset(device_user):".isset($this->device_user)) ;
        }

        //emailでユーザーを取得
        $result = $this->get_user_by_email($params);
        if($result["status"] === "success"
            && isset($result["data"]) && count($result["data"])==1){
            $this->email_user = $result["data"][0];
            @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "isset(email_user):".isset($this->email_user)) ;
        }

        //passcodeでユーザーを取得
        $result = $this->get_user_by_passcode($params);
        if($result["status"] === "success"
            && isset($result["data"]) && count($result["data"])==1){
            $this->passcode_user = $result["data"][0];
            @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "isset(passcode_user):".isset($this->passcode_user)) ;
        }

        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "---------------------_init end--------------------------") ;

    }
    /**
    * ユーザーログイン
	* @access private
	* @param array $params
	* @return json response
	*/
    private function post_login($params)
    {
        $result = $this->badRequestResponce();
        if(isset($params["device_id"]) && !empty($params["device_id"])){
            $result = $this->get_user_by_deviceid($params);
            if($result["status"] !== "success"
                || !isset($result["data"]) || count($result["data"])<1){
                //deviceユーザーがなければ、追加
                $this->app_user_ins($params);
            }

            $result = postLogin($this->db, $params);
            @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "postLogin:", $result["status"]) ;
            if($result["status"] !== "success")  {
                //email or passcodeの入力が間違っている場合、エラーを返す
                return $result;
            }


            else {
                $result = $this->get_user_by_session();
                if ($result["status"] === "success"
                  && isset($result["data"]) && count($result["data"]) > 0) {
                  $this->token_user = $result["data"];

                  @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "isset(token_user):".isset($this->token_user)) ;
                }
                $this->_init($params);
                $this->device_user_merge_upd($params);

                $token = $this->app_user_token_upd($this->token_user['user_id']);
                $result['data'] = array('api_token' => $token);
            }
        }
        return $result;
    }
    /**
    * ユーザー登録
    * passcodeユーザーだと使えない
	* @access private
	* @param array $params
	* @return json response
	*/
    private function post_users($params)
    {
        $result = $this->badRequestResponce();

        if(!isset($params["device_id"]) || empty($params["device_id"]) || !isset($this->device_user)) {
            @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "post_users", "device_userがない") ;
            return $result;
        }

        if(!empty($this->token_user["passcode"])) {
            @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "post_users", "passcode_userはこの機能は使えません") ;
            return $this->forbiddenResponce();
        }

        if(!isset($this->email_user)){
            if(isset($params["auth_code"]) && !empty($params["auth_code"])){
                @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "post_users", "本登録更新") ;
                $result = $this->get_user_by_auth_code($params);
                if($result["status"] === "success"){
                    return $this->user_register_upd($params);
                }
            }
            else {
                @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "post_users", "仮登録") ;
                return $this->user_pre_ins_upd($params);
            }
        }
        else {
            //登録済みの場合
            return $this->getResponce("failed", "E_ALREADY_SAVED");
        }
        return $result;
    }
  /**
  * $_SERVER["HTTP_API_TOKEN"]よりユーザー情報を取得
  * @access protected
	* @param array $params
	* @return json response
	*/
  protected function get_user_by_token(){
    $result = $this->badRequestResponce();
      if (isset($_SERVER["HTTP_API_TOKEN"])) {
        $result = execConfigQuery($this->db, 'get_user_by_token', array('token' => $_SERVER["HTTP_API_TOKEN"]));
      if (!isset($result["data"]) || count($result["data"]) < 1) {
        $result = $this->notExistResponce();
      }
      else {
        $result["data"] = $result["data"][0];
      }
    }
    return $result;
  }

  /**
  * $_SESSION['sUsrID']よりユーザー情報を取得
  * @access protected
	* @param array $params
	* @return json response
	*/
  protected function get_user_by_session() {
    $result = $this->badRequestResponce();
      if (isset($_SESSION['sUsrID'])) {
        $result = execConfigQuery($this->db, 'get_user_by_session');
      if (!isset($result["data"]) || count($result["data"]) < 1) {
        $result = $this->notExistResponce();
      }
      else {
        $result["data"] = $result["data"][0];
      }
    }
    return $result;
  }
    /**
    * emailよりDBからユーザー情報を取得
    * @access private
	* @param array $params
	* @return json response
	*/
    private function get_user_by_email($params){
        $result = $this->badRequestResponce();
        if(isset($params["email"]) && !empty($params["email"])){
            $result = execConfigQuery($this->db, 'get_user_by_email', array("email" => $params["email"]));
            if($result["status"] !=="success"){
                $result = $this->errorResponce();
            }
        }
        return $result;
    }
    /**
    * passcodeよりDBからユーザー情報を取得
    * @access private
	* @param array $params
	* @return json response
	*/
    private function get_user_by_passcode($params){
        $result = $this->badRequestResponce();
        if(isset($params["passcode"]) && !empty($params["passcode"])){
            $result = execConfigQuery($this->db, 'get_user_by_passcode', array("passcode" => $params["passcode"]));
            if($result["status"] !=="success"){
                $result = $this->errorResponce();
            }
        }
        return $result;
    }
    /**
    * deviceIdよりDBからユーザー情報を取得
    * @access protected
    * @param array $params
    * @return json response
    */
    protected function get_user_by_deviceid($params){
        $result = $this->badRequestResponce();
        if(isset($params["device_id"]) && !empty($params["device_id"])){
            $result = execConfigQuery($this->db, 'get_user_by_deviceid', array("device_id" => $params["device_id"]));
            if($result["status"] !=="success"){
                $result = $this->errorResponce();
            }
        }
        return $result;
    }
    /**
    * auth_codeよりDBからユーザー情報を取得
    * @access private
    * @param array $params
    * @return json response
    */
    private function get_user_by_auth_code($params){
        $result = $this->badRequestResponce();
        if(isset($params["auth_code"]) && !empty($params["auth_code"])){
            $result = execConfigQuery($this->db, 'get_user_by_auth_code', array("auth_code" => $params["auth_code"]));
            if($result["status"] !=="success"){
                $result = $this->errorResponce();
            }
            if(!isset($result["data"]) || count($result["data"])<1){
                $result = $this->getResponce("failed", "E_EXPIRED");
            }
        }
        return $result;
    }
    /**
    * トークンの更新 ユーザの認証用トークン情報を更新します
    * @access protected
    * @param integer $user_id
    * @return String
    */
    protected function app_user_token_upd($user_id)
    {
      $token = $this->get_user_token($user_id);
      $result = execConfigQuery($this->db, 'app_user_token_upd',
        array(
          'user_id' => $user_id,
          'api_token'   => $token,
        ));
      if($result["status"]!=="success"){
          return "";
      }
      return $token;
    }
    /**
    * アプリユーザー登録 （emailをもたない、device_idのみのユーザー）
    * @access protected
    * @param array $params
    * @return json response
    */
    protected function app_user_ins($params)
    {
        $result = $this->badRequestResponce();
        if(isset($params["device_id"]) && !empty($params["device_id"])){
            @INS_SQL_LOG("auth", "app_user_ins", "\ndeviceid=[".$params["device_id"]."]");
            $result = execConfigQuery($this->db, 'app_user_ins',
                array(
                    "device_id" => $params["device_id"],
                    'login_ip' => $_SERVER["REMOTE_ADDR"]
                ));
        }
        return $result;
    }
    /**
    * アプリユーザーを本ユーザーとして更新
    * @access private
    * @param array $params
    * @return json response
    */
    private function user_register_upd($params)
    {
        $result = $this->badRequestResponce();
        if(isset($params["email"]) && !empty($params["email"])
        && isset($params["device_id"]) && !empty($params["device_id"])
        && isset($params["auth_code"]) && !empty($params["auth_code"])
            && isset($params["password"]) && !empty($params["password"])){
            $result = execConfigQuery($this->db, 'user_register_upd', $params);
            //emailでユーザーを取得（登録完了確認）
            $result = $this->get_user_by_email($params);
            if($result["status"] === "success"
                && isset($result["data"]) && count($result["data"])==1){
                $this->email_user = $result["data"][0];
                //登録完了後、email+passwordで再ログインする
                //ログインするとアプリ会員は自動昇格する
                $result = $this->post_login($params);
                //通知メール送信
                execConfigMail($this->db);
            }
        }
        return $result;
    }
    /**
    * アプリユーザーを本ユーザーに統合
    * @access protected
    * @param array $params
    * @return json response
    */
    protected function device_user_merge_upd($params)
    {
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "-------------device_user_merge_upd(".$params["device_id"].") start----------------") ;
        $result = $this->badRequestResponce();
        $merge_user_id = 0;
        if(isset($this->device_user) && isset($this->email_user)
            && $this->email_user["user_id"] !== $this->device_user["user_id"]){
            $merge_user_id = $this->email_user["user_id"];
        }
        if($merge_user_id === 0 && isset($this->device_user) && isset($this->passcode_user)
            && $this->passcode_user["user_id"] !== $this->device_user["user_id"]){
            $merge_user_id = $this->passcode_user["user_id"];
        }
        if($merge_user_id !== 0){
            //アプリユーザーと、
            //mailユーザー or passcodeユーザーのIDが異なる場合は、マージする
            @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "merge実行前 device_user.user_id=".$this->device_user["user_id"]) ;
            @INS_SQL_LOG("auth", "device_user_merge_upd", "device_user.user_id=[".$this->device_user["user_id"]."] merge.user_id=[".$merge_user_id."]");
            $result = execConfigQuery($this->db, 'device_user_merge_upd', array(
                "device_id" => $params["device_id"],
                "merge_user_id" => $merge_user_id ,
                "device_user_id" => $this->device_user["user_id"]));
            //マージ後、アプリユーザーを再取得
            if($result["status"] === "success") {
                $result = $this->get_user_by_deviceid($params);
                if($result["status"] === "success"
                    && isset($result["data"]) && count($result["data"])==1){
                    $this->device_user = $result["data"][0];
                }
            }
            @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "merge実行後 device_user.user_id=".$this->device_user["user_id"]) ;
        }
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "-------------device_user_merge_upd end----------------") ;
        return $result;
    }
    /**
    * 仮登録ユーザー更新 / メール送信
    * @access private
    * @param array $params
    * @return json response
    */
    private function user_pre_ins_upd($params)
    {
        $result = $this->badRequestResponce();
        $auth_code = $this->createAuthCode($params["email"]);
        if(isset($params["email"]) && !empty($params["email"])){
            $result = $this->get_user_by_email($params);
            if($result["status"] === "success"
                && isset($result["data"]) && count($result["data"])>0){
                    //本登録ユーザーが存在する場合、
                    return $this->getResponce("failed", "E_ALREADY_SAVED");
            }

            //存在しなければレコードを登録
            $result = execConfigQuery($this->db, 'user_pre_ins', array(
    	       'email'          => $params["email"],
    		   'auth_code'      => '',
    	       'login_ip'       => $_SERVER["REMOTE_ADDR"],
    		));
            //auth_codeを更新
            $result = execConfigQuery($this->db, 'user_pre_upd', array(
                'email'          => $params["email"],
                'auth_code'      => $auth_code,
                'login_ip'       => $_SERVER["REMOTE_ADDR"],
            ));
            //登録確認
            $result = $this->get_user_by_auth_code(array("auth_code" => $auth_code));
            if($result["status"] === "success"
                && isset($result["data"]) && count($result["data"])==1
                && $result["data"][0]["email"] === $params["email"]){
                    @INS_SQL_LOG("auth", "user_pre_ins_upd", "email=[".$params["email"]."]auth_code=[".$auth_code."]");
                    //通知メール送信
                    $result = execConfigQuery($this->db, 'api_send_mail_pre_register', array(
                        'auth_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/register?key=' . $auth_code,
                        'email'    => $params["email"],
                        'ip'       => $_SERVER["REMOTE_ADDR"],
                        'date'     => $GLOBALS['nowDateTime'],
                    ));
                    execConfigMail($this->db);
            }
            else {
                $result = $this->errorResponce();
            }
        }
        //$result["data"] = array("auth_code" => $auth_code, "email" => $params["email"]);
        $result["data"] = array("email" => $params["email"]);
        return $result;
    }
    /**
     * 期限付き認証コード生成
     * @param  String $email
     * @return String AuthCode
     */
    function createAuthCode($email) {
    	return sha1($email . COM_getRandomKey(12) . $GLOBALS['nowDateTime']);
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
    /**
     * API用 共通レスポンス
     * @param string [$status]
     * @param string [$message]
     * @param string [$description]
     * @param array [$data]
     * @return array
     */
    public function getResponce($status="success", $message="", $description="", $data=array())
    {
        $result = array(
          'status'      => $status,
          'message'     => $message,
          'description' => $description,
          'data' => $data,
        );
        return $result;
    }
    /**
     * API用 Token発行ロジック
     * @param integer $user_id
     * @return string
     */
    private function get_user_token($user_id)
    {
        $tmp = $user_id.bin2hex(openssl_random_pseudo_bytes(16));
        $token = substr($tmp,0 ,32);
        return $token;
    }
    /**
    * ログイン処理し、tokenを更新し、成功時にtokenを返却する。
    * @param json $params
    * @return json response
    */
    public function login_token_update(Array $params)
    {
      // excecute login
      $result = postLogin($this->db, $params);
      @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "login_token_update:", $result["status"]) ;
      if ($result["status"] !== "success") {
          return $result;
      }
      $result = $this->get_user_by_session();
      if ($result["status"] === "success"
          && isset($result["data"]) && count($result["data"]) > 0) {
          $this->token_user = $result["data"];
          $token = $this->app_user_token_upd($this->token_user['user_id']);
          $result['data'] = array('api_token' => $token);
      }
      return $result;
    }

}
