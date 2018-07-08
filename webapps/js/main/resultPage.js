var _cacheData = {};
var taxonListDom = ['<tr>',
	'<td class="result__table_body">',
		'<div class="block--bacteria">',
			'<div class="block--bacteria__eng">#ALIAS_ENG#</div>',
			'<div class="block--bacteria__title">#ALIAS_JPN#',
				'<div class="nav--tooltip js_tooltip">',
					'<span class="nav--tooltip__icon icon--question"></span>',
					'<div class="nav--tooltip__inner">',
						'<div class="nav--tooltip__title">#ALIAS_JPN#</div>',
						'<div class="nav--tooltip__body">#DESCRIPTION#</div>',
					'</div>',
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

//検査結果表示
function showResult(kitIdStr){
	$(".section--result").hide();
	$(".section--result").fadeIn(100, function(){
		if(!_cacheData["dataSource"] || _cacheData["dataSource"] == null){
			var _req = { "kit_id_str" : kitIdStr };
			getData("get_resultdata_by_kit_id", _req, function(data){
				setResultdata(data, kitIdStr);
			});
		}
		else {
			setResultdata(_cacheData["dataSource"], kitIdStr);
		}
	});
}
//検査結果データ設定
function setResultdata(dataSource, kitIdStr){
	//カレントのデータを取得
	var data = null;
	for(var i=0;i<dataSource.length;i++){
		var _kitIdStr = dataSource[i]["kit_id_str"];
		if(kitIdStr ==_kitIdStr) {
			data = dataSource[i];
		}
	}

	//総合検査結果の場合、検査キットリストを設定
	setJudgeResultData(dataSource, data);

	//ヘルプアイコンの内容設定
	setTooltipData();

	//検査結果詳細リンク設定
	$("#resultDetailButton").attr("href", "/result_detail?kit="+kitIdStr);

	//自覚症状(便秘・下痢）
	var is_constipation = data["is_constipation"];
	var is_diarrhea = data["is_diarrhea"];
	if(!_cacheData["flora_judge"] || _cacheData["flora_judge"] == null){
		getData("get_flora_judge", {"kit_id_str" : data["kit_id_str"]}, function(data){
			_cacheData["flora_judge"] = data;
			setFloraJudgeData(data, {"diarrhea" : is_diarrhea, "constipation" : is_constipation});
		});
	}
	else {
		setFloraJudgeData(_cacheData["flora_judge"], {"diarrhea" : is_diarrhea, "constipation" : is_constipation});
	}

	if(!_cacheData["taxonlist"] || _cacheData["taxonlist"] == null){
		getData("get_taxon_list", {"kit_id_str" : data["kit_id_str"]}, function(data){
			_cacheData["taxonlist"] = data;
			setTaxonlist(data);
		});
	}
	else {
		setTaxonlist(_cacheData["taxonlist"]);
	}
	_cacheData["dataSource"] = dataSource;
	_cacheData["data"] = data;
	$(window).trigger("resize");
}
// 検査データリスト設定
function setJudgeResultData(dataSource, data){
	var previousData = getPreviousData(dataSource, data);
	$("*[id]", $(".section--result")).each(function(e){
		var tagName = $(this).prop("tagName");
		var id = $(this).attr("id");

		// 前回の表示
		if(id.substr(-3,3)=="Pre"){
			if (!util.isEmpty(previousData)) {
				var _id=id.replace("Pre", "");
				$(this).html(previousData[_id]);
			}
			else {
				$(this).html("-");
			}
		}

		// 無限大(∞)の表示
		if (id.match(/Value/)) {
			var _currentId = id;
			var _previousId = id.replace("Pre", "");
			// 前回の場合
			if (id.substr(-3,3) === "Pre") {
				if (!util.isEmpty(previousData)
					&& isNaN(util.parseStrToFloat(previousData[_previousId]))) {
					$(this).html('∞');
				}
			}
			// 今回の場合
			else {
				if (isNaN(util.parseStrToFloat(data[id]))) {
					$(this).html('∞');
				}
			}
		}

		if(!data[id]) return;
		switch(tagName.toUpperCase()){
			case "IMG":
				var src = "/images/result/";
				$(this).attr("src",src+data[id]);
				break;
			default:
				$(this).html(data[id]);
		}
	});
}
//ツールチップデータ設定
function setTooltipData(){
	$("div.nav--tooltip__inner[accesskey]").each(function(){
		var key = $(this).attr("accesskey");
		var _help = getHelpText(key);
		if(_help!=null){
			$(".nav--tooltip__title" , $(this)).html(_help["title"]);
			$(".nav--tooltip__body" , $(this)).html(_help["body"]);
		}
	});
}
//グラフ表示設定
function setChart(pageId, dataSource, data){
	$(".section--result__body").show();
	if(dataSource.length > 1) {
		//2回以上検査している場合
		setLinechart(dataSource);
		setStackbarchartV(dataSource);
	}
	setBarChart();
	setStackbarchartV2(data, getPreviousData(dataSource, data));
	setPiechart(data);
	setStackbarchart(data);
	if(dataSource.length > 1) {
		//2回以上検査している場合
		$("*[pagemode=totalResult]:not(.print--title)").show();
		$("*:not(.print--title)" , $("*[pagemode=totalResult]")).show();
		$("*[pagemode=singleResult]").hide();
		$("*" , $("*[pagemode=singleResult]")).hide();
	}
	else {
		$("*[pagemode=totalResult]").hide();
		$("*" , $("*[pagemode=totalResult]")).hide();
		$("*[pagemode=singleResult]").show();
		$("*" , $("*[pagemode=singleResult]")).show();
	}

	$(".section--result__body").hide();
	$("#"+pageId+".section--result__body").show();
}
//菌叢判定設定
function setFloraJudgeData(data,  awareness){
	for(var i=0, n = data.length;i<n; i++){
		var flora_judge = "";　			//便秘 or 下痢の判定文言を取得するキー
		if(data[i]["data_type"] == "constipation"	|| data[i]["data_type"] == "diarrhea" ) {
			if(!awareness[data[i]["data_type"]]) {
				//アンケートの回答がない場合
				flora_judge = data[i]["data_type"] + "_" + data[i]["data_level"];
			}
			else {
				//アンケートの回答がある場合
				flora_judge = data[i]["data_type"] + "_" + data[i]["data_level"] + "_"+  awareness[data[i]["data_type"]];
			}
		}
		else if(data[i]["data_type"].indexOf("_adv_") >= 0 ) {
			//便秘、下痢のアドバイス適用度（★の数）を設定する
			var advice = data[i]["data_type"];
			var star = data[i]["data_level"];
			if(util.isEmpty(advice)) continue;
			if(util.isEmpty(star)) continue;
			star = star|0;
			$("#"+advice+" .icon--star").removeClass("is--active");
			for(var j=0;j<star;j++){
				$("#"+advice+" .icon--star:eq("+j+")").addClass("is--active");
			}
		}
		if(!util.isEmpty(flora_judge)){
			//便秘、下痢の判定文言を設定する
			var flora_judge_text = getFloraJudgeText(flora_judge);
			if(flora_judge_text != null){
				$("#flora_judge_"+data[i]["data_type"]+"_title").html(flora_judge_text["title"]);
				$("#flora_judge_"+data[i]["data_type"]+"_body").html(flora_judge_text["body"].replaceAll("\n", "<br>"));
				$("#flora_judge_"+data[i]["data_type"]+"_image").attr("src", "/images/result/"+data[i]["judge_image"]);
			}
		}
	}
}

//折れ線グラフ設定
function setLinechart(dataSource){
	$(".basicchart.linechart").each(function(){
		var _id = $(this).attr("id");
		if(util.isEmpty(_id)) return;
		var _labelField = $(this).attr("labelField");
		if(util.isEmpty(_labelField)) return;
		var _dataField = $(this).attr("dataField");
		if(util.isEmpty(_dataField)) return;
		var _chartUnit = $(this).attr("chartUnit");
		if(util.isEmpty(_chartUnit)) _chartUnit="";

		var _chartColor = $(this).attr("chartColor");
		var _averageColor = $(this).attr("averageColor");
		var data = [];
		var _average = null;
		var _fieldId = _dataField+"Value";
		var _averageField = _dataField+"_avg";
		for(var i=0, n=dataSource.length;i<n;i++){
			var _record = {};
			_record[_labelField] = dataSource[i][_labelField];
			_record[_fieldId] = util.parseStrToFloat(dataSource[i][_fieldId]);
			//ツールチップ：日付：データ名：データ値
			_record["tooltip_text"] =dataSource[i][_labelField]+"<br>";
			_record["tooltip_text"] += dataSource[i][_fieldId]+_chartUnit;
			if(_average==null) {
				_average = util.parseStrToFloat(dataSource[0][_averageField]);
				$("#"+_averageField).html(_average);
			}
			data.unshift(_record);
		}
		for(var i=0,n=data.length;i<n;i++){
			data[i][_labelField] = Date.parse(data[i][_labelField]);
			data[i][_labelField] = new Date(data[i][_labelField]);
		}

		var xScale = [d3.min(data, function(d){ return (d[_labelField]); }),
								d3.max(data, function(d){ return  (d[_labelField]); })];
		var xScaleSize = data.length+1;

		var maxy = d3.max(data, function(d){ return (d[_fieldId]); });
		var miny = d3.min(data, function(d){ return (d[_fieldId]); });
		//最小-20%～最大+20%の範囲で出す、ただし範囲の下限＝０、上限＝０の場合は１（暫定）
		//if(maxy < _average ) maxy = _average;
		miny -= miny*0.2;
		maxy += maxy*0.2;
		if(miny < 0) miny = 0;
		if(maxy == 0) maxy = 1;
		var _averageLineStyle = null;
		if(_average < maxy && _average!=null && !util.isEmpty(_averageColor)){
			_averageLineStyle = {
				value: _average,
				stroke : _averageColor,
				lineMargin : [16, 64]
			};
		}
		var yScale = [maxy, miny];
		var yScaleSize = 5;
		var options = {
				data :data,
				padding : [8,24,32,64],
				xScale : xScale,
				xScaleSize : xScaleSize,
				yScaleSize : yScaleSize,
				yScale : yScale
		};
		$(this).unbind("basicchart");
		var chart1 = $(this).basicchart(options);
		chart1.basicchart("drawAxisX",{
			type : "time",
			labels : "%y/%m",
			lineMargin : [0,0],
			lineWidth:0,
			innerLineWidth : 0,
			textAlign : "start",
			textSize : 0,
			padding : [0,0],
			visible : true,
			orient: "bottom",
			transform: [0, chart1.basicchart("getAttributes").drawAxisHeight],
		});
		chart1.basicchart("drawAxisY",{
			lineMargin : [0,0],
			lineWidth:0,
			padding : [-18,0]
		});

		chart1.basicchart("lineChart",  {
			symbolStyle : "liner",  //cardinal,step,liner
			animationWait : 0,
			animationTime : 1000,
			labels : "%Y/%m",
			fieldX : _labelField,
			fieldY : _fieldId,
			fieldLabel : _labelField,
			textSize : 10,
			textOffset : [0,16],
			lineColor: _chartColor,
			lineWidth:3
		},_averageLineStyle);
		addHighlightLabel(_id, "最新", {"fill" : "#DF8672", "font-size" : "12px"});
		chart1.basicchart("scatterChart", {
			lineWidth:4,
			labels : "%v"+_chartUnit,
			animation : 1000,
			fieldX : _labelField,
			fieldY : _fieldId,
			fieldLabel : _fieldId,
			lineColor: _chartColor,
			symbolColor:  "#FFF",
			symbolSize : 7,
			textSize : 10,
			textAlign : "middle",
			textOffset : [0,-14],
			onMouseover : chartMouseover,
			onMousemove : chartMousemove,
			onMouseout :chartMouseout
		});
	});
}
//円グラフ設定
function setPiechart(dataSource){
	$(".basicchart.piechart").each(function(){
		var _id = $(this).attr("id");
		if(util.isEmpty(_id)) return;
		var _chartColor = $(this).attr("chartColor");
		if(_chartColor.indexOf(",")>=0) _chartColor = _chartColor.split(",");
		else _chartColor = [_chartColor];

		var data = {};
		var _dataField = [];

		for(var _filter in dataSource){
			if(_filter.indexOf("entero_ratio") < 0 ) continue;
			data[_filter] = util.parseStrToFloat(dataSource[_filter]);
			$("li.result__percentage_item #"+ _filter).html(dataSource[_filter]+"%");
		}
		var yScale = [d3.max(data, function(d){ return d.y; }),0];
		var yScaleSize = (yScale[1] - yScale[0]);

		var xScale = [d3.min(data, function(d){ return d.x; }),d3.max(data, function(d){ return d.x; })];
		var xScaleSize = (xScale[1] - xScale[0]);
		if(xScaleSize < 0) xScaleSize*=-1;
		if(yScaleSize < 0) yScaleSize*=-1;
		yScaleSize /= 50;
		var options = {
				data : data,
				padding : [8,8,8,8],
				xScale : xScale,
				yScale : yScale,
				yScaleSize : yScaleSize+1,
				xScaleSize : xScaleSize+1
		};
		$(this).unbind("basicchart");
		var chart1 = $(this).basicchart(options);
		chart1.basicchart("pieChart",{
			textColor:"#FFF",
			lineColor : "#FFF",
			lineWidth:1,
			symbolSize:0,
			selectSymbolSize : 8,
			labels : "%v%",
			textSize:0,
			symbolColor : function(d, i, j ){
				return _chartColor[i%_chartColor.length];
			},
			onMouseover : _chartMouseover,
			onMousemove : chartMousemove,
			onMouseout :chartMouseout
		});
	});
	function _chartMouseover(d, i, j, selecter, style){
		if(!selecter) return;
		var tag = $(selecter).prop("tagName");
		var cl = $(selecter).attr("class");
		var alt = $(selecter).attr("alt")
		if(tag.toLowerCase() == "path") {
			$(".onSelect "+tag+"[alt="+alt+"]", $(selecter).parent().parent()).attr("opacity", 0.5);
		}
		var _help = getHelpText(d.data.key);
		var _title = "";
		if(_help != null) _title = _help["title"]+"<br>";
		var _tooltip_text = _title+d.data.value+"%";
		return d3.select("body").select("#tooltip").style("visibility", "visible").html(_tooltip_text);
	}
}
//積み上げ棒グラフ（横）設定
function setStackbarchart(dataSource){
	$(".basicchart.stackbarchart").each(function(){
		var _id = $(this).attr("id");
		if(util.isEmpty(_id)) return;
		var _labelField = $(this).attr("labelField");
		if(util.isEmpty(_labelField)) return;
		var _dataField = $(this).attr("dataField");
		if(util.isEmpty(_dataField)) return;
		if(_dataField.indexOf(",")>=0) _dataField = _dataField.split(",");
		else _dataField = [_dataField];
		var _chartColor = $(this).attr("chartColor");
		if(_chartColor.indexOf(",")>=0) _chartColor = _chartColor.split(",");
		else _chartColor = [_chartColor];
		var labels = [];
		var data = [];
		labels.push("あなた");
		labels.push("平均");
		var _record = {};
		var _avgRecord = {};
		for(var j=0, m = _dataField.length;j<m;j++){
			var key = _dataField[j];
			_record[key] = util.parseStrToFloat(dataSource[key]);
			_avgRecord[key] = util.parseStrToFloat(dataSource[key+"_avg"]);
		}
		data.push(_record);
		data.push(_avgRecord);
		var options = {
				data : data,
				padding : [0,20,24,48],
				xScaleSize : 10,
				labelY : labels,
				xScaleSize :5,
				yScaleSize :2,
				xScale : [0,100],
				yScale : [0,data.length]
		};
		$(this).unbind("basicchart");
		var	chart1 = $(this).basicchart(options);

		chart1.basicchart("drawAxisX",{
			labels : "%v%",
			lineMargin : [18,18],
			lineWidth :0,
			textAlign : "start",
			textOffset : [0,0],
			padding : [0,10],
			orient: "bottom",
			transform: [0, chart1.basicchart("getAttributes").drawAxisHeight+10],
		});
		chart1.basicchart("setOption", "axisStyleY" , {
			labels : labels,
			lineMargin : [10,10],
			lineWidth :1,
			textAlign : "end",
			textOffset : [0,0]
		});

		chart1.basicchart("stackedBarChart", {
			fieldY : _dataField,
			labels : "%v%",
			textSize : 8,
			textAlign : "end",
			lineColor : "#FFF",
			symbolColor : function(d, i, j ){
				return _chartColor[j%_chartColor.length];
			},
			padding : [0, 24],
			lineWidth : 1,
			textOffset : [-6, 0],
			symbolSize : 0.7,
			onMouseover : _chartMouseover,
			onMousemove : chartMousemove,
			onMouseout :chartMouseout
		},true);

		function _chartMouseover(d, i, j, selecter, style){
			if(!selecter) return;
			var tag = $(selecter).prop("tagName");
			var cl = $(selecter).attr("class");
			var alt = $(selecter).attr("alt")
			if(cl == "chartShape") {
				$(".onSelect "+tag+"[alt="+alt+"]", $(selecter).parent().parent()).attr("opacity", 0.5);
			}
			var _text = labels[d.row]+":"+getDataName(d.col)+"<br>"+d["y"]+"%";
			return d3.select("body").select("#tooltip").style("visibility", "visible").html(_text);
		}
	});
}
//マウスオーバー時の処理（ツールチップ表示）
function chartMouseover(d, i, j, selecter, style){
	if(!selecter) return;
	var tag = $(selecter).prop("tagName");
	var cl = $(selecter).attr("class");
	var alt = $(selecter).attr("alt")
	if(!d["tooltip_text"] || d["tooltip_text"] == "") return;
	if(cl == "chartShape") {
		$(".onSelect "+tag+"[alt="+alt+"]", $(selecter).parent().parent()).attr("opacity", 0.5);
	}
	if(tag=="circle"){
		var _color = $(selecter).attr("stroke");
		$(selecter).attr("fill",  _color);
	}
	d3.select("body").select("#tooltip").style("visibility", "visible").html(d["tooltip_text"]);
}
//マウスムーブ時の処理（ツールチップの位置設定）
function chartMousemove(d, i, j, selecter, style){
	var top = (d3.event.pageY-20);
	var left = (d3.event.pageX+10);
	d3.select("body").select("#tooltip").style("top" , top+"px").style("left", left+"px");
}
//マウスアウト時の処理（ツールチップ非表示）
function chartMouseout(d, i, j, selecter, style){
	if(!selecter) return;
	var tag = $(selecter).prop("tagName");
	var cl = $(selecter).attr("class");
	var alt = $(selecter).attr("alt");
	if(tag == "path") {
		$(".onSelect "+tag+"[alt="+alt+"]", $(selecter).parent().parent()).attr("opacity", 0);
	}
	else if(tag=="circle"){
		$(selecter).attr("fill", "#FFF");
	}
	d3.select("body").select("#tooltip").style("visibility", "hidden");
}
//範囲値(from-to)にvalが収まているか判定する
function _isInRange(val, from, to){
	var _val = util.parseStrToFloat(val);
	if(!util.isEmpty(from)){
		var _from = util.parseStrToFloat(from);
		if(_from <= _val){
			if(util.isEmpty(to)) return true;
			else if(_val <= util.parseStrToFloat(to)) return true;
		}
	}
	else {
		if(util.isEmpty(to)) return false;
		else if(_val <= util.parseStrToFloat(to)) return true;
	}
	return false;
}
//フィールド名からラベルを取得する
function getDataName(key){
	var _help = getHelpText(key);
	if(_help==null) return "";
	return _help["title"];
}
//ヘルプ内容取得
function getHelpText(key){
	var _data = service.getCodeData("result_help", key);
	if(util.isEmpty(_data)) return null;
	if(_data.length < 2) return null;

	return {"title" : _data[1], "body" : _data[2]};
}
//菌叢判定文言取得
function getFloraJudgeText(key){
	var _data = service.getCodeData("flora_judge", key);
	if(util.isEmpty(_data)) return null;
	if(_data.length < 2) return null;
	return {"title" : _data[1], "body" : _data[2]};
}
//積み上げグラフver2設定(基準値つき)
function setStackbarchartV2(dataSource, previousDataSource){
	$(".basicchart.stackbarchart_v2").each(function(){
		// グラフの高さをグラフの大きさの基準値にする
		var _graphHeight = $(this).height();
		var _graphStandardVal = _graphHeight / 10;
		var _graphStandardValHalf = _graphStandardVal / 2;
		var _graphStandardValQuarter = _graphStandardVal / 4;

		// Default
		var _data = [];
		var _scales = [];
		var _colors = [];
		var _axisLevel = 6;
		var _topPadding = _graphStandardVal;
		var _bottomPadding = _graphStandardVal;
		var _leftPadding = _graphStandardVal + _graphStandardValQuarter;
		var _rightPadding = _graphStandardVal + _graphStandardValQuarter;
		var _graphBarHeight = _graphStandardVal * 2;
		var _visibleLeftGradation = false; // グラフ左端ぼかし表示フラグ
		var _visibleRightGradation = false; // グラフ左端ぼかし表示フラグ
		var _exceptGraphWidth = _graphStandardVal; // 基準値以外のグラフWidth
		var _isLowerGraphMin = false; // 基準値の最小値がグラフ最小値よりも小さいか
		var _isUpperGraphMax = false; // 基準値の最大値がグラフ最大値よりも大きいか

		// Attr属性
		var _dataField = $(this).attr("dataField");
		var _chartColor = $(this).attr("chartColor");
		var _backgroundColor = $(this).attr("backgroundColor");
		var _highlightColor = $(this).attr("highlightColor");
		var _highlightLabel = $(this).attr("highlightLabel");
		var _axisTextColor = $(this).attr("axisTextColor");
		var _averageColor = $(this).attr("averageColor");
		var _averageLabel = $(this).attr("averageLabel");
		var _currentColor = $(this).attr("currentColor");
		var _currentLabel = $(this).attr("currentLabel");
		var _previousColor = $(this).attr("previousColor");
		var _previousLabel = $(this).attr("previousLabel");
		if(util.isEmpty(_dataField)) return;

		// 目盛り
		_scales = JSON.parse(dataSource[_dataField+"_mean_segment"]);
		var _axisNumVal = _scales.length;
		var _intervalNumVal = _axisNumVal - 1;

		// グラフの値
		var _currentVal = util.parseStrToFloat(dataSource[_dataField+"Value"]);
		var _meanVal = util.parseStrToFloat(dataSource[_dataField+"_mean"]);
		var _meanMinVal = util.parseStrToFloat(dataSource[_dataField+"_mean_min"]);
		var _meanMaxVal = util.parseStrToFloat(dataSource[_dataField+"_mean_max"]);
		var _minVal = util.parseStrToFloat(_scales[0]);
		var _maxVal = util.parseStrToFloat(_scales[_axisNumVal - 1]);

		// 前回の値
		var _previousVal = null;
		if (!util.isEmpty(previousDataSource)) {
			_previousVal = util.parseStrToFloat(previousDataSource[_dataField+"Value"]);
		}

		// グラフ左端ぼかしを表示するかの判定
		if (_minVal > 0) _visibleLeftGradation = true;

		// グラフ右端ぼかしを表示するかの判定
		if (_maxVal < 100) _visibleRightGradation = true;

		// グラフのPaddingの再計算する
		if (_visibleLeftGradation) _leftPadding += _exceptGraphWidth;
		if (_visibleRightGradation) _rightPadding += _exceptGraphWidth;

		// グラフ描画の最大・最小のグラフ判定
		if (_meanMinVal < _minVal) _isLowerGraphMin = true;
		if (_meanMaxVal > _maxVal) _isUpperGraphMax = true;

		// 現在値、平均の基準値・上限値・下限値の座標軸上の全体割合を計算する
		_currentTotalRate = _calculateScaleRate(_currentVal, _scales, _intervalNumVal);
		_previousTotalRate = _calculateScaleRate(_previousVal, _scales, _intervalNumVal);
		_meanTotalRate = _calculateScaleRate(_meanVal, _scales, _intervalNumVal);
		_meanMinTotalRate = _calculateScaleRate(_meanMinVal, _scales, _intervalNumVal);
		_meanMaxTotalRate = _calculateScaleRate(_meanMaxVal, _scales, _intervalNumVal);

		// グラフのデータ
		_data.push({
			"f1": _meanMinTotalRate,
			"f2": _meanMaxTotalRate - _meanMinTotalRate,
			"f3": _intervalNumVal - _meanMaxTotalRate
		});

		// グラフの色
		_colors.push(
			_chartColor,
			_highlightColor,
			_chartColor
		);

		// グラフの幅の取得
		var _graphWidth = $(this).width();
		var _graphInnerWidth = _graphWidth - (_leftPadding + _rightPadding);
		var _graphInnerHeight = _graphHeight - (_topPadding + _bottomPadding);

		// グラフの基本表示
		$(this).unbind("basicchart");
		var	chart1 = $(this).basicchart({
			data: _data,
			padding: [_topPadding, _rightPadding, _bottomPadding, _leftPadding],
			xScaleSize: _axisNumVal,
			xScale: [0, _intervalNumVal],
			yScale: [0, 1],
		});
		chart1.basicchart("drawAxisX", {
			labels: _scales.map(function(val){return val+"";}),
			textSize: 12,
			textOffset: [0, -7],
			textAlign: "middle",
			textColor: _axisTextColor,
			innerLineWidth: 1,
			lineWidth: 0,
			lineMargin: [0, 0],
			lineColor: _chartColor,
			padding: [0, _graphStandardVal],
			innerLineStyle: "2, 0.8",
			orient: "top",
			transform: [0, _graphStandardVal],
		});
		chart1.basicchart("setOption", "axisStyleY" , {
			labels: [],
			lineMargin: [0, 0],
			lineWidth: 0,
			textOffset: [0, 0],
			padding: [0, 0]
		});
		chart1.basicchart("stackedBarChart", {
			fieldY: ["f1", "f2", "f3"],
			textSize: 8,
			textAlign: "end",
			lineColor: function(d, i, j) {
				return _colors[j%_colors.length];
			},
			symbolColor: function(d, i, j) {
				return _colors[j%_colors.length];
			},
			padding: [0, _graphStandardVal * 3],
			lineWidth: 1,
			textOffset: [0, 0],
			symbolSize: _graphBarHeight,
			onMouseover: chartMouseover,
		    onMousemove: chartMousemove,
		    onMouseout: chartMouseout
		}, true);

		// 平均値の最小のラベルを表示する
		if (!_isLowerGraphMin) {
			var _xScaleMinMean = (_graphInnerWidth * _meanMinTotalRate / _intervalNumVal) + _leftPadding;
			chart1.basicchart("drawLabel", {
				// 枠付きテキストの設定
				xScaleText: _xScaleMinMean,
				yScaleText: _graphInnerHeight - _graphStandardVal,
				textSize: 16,
				textColor: _highlightColor,
				textVal: _meanMinVal,
				// 線の設定
				xScaleLine: [_xScaleMinMean, _xScaleMinMean],
				yScaleLine: [(_graphStandardVal * 4 - _graphStandardValQuarter), (_graphStandardVal * 4 + _graphBarHeight + _graphStandardValQuarter)],
				lineWidth: 2,
				lineColor: _highlightColor,
			});
		}

		// 平均値の最大のラベルを表示する
		if (!_isUpperGraphMax) {
			var _xScaleMaxMean = (_graphInnerWidth * _meanMaxTotalRate / _intervalNumVal) + _leftPadding;
			chart1.basicchart("drawLabel", {
				// 枠付きテキストの設定
				xScaleText: _xScaleMaxMean,
				yScaleText: _graphInnerHeight - _graphStandardVal,
				textSize: 16,
				textColor: _highlightColor,
				textVal: _meanMaxVal,
				// 線の設定
				xScaleLine: [_xScaleMaxMean, _xScaleMaxMean],
				yScaleLine: [(_graphStandardVal * 4 - _graphStandardValQuarter), (_graphStandardVal * 4 + _graphBarHeight + _graphStandardValQuarter)],
				lineWidth: 2,
				lineColor: _highlightColor,
			});
		}

		// グラフ左端ぼかしを表示する
		if (_visibleLeftGradation) {
			chart1.basicchart("drawRectWithAnimation", {
				xScale: _graphStandardVal + _graphStandardValQuarter,
				yScale: _graphStandardVal * 4,
				startWidth: 0,
				endWidth: _exceptGraphWidth,
				startHeight: _graphBarHeight,
				endHeight: _graphBarHeight,
				animationTime: 50,
				animationWait: 200,
				strokeWidth: 1,
				gradientId: _dataField+"_lower_graph",
				startColor: _backgroundColor,
				endColor: _chartColor,
			});
		}

		// グラフ右端ぼかしを表示する
		if (_visibleRightGradation) {
			var _upperGraphColor = (_isUpperGraphMax) ? _highlightColor : _chartColor;
			chart1.basicchart("drawRectWithAnimation", {
				xScale: _graphWidth - (_graphStandardVal + _exceptGraphWidth + _graphStandardValQuarter),
				yScale: _graphStandardVal * 4,
				startWidth: 0,
				endWidth: _exceptGraphWidth,
				startHeight: _graphBarHeight,
				endHeight: _graphBarHeight,
				animationTime: 250,
				animationWait: 700,
				strokeWidth: 1,
				gradientId: _dataField+"_upper_graph",
				startColor: _upperGraphColor,
				endColor: _backgroundColor,
			});
		}

		// 今回値のラベルを表示する
		var _xScaleCurrent = (_graphInnerWidth * _currentTotalRate / _intervalNumVal) + _leftPadding;
		// 最小値より小さい場合
		if (_currentVal < _minVal) _xScaleCurrent = _exceptGraphWidth + _graphStandardValQuarter;
		// 最大値より大きい場合
		if (_currentVal > _maxVal) _xScaleCurrent = _graphWidth - (_exceptGraphWidth + _graphStandardValQuarter);
		// 値がNULLの場合：右端に表示
		if (isNaN(_currentVal)) _xScaleCurrent = _graphWidth - (_exceptGraphWidth + _graphStandardValQuarter);
		chart1.basicchart("drawLabel", {
			// テキストの設定
			xScaleText: _xScaleCurrent,
			yScaleText: _graphStandardVal * 2 - _graphStandardValHalf + 2,
			xScaleLabel: _xScaleCurrent,
			yScaleLabel: _graphStandardVal * 2 - _graphStandardValHalf,
			labelWidth: 36,
			labelHeight: 22,
			lineWidth: 4,
			labelColor: _currentColor,
			textSize: 13,
			textColor: "#fff",
			textVal: _currentLabel,
			// 線の設定
			xScaleLine: [_xScaleCurrent, _xScaleCurrent],
			yScaleLine: [(_graphStandardVal * 2 + _graphStandardValQuarter), (_graphStandardVal * 4 + _graphBarHeight + _graphStandardValQuarter)],
			lineWidth: 4,
			lineColor: _currentColor,
			strokeLinecap: "round",
			borderWidth: 2,
			borderColor: "#fff",
		});

		// 前回の値があれば、ラベルを表示する
		if (!util.isEmpty(previousDataSource)) {
			var _xScalePrevious = (_graphInnerWidth * _previousTotalRate / _intervalNumVal) + _leftPadding;
			// 最小値より小さい場合
			if (_previousVal < _minVal) _xScalePrevious = _exceptGraphWidth + _graphStandardValQuarter;
			// 最大値より大きい場合
			if (_previousVal > _maxVal) _xScalePrevious = _graphWidth - (_exceptGraphWidth + _graphStandardValQuarter);
			// 値がNULLの場合：右端に表示
			if (isNaN(_previousVal)) _xScalePrevious = _graphWidth - (_exceptGraphWidth + _graphStandardValQuarter);
			chart1.basicchart("drawLabel", {
				// テキストの設定
				xScaleText: _xScalePrevious,
				yScaleText: _graphStandardVal * 3 + 2,
				xScaleLabel: _xScalePrevious,
				yScaleLabel: _graphStandardVal * 3,
				labelWidth: 36,
				labelHeight: 22,
				lineWidth: 4,
				labelColor: _previousColor,
				textSize: 13,
				textColor: "#fff",
				textVal: _previousLabel,
				// 線の設定
				xScaleLine: [_xScalePrevious, _xScalePrevious],
				yScaleLine: [(_graphStandardVal * 4 - _graphStandardValQuarter), (_graphStandardVal * 4 + _graphBarHeight + _graphStandardValQuarter)],
				lineWidth: 4,
				lineColor: _previousColor,
				strokeLinecap: "round",
				borderWidth: 2,
				borderColor: "#fff"
			});
		}

		// 平均値のラベルを表示する
		var _xScaleMean = (_graphInnerWidth * _meanTotalRate / _intervalNumVal) + _leftPadding;
		chart1.basicchart("drawLabel", {
			// テキストの設定
			xScaleText: _xScaleMean,
			yScaleText: _graphHeight - _graphStandardVal,
			textSize: 12,
			textColor: _axisTextColor,
			textVal: _averageLabel,
			// 線の設定
			xScaleLine: [_graphStandardVal, _graphWidth - _graphStandardVal],
			yScaleLine: [_graphInnerHeight, _graphInnerHeight],
			lineWidth: 2,
			lineColor: _chartColor,
			// 円の設定
			xScaleCircle: _xScaleMean,
			yScaleCircle: _graphInnerHeight,
			circleRadius: 4,
			circleColor: _axisTextColor,
			// 追加テキスト設定
			xScaleText2: _xScaleMean,
			yScaleText2: _graphHeight - _graphStandardValQuarter,
			text2Size: 14,
			text2Color: _axisTextColor,
			text2Val: _meanVal,
		});

		// 結果のアイコン設定
		if (_isInRange(_currentVal, _meanMinVal, _meanMaxVal)) {
			var _id = "#" + _dataField + "ResultIcon";
			$(_id).addClass("goodicon");
		}
	});
}
//積み上げ棒グラフ（縦）設定
function setStackbarchartV(dataSource){
	$(".basicchart.stackbarchart_v").each(function(){
		var _id = $(this).attr("id");
		if(util.isEmpty(_id)) return;
		var _labelField = $(this).attr("labelField");
		if(util.isEmpty(_labelField)) return;
		var _dataField = $(this).attr("dataField");
		if(util.isEmpty(_dataField)) return;
		if(_dataField.indexOf(",")>=0) _dataField = _dataField.split(",");
		else _dataField = [_dataField];
		var _chartColor = $(this).attr("chartColor");
		if(_chartColor.indexOf(",")>=0) _chartColor = _chartColor.split(",");
		else _chartColor = [_chartColor];
		var labels = [];
		var data = [];
		for(var i=dataSource.length-1;i>=0;i--){
			var _ym = dataSource[i][_labelField];
			labels.push(_ym);
			var _record = {};
			for(var j=0, m = _dataField.length;j<m;j++){
				var key = _dataField[j];
				_record[key] = util.parseStrToFloat(dataSource[i][key]);
			}
			data.push(_record);
		}
		var options = {
				data : data,
				padding : [8,0,32,40],
				xScaleSize : 10,
				labelY : labels,
				yScaleSize :5,
				xScaleSize :2,
				yScale : [100,0],
				xScale : [0,data.length]
		};
		$(this).unbind("basicchart");
		var	chart1 = $(this).basicchart(options);
		chart1.basicchart("drawAxisY",{
			labels : "%v%",
			lineWidth :0,
			lineColor : "#FFF",
			textAlign : "middle",
			textOffset : [0,0],
			padding : [0,0]
		});
		chart1.basicchart("setOption", "axisStyleY" , {
			labels : labels,
			lineMargin : [0,0],
			lineWidth :0,
			lineColor : "#FFF",
			textAlign : "middle",
			textOffset : [0,0]
		});
		chart1.basicchart("setOption", "axisStyleX" , {
			lineWidth :0,
			orient: "bottom",
			transform: [0, chart1.basicchart("getAttributes").drawAxisHeight],
		});
		chart1.basicchart("stackedBarChart", {
			fieldY : _dataField,
			labels : "%v%",
			textSize : 8,
			textAlign : "middle",
			lineWidth :0,
			lineColor : "#FFF",
			symbolColor : function(d, i, j ){
				return _chartColor[j%_chartColor.length];
			},
			lineWidth :0,
			lineColor : "#FFF",
			textOffset : [0, 16],
			symbolSize : 0.6,
			padding : [0.3, 0],
			onMouseover : _chartMouseover,
			onMousemove : chartMousemove,
			onMouseout :chartMouseout
		});
		function _chartMouseover(d, i, j, selecter, style){
			if(!selecter) return;
			var tag = $(selecter).prop("tagName");
			var cl = $(selecter).attr("class");
			var alt = $(selecter).attr("alt")
			if(cl == "chartShape") {
				$(".onSelect "+tag+"[alt="+alt+"]", $(selecter).parent().parent()).attr("opacity", 0.5);
			}
			//日付＋ﾃﾞｰﾀ名＋値
			//var _text = labels[d.row]+":"+getDataName(d.col)+"<br>"+d["y"]+"%";
			var _text = getDataName(d.col)+"<br>"+d["y"]+"%";
			return d3.select("body").select("#tooltip").style("visibility", "visible").html(_text);
		}
		addHighlightLabel(_id, "最新", {"fill" : "#DF8672", "font-size" : "12px"});
	});
}
//強調ラベル追加
function addHighlightLabel(id, text, style){
	//右端ラベルの下に追加する
	var element = d3.selectAll("#"+id+" .chartLabel:nth-last-of-type(1)");
	var x = element.attr("x");
	var y = element.attr("y");
	y = (y|0)+20
	d3.select("#"+id).select("svg").append("text")
	.text(text)
	.attr("x", x).attr("y", y)
	.attr("text-anchor", "middle")
	.attr(style);
}
//棒グラフ設定
function setBarChart(){
	$(".basicchart.barchart").each(function(){
		var _id = $(this).attr("id");
		if(util.isEmpty(_id)) return;
		var _dataVal = $(this).attr("dataVal");
		if(util.isEmpty(_dataVal)) return;
		if(_dataVal.indexOf(",")>=0) _dataVal = _dataVal.split(",");

		var _legendLabel = $(this).attr("legendLabel");
		var _chartUnit = $(this).attr("chartUnit");
		if(util.isEmpty(_chartUnit)) _chartUnit="";
		var _chartColor = $(this).attr("chartColor");
		var data = [];
		for(var i=0,n=_dataVal.length;i<n;i++){
			data.push({x:i, y:_dataVal[i]|0});
		}

		var labels = ["1","2","3","4","5","6","7","8","9","10"];
		var yScale = [100,0];
		var yScaleSize = (yScale[1] - yScale[0]);

		var xScale = [d3.min(data, function(d){ return d.x; }),d3.max(data, function(d){ return d.x; })];
		var xScaleSize = (xScale[1] - xScale[0]);
		if(xScaleSize<0) xScaleSize*=-1;
		if(yScaleSize<0) yScaleSize*=-1;
		yScaleSize/=10;
		var options = {
				data : data,
				padding : [8,40,20,80],
				xScale : xScale,
				yScale : yScale,
				yScaleSize : yScaleSize+1,
				xScaleSize : xScaleSize+1
		};
		var chart1 = $(this).basicchart(options);

		chart1.basicchart("drawAxisX", {
			"padding":[0,0],
			"textOffset" : [0,6],
			"lineMargin" : [40, 40],
			"textAlign" : "middle",
			"labels"  : labels,
			"orient": "bottom",
			"transform": [0, chart1.basicchart("getAttributes").drawAxisHeight],
		});
		chart1.basicchart("drawAxisY", {
			"padding":[-40,0],
			"textOffset" : [-8,0],
			"lineMargin" : [16,16],
			labels : "%v%",
			textAlign : "start"
		});
		chart1.basicchart("barChart",{
			symbolColor : function(d, i, j ){
				return _chartColor;
			},
			textColor : "#FFF",
			textOffset : [0, 20],
			lineWidth : 0,
			labels : "%v%",
			symbolSize:0.8
		});
	});
}
//特徴菌一覧設定
function setTaxonlist(data){
	//tr-tdタグをテンプレートとして使う
	var _html = {};
	for(var i=0, n = data.length;i<n; i++){
		if(!_html[data[i]["data_type"]]) _html[data[i]["data_type"]] = "";
		var _record = taxonListDom;
		for(var key in data[i]){
			//テンプレートにデータをセット
			_record = _record.replaceAll("#"+key+"#", data[i][key]);
		}
		  //行追加
		_html[data[i]["data_type"]] += _record;
	}

	$(".result__table").hide();
	for(var key in _html){
		$("#"+key+"_taxon_list table tbody").html(_html[key]);
		$("#"+key+"_taxon_list").show();
	}
	//tooltip
	  $('.js_tooltip').unbind("click");
  	  $('.js_tooltip').on("click", function() {
		  $(".js_tooltip").not(this).removeClass("is--active");
		  $(this).toggleClass("is--active");
  	  });
}
// 前回データを取得する
function getPreviousData(dataSource, currentData) {
	var previousData = null;
	for (var i=0;i<dataSource.length;i++) {
		var _kitIdStr = dataSource[i]["kit_id_str"];
		if (currentData["kit_id_str"] !== _kitIdStr) continue;

		// 過去のデータがある場合
		if (!util.isEmpty(dataSource[i+1])) {
			previousData = dataSource[i+1];
		}
	}
	return previousData;
}
// ある値の配列中のIndexを取得する
function _getArrayIndex(val, ary) {
	var _index = null;
	for (var i=0; i<ary.length;i++) {
		// 最後の場合
		if(i === (ary.length - 1)) {
			if (val >= ary[i]) {
				return i;
			}
		}
		// それ以外
		else {
			if (ary[i] <= val && val < ary[i+1]) {
				return i;
			}
		}
	}
}
// 座標軸上の割合を計算する
function _calculateScaleRate(val, scales, totalRateVal) {
	// 配列の中のIndexを取得する
	var index = _getArrayIndex(val, scales);

	// 値が最小の座標値よりも小さい場合
	if (val < scales[0]) return 0;
	// 値が最大の座標値のよりも大きい場合
	if (val > scales[scales.length-1]) return totalRateVal;

	var beforeVal = scales[index];
	var afterVal = scales[index + 1];
	var diffVal = val - beforeVal;
	var diffRateVal =  diffVal / (afterVal - beforeVal);
	var beforeRateVal = index;
	return diffRateVal + beforeRateVal;
}
