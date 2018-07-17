/**
* baseプラグイン
* 汎用的なページ処理をまとめる
* @namespace
* @class base
**/
;(function(root, undefined) {
	"use strict";
	var _saveTimer = null;
	var _isUpdate = false;
	var _isBarcodeScan = false;
	var _isImport = false;
	var _maxInt = Number.MAX_SAFE_INTEGER;
	var _cache = {
		"_fileUI" : {},
		"userSetting" : {
			"pageSize" : 20,
			"maxPageSize" : 200
		},
		"_url" : {
		},
	};
	var _isHistoryLoad = false;
	var _currentHistoryNo = 0;
	var _currentForm = "main";
	var _formHistory = [];
	//treeView用のIF
	var _treeView = null;
	//list_table用のIF
	var _currentRequest = null;
	var _listTable = {};
	var _listInfo = {"page" : 0, "maxpage" : 0, "count" : 0, "option" : null};
	var _editPageId = "sub";
	var _dialogId = "subDialog";
	var _isDebug = false;
	var _isPageLoad = false;
	var public_method = {
		//初期処理
		init : function(){
			window.onpopstate=function(e){
				pageOnload( e.state);
			};
			pageOnload();
		},
		showMessage : showMessage,
		pageSettinged : pageSettinged,
		getFileForm : getFileForm,
		listRefresh : listRefresh,
		linkProc : linkProc,
		exportProc : exportProc
	};
	//---------------------------------------------------
	//onload系～
	//---------------------------------------------------
	function pageOnload(state){
		_isPageLoad=true;
		service.setQueryParam();
		var lq = service.getQueryParam("query_code");
		if(!util.isEmpty(lq)){
			var _req = util.convQueryStringToJson();
			listInit("listTable", _req, false, function(){
				var lt = service.getQueryParam("lt");
				if(!util.isEmpty(lt) && lt!=="undefined"){
					$(".content-title").html(lt)
				}
				var su = service.getQueryParam("su");
				if(!util.isEmpty(su) && su==1) _isUpdate=true;
				var pagecode = service.getQueryParam("sp");
				var formId = service.getQueryParam("sf");
				var data = util.getLocalData("autosave");
				if(!util.isEmpty(formId) && !util.isEmpty(pagecode)){
					_isPageLoad=true;
					showPage(formId, pagecode, function(){
						dom.setEditForm(data, formId, _isUpdate);
						pageOpen(formId);
					});
				}
			});
		}
	}
	//---------------------------------------------------
	//画面表示系～
	//---------------------------------------------------
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
		$("button.btn[accesskey]", $("#"+formId)).unbind('click');
		$("button.btn[accesskey]", $("#"+formId)).click(function(e){
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
				case "cancel":
					pageClose();
					break;
				case "clear":
					front.clearFormValue(_currentForm);
					break;
				case "back":
					pageClose();
					//window.history.back();
					break;
				case "yes":
					if(util.isFunction(_cache["__callback"])) _cache["__callback"]();
					break;
				case "no":
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
		$("select.select2", $("#"+formId)).select2();

		/*
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
		*/
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
			$("*[accesskey=detailsearch]").hide();
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
							$("button.btn[accesskey=search]").click();
						}
					});
				}
				else if(key == "detailsearch"){
					$("*[accesskey="+key+"]").css("display", "inline-block");
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
			$("button.btn[accesskey]").unbind('click');
			$("button.btn[accesskey]").click(function(e){
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
						keyword = keyword.replaceAll("　", " "); //全角スペースを半角スペースに変換
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
						_isPageLoad = true;
						listRefresh(true);
						break;
					case "back":
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
		var sp = service.getQueryParam("sp");
		if(!util.isEmpty(sp)){
		}

	}
	//---------------------------------------------------
	//message
	//---------------------------------------------------
	//メッセージ表示
	function showMessage(messageCode, messageParam){
		var message = service.getMessage(messageCode, messageParam);
		$("#message .modal-body").html(message["body"]);
		$("#message .modal-title", $("#message").parent()).html(title);
		$('#message').modal('show');
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
	//サブページ表示処理
	function showPage(formId, pagecode,  callback, windowName){
		if(!util.isEmpty(formId)) _currentForm = formId;
		if(util.isEmpty(pagecode)) return;
		var async = true;
		if(!util.isEmpty(windowName)) async = false;
		_cache["_url"]["showPage"] = {"sf" : formId, "sp" : pagecode};
		if(_isUpdate) _cache["_url"]["showPage"]["su"] = 1;
		autoSave();
		service.getAjax(async, "/getpage/"+pagecode, {},
			function(result, st, xhr) {
				setUrl();
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
					var type=$("#"+formId).attr("type");
					$(".content-sub-title", $("#"+formId)).html(title);
					switch(param["PAGE_TYPE"]){
						case "param":
							var _pageContents = dom.paramPageLoad(option["form"],option["button"],_listTable["listTable"]);
							$(".content-sub-body", $("#"+formId)).html(_pageContents);
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

	//---------------------------------------------------
	//list_table UI系～
	//---------------------------------------------------
	function listInit(id, req, isNoCache, callback){
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

		_cache["_url"]["listInit"] = req;
		_cache["_url"]["showPage"] = "";

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
				if(count==0) data = [];
				_listInfo["count"] = count;
				var _pageSize = data.length;
				if(util.diffVal(_cache["userSetting"]["pageSize"], _pageSize)>0) _pageSize = _cache["userSetting"]["pageSize"]|0;
				if(util.diffVal(_cache["userSetting"]["maxPageSize"], _pageSize)<0) _pageSize = _cache["userSetting"]["maxPageSize"]|0;
				_listInfo["maxpage"] = (count / _pageSize)|0;
				if(count==_listInfo["maxpage"]*_pageSize && _listInfo["maxpage"]>0) _listInfo["maxpage"]-=1;

				buttonControl(option);

				var _listParam = {
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
				};
				if(_listTable[id]){
					_listTable[id].listtable(_listParam);
				}
				else {
					_listTable[id] = $("#"+id).listtable(_listParam);
				}
				var _fromRecord = _listInfo["page"]*_pageSize+1;
				var _toRecord = _fromRecord+_pageSize-1;
				if(_toRecord > _listInfo["count"]) _toRecord = _listInfo["count"];
				var _pageInfo = (_fromRecord)+"-"+(_toRecord)+"件/"+_listInfo["count"]+"件中";
				if(count==0) _pageInfo = "-";
				$("#pageInfo").html(""+(_listInfo["page"]+1) + " ページ /  " + (_listInfo["maxpage"]+1)+"ページ中");
				$("#pageInfo").attr("title" , _pageInfo);
				_listTable[id].listtable("refresh");
				//切り替えた場合は、画面をスクロールトップにする
				$(".content-title").html(title);
				$('body, html').scrollTop(0);
				if(callback && $.type(callback)=="function") callback(option);
				setUrl();
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
				var _req = service.extendRequestJson({"query_code" : target}, data);
				listInit("listTable",_req, false, function(){
					var _title = $(".content-title").html();
					_title = _title.replace("#param#", val);
					for(var key in data){
						_title = _title.replace("#"+key+"#", data[key]);
					}
					$(".content-title").html(_title);
				});
				break;
			case "url":
				//リンククリック＞ページ遷移（パラメータはローカルストレージで渡す）
				util.setLocalData("_sendParam", JSON.stringify(data));
				var url = target;
				location.href = url;
				break;
			case "dialog":
				var pagecode = _currentRequest["query_code"]+"_"+target;
				showPage(_dialogId, pagecode, function(){
					dom.setEditForm(data, _dialogId, _isUpdate);
					pageOpen(_dialogId);
				});
				break;
			case "subpage":
				if(alt == "newadd") _isUpdate = false;
				var pagecode = _currentRequest["query_code"]+"_"+target;
				showPage(_editPageId, pagecode, function(){
					dom.setEditForm(data, _editPageId, _isUpdate);
					pageOpen(_editPageId);
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
	//編集画面表示
	function showEditPage(formId, pagecode, querycode, data, isEdit){
		//編集値取得→編集画面表示→フォームセット
		var _req = service.extendRequestJson({}, data);
		if(util.isEmpty(querycode)){
			showPage(formId, pagecode,  function(){
				_isUpdate = isEdit;
				dom.setEditForm(data, formId, _isUpdate);
				pageOpen();
			});
		}
		else {
			service.getAjax(true, "/getedit/"+querycode, _req,
				function(result, st, xhr) {
					var data =  result["data"];
					_isUpdate = isEdit;
					showPage(formId, pagecode, function(){
						dom.setEditForm(data[0], formId, _isUpdate);
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
		if($("#"+formId).hasClass("modal")){
			$("#"+formId).modal('show');
		}
		else {
			$("#"+_dialogId).modal('hide');
			$("#"+formId).show();
			$("#main").hide();
		}
		$(':focus').blur();
	}
	function pageClose(formId){
		if(util.isEmpty(formId)) formId = _currentForm;
		if(util.isEmpty(formId)) return;
		if(formId=="main") return;
		if($("#"+formId).hasClass("modal")){
			$("#"+formId).modal('hide');
		}
		else {
			$("#"+formId).hide();
			$("#main").show();
		}
	}
	function saveProc(type, query_code, callback, noConfirm){
		var msg = "INS";
		if(type=="upd") {
			msg = "SAVE";
		}
		if(!front.validateFormValue(_currentForm)) return false;
		var _callback = function(){
			var _req = front.getFormValue(_currentForm);
			_isImport = false;
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
	/*保存処理後の再表示*/
	function _savedReload(){
		pageClose(_currentForm);
		listRefresh(true);
	}
	function autoSave(){
		if(_saveTimer!=null){
			clearTimeout(_saveTimer);
		}
		var _req = front.getFormValue(_currentForm);
		util.setLocalData("autosave", _req);
		_saveTimer = setTimeout(autoSave, 10000);
	}
	function autoSaveDispose(){
		if(_saveTimer!=null){
			clearTimeout(_saveTimer);
		}
		util.setLocalData("autosave", {});
	}
	function setUrl(){
		if(!_isPageLoad) {
			var url = location.pathname;
			var lt = $(".content-title").html();
			var _url_params = _cache["_url"]["listInit"];
			var treestatus = getTreeStatus();
			_url_params =$.extend(_url_params, _cache["_url"]["showPage"]);
			_url_params =$.extend(_url_params, treestatus);
			_url_params["lt"] = lt;
			url += "?" + util.convJsonToQueryString(_url_params);
			window.history.pushState(null, null, url);
		}
		_isPageLoad = false;
	}
	function getTreeStatus(){
		var ta = "";
		$(".nav-link.active").each(function(t){
			var id = $(this).attr("id");
			ta += id+",";
		});
		var to = "";
		$(".nav-item.menu-open>.nav-link").each(function(t){
			var id = $(this).attr("id");
			to += id+",";
		});
		return {"ta" : ta, "to" : to};
	}
	//検索処理
	function _searchProc(data){
		var _param = $.extend({}, data);
		var _req = $.extend({}, _currentRequest);
		var _req = service.extendRequestJson({}, _currentRequest);
		_req = service.extendRequestJson(_req, data, true);
		_listInfo["page"] = 0;
		listInit("listTable",_req);
	}
	//検索ダイアログからの検索処理
	function searchProc(){
		var _req = front.getFormValue(_currentForm);
		_searchProc(_req);
		pageClose();
	}

	root.base = $.extend({}, root.base, public_method);

})(this);

base.init();
