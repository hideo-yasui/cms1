<?php

/**
 * Controller.
 */
abstract class Controller
{
	protected $controller_name;
	protected $action_name;
	protected $application;
	protected $request;
	protected $system;
	protected $db;
	protected $is_view_response = false;

	/**
	 * コンストラクタ
	 *
	 * @param Application $application
	 */
	public function __construct($application)
	{
		$this->controller_name = strtolower(substr(get_class($this), 0, -10));
		$this->application = $application;
		$this->system = $application->system;
		$this->db = $application->db;
		$this->request     = new Request();

	}
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

		if ($this->needsAuthentication($action)) {
			$values = $this->application->dbi->getAuthenticateResponse();
			if($values["status"] !== "success"){
				authenticate_error($values["message"], $values["description"]);
				return $this->forbiddenResponce();
			}
		}
		return $this->$action($params);
	}
	/**
	 * 共通レスポンス
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
		  'data' => $data
		);
		return $result;
	}
	/**
	 * Internal Error扱いのレスポンス返却
	 * @param string [$description]
	 * @return array
	 */
	public function errorResponce($description="")
	{
		return $this->getResponce("failed", "E_ERROR", $description);
	}
	/**
	 * not found扱いのレスポンス返却
	 * @param string [$description]
	 * @return array
	 */
	public function notFoundResponce($description="")
	{
		if($this->is_view_response){
			header( "Location: notfound") ;
			exitProc($this->db);
		}
		return $this->getResponce("failed", "E_NOT_FOUND", $description);
	}
	/**
	 * Forbidden扱いのレスポンス返却
	 * @param string [$description]
	 * @return array
	 */
	public function forbiddenResponce($description="")
	{
		if($this->is_view_response){
			header( "Location: forbidden") ;
			exitProc($this->db);
		}
		return $this->getResponce("failed", "E_FORBIDDEN", $description);
	}
	/**
	 * BadRequest扱いのレスポンス返却
	 * @param string [$description]
	 * @return array
	 */
	public function badRequestResponce($description="")
	{
		return $this->getResponce("failed", "E_BAD_REQUEST", $description);
	}
	/**
	 * NotRequest扱いのレスポンス返却
	 * @param string [$description]
	 * @return array
	 */
	public function notExistResponce($description="")
	{
		return $this->getResponce("failed", "E_NOT_EXISTS", $description);
	}
	/**
	 * NotRequest扱いのレスポンス返却
	 * @param JSON $response
	 * @return void (exit)
	 */
	public function sendJSONResponse(Array $response, $isArrowOrigin=false, $opt=null)
	{
		sendJSONResponse($response, $isArrowOrigin, $opt);
		exitProc($this->db);
	}
	/**
	 * 指定されたアクションが認証済みでないとアクセスできないか判定
	 *
	 * @param string $action
	 * @return boolean
	 */
	protected function needsAuthentication($action)
	{
		if ($this->auth_actions === true
			|| (is_array($this->auth_actions) && in_array($action, $this->auth_actions))
		) {
			return true;
		}
		return false;
	}
	protected function isView(){
		return $is_view_response;
	}
}
