
//on ready statement
$(function(){
	window.onresize = function(){
		setChart(_cacheData["pageId"],_cacheData["dataSource"], _cacheData["data"]);
	}
	$(".section--result__body").hide();

	//タブのクリックとボタンのクリックイベント
	$("header .nav--tab__item a[for], .result__footer_btn a[for]").on("click", function(e){
		e.preventDefault();
		var pageId = $(this).attr("for");
		var kitIdStr = $("input[name=kit_id_str]").val();
		var h = base.getPageHistory();
		h["resultKit"] = kitIdStr;
		h["resultPage"] = pageId;
		base.pageHistoryAdd(h);
		pageInit(pageId);
	});

	//モバイル端末用のselect変更イベント（タブの替わり）
	$(".nav--tab__select select").on("change", function(){
		var pageId = $(this).val();
		var kitIdStr = $("input[name=kit_id_str]").val();
		var h = base.getPageHistory();
		h["resultPage"] = pageId;
		h["resultKit"] = kitIdStr;
		base.pageHistoryAdd(h);
		pageInit(pageId);
	});

	taxonListDom = ['<tr>',
		'<td class="result__table_body">',
			'<div class="block--bacteria">',
				'<div class="block--bacteria__eng">#ALIAS_ENG#</div>',
				'<div class="block--bacteria__title">#ALIAS_JPN#',
				'<div class="nav--tooltip-compact js_tooltip">',
					'<div class="nav--tooltip__inner">',
						'<div class="nav--tooltip__body">#DESCRIPTION#</div>',
					'</div>',
				'</div>',
			'</div>',
		'</td>',
		'<td class="result__table_body">#average#%</td>',
		'<td class="result__table_body">',
			'<div class="result__table_you">#ratio#',
				'<span>%</span>',
			'</div>',
			'<span class="result__table_ratio">#remark#</span>',
		'</td>',
	'</tr>' ].join('');

	pageInit();
});
//新・旧検査結果ページ切り替え
function pageModeChange(pagemode){
	var _url = location.href;
	if(_url.indexOf("https://pro.mykinso.com")>=0 && pagemode==="old"){
		ga('send', 'event', 'content', 'click', 'old_result');
	}
	var kitIdStr = $("input[name=kit_id_str]").val();
	var h = base.getPageHistory();
	var pageCode = h["linkParam"];
	if(util.isEmpty(pageCode)) return;
	_cacheData["pagemode"] = pagemode;
	cacheSave();
	pageCode = pageCode.replace("2", "");
	pageCode = pageCode.replace("1", "");
	var h = base.getPageHistory();
	h["resultKit"] = kitIdStr;
	h["resultPage"] = "result";
	h["resultUrl"] = pageCode+"1";
	base.pageHistoryAdd(h);
	base.showPage("", pageCode, "", {"kit" : kitIdStr});
}
//検査結果ページ設定保存
function cacheSave(){
	util.setLocalData("_pro_result_mode", {"pagemode" : _cacheData["pagemode"]});
}
//検査結果ページ設定ロード
function cacheLoad(){
	var c = util.getLocalData("_pro_result_mode");
	if(c!=null && !util.isEmpty(c["pagemode"]))_cacheData["pagemode"] = c["pagemode"];
	if(!_cacheData["pagemode"]) _cacheData["pagemode"] = "new";
}
//ページ表示初期処理
function pageInit(pageId){
	base.setQueryParam();
	cacheLoad();
	//各サブページの表示切替
	var _hash =  service.getQueryParam("_hash");
	var kitIdStr = $("input[name=kit_id_str]").val();

	//履歴から、検査結果表示タブ取得
	var h = base.getPageHistory();
	if(h["resultPage"]) pageId = h["resultPage"];
	if(h["resultKit"]) kitIdStr = h["resultKit"];
	if(util.isEmpty(pageId)) pageId = "result";
	//タブの状態の切り替え
	$("header .nav--tab__item").removeClass("is--active");
	$("header .nav--tab__item a[for="+pageId+"]").parent().addClass("is--active");
	//モバイル端末用のselectに値設定（タブの替わり）
	$(".nav--tab__select select").val(pageId);
	if(location.href.indexOf("#") < 0 ){
		//切り替えた場合は、画面をスクロールトップにする(URLに#ハッシュがない場合）
		$('body, html').scrollTop(0);
	}
	if(!util.isEmpty(_hash)) {
		$("body, html").animate({scrollTop:$('*[name='+_hash+']').offset().top});
	}
	if(!util.isEmpty(kitIdStr)) showResult(kitIdStr);
	_cacheData["pageId"] = pageId;
}
