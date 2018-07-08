<?php
require_once($gPathList["lib"].'configMail.php');   // メールを即送信する必要がある場合execConfigMailを使う
require_once($gPathList["lib"].'router.php');
require_once($gPathList["lib"].'request.php');
require_once($gPathList["lib"].'controller.php');
require_once($gPathList["lib"].'comDbi.php');

/**
 * Application.
 */
abstract class Application
{
	protected $debug = false;
	protected $request;
	public $db;
	public $dbi;
	public $system;

	/**
	 * コンストラクタ
	 *
	 * @param boolean $debug
	 */
	public function __construct($DBCON, $debug = false)
	{
		$this->system = $GLOBALS['gEnvList']['system'];
		$this->dbi = new comDbi();
		$this->db = $this->dbi->db;
		$this->setDebugMode($debug);
		$this->initialize();
		$this->configure();
	}
	/**
	 * デバッグモードを設定
	 *
	 * @param boolean $debug
	 */
	protected function setDebugMode($debug)
	{
		if ($debug) {
			$this->debug = true;
			ini_set('display_errors', 1);
			error_reporting(-1);
		} else {
			$this->debug = false;
			ini_set('display_errors', 0);
		}
	}
	/**
	 * アプリケーションの初期化
	 */
	protected function initialize()
	{
		$this->request    = new Request();
		$this->router     = new Router($this->registerRoutes());
	}
	/**
	 * アプリケーションの設定
	 */
	protected function configure()
	{
	}

	/**
	 * ルーティングを取得
	 *
	 * @return array
	 */
	abstract protected function registerRoutes();
	/**
	 * デバッグモードか判定
	 *
	 * @return boolean
	 */
	public function isDebugMode()
	{
		return $this->debug;
	}
	/**
	 * Requestオブジェクトを取得
	 *
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->request;
	}
	/**
	 * コントローラファイルが格納されているディレクトリへのパスを取得
	 *
	 * @return string
	 */
	public function getAppDir()
	{
		return $_SERVER["DOCUMENT_ROOT"] .'/app';
	}
	/**
	 * コントローラファイルが格納されているディレクトリへのパスを取得
	 *
	 * @return string
	 */
	public function getControllerDir()
	{
		return $_SERVER["DOCUMENT_ROOT"] .'/controller';
	}

	/**
	 * アプリケーションを実行する
	 *
	 * @throws HttpNotFoundException ルートが見つからない場合
	 */
	public function run()
	{
		try {
			$params = $this->router->resolve($this->request->getPathInfo());
			if ($params === false) {
				throw new HttpNotFoundException('No route found for ' . $this->request->getPathInfo());
			}
			$controller = $params['controller'];
			$action = $params['action'];
			$this->runAction($controller, $action, $params);
		} catch (HttpNotFoundException $e) {
			$this->render404Page($e);
		}
	}
	/**
	 * 指定されたアクションを実行する
	 *
	 * @param string $controller_name
	 * @param string $action
	 * @param array $params
	 *
	 * @throws HttpNotFoundException コントローラが特定できない場合
	 */
	public function runAction($controller_name, $action, $params = array())
	{
		@TXT_LOG("service", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "-------------", "runAction",$controller_name, $action,"-------------") ;
		$controller_class = "";
		$controller_file = "";
		switch($controller_name){
			case "app":
			case "comService":
			case "comView":
			case "testtool":
				$controller_path = "/";
				if(isset( $params["subdir"])){
					$controller_path = $params["subdir"].$controller_path;
				}
				if(isset( $params["controller_file"])){
					$controller_path .= $params["controller_file"];
				}
				else {
					$controller_path .= $controller_name;
				}
				$controller_class = $controller_name;
				if(isset( $params["controller_class"])){
					$controller_class = $params["controller_class"];
				}
				$controller = $this->findController($controller_path, $controller_class);
				if ($controller === false) {
					throw new HttpNotFoundException($controller_name . ' controller is not found.');
				}
				$response = $controller->run($action, $params);
				if(isset($params["mode"]) && $params["mode"] === "view"){
					if($response["status"] === "failed"){
						showPageForbidden();
						exitProc($this->db);
					}
				}
				else {
					sendJSONResponse($response);
					exitProc($this->db);
				}
				break;
			case "service" :
			case "view" :
				$controller_class = "common".ucfirst($controller_name);
				$controller_file = $this->getAppDir() . '/'.$controller_class.'.php';
				break;
			case "password" :
				$controller_class = "recover".ucfirst($controller_name).ucfirst($params["method"]);
				$controller_file = $this->getAppDir() . '/'.$controller_class.'.php';
				break;
			case "require":
				$controller_class = $action;
				$controller_file = $_SERVER["DOCUMENT_ROOT"] . '/'.$controller_class;
				break;
			case "redirect":
				$controller_class = $action;
				$controller_file = $controller_class;
				break;
			default:
				$controller_class = $controller_name;
				$controller_file = $this->getAppDir() . '/'.$controller_class.'.php';
		}
		if ($controller_name !== "redirect" && !is_readable($controller_file)) {
			throw new HttpNotFoundException($controller_class . ' controller is not found.');
		}
		if($this->db){
			@TXT_LOG("service", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "runAction",$controller_file, $controller_name, $action) ;
			switch($controller_name){
				case "service" :
					require_once($controller_file);
					commonService($this->db, $action, $params);
					break;
				case "view" :
					require_once($controller_file);
					commonView($this->db, $action, $params);
					break;
				case "require" :
					require_once($controller_file);
					break;
				case "redirect" :
					//require_once($controller_file);
					$queryString = $this->request->getQueryString();
					if(!empty($queryString)){
						if (false !== ($pos = strpos($controller_file, '?'))) {
							//クエリ文字列がついている場合は、&で後ろに連結
							$queryString = "&".$queryString;
						}
						else {
							$queryString = "?".$queryString;
						}
						$controller_file .= $queryString;
					}
					header( "Location: ". $controller_file ) ;
					exitProc($DBCON);
					break;
				default:
					require_once($controller_file);
					showAddonPage($this->db);
					break;
			}
		}
	}
	/**
	 * 指定されたコントローラ名から対応するControllerオブジェクトを取得
	 *
	 * @param string $controller_class
	 * @return Controller
	 */
	protected function findController($controller_path, $controller_class)
	{
		if (!class_exists($controller_path)) {
			$controller_file = $this->getControllerDir() . '/' .$this->system."/".$controller_path . '.php';
			if (!is_readable($controller_file)) {
				$controller_file = $this->getControllerDir() . '/' .$controller_path . '.php';
				if (!is_readable($controller_file))   return false;
			}
			require_once $controller_file;
			if (!class_exists($controller_class)) {
				return false;
			}
		}

		return new $controller_class($this);
	}
	/**
	 * 404エラー画面を返す設定
	 *
	 * @param Exception $e
	 */
	protected function render404Page($e)
	{
		$message = $this->isDebugMode() ? $e->getMessage() : 'Page not found.';
		$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
		$controller_file = $this->getAppDir() . '/commonView.php';
		require_once $controller_file;
		showPageNotFound();
	}
}

/**
 * HttpNotFoundException.
 *
 * @author Katsuhiro Ogawa <fivestar@nequal.jp>
 */
class HttpNotFoundException extends Exception {};
