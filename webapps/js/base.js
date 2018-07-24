/**
* baseプラグイン
* 汎用的なページ処理をまとめる
* @namespace
* @class base
**/
;(function(root, undefined) {
	"use strict";
	var _isUpdate = false;
	var _isBarcodeScan = false;
	var _isImport = false;
	var _maxInt = Number.MAX_SAFE_INTEGER;
	var _cache = {"_fileUI" : {}};
	var _pageHistory = [];
	var _isHistoryLoad = false;
	var _currentHistoryNo = 0;
	var _currentForm = "page1";
	var _formHistory = [];
	//treeView用のIF
	var _treeView = null;
	//list_table用のIF
	var _currentRequest = null;
	var _listTable = {};
	var _listInfo = {"page" : 0, "maxpage" : 0, "count" : 0, "option" : null};
	var _editPageId = "page2";
	var _dialogId = "subDialog";
	var _pageCode = setQueryParam();
	var _isDebug = false;
	var public_method = {
		//ページコード=URL
		pageCode : _pageCode,
		//初期処理
		init : function(){
			window.onpopstate=function(e){
				pageHistoryBack( e.state);
			};
			dom.dialogLoad();
		},
		setQueryParam : setQueryParam,
		showMessage : showMessage,
		showPage : showPage,
		showEditPage : showEditPage,
		setEditForm : setEditForm,
		pageClose : pageClose,
		pageHistoryAdd : pageHistoryAdd,
		getPageHistory : getPageHistory,
		pageHistoryBack : pageHistoryBack,
		pageSettinged : pageSettinged,
		setTitle : setTitle,
		setSubTitle : setSubTitle,
		searchProc : _searchProc,
		saveProc : saveProc,
		userSetting : userSetting,
		treeInit : treeInit,
		getFileForm : getFileForm,
		listRefresh : listRefresh,
		linkProc : linkProc,
		exportProc : exportProc
	};
	startProc();
	//クロージャー
	function startProc(){
		var data = util.getLocalData("userSetting");
		_cache["userSetting"] = {
			"pageSize" : 20,
			"maxPageSize" : 200
		};
		if(!util.isEmpty(data)){
			for(var key in _cache["userSetting"]){
				if(!util.isEmpty(data[key])) continue;
				_cache["userSetting"][key] = data[key];
			}
		}
	}
	//---------------------------------------------------
	//onload系～
	//---------------------------------------------------
	//バーコードスキャンフォームを有効にする開始処理
	function scanStart(){
		_isBarcodeScan = true;
		$("*[accesskey=scan][alt=codes]").attr("multiple", "multiple");
		$("*[accesskey=scan][alt=code]").focus();
	}

	//バーコードスキャンフォームに対するチェック処理
	function scanCodeCheck(){
		var form = $("*[accesskey=scan][alt=code]");
		var format = $("*[accesskey=scan][alt=format]");
		var minl = format.attr("minlength");
		var maxl = format.attr("maxlength");
		var inputtype = format.attr("inputtype");
		var _isError = false;
		if(util.isEmpty(minl)) minl = 0;
		else minl = minl|0;
		if(util.isEmpty(maxl) || maxl > 24) maxl = 24;
		else maxl = maxl|0;
		front.clearValidateError(_currentForm);

		var scan = form.attr("scan");
		var scan_field = form.attr("scan_field");
		var _inputkey = null;
		if($(form).val().indexOf("\n")>=0){
			_inputkey = $(form).val().split("\n");
		}
		else if(util.isEmpty($(form).val())){
			return "";
		}
		else {
			_inputkey = [$(form).val()];
		}

		var _adjustInputkey = new Array(_inputkey.length);
		var message = [];
		for(var i=0;i<_inputkey.length;i++){
			_adjustInputkey[i] = "";
			if(util.isEmpty(_inputkey[i])) continue;
			if(_inputkey[i].length < minl || _inputkey[i].length > maxl) {
				message.push(service.getMessage("E_IGNORE_DATA", _inputkey[i])["body"]);
				continue;
			}
			_adjustInputkey[i] = _inputkey[i];
			_adjustInputkey[i] = front.inputAdjust(_adjustInputkey[i], maxl, minl, inputtype);
			if(_adjustInputkey[i].length < minl || _adjustInputkey[i].length > maxl) {
				message.push(service.getMessage("E_IGNORE_DATA", _inputkey[i])["body"]);
				_adjustInputkey[i] = "";
				continue;
			}
		}

		//重複なく有効なコードのみ取得し、出現回数を管理
		var _inputkeyDetail = {};
		var _inputkeyDetailCount = 0;
		for(var i=0,n=_adjustInputkey.length;i<n;i++){
			if(util.isEmpty(_adjustInputkey[i])) continue;
			if(!_inputkeyDetail[_adjustInputkey[i]]) _inputkeyDetail[_adjustInputkey[i]]={"count" : 0, "exist" : false, "source" : _inputkey[i]};
			_inputkeyDetail[_adjustInputkey[i]]["count"]++;
			if(_inputkeyDetail[_adjustInputkey[i]]["count"]==1){
				_inputkeyDetailCount++;
			}
		}
		var _isExistCheck = false;
		if(!util.isEmpty(scan) && !util.isEmpty(scan_field) && _inputkeyDetailCount>0){
			switch(scan){
				case "querycheck":
					var query = form.attr("query");
					var _req = {};
					_req[scan_field] = _adjustInputkey;
					_isExistCheck = true;
					service.getAjax(false, "/get/"+query, _req,
						function(result, st, xhr) {
							var data =  result["data"];
							for(var i=0,n=data.length;i<n;i++){
								if((data[i]["COUNT"]|0) > 0){
									_inputkeyDetail[data[i][scan_field]]["exist"] = true;
								}
							}
						},
						function(xhr, st, err) {
							service.error("querycheck\n"+err.message+"\n"+xhr.responseText);
							_isError = true;
						}
					);
					break;
			}
		}
		if(_isError) {
			return false;
		}
		var _enableKeys = [];
		if(_isExistCheck){
			for(var key in _inputkeyDetail){
				if(!_inputkeyDetail[key]["exist"]){
					var messageParam = _inputkeyDetail[key]["source"];
					if(_inputkeyDetail[key]["source"]!=key) messageParam += "("+key+")";
					message.push(service.getMessage("E_EXIST_NODATA", messageParam)["body"]);
				}
				else {
					_enableKeys.push(key);
				}
			}
		}
		else {
			//有効コードを取得
			for(var key in _inputkeyDetail){
				_enableKeys.push(key);
			}
		}

		//有効コード数（重複は無視）
		if($("*[accesskey=scan][alt=count]")[0]) $("*[accesskey=scan][alt=count]").html(_enableKeys.length+"件");
		//有効コードを文字列として設定
		$("*[accesskey=scan][alt=codes]").val(_enableKeys.join(","));
		var scaned = form.attr("scaned");
		if(!util.isEmpty(scaned) && !util.isEmpty(scan_field)){
			switch(scaned){
				case "listfilter":
					_listTable["listTable"].listtable("filter", scan_field);
					_listTable["listTable"].listtable("selectAll");
					break;
			}
		}
		if(message.length>0) {
			dom.alertMessage("スキャンデータチェック", "・"+message.join("<br>・"), null);
			return false;
		}
		return true;
	}
	function setQueryParam(){
		var p = service.setQueryParam();
		return p["page"];
	}
	//ページ履歴取得
	function getPageHistory(index){
		var _h = {};
		if(util.isEmpty(index)) index = _currentHistoryNo;
		return $.extend(_h, _pageHistory[index]);
	}
	//ページ履歴追加
	function pageHistoryAdd(data){
		var _isAdd = true;
		//if(_pageHistory.length>0 && _pageHistory[_pageHistory.length-1]["type"] == data["type"] && _pageHistory[_pageHistory.length-1]["element"] == data["element"]) _isAdd=false;
		if(!_isHistoryLoad){
			if(_currentHistoryNo < _pageHistory.length-1){
				//現在位置から先を削除
				_pageHistory.splice((_currentHistoryNo|0)+1, (_pageHistory.length-(_currentHistoryNo|0)-1));
			}
			window.history.pushState(_pageHistory.length, null, "?h="+_pageHistory.length);
			if(_isAdd) _pageHistory.push(data);
			_currentHistoryNo = _pageHistory.length-1;
		}
		_isHistoryLoad = false;
	}

	//ページ履歴バック
	function pageHistoryBack(historyNo){
		_currentHistoryNo  = historyNo;
		_isHistoryLoad = true;
		if(_isImport){
			service.confirm("C_IMP_BACK", "", function(){
				_isImport = false;
				_loadHistory(historyNo);
			});
		}
		else {
			_loadHistory(historyNo);
		}
	}
	//履歴データのロード
	function _loadHistory(historyNo){
		if(!_pageHistory[historyNo]) return;
		var state = _pageHistory[historyNo];
		switch(state["type"]){
			case "button" :
				editProc(state["element"], state["data"]);
				break;
			case "link" :
				linkProc(state["element"], state["data"]);
				break;
			case "tree" :
				_treeView.treeview("nodeClickLoad", state["element"]);
				break;
			case "subpage" :
				showPage(state["element"], state["param"], state["url"], state["data"], state["callback"]);
				break;
			case "search" :
				_currentRequest = state["data"];
				//検索ワードがあれば、入力フォームに値セット
				if(state["param"]["searchword"] && !util.isEmpty(state["param"]["searchword"])) $("input[name='p_search_word']").val(state["param"]["searchword"]);
				_searchProc(state["param"]);
				break;
			case "pager":
				_currentRequest = state["data"];
				_listInfo = state["param"];
				listRefresh(true);
				break;
			}
	}

	//---------------------------------------------------
	//画面表示系～
	//---------------------------------------------------
	function setTitle(title){
		$("#systemname").html(title);
	}
	function setSubTitle(title){
		if(util.isEmpty(title)) title = _setSubTitle();
		$(".nav--header__title").html(title);
		$(".nav--header__title a").unbind("click");
		$(".nav--header__title a").click(function(e){
			var historyNo = $(this).attr("accesskey");
			pageHistoryBack(historyNo|0);
			//画面の挙動として、履歴からたどるが、挙動上画面遷移のため
			//ブラウザ履歴にも追加する
			window.history.pushState(historyNo, null, "?h="+_pageHistory.length);
		});
	}
	function _setSubTitle(){
		var html = [];

		var _prev_title = "";
		var _prev_type = "";
		for(var i=_currentHistoryNo;i>=0;i--){
			if(_pageHistory.length<1 || _pageHistory.length < i ) break;
			var _title = _pageHistory[i]["title"];
			var _type = _pageHistory[i]["type"];
			//var _script = 'base.pageHistoryBack('+i+')';
			//if(c==0) _script = 'base.pageClose()';
			//if(_type=="button") continue;//_script = 'base.pageClose()';
			var _param = "";
			if(_type == "search") continue;
			if(_type == "pager") continue;
			if(!util.isEmpty(_title)){
				if(_pageHistory[i]["param"]) _param =  _pageHistory[i]["param"];
				_title=_title.replace("#param#", _param);
				_title = dom.textFormat(_title, _pageHistory[i]["data"]);
				if(i>0 && _prev_title == _title && _prev_type == _type) continue;
				if(html.length==0){
					html.unshift(_title);
				}
				else {
					html.unshift('<a accesskey="'+i+'" href="javascript:void(0)">'+_title+'<span class="icon--arwr"></span></a>');
				}
				_prev_title = _title;
				_prev_type = _type;
			}
			if(_type == "tree") break;
		}
		return html.join('');
	}
	//サブページ表示処理
	function showPage(formId, pagecode, url, data, callback, windowName){
		if(!util.isEmpty(formId)) _currentForm = formId;

		if(util.isEmpty(url)){
			if(util.isEmpty(pagecode)) return;
			var async = true;
			if(!util.isEmpty(windowName)) async = false;
			service.getAjax(async, "/getpage/"+pagecode, {},
				function(result, st, xhr) {
					if(util.isEmpty(_currentRequest)) _currentRequest = {"query_code" : pagecode};
					var param = result["data"][0];
					var title = param["NAME"];
					var p = $("<p></p>");
					p.html(param["OPTION_STRING"]);
					var option = p.html();
					if(option && option!="" && option.indexOf(":")>=0) {
						option = JSON.parse("{"+option+"}");
						if(option["title"] && !util.isEmpty(option["title"])) title = option["title"];
						//更新モードの場合、OPTION_STRING.edit_title設定があれば、優先的に使う
						if(_isUpdate && option["edit_title"] && !util.isEmpty(option["edit_title"])) title = option["edit_title"];
						//if(_pageHistory.length>0 && _pageHistory.length>0 && _pageHistory[_pageHistory.length-1]["title"]=="") _pageHistory[_pageHistory.length-1]["title"] =title;
						var type=$("#"+formId).attr("type");
						if(type != "dialog" && _pageHistory.length>0 && _pageHistory.length>0) _pageHistory[_pageHistory.length-1]["title"] =title;
						_cache[formId] = title;
						switch(param["PAGE_TYPE"]){
							case "param":
								var _pageContents = dom.paramPageLoad(option["form"],option["button"],_listTable["listTable"]);
								$("#"+_currentForm).html(_pageContents);
								pageSettinged(_currentForm);
								buttonControl(option);
								if(callback && $.type(callback)=="function") callback(option);
								break;
							case "url" :
								var _url = option["url"];
								if(!util.isEmpty(option["paramater"])){
									var _param = [];
									for(var i=0,n=option["paramater"].length;i<n;i++){
										var key = option["paramater"][i];
										if(util.isEmpty(data[key])) continue;
										_param.push(key+"="+data[key]);
									}
									if(_param.length>0){
										_url+="?"+_param.join("&");
									}
								}
								if(!util.isEmpty(windowName)) {
									window.open(_url,  windowName);
								}
								else {
									$("#"+_currentForm).load("/"+_url, function(e){
										pageSettinged(_currentForm);
										buttonControl(option);
										if(callback && $.type(callback)=="function") callback(option);
									});
								}
								break;
							case "html" :
								$("#"+_currentForm).html(option["form"]);
								pageSettinged(_currentForm);
								buttonControl(option);
								if(callback && $.type(callback)=="function") callback(option);
								break;
						}
					}

				},
				function(xhr, st, err) {
					service.error("showPage\n"+err.message+"\n"+xhr.responseText);
				}
			);
		}
		else {
			$("#"+formId).load(url, function(){
				pageSettinged(_currentForm);
				if(callback && $.type(callback)=="function") callback();
			});
		}
	}
	//ページ設定後処理：各フォームUIのロード
	function pageSettinged(formId){
		_currentForm = formId;
		$("input[type=file]", $("#"+formId)).each(function(){
			var name = $(this).attr("name");
			if(util.isEmpty(name)) return;
			_cache["_fileUI"][name] = $(this).fileUI({
				"formId" : formId,
				"dragdrop" : ".dragdropupload",
				"onChange" : function(element, fileData){
					var name = $(element).attr("name");
					var accesskey = $(element).attr("accesskey");
					$("span[alt="+name+"][accesskey=filename]", $("#"+formId)).html(fileData["name"]);
					$("span[alt="+name+"][accesskey=filesize]", $("#"+formId)).html(fileData["size"]);
					$("span[alt="+name+"][accesskey=filetype]", $("#"+formId)).html(fileData["type"]);
					switch(accesskey){
						case "upload":
							fileUpload(name);
							break;
						case "auto_upload":
							_fileUpload(name, _currentRequest["query_code"], function(result){
								setFileForm(name, result);
							});
							break;
						case "import":
							fileImport(name);
							break;
					}
				}
			});
		});
		$("a.btn[accesskey]", $("#"+formId)).unbind('click');
		$("a.btn[accesskey]", $("#"+formId)).click(function(e){
			var alt = $(this).attr("alt");
			var accesskey = $(this).attr("accesskey");
			var target = $(this).attr("target");
			var type = $(this).attr("type");
			//console.log("accesskey="+accesskey+",alt="+alt+",target="+target);
			switch(accesskey){
				case "fileclear":
					$("#btnFileReference", $("#"+formId)).show();
					$("#btnFileUpload", $("#"+formId)).hide();
					_cache["_fileUI"][alt].fileUI("clear");
					break;
				case "fileopen":
					_cache["_fileUI"][alt].fileUI("click");
					break;
				case "fileupload":
					fileUpload(alt);
					break;
				case "fileimport":
					fileImport(alt);
					break;
				case "print":
					window.print();
					break;
				case "save":
					_saveProc(function(){
						if(util.isEmpty(target)){
							_savedReload();
						}
						else {
							var _req = front.getFormValue(_currentForm);
							var link = $("*[name="+target+"]", $("#"+formId));
							linkProc(link, _req);
						}
					});
					break;
				case "savemulti":
					if(util.isEmpty(type)) type = "upd";
					saveProc(type, alt,  function(){
						if(util.isEmpty(target)){
							_savedReload();
						}
						else {
							_pageHistory.splice(_pageHistory.length-1, 1);
							var _req = front.getFormValue(_currentForm);
							var link = $("*[name="+target+"]", $("#"+formId));
							linkProc(link, _req);
						}
					});
					break;
				case "export":
					if(util.isEmpty(target)) target = _currentRequest["query_code"];
					exportProc(target);
					break;
				case "search":
					searchProc();
					break;
				case "close":
					pageClose();
					break;
				case "clear":
					front.clearFormValue(_currentForm);
					break;
				case "cancel":
				case "back":
					window.history.back();
					break;
				case "yes":
					pageClose("message");
					if(util.isFunction(_cache["__callback"])) _cache["__callback"]();
					break;
				case "no":
					pageClose("message");
					_cache["__callback"] = null;
					break;
			}
			e.preventDefault();
		});
		$("select[accesskey]", $("#"+formId)).each(function(i){
			var _defaultSelect = $(this).attr("defaultSelect");
			dom.selectFormLoad(_currentForm, this, _defaultSelect, null, _currentRequest);
		});
		$("div[accesskey][uitype=radio]", $("#"+formId)).each(function(i){
			var _defaultSelect = $(this).attr("defaultSelect");
			dom.selectFormLoad(_currentForm, this, _defaultSelect, $(this).html(), _currentRequest);
		});
		//バーコードフォーム
		dom.setBarcodeForm(formId);
		//パスワード入力
		dom.setPasswordForm(formId);
		//郵便番号入力
		dom.setPostnoForm(formId);
		//日付入力
		dom.setCalenderForm(formId);
		//数値入力
		dom.setNumberForm(formId);
		//値自動調整
		front.setInputAdjust(formId);
	}

	//編集画面表示
	function showEditPage(formId, pagecode, querycode, data, isEdit){
		//編集値取得→編集画面表示→フォームセット
		var _req = service.extendRequestJson({}, data);
		if(util.isEmpty(querycode)){
			showPage(formId, pagecode, "",  data, function(){
				_isUpdate = isEdit;
				setEditForm(data);
				pageOpen();
			});
		}
		else {
			service.getAjax(true, "/getedit/"+querycode, _req,
				function(result, st, xhr) {
					var data =  result["data"];
					_isUpdate = isEdit;
					showPage(formId, pagecode, "", data[0], function(){
						setEditForm(data[0]);
						pageOpen();
					});
				},
				function(xhr, st, err) {
					service.error("showEditPage\n"+err.message+"\n"+xhr.responseText);
				}
			);
		}
	}
	function pageOpen(formId){
		if(util.isEmpty(formId)) formId = _currentForm;
		var _isExist = false;
		for(var i=0,n=_formHistory.length;i<n;i++){
			if(_formHistory[i]==formId){
				_isExist = true;
				break;
			}
		}
		if(!_isExist){
			_formHistory.unshift(formId);
		}

		var type=$("#"+formId).attr("type");
		switch(type){
			case "dialog":
				dom.dialogOpen(formId, _cache[formId]);
				break;
			case "page":
				//切り替えた場合は、画面をスクロールトップにする
				$('body, html').scrollTop(0);
				setSubTitle();
				$("div[type="+type+"]").hide();
				$("#"+formId+"[type="+type+"]").show();
				break;
		}
		$(':focus').blur();
	}
	/*保存処理後の再表示*/
	function _savedReload(){
		//特に後続のページ遷移を指定していない場合
		var type=$("#"+_currentForm).attr("type");
		switch(type){
			case "dialog":
				pageClose();
				pageHistoryBack(_currentHistoryNo);
				break;
			case "page":
				window.history.back();
				break;
		}
	}
	function pageClose(formId){
		if(util.isEmpty(formId)) formId = _currentForm;
		if(util.isEmpty(formId)) return;
		if(formId=="page1") return;
		if(formId!="message"){
			_isBarcodeScan = false;
		}

		for(var i=0,n=_formHistory.length;i<n;i++){
			if(_formHistory[i]==formId){
				_formHistory.splice(i,1);
				break;
			}
		}
		var type=$("#"+formId).attr("type");
		switch(type){
			case "dialog":
				dom.dialogClose(formId);
				break;
			case "page":
				$("div[type="+type+"]").hide();
				$("#page1[type="+type+"]").show();
				break;
		}
		if(_formHistory.length>0) _currentForm = _formHistory[0];
		else  _currentForm = "page1";
		setSubTitle();
	}

	//引数(json)を登録用のフォームに値をセット
	function setEditForm(data, form){
		//$("input, textarea, select", $("#"+_currentForm)).val("");
		if(!util.isEmpty(form)) _currentForm = form;
		$("input[type=text], textarea", $("#"+_currentForm)).val("");
		$("input[type=radio],input[type=checkbox]", $("#"+_currentForm)).prop("checked", false);
		if(data){
			$("input, textarea, select", $("#"+_currentForm)).each(function(){
				var field = $(this).attr("name");
				var tag = $(this).prop("tagName");
				var formControl = $(this).attr("new");
				var type = $(this).attr("type");
				var inputtype = $(this).attr("inputtype");
				if(_isUpdate) formControl = $(this).attr("edit");
				var isset = true;
				var val = null;
				if(type == "checkbox" || type=="radio"){
					val = $("input[name="+field+"]:checked").val();
					if(!val && $(this).attr("defaultSelect")){
							val = $(this).attr("defaultSelect");
					}
				}

				if(!util.isEmpty(formControl)){
					switch(formControl){
						case "clear":
							isset=false;
							break;
						case "disabled":
							$(this).attr("disabled", "true");
							break;
						case "hidden":
							$(this).attr("disabled", "true");
							$("dl:has("+tag+"[name="+field+"])").hide();
							break;
					}
				}

				if(isset && field && field in data) {
					var p = $("<p></p>");
					p.html(data[field]);
					val = p.text();
					switch($(this).attr("uitype")){
						case "unktext":
							//unktextロジックはいつか消したい
							/*説明：
							t_hospital_usersのログインＩＤが、保存時にloginid@hospital_idとなっている
							このフォーム編集時は、@以降を除去して値をセットする
							*/
							if(val.indexOf("@")>=0) {
								val = (val.split("@"))[0];
							}
							break;
					}
				}

				if(val !== null){
					switch(type){
						case "radio":
						case "checkbox":
							$(this).val([val]).change();
							break;
						case "select":
							$(this).val(val);
							$(this).change();
						default:
							$(this).val(val);
					}
				}
				if(!util.isEmpty(inputtype)){
					$(this).blur();
				}
			});
			$("div, span, td", $("#"+_currentForm)).each(function(){
				var field = $(this).attr("id");
				var type =  $(this).attr("type");

				if(util.isEmpty(field)) return;
				if(!(field in data)) return;
				var val =data[field];
				if(type=="filesize"){
					val = util.setFileUnit(val);
				}
				if(typeof val === 'string' && val.indexOf("\n")>=0) val = val.replace_all("\n", "<br>");
				$(this).html(val);
			});
			$("a", $("#"+_currentForm)).each(function(){
				var field = $(this).attr("id");
				if(field && field in data) {
					$(this).html(data[field]);
					var type =  $(this).attr("type");
					var url = data[field];
					if(type=="fileid"){
						url = "/download/"+url;
					}
					$(this).attr("href", url);
				}
			});
		}
	}
	//---------------------------------------------------
	//button
	//---------------------------------------------------
	//ボタンフォームの制御
	function buttonControl(option){
		if(option["control"] && !util.isEmpty(option["control"])){
			var control = option["control"];
			var buttonForm = option["button"];
			var permission = util.getLocalData("permission");
			var button = {
					"scansearch" : { "control" : "hidden", "target" : "top", "text" : "ｽｷｬﾝ"},
					"newadd" : { "control" : "hidden", "target" : "left", "align" : "right_text", "icon" : "plus", "text" : "追加", "alt" : "add"},
					"setting" : { "control" : "hidden", "target" : "left", "align" : "right_text", "icon" : "cogs", "text" : "一括更新"},
					"refresh" : { "control" : "hidden", "target" : "left", "align" : "right_text", "icon" : "sync", "text" : "再表示"},
					"import" : { "control" : "hidden", "target" : "left", "align" : "right_text", "icon" : "upload", "text" : "ｲﾝﾎﾟｰﾄ"},
					"export" : { "control" : "hidden", "target" : "left", "align" : "right_text", "icon" : "download", "text" : "ｴｸｽﾎﾟｰﾄ"},
					"rowedit" : { "control" : "enabled"},
					"rowcopy" : { "control" : "enabled"},
					"rowdelete" : { "control" : "enabled"}
				};


			if(buttonForm){
				$.extend(button, buttonForm);
			}
			if(control){
				for(var i=0;i<control.length;i++){
					if(util.isEmpty(control[i])) continue;
					if(!button[control[i]]) button[control[i]] = {};
					var _status = "enabled";
					/*
					if((control[i] == "pagestart" || control[i] == "pageprev") && _listInfo["page"] == 0){
						_status = "disabled";
					}
					else if((control[i] == "pagenext" || control[i] == "pageend") && _listInfo["page"] >= _listInfo["maxpage"]){
						_status = "disabled";
					}
					*/
					button[control[i]]["control"] = _status;
				}
			}

			if(!util.isEmpty(_cache["pageCode"]) && !util.isEmpty(permission) &&
			!util.isEmpty(permission[_cache["pageCode"]]) && !util.isEmpty(permission[_cache["pageCode"]]["button"])){
				//パーミションが存在する場合、ボタン制御追加
				//存在しない場合は無制御運用
				var buttonPermission = permission[_cache["pageCode"]]["button"];
				for(var key in buttonPermission){
					if(util.isEmpty(buttonPermission[key]["control"])) continue;
					if(util.isEmpty(button[key])) continue;
					button[key]["control"] = buttonPermission[key]["control"];
				}
			}

			$("#buttonmenu", $("#main")).empty();
			$("*[accesskey=detailsearch]", $("#main")).hide();
			$("*[accesskey=search]", $("#main")).hide();
			$("*[accesskey=pagestart]", $("#main")).hide();
			$("*[accesskey=pageprev]", $("#main")).hide();
			$("*[accesskey=pagenext]", $("#main")).hide();
			$("*[accesskey=pageend]", $("#main")).hide();
			$("input[name='p_search_word']").unbind("keypress");
			var _tpl = [
				'<button type="button" #attr# class="btn btn-info btn-sm mr-1">',
				'<i class="fa fa-#icon# mr-1"></i></i>',
				'<span class="btn-label">#text#</span>',
				'</button>',
			].join('');

			for(var i=0;i<control.length;i++){
				var key = control[i];
				if(util.isEmpty(button[key])) continue;
				//紐づくフォームの表示制御を行うので、a[accesskey...とはしない
				if(button[key]["control"]=="hidden") continue;
				var _html = _tpl;
				var _align = "";
				var _accesskey = key;
				var _attr = "";
				if(!button[key]["accesskey"]) button[key]["accesskey"] = key;
				if(button[key]["text"]) _html = _html.replace("#text#", button[key]["text"]);
				if(button[key]["icon"]) _html = _html.replace("#icon#", button[key]["icon"]);

				if(_listInfo["maxpage"] > 0 && (
						key=="pagestart" || key=="pageprev" ||
						key=="pagenext" || key=="pageend" ||
						key=="search" || key=="detailsearch")){
					$("*[accesskey="+key+"]", $("#main")).css("display", "inline-block");
				}
				if(key == "search"){
					$("*[accesskey="+key+"]", $("#main")).css("display", "inline-block");
					$("input[name='p_search_word']").on("keypress", function(e){
						if(e.keyCode==13){
							//検索入力～Enterで、検索ボタン押下
							$("._buttonmenu button.btn[accesskey=search]").click();
						}
					});
				}
				for(var attribute in button[key]){
					if(attribute == "class" || attribute == "target" || attribute == "control" ||
						attribute == "align" || attribute == "icon" || attribute == "text" ) continue;
					_attr += " "+attribute+"="+button[key][attribute];
				}

				if(button[key]["control"]=="disabled"){
					_attr += ' disabled="disabled"';
					$("*[accesskey="+key+"]", $("#main")).prop("disabled", true);
				}
				if(button[key]["target"]) {
					_html = dom.textFormat(_html, {"text" : "", "icon" : "", "key" : key, "attr" : _attr});
					$("#buttonmenu", $("#main")).append(_html);
				}
			}
			$("button.btn", $("#main")).unbind('click');
			$("button.btn[accesskey]", $("#main")).click(function(e){
				//var type = $("span", this).attr("class").replace("icon ", "");
				var accesskey = $(this).attr("accesskey");
				var alt = $(this).attr("alt");
				//console.log("accesskey="+accesskey);

				//疑似的にlinkProcの仕様にあわせて、linkを作成する
				var _linkDOM = $("<a></a>");
				if(util.isEmpty(alt)) alt = accesskey;
				_linkDOM.attr("target", alt);
				_linkDOM.attr("alt", accesskey);
				_linkDOM.text("target", $(this).text());

				if(util.isEmpty(alt)) alt = accesskey;
				switch(accesskey){
					case "newadd":
					case "export":
					case "subpage" :
						_linkDOM.attr("accesskey", "subpage");
						linkProc(_linkDOM, _currentRequest);
						break;
					case "detailsearch":
					case "import":
					case "dialog" :
					case "scansearch":
					case "scanupd":
						_linkDOM.attr("accesskey", "dialog");
						linkProc(_linkDOM, _currentRequest);
						break;
					case "search":
						var keyword = $("input[name='p_search_word']").val();
						$("input, textarea, select", $("#"+_currentForm)).val("");
						//console.log("検索"+keyword);
						keyword = keyword.replace_all("　", " "); //全角スペースを半角スペースに変換
						_searchProc({"searchword" : keyword});
						break;
					case "setting":
						var selectData = _listTable["listTable"].listtable("getSelectData", null);
						if(selectData.length<1){
							service.alert("E_LIST_NOSELECT", "", null);
						}
						else {
							_linkDOM.attr("accesskey", "dialog");
							linkProc(_linkDOM, _currentRequest);
						}
						break;
					case "close":
						pageClose();
						break;
					case "refresh":
						listRefresh(true);
						break;
					case "back":
						if(_isImport) pageHistoryBack(_currentHistoryNo);
						else window.history.back(); //pageHistoryBack(_currentHistoryNo-1);
						//pageClose();
						break;
					case "preview":
						//プレビューボタン
						break;
					case "sendmail":
						service.sendMail();
						break;
					case "pageprev":
						listPageMove(-1);
						break;
					case "pagenext":
						listPageMove(1);
						break;
					case "pagestart":
						listPageStart();
						break;
					case "pageend":
						listPageEnd();
						break;
				}
				e.preventDefault();
			});

		}
	}
	//---------------------------------------------------
	//message
	//---------------------------------------------------
	//メッセージ表示
	function showMessage(messageCode, messageParam){
		var message = service.getMessage(messageCode, messageParam);
		showPage("message", "" , "", {}, function(){
			$("#message .detail").html(message["body"]);
			$(".ui-dialog-title", $("#message").parent()).html(title);
			pageOpen();
		});
	}

	//---------------------------------------------------
	//ajax処理系～
	//---------------------------------------------------
	//ajax file export
	function exportProc(target){
		if(!front.validateFormValue(_currentForm)) return false;
		service.confirm("C_POST_EXP", "", function(){
			var url = "/export/"+target;
			var _req = front.getFormValue(_currentForm);
			service.downloadAjax(url, _req,
				function(result, st, xhr) {
					if(result["status"] =="success"){
						service.fileDownload(result.url);
						if($("#"+_currentForm).attr("type") == "dialog") pageClose();
					}
				},
				function(xhr, st, err) {
					service.error("exportProc\n"+err.message+"\n"+xhr.responseText);
				});
		});
	}

	//---------------------------------------------------
	//汎用イベント処理系～
	//---------------------------------------------------
	//ファイルアップロード
	function fileUpload(formname, callback){
		if(!front.validateFormValue(_currentForm)) return false;
		service.confirm("C_POST_UPL", "", function(){
			_fileUpload(formname, _currentRequest["query_code"], function(result){
					setFileForm(formname, result);
					if(util.isFunction(callback)) callback(fileid);
			});
		});
	}
	function setFileForm(formname, result){
		var fileid = result["fileid"];
		var url = "/download/"+fileid;
		$("a[alt="+formname+"][accesskey=fileid]", $("#"+_currentForm)).html(fileid);
		$("a[alt="+formname+"][accesskey=fileid]", $("#"+_currentForm)).attr("href", url);
		$("input[type=hidden]", $("#"+_currentForm)).each(function(){
			var field = $(this).attr("name");
			var val = $("span[accesskey='"+field+"']", $("#"+_currentForm)).html();
			if(field=="filesize") val = result["fsize"];
			if(field=="fileid") val = fileid;
			if(!util.isEmpty(val)) $(this).val(val);
			else {
				val = $("a[accesskey="+field+"]", $("#"+_currentForm)).html();
				if(!util.isEmpty(val)) $(this).val(val);
			}
		});
	}
	function getFileForm(formname){
		if(!_cache["_fileUI"][formname]) return null;
		return _cache["_fileUI"][formname].fileUI("getFile");
	}

	//ファイルアップロード(バックグラウンド）
	function _fileUpload(formname, remark, callback){
		var _file = $("input[name="+formname+"][type=file]");
		var fileid = _file.attr("id");
		var fileData = _cache["_fileUI"][formname].fileUI("getFile");
		var fval = fileData["file"];
		var fsize  = fileData["sizeVal"];
		var fname = fileData["name"];
		var _req  = new FormData();
		_req.append("formid", fileid);
		_req.append(formname,  fval);
		_req.append("filename", fname);
		_req.append("remark", remark);
		service.uploadAjax("/upload", _req,
			function(result, st, xhr) {
				result["formid"] = fileid;
				result["filename"] = fname;
				result["remark"] = remark;
				result["fsize"] = fsize;
				if(util.isFunction(callback)) callback(result);
			},
			function(xhr, st, err) {
				service.error("fileUpload\n"+err.message+"\n"+xhr.responseText);
			}
		);
	}
	//ファイルインポート
	function fileImport(formname){
		if(!front.validateFormValue(_currentForm)) return false;
		var _file = $("input[name="+formname+"]", $("#"+_currentForm));
		var fileid = _file.attr("id");
		var query = _file.attr("query");
		var fileData = _cache["_fileUI"][formname].fileUI("getFile");
		var fval = fileData["file"];
		var fsize  = fileData["sizeVal"];
		var fname = fileData["name"];

		service.confirm("C_POST_IMP", "", function(){
			var _current = front.getFormValue(_currentForm);
			var _req  = new FormData();
			for(var key in _current){
				_req.append(key, _current[key]);
			}
			_req.append("formid", fileid);
			_req.append(formname,  fval);
			_req.append("filename", fname);
			_req.append("remark", _currentRequest["query_code"]);
			pageClose();
			service.uploadAjax("/import/"+query, _req,
				function(result, st, xhr) {
					if(result["status"] =="success"){
						_listInfo["page"] = 0;
						var _req = {"query_code" :  query};
						_isImport = true;
						listInit("listTable",_req);
					}
					else {
						service.alert(result["message"], result["description"], null);
					}
				},
				function(xhr, st, err) {
					service.error("fileImport\n"+err.message+"\n"+xhr.responseText);
				}
			);
		});
	}
	function saveProc(type, query_code, callback, noConfirm){
		var msg = "INS";
		if(type=="upd") {
			msg = "SAVE";
		}
		if(_isBarcodeScan){
			var ret = scanCodeCheck();
			if(!ret) return false;
		}
		if(!front.validateFormValue(_currentForm)) return false;
		var _callback = function(){
			var _req = front.getFormValue(_currentForm);
			_isImport = false;
			_isBarcodeScan = false;
			service.postAjax("/save/"+type+"/"+query_code, _req,
				function(result, st, xhr) {
					if(callback && $.type(callback)=="function") callback(result);
				},
				function(xhr, st, err) {
					service.error("service.postAjax\n"+err.message+"\n"+xhr.responseText);
				}
			);
		};
		if(noConfirm){
			_callback();
		}
		else {
			service.confirm("C_POST_"+msg ,"",  _callback);
		}
	}
	function _saveProc(callback){
		if(!front.validateFormValue(_currentForm)) return false;
		var type = "ins";
		if(_isUpdate) {
			type="upd";
		}
		saveProc(type, _currentRequest["query_code"], callback);
	}
	//検索処理
	function _searchProc(data){
		var _param = $.extend({}, data);
		var _req = $.extend({}, _currentRequest);
		pageHistoryAdd({"type" : "search", "element" : "", "data" : _req, "title" : "", "param" : _param});
		var _req = service.extendRequestJson({}, _currentRequest);
		_req = service.extendRequestJson(_req, data, true);
		_listInfo["page"] = 0;
		listInit("listTable",_req);
	}
	//検索ダイアログからの検索処理
	function searchProc(){
		if(_isBarcodeScan){
			var ret = scanCodeCheck();
			if(!ret) return false;
		}
		var _req = front.getFormValue(_currentForm);
		_searchProc(_req);
		pageClose();
	}
	function userSetting(){
		if(!front.validateFormValue(_currentForm)) return false;
		var data = front.getFormValue(_currentForm);
		util.setLocalData("userSetting", data);
		for(var key in data){
			_cache["userSetting"][key] = data[key];
		}
		pageClose();
		return true;
	}
	//---------------------------------------------------
	//treeView UI系～
	//---------------------------------------------------
	function treeInit(ele, tree_type, tree_code, defaultNode){
		var _req = {};
		var ret = null;
		var url = "gettree";
		var param = util.getLocalData("_sendParam");
		if(!util.isEmpty(param)){
			_req = param;
		}
		if(!util.isEmpty(tree_type) && tree_type=="organization") {
			url = "getorganization";
			tree_code = _req["COMPANY_CODE"];
		}
		ret =service.getAjax(true, "/"+url+"/"+tree_code, _req,
			function(result, st, xhr) {
				var data =  result["data"];
				if(data && data.length >=0){
					_treeView = $(ele).treeview({
						dataList: data,
						defaultNode : defaultNode,
						onNodeClick : nodeClick
					});
				}
			},
			function(xhr, st, err) {
				service.error("treeInit\n"+err.message+"\n"+xhr.responseText);
			}, true
		);
		return ret;
	}
	//tree Nodeクリック
	function nodeClick(nodeId, clickData){
		if(util.isEmpty(clickData["OPTION_STRING"]["onclick"])) return;
		if(_isImport){
			service.confirm("C_IMP_BACK", "", function(){
				_isImport = false;
				_nodeClick(nodeId, clickData);
			});
		}
		else {
			_nodeClick(nodeId, clickData);
		}
	}
	function _nodeClick(nodeId, clickData){
		//var isErr = true;
		var title = clickData["NAME"];
		_isUpdate = false;
		switch(clickData["OPTION_STRING"]["onclick"]){
			case "listInit":
				if(!util.isEmpty(clickData["OPTION_STRING"]["query_code"])){
					//isErr = false;
					pageHistoryAdd({"type" : "tree", "element" : nodeId, "title" : "", "param" : title});
					$("input[name='p_search_word']").val("");
					//title = clickData["NAME"];
					//if(!util.isEmpty(clickData["REMARK"])) title = clickData["REMARK"];
					_listInfo["page"] = 0;
					var _req = {};
					_req["query_code"] = clickData["OPTION_STRING"]["query_code"];
					if(!util.isEmpty(clickData["OPTION_STRING"]["setData"])){
						for(var key in clickData["OPTION_STRING"]["setData"]){
							var field = clickData["OPTION_STRING"]["setData"][key];
							if(util.isEmpty(clickData[key])) continue;
							_req[clickData["OPTION_STRING"]["setData"][key]] = clickData[key];
						}
					}
					listInit("listTable", _req, true);
				}
				break;
			case "getpage":
				if(!util.isEmpty(clickData["OPTION_STRING"]["page_code"])){
					//isErr = false;
					pageHistoryAdd({"type" : "tree", "element" : nodeId, "title" : "", "param" : title});
					pageClose();
					showPage(_editPageId, clickData["OPTION_STRING"]["page_code"] , "", clickData, function(){
						pageOpen();
					});
				}
				break;
		}
		//if(!isErr) pageHistoryAdd({"type" : "tree", "element" : nodeId, "title" : "", "param" : title});
	}
	//---------------------------------------------------
	//list_table UI系～
	//---------------------------------------------------
	function listInit(id, req, isNoCache){
		pageClose();
		//初期表示・（未対応：ソート）・ページング・検索にて通過(ページはクリアしない）
		if(!req["_order_"]) req["_order_"] = 1;
		req["_offset_"] = _cache["userSetting"]["pageSize"]*_listInfo["page"];
		req["_limit_"] = _cache["userSetting"]["pageSize"];
		_currentRequest = req;
		$("[accesskey=search] select, [accesskey=search] input").each(function(){
			//検索フォームの値をパラメータと同期
			var _name = $(this).attr("name");
			if(!util.isEmpty(req[_name])) $(this).val(req[_name]);
			else $(this).val("");
		});
		service.getAjax(true, "/search/"+req["query_code"], req,
			function(result, st, xhr) {
				var data =  result["data"];
				var count =  result["count"];
				var option =  result["option"];
				var type  =  result["type"];
				var title = result["name"];
				if(option && option!="" && option.indexOf(":")>=0){
					option = JSON.parse("{"+option+"}");
					_listInfo["option"] = option;
					if(option["title"] && !util.isEmpty(option["title"])) title = option["title"];
				}
				if(_pageHistory.length>0 && _pageHistory[_pageHistory.length-1]["title"]=="") _pageHistory[_pageHistory.length-1]["title"] =title;

				if(type=="list"){
					if(count==0) data = [];
					_listInfo["count"] = count;
					var _pageSize = data.length;
					if(util.diffVal(_cache["userSetting"]["pageSize"], _pageSize)>0) _pageSize = _cache["userSetting"]["pageSize"]|0;
					if(util.diffVal(_cache["userSetting"]["maxPageSize"], _pageSize)<0) _pageSize = _cache["userSetting"]["maxPageSize"]|0;
					_listInfo["maxpage"] = (count / _pageSize)|0;
					if(count==_listInfo["maxpage"]*_pageSize && _listInfo["maxpage"]>0) _listInfo["maxpage"]-=1;
					if(_listTable[id]){
						_listTable[id].listtable({
							"data" : data,
							"header" : option["header"],
							"tableStyleName" : "table table-bordered table-striped table-responsive",
							"zeroPaddingSize" : 3,
							"maxPageSize" : _cache["userSetting"]["maxPageSize"],
							"sortField" : "",
							"onSort" : listSort,
							"filterVal" : null,
							"onFilter" : listFilter,
							"onButtonClick" : editProc,
							"onLinkClick" : linkProc
						});
					}
					else {
						_listTable[id] = $("#"+id).listtable({
							"data" : data,
							"header" : option["header"],
							"tableStyleName" : "table table-bordered table-striped table-responsive",
							"zeroPaddingSize" : 3,
							"maxPageSize" : _cache["userSetting"]["maxPageSize"],
							"sortField" : "",
							"onSort" : listSort,
							"filterVal" : null,
							"onFilter" : listFilter,
							"onButtonClick" : editProc,
							"onLinkClick" : linkProc
						});
					}
					var _fromRecord = _listInfo["page"]*_pageSize+1;
					var _toRecord = _fromRecord+_pageSize-1;
					if(_toRecord > _listInfo["count"]) _toRecord = _listInfo["count"];
					var _pageInfo = (_fromRecord)+"-"+(_toRecord)+"件/"+_listInfo["count"]+"件中";
					if(count==0) _pageInfo = "-";
					//console.log(_listInfo["page"]+"/"+_listInfo["maxpage"]);
					$("#pageInfo").html(""+(_listInfo["page"]+1) + " ページ /  " + (_listInfo["maxpage"]+1)+"ページ中");
					$("#pageInfo").attr("title" , _pageInfo);
					_listTable[id].listtable("refresh");
					//切り替えた場合は、画面をスクロールトップにする
					$('body, html').scrollTop(0);
				}
				setSubTitle();
				buttonControl(option);
			},
			function(xhr, st, err) {
				service.error("listInit\n"+err.message+"\n"+xhr.responseText);
			}, isNoCache
		);
	}
	function listRefresh(isRefresh, params){
		var _req = $.extend({}, _currentRequest);
		if(!util.isEmpty(params)){
			_req = $.extend(_req, params);
		}
		if(_currentRequest!=null) listInit("listTable", _req, isRefresh);
	}
	function listPageMove(v){
		var _p = _listInfo["page"];
		_listInfo["page"]+=v;
		if(_listInfo["page"]<0) _listInfo["page"]=0;
		else if(_listInfo["page"]>_listInfo["maxpage"]) _listInfo["page"]=_listInfo["maxpage"];
		var _param = $.extend({}, _listInfo);
		var _req = $.extend({}, _currentRequest);
		pageHistoryAdd({"type" : "pager", "element" : "", "data" : _req, "title" : "", "param" : _param});

		if(_p == _listInfo["page"]) return;
		listRefresh();
	}
	function listPageStart(){
		listPageMove(-_maxInt);
	}
	function listPageEnd(){
		listPageMove(_maxInt);
	}
	function listFilter(data, filter){
		if(util.isEmpty(filter)) return  data;
		var filterVal = $("*[accesskey=scan][alt=codes]").val();
		filterVal = (filterVal+",").split(",");
		var result = [];
		for(var i=0,n=filterVal.length;i<n;i++){
			if(util.isEmpty(filterVal[i])) continue;
			var rowNo = _listTable["listTable"].listtable("existData", null, filter, filterVal[i]);
			result.push(data[rowNo]);
		}
		return result;
	}
	function listSort(data, sortField){
		if(util.isEmpty(sortField)) return  data;
		var _isLocal = false;

		if(_listInfo["page"]==0 && _listInfo["maxpage"]==0) _isLocal=true;
		if(_isLocal){
			var _sortField = [];
			for(var key in sortField){
				_sortField.unshift(key);
			}
			for(var i=0,n=_sortField.length;i<n;i++){
				var key = _sortField[i];
				var _order = 0;
				if(sortField[key]=="desc") _order = -1;
				else if(sortField[key]=="asc") _order = 1;
				if(_order==0) continue;
				data.timsort(function(first, second){
					var diff = util.diffVal(first[key] , second[key]);
					if (diff<0) return _order;
					else if (diff>0) return -_order;
					else return 0;
				});
			}
		}
		else {
			var _order = [];
			for(var key in sortField){
				if(util.isEmpty(sortField[key])) continue;
				_order.push(key+" "+sortField[key]);
			}
			if(!util.isEmpty(_order) && _order.length>0){
			/*
				var _req = {
					"_order_":	_order
				};
				$.extend(_req, _currentRequest);
				_req["_offset_"] = _cache["userSetting"]["pageSize"]*_listInfo["page"];
				_req["_limit_"] = _cache["userSetting"]["pageSize"];
			*/
				_currentRequest["_order_"] = _order;
				service.getAjax(false, "/search/"+_currentRequest["query_code"], _currentRequest,
					function(result, st, xhr) {
						data =  result["data"];
					},
					function(xhr, st, err) {
						service.error("listSort\n"+err.message+"\n"+xhr.responseText);
					}, true
				);
			}
		}
		return data;
	}
	//編集操作時の制御（編集・削除・コピー）
	function editProc(button, data){
		var _accesskey = $( button).attr("accesskey");
		if(util.isEmpty(_accesskey)) return;
		if(_accesskey=="rowedit" || _accesskey=="rowcopy"){
			var isEdit = false;
			if(_accesskey=="rowedit") isEdit = true;
			pageHistoryAdd({"type" : "button", "element" : button, "data" : data, "title" : "", "param" : ""});
			showEditPage(_editPageId, _currentRequest["query_code"]+"_add",_currentRequest["query_code"], data, isEdit);
		}
		else if(_accesskey == "rowdelete"){
			service.confirm("C_POST_DEL", "", function(){
				var _req = {
						"DELETE_FLAG":	 		1
						,"ID" :  data["ID"]
				};

				service.postAjax("/delete/"+_currentRequest["query_code"], _req,
					function(result, st, xhr) {
						pageClose();
						listRefresh(true);
					},
					function(xhr, st, err) {
						service.error("editProc\n"+err.message+"\n"+xhr.responseText);
					}
				);
			});
		}
		else {
			linkProc(button, data);
		}
	}
	//リンククリック時の制御
	function linkProc(link, data){
		var accesskey = $(link).attr("accesskey");
		var target = $(link).attr("target");
		var alt = $(link).attr("alt");
		var windowName  = $(link).attr("windowName");
		var field = $(link).attr("name");
		var val = $(link).text();
		if(util.isEmpty(val) && !util.isEmpty($(link).val())) val = $(link).val();
		var _isHistoryAdd = false;
		switch(accesskey){
			case "listInit":
				_listInfo["page"] = 0;
				pageHistoryAdd({"type" : "link", "element" : link, "data" : data, "title" : "", "param" : val, "linkProc" : accesskey, "linkParam" : target});
				var _req = service.extendRequestJson({"query_code" : target}, data);
				listInit("listTable",_req);
				break;
			case "url":
				//リンククリック＞ページ遷移（パラメータはローカルストレージで渡す）
				util.setLocalData("_sendParam", JSON.stringify(data));
				var url = target;
				location.href = url;
				break;
			case "dialog":
				showPage(_dialogId,_currentRequest["query_code"]+"_"+target, "",  data, function(){
					setEditForm(data);
					pageOpen();
					if(alt && (alt == "scansearch" || alt == "scanupd")){
						scanStart();
					}
				});
				break;
			case "subpage":
				if(alt == "newadd") _isUpdate = false;
				if(util.isEmpty(windowName)) pageHistoryAdd({"type" : "link", "element" : link, "data" : data, "title" : "", "param" : val, "linkProc" : accesskey, "linkParam" : _currentRequest["query_code"]+"_"+target});
				showPage(_editPageId,_currentRequest["query_code"]+"_"+target, "", data, function(){
					setEditForm(data);
					pageOpen();
				}, windowName);
				break;
			case "message":
				if(!util.isEmpty(target) && !util.isEmpty(alt)){
					service.alert(data[target],data[alt]);
				}
				break;
			case "filedownload":
				if(!util.isEmpty(alt) && data[alt]) val=data[alt];
				service.fileDownload("/download/"+val);
				break;
			case "getfile":
				if(!util.isEmpty(alt) && data[alt]) val=data[alt];
				window.open("/getfile/"+val);
				break;
		}
	}

	root.base = $.extend({}, root.base, public_method);

})(this);
