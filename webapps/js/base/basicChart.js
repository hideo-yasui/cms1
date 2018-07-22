;(function($, undefined){
	"use strict";
	var _options = null;
	var _padding = null;
	var _cache = {"drawData" : []};
	// definition
	function basicChart(element, options) {
		this.element = element;
		this._init(options);
	}
	//refresh
	basicChart.prototype.refresh = function(options) {
		this._init(options);
	};
	//dispose
	basicChart.prototype.dispose = function() {
		d3.select("#"+this.element.attr("id")).selectAll("svg").remove();
	};
	//update
	basicChart.prototype.update = function() {
		this.svg.selectAll(".chartShape, .chartLabel, axisLine").remove();
		var _drawData = [];
		for(var i=0,n=_cache["drawData"].length;i<n;i++){
			_drawData.push(_cache["drawData"][i]);
		}
		_cache["drawData"]=[];
		for(var i=0,n=_drawData.length;i<n;i++){
			var _graphData = _drawData[i];
			switch(_graphData["type"]){
				case "barChart":
					this.barChart(_graphData["style"], _graphData["isHorizon"]);
					break;
				case "stackedBarChart":
					this.stackedBarChart(_graphData["style"], _graphData["isHorizon"]);
					break;
				case "pieChart":
					this.pieChart(_graphData["style"]);
					break;
				case "lineChart":
					this.lineChart(_graphData["style"]);
					break;
				case "scatterChart":
					this.scatterChart(_graphData["style"]);
					break;
				case "bubbleChart":
					this.bubbleChart(_graphData["style"]);
					break;
			}
		}
	};
	//init setting
	basicChart.prototype._init = function(options) {
		_options = options;
		_padding = _options.padding;
		this.data = _options.data;
		_options.width = this.element.width();
		_options.height =this.element.height();
		_options.center = [_options.width/2, _options.height/2];
		var _width = _options.width;
		var _height = _options.height;
		var drawAxisWidth = _width - _padding[1]-_padding[3];
		var drawAxisHeight = _height - _padding[0]-_padding[2];
		this.drawAxisWidth = drawAxisWidth;
		this.drawAxisHeight = drawAxisHeight;
		if(_options.xScale[0] < _options.xScale[1]) _options.xRange=[_options.xScale[0], _options.xScale[1]];
		else _options.xRange=[_options.xScale[1], _options.xScale[0]];
		if(_options.yScale[0] < _options.yScale[1]) _options.yRange=[_options.yScale[0], _options.yScale[1]];
		else _options.yRange=[_options.yScale[1], _options.yScale[0]];
		this._scaleWidth = _options.xRange[1]-_options.xRange[0];
		this._scaleHeight = _options.yRange[1]-_options.yRange[0];
		this.dispose();
		this.svg = d3.select("#"+this.element.attr("id"))
			.append("svg")
			.attr("width", _options.width)
			.attr("height", _options.height);
		this.trigger = null;
	};
	// 属性を取得する（外部から）
	basicChart.prototype.getAttributes = function () {
		return this;
	};
	//X軸
	basicChart.prototype.drawAxisX = function(style) {
		var _style = setOption(_options.axisStyleX, style);
		_options.axisStyleX = _style;

		// 軸
		var scale = null;
		if(_style.type == "time"){
			scale = d3.time.scale();
		}
		else{
			scale = d3.scale.linear();
		}
		scale.domain(_options.xScale)
			.range([_padding[3], _options.width-_padding[1]]);

		_cache["xScale"] = scale;
		if(!_style.visible) return;

		// 目盛りを設定し表示する
		var axis = d3.svg.axis()
			.scale(scale)
			.orient(_style.orient)
			.ticks(_options.xScaleSize)
			.tickSize(_style.tickSize[0], _style.tickSize[1])
			.innerTickSize(-this.drawAxisHeight+_padding[0])
			.tickFormat(labelFormat(_style.labels));

		setTextStyle(
			this.svg.append("g")
				.attr("class", "axisX")
				.attr("transform", "translate(" + _style.transform[0] + ", " + _style.transform[1] + ")")
				.attr("fill", "none")
				.call(axis)
				.selectAll("text")
				.attr("x", _style.textOffset[0]- _style.padding[0])
				.attr("y", _style.textOffset[1])
		, _style);

		setLineStyle(
			this.svg.append("line")
			.attr("class", "axisLineX")
			.attr("x1", _padding[3]-_style.lineMargin[0]+ _style.padding[0])
			.attr("y1", this.drawAxisHeight+ _style.padding[1])
			.attr("x2", this.drawAxisWidth+_style.lineMargin[1]+ _style.padding[0])
			.attr("y2", this.drawAxisHeight+ _style.padding[1])
		, _style);

		this.svg.selectAll(".axisX .tick line")
			.attr("stroke-width",  _style.innerLineWidth)
			.attr("stroke", _style.innerLineColor)
			.attr("stroke-dasharray" , _style.innerLineStyle)
			.attr("opacity", _style.innerLineOpacity);
	};
	//Y軸
	basicChart.prototype.drawAxisY = function(style) {
		var _style = setOption(_options.axisStyleY, style);
		_options.axisStyleY = _style;

		if(!_style.visible) return;

		var scale = d3.scale.linear()
			.domain(_options.yScale)
			.range([_padding[0], this.drawAxisHeight]);
		_cache["yScale"] = scale;

		var axis = d3.svg.axis()
			.scale(scale)
			.orient("left")
			.ticks(_options.yScaleSize)
			.tickSize(_style.tickSize[0], _style.tickSize[1])
			.innerTickSize(-_options.width+_padding[3]-_options.axisStyleX.lineMargin[0]-_options.axisStyleX.lineMargin[1])
			.tickFormat(labelFormat(_style.labels));

		_style.x = _style.textOffset[0];
		_style.y = _style.textOffset[1]+_style.padding[1];

		setTextStyle(
			this.svg.append("g")
				.attr("class", "axisY")
				.attr("transform", "translate(" +  (_padding[3]+_style.padding[0]) + ", "+_style.padding[1]+")")
				.attr("fill", "none")
				.call(axis)
				.selectAll("text")
		, _style);

		setLineStyle(
			this.svg.append("line")
				.attr("class", "axisLineY")
				.attr("x1" , _padding[3]+ _style.padding[0])
				.attr("y1" , _padding[0]-_style.lineMargin[0]+_style.padding[1])
				.attr("x2", _padding[3]+ _style.padding[0])
				.attr("y2", this.drawAxisHeight+_style.lineMargin[1]+ _style.padding[1])
		, _style);

		//inner axis draw setting
		this.svg.selectAll(".axisY .tick line")
			.attr("stroke-width",  _style.innerLineWidth)
			.attr("stroke", _style.innerLineColor)
			.attr("stroke-dasharray" , _style.innerLineStyle)
			.attr("opacity", _style.innerLineOpacity);

	};
	// ラベルを表示する（枠・線・円もオプションで表示可能）
	basicChart.prototype.drawLabel = function(style) {
		var label = this.svg.append("g");

		// 線の設定がある場合
		if (style.xScaleLine && style.yScaleLine) {
			label.append("line")
				.attr({
					"x1": style.xScaleLine[0],
					"x2": style.xScaleLine[1],
					"y1": style.yScaleLine[0],
					"y2": style.yScaleLine[1],
					"stroke-width": style.lineWidth + style.borderWidth,
					"stroke": style.borderColor,
					"stroke-linecap": style.strokeLinecap
				});
			label.append("line")
				.attr({
					"x1": style.xScaleLine[0],
					"x2": style.xScaleLine[1],
					"y1": style.yScaleLine[0],
					"y2": style.yScaleLine[1],
					"stroke": style.lineColor,
					"stroke-width": style.lineWidth,
					"stroke-linecap": style.strokeLinecap
				});
		}

		// ラベルの設定がある場合
		if (style.labelWidth > 0 && style.labelHeight > 0) {
			label.append("rect")
			.attr({
				"x": style.xScaleLabel,
				"y": style.yScaleLabel,
				"width": style.labelWidth,
				"height": style.labelHeight,
				"rx": 5,
				"ry": 5,
				"fill": style.labelColor,
				"transform": "translate(-"+style.labelWidth/2+", -"+style.labelHeight/2+")"
			});
		}

		// 円の設定が場合
		if (style.xScaleCircle && style.yScaleCircle) {
			this.svg.append("circle")
				.attr({
					"cx": style.xScaleCircle,
					"cy": style.yScaleCircle,
					"r": style.circleRadius,
					"fill": style.circleColor
				});
		}

		// 追加のテキスト設定がある場合（改行などで使う）
		if (style.xScaleText2 && style.yScaleText) {
			label.append("text")
			.attr({
				"x": style.xScaleText2,
				"y": style.yScaleText2,
				"font-size": style.text2Size,
				"fill": style.text2Color,
				"text-anchor": "middle",
				"dominant-baseline": "middle",
			})
			.text(style.text2Val);
		}

		// テキストの表示
		label.append("text")
		.attr({
			"x": style.xScaleText,
			"y": style.yScaleText,
			"font-size": style.textSize,
			"fill": style.textColor,
			"text-anchor": "middle",
			"dominant-baseline": "middle",
		})
		.text(style.textVal);
	}
	// 四角を表示する
	basicChart.prototype.drawRectWithAnimation = function (style) {
		var g = this.svg.append("rect")
			.attr({
				"x": style.xScale,
				"y": style.yScale,
				"fill": style.color,
				"stroke": style.strokeColor,
				"stroke-width": style.strokeWidth,
				"stroke-linecap": style.strokeLinecap
			});

		// グラデーションがある場合
		if (style.gradientId) {
			g.attr({
				"fill": "url(#" + style.gradientId + ")",
				"stroke": "url(#" + style.gradientId + ")"
			});
			var svgDefs = this.svg.append("defs");
			var mainGradient = svgDefs.append("linearGradient")
				.attr("id", style.gradientId);

			mainGradient.append("stop")
				.attr("stop-color", style.startColor)
				.attr("offset", "0");

			mainGradient.append("stop")
				.attr("stop-color", style.endColor)
				.attr("offset", "1");
		}

		var _style = setOption(_options.chartStyle, style);

		setAnimation(g, _style, {
			"width": style.startWidth,
			"height": style.startHeight,
		}, {
			"width": style.endWidth,
			"height": style.endHeight,
		});
	}
	//bar chart draw
	basicChart.prototype.barChart = function(style, isHorizon) {
		_cache["drawData"] .push({
			"type": "barChart",
			"style" : style,
			"isHorizon" : isHorizon
		});

		var scaleWidth = (this.drawAxisWidth );
		var scaleHeight = (this.drawAxisHeight-_padding[0]);

		if(isHorizon){
			scaleWidth /=   this._scaleWidth;
			scaleHeight /=  (_options.yScaleSize-1 );
		}
		else {
			scaleWidth /=  (_options.xScaleSize-1 );
			scaleHeight /=  this._scaleHeight;
		}
		var _drawAxisHeight = this.drawAxisHeight;
		var _style = setOption(_options.chartStyle, style);

		var _symbolSize = _style.symbolSize;
		var _baseSize = scaleWidth;
		if(isHorizon) _baseSize = scaleHeight;
		if(_symbolSize>0 && _symbolSize<1) _symbolSize = _baseSize*_symbolSize;
		var _labelx, _labely;
		var _startStyle={};
		var _endStyle={};

		if(isHorizon){
			//expand direction is horizontal
			_startStyle.width = 0;
			_endStyle.x = _padding[3]+_style.padding[0];
			_endStyle.y = function(d, idx){
				var dx = d[_style.fieldX]-_options.xRange[0];
				if(dx<0) dx=-dx;
				return _drawAxisHeight-(dx*scaleHeight)-_symbolSize/2+_style.padding[1];
			};
			_endStyle.width = function(d){
				var w = scaleWidth * ifnull(d[_style.fieldY], 0);
				return w > 0 ? w : 0;
			};
			_endStyle.height = _symbolSize > 0 ? _symbolSize : 0;
			_labelx = function(d){
				return _padding[3]+scaleWidth * ifnull(d[_style.fieldY], 0)+_style.textOffset[0];
			};
			_labely = function(d, idx){
				var dx = d[_style.fieldX]-_options.xRange[0];
				if(dx<0) dx*=-1;
				return _drawAxisHeight-(dx*scaleHeight)+_style.textOffset[1];
			};
		}
		else {
			_startStyle.y =  _drawAxisHeight;
			_startStyle.height = 0;
			_endStyle.x = function(d,idx){
				return _padding[3]+((d[_style.fieldX]-_options.xRange[0]) * scaleWidth)-_symbolSize/2+_style.padding[0];
			};
			_endStyle.width = _symbolSize > 0 ? _symbolSize : 0;
			_endStyle.y = function(d){
				return _drawAxisHeight - (scaleHeight * (ifnull(d[_style.fieldY], 0)-_options.yRange[0]))+_style.padding[1];
			};
			_endStyle.height = function(d){
				var h = scaleHeight * (ifnull(d[_style.fieldY], 0)-_options.yRange[0]);
				return h > 0 ? h : 0;
			};
			_labelx = function(d){
				return _padding[3]+((d[_style.fieldX]-_options.xRange[0]) * scaleWidth)+_style.textOffset[0];
			};
			_labely = function(d, idx){
				var dy = ifnull(d[_style.fieldY], 0)-_options.yRange[0];
				if(dy<0) dy*=-1;
				return _drawAxisHeight - (scaleHeight * (dy))+_style.textOffset[1];
			};
		}
		//animation setting
		var rect = setAnimation(
			setPolygonStyle(
				this.svg.selectAll(".chartShape")
					.data(this.data)
					.enter()
					.append("rect")
					.attr("class", "chartShape")
					.attr("alt", function(d){
						return d[_style.fieldX];
					})
				,_style),
			_style, _startStyle, _endStyle);
		this.trigger = setEvent(rect,  _style);

		_style.x = _labelx;
		_style.y = _labely;
		setTextStyle(
			this.svg.selectAll(".chartLabel")
				.data(this.data)
				.enter()
				.append("text")
				.attr("class", "chartLabel")
				.text(function (d) {
					return labelFormat(_style.labels)(d[_style.fieldLabel]);
				})
			, _style);
	};

	// line chart draw
	basicChart.prototype.lineChart = function( style, baseLineStyle) {
		_cache["drawData"] .push({
			"type": "lineChart",
			"style" : style
		});

		var scaleWidth = (this.drawAxisWidth ) / (_options.xScaleSize-1); // 項目分の横幅
		var scaleHeight = (this.drawAxisHeight-_padding[0] ) / this._scaleHeight;
		var _drawAxisHeight = this.drawAxisHeight;

		var _style = setOption(_options.chartStyle, style);

		//基準線
		if(baseLineStyle){
			var _baseLineStyle = setOption(_options.baseLine, baseLineStyle);
			var _baseLineY= _drawAxisHeight - (scaleHeight * (ifnull(_baseLineStyle.value, 0)-_options.yRange[0]));
			if(_baseLineY>0 && _baseLineY<_drawAxisHeight){
				var baseLine = this.svg.selectAll(".baseLine")
				.data(this.data)
				.enter()
				.append("line")
				.attr("class", "baseLine")
				.attr("x1" , _padding[3]-_baseLineStyle.lineMargin[0])
				.attr("y1" , _baseLineY)
				.attr("x2" , this.drawAxisWidth+_baseLineStyle.lineMargin[1])
				.attr("y2" , _baseLineY);
				baseLine.attr(_baseLineStyle);
			}
		}

		var line = d3.svg.line()
			.interpolate(_style.symbolStyle)
			.x(function(d, idx){
				var x =	_cache["xScale"] (d[_style.fieldX]);
				return x;
/*
				var dx = d[_style.fieldX]-_options.xRange[0];
				return _padding[3]+((dx) * scaleWidth) ;
*/
			})
			.y(function(d){
				var y = _drawAxisHeight - (scaleHeight * (ifnull(d[_style.fieldY], 0)-_options.yRange[0]));
				return y;
			});

		//line chart prot
		var g = setLineStyle(
			this.svg
				.append("path")
				.attr("class", "chartShape")
				.attr("d", line(this.data))
			, _style);

		var pathLength = g.node().getTotalLength();
		var _startStyle = {
			"stroke-dasharray" : pathLength + " " + pathLength,
			"stroke-dashoffset" : pathLength
		};
		var _endStyle = {
			"stroke-dashoffset" : 0
		};
		//animation setting
		setAnimation(g, _style,_startStyle, _endStyle);

		//頂点ごとの軸をプロット
		this.svg.selectAll(".axisLine")
		.data(this.data)
		.enter()
		.append("line")
		.attr("class", "axisLine")
		.attr("x1" , function(d){
			var x =	_cache["xScale"] (d[_style.fieldX]);
			return x;
		})
		.attr("y1" , _padding[0])
		.attr("x2" , function(d){
			var x =	_cache["xScale"] (d[_style.fieldX]);
			return x;
		})
		.attr("y2", this.drawAxisHeight)
		.attr("stroke-width",  _options.axisStyleY.innerLineWidth)
		.attr("stroke", _options.axisStyleY.innerLineColor)
		.attr("opacity", _options.axisStyleY.innerLineOpacity);

		_style.x =  function(d){
			var dx = _cache["xScale"] (d[_style.fieldX])+_style.textOffset[0];
			return dx;
		};
		_style.y = function(d){
			var dy = _drawAxisHeight +_style.textOffset[1];
			return dy;
		};
		setTextStyle(
			this.svg.selectAll(".lineChart")
				.data(this.data)
				.enter()
				.append("text")
				.attr("class", "chartLabel")
				.text(function (d) {
					return labelFormat(_style.labels)(d[_style.fieldLabel]);
				})
			, _style);
	};
	//scatter chart draw
	basicChart.prototype.scatterChart = function(style) {
		_cache["drawData"] .push({
			"type": "scatterChart",
			"style" : style
		});

		var scaleWidth = (this.drawAxisWidth ) / (_options.xScaleSize-1); // 項目分の横幅
		var scaleHeight = (this.drawAxisHeight-_padding[0] ) / this._scaleHeight;
		var _drawAxisHeight = this.drawAxisHeight;

		var _style = setOption(_options.chartStyle, style);
		if(typeof  _style.symbolSize != "function" && _style.symbolSize>0 && _style.symbolSize<1) _style.symbolSize = (scaleWidth/4)*_style.symbolSize;
		_style.x =  function(d){
			return _cache["xScale"] (d[_style.fieldX]);
		};
		_style.y =  function(d){
			var y = _drawAxisHeight - (scaleHeight * (ifnull(d[_style.fieldY], 0)-_options.yRange[0]));
			return y;
		};
		var _startStyle = {
			"r" : 0
		};
		var _endStyle =  {};
		if(typeof  _style.symbolSize != "function") _endStyle["r"] =  _style.symbolSize+"px";
		else _endStyle["r"] =  _style.symbolSize;
		var g = setAnimation(
			setSymbolStyle(
				this.svg.selectAll(".scatterChart")
					.data(this.data)
					.enter()
					.append(_style.symbolStyle)
					.attr("class", "chartShape")
			, _style),
			_style, _startStyle, _endStyle);
		this.trigger = setEvent(this.svg.selectAll(_style.symbolStyle), _style);
		if(_style.textSize <1) return;
/*
		_style.x = function(d){
			return _padding[3]+((d[_style.fieldX]-_options.xRange[0]) * scaleWidth) +_style.textOffset[0];
		};
*/
		_style.x =  function(d){
			var dx = _cache["xScale"] (d[_style.fieldX])+_style.textOffset[0];
			return dx;
		};
		_style.y = function(d){
			var y = _drawAxisHeight - (scaleHeight * (ifnull(d[_style.fieldY], 0)-_options.yRange[0]))+_style.textOffset[1];
			return y;
		};
		setTextStyle(
			this.svg.selectAll(".scatterChart")
				.data(this.data)
				.enter()
				.append("text")
				.attr("class", "chartLabel")
				.text(function (d, i, j) {
					return labelFormat(_style.labels)(d[_style.fieldLabel]);
				})
			, _style);
	};
	//pie chart draw
	basicChart.prototype.pieChart = function(style) {
		_cache["drawData"] .push({
			"type": "pieChart",
			"style" : style
		});
		var pie = d3.layout.pie().sort(null).value(function(d){ return d.value; });
		var size = this.drawAxisHeight;
		if(size > this.drawAxisWidth) size = this.drawAxisWidth;
		//size is smaller value of width and height
		var radius = size/2;
		var _style = setOption(_options.chartStyle, style);
		if(_style.symbolSize>0 && _style.symbolSize<1) _style.symbolSize = (radius)*_style.symbolSize;
		if(_style.selectSymbolSize>0 && _style.selectSymbolSize<1) _style.selectSymbolSize = (radius)*_style.selectSymbolSize;
		var arc1 = d3.svg.arc().innerRadius(_style.symbolSize).outerRadius(radius);
		//arcBurrer is a size larger than arc when select arc
		var arcBuffer = d3.svg.arc().innerRadius(_style.symbolSize).outerRadius(radius+_style.selectSymbolSize);
		//arcLabel is label inside of arc
		var  arcLabel   = d3.svg.arc();

		arcLabel.outerRadius(radius);
		arcLabel.innerRadius(radius*0.6);
		//pieChart use data of one dimension
		var _data = [];
		for(var key in  this.data){
			_data.push({"value" : this.data[key], "key" : key})
		}
		var _cx = _options.center[0];
		var _cy = _options.center[1];
		setPolygonStyle(this.svg.selectAll(".onSelect")
			.data(pie(_data))
			.enter()
			.append("g")
			.attr("class", "chartShape onSelect")
			.append("path")
			.attr("d", arcBuffer)
			.attr("alt", function(d,i){ return i;})
			,_style)
			.attr("opacity", 0)
			.attr("transform", "translate(" + (_cx) + "," + (_cy) + ")");
			//.attr("transform", "translate(" + (radius+_padding[3]) + "," + (radius+_padding[0]) + ")");

		//polygon setting
		setPolygonStyle(this.svg.selectAll(".arc")
			.data(pie(_data))
			.enter()
			.append("g")
			.attr("class", "chartShape arc")
			.append("path")
			.attr("alt", function(d,i){ return i;})
			,_style);

		this.svg.selectAll(".arc")
		.attr("transform", "translate(" + (_cx) + "," + (_cy) + ")");
		//.attr("transform", "translate(" + (radius+_padding[3]) + "," + (radius+_padding[0]) + ")");

		var g1 = this.svg.selectAll(".arc").selectAll("path");

		//animation setting
		g1.transition()
		.delay(_style.animationWait)
		.duration(_style.animationTime)
		.ease(_style.animationType)
		.attrTween("d", function(d){
			var interpolate = d3.interpolate(
				{startAngle: 0, endAngle: 0},
				{startAngle: d.startAngle, endAngle: d.endAngle}
			);
			return function(t){
				return arc1(interpolate(t));
			};
		})
		.each("end", function(transition, callback){
			//g.attr("d", arc);
		});
		this.trigger = setEvent(this.svg.selectAll("path"), _style);
		if(_style.textSize <1) return;

		// label setting
		setTextStyle(
			this.svg.selectAll(".arc")
				.append("text")
				.attr("class", "chartLabel")
				.attr("transform", function(d){ return "translate(" + arc1.centroid(d) + ")"; })
				.text(function(d,i,j){
					if(d.data.value < 5) return "";
					return labelFormat(_style.labels)(d.data.value);
				})
			,_style)
			.attr("transform", function(d){ return "translate(" + arcLabel.centroid(d) + ")"; });

	};
	//stack bar chart draw
	basicChart.prototype.stackedBarChart = function(style,isHorizon) {
		_cache["drawData"] .push({
			"type": "stackedBarChart",
			"style" : style,
			"isHorizon" : isHorizon
		});
		var _style = setOption(_options.chartStyle, style);
		var scaleWidth = (this.drawAxisWidth) / this._scaleWidth;
		var scaleHeight = (this.drawAxisHeight-_padding[0] ) / this._scaleHeight;

		var keyArray = _style.fieldY;
		var n=this.data.length;
		var m=keyArray.length
		var _dataset = new Array(m);
		for(var t=0;t<m;t++){
			var key = keyArray[t];
			_dataset[t] = new Array(n);
			for(var i=0;i<n;i++){
				_dataset[t][i] = { "x" : i, "y" : this.data[i][key], "row" : i , "col" : key};
			}
		}

		var stack = d3.layout.stack();
		var dataSet = stack(_dataset);
		var _drawAxisHeight = this.drawAxisHeight;

		var _baseSize = scaleWidth;
		if(isHorizon) _baseSize = scaleHeight;
		var _symbolSize = _style.symbolSize;
		if(_symbolSize>0 && _symbolSize<1) _symbolSize = _baseSize*_symbolSize;
		var _axisLabelx, _axisLabely;
		var _innerLabelx, _innerLabely;
		var _startStyle={};
		var _endStyle={};
		//padding is setting of _baseSize ratio
		if(_style.padding[0]>0 && _style.padding[0]<1) _style.padding[0] = _baseSize*_style.padding[0];
		if(_style.padding[1]>0 && _style.padding[1]<1) _style.padding[1] = _baseSize*_style.padding[1];

		var _paddingX = _padding[3]+_style.padding[0] ;
		var _paddingY = _padding[0]+_style.padding[1] ;
		if(isHorizon){
			//expand direction is horizontal
			_endStyle.x = function(d, idx){
				var x= scaleWidth*(d["y0"])+_paddingX;
				return x;
			};
			_endStyle.y = function(d){
					var y = (d[_style.fieldX] * scaleHeight)+_paddingY;
					return y;
			};
			_endStyle.height = _symbolSize > 0 ? _symbolSize : 0;
			_endStyle.width = function(d){
				var w = scaleWidth*d["y"];
				return w > 0 ? w : 0;
			};
			//animation setting
			_startStyle.x = _padding[3];
			_startStyle.width = 0;

			_axisLabelx = _padding[3]+_style.textOffset[0];
			_axisLabely = function(d, i){
				var y = (i * scaleHeight)+_symbolSize/2+_style.textOffset[1]+_paddingY;
				return y;
			};
			_innerLabelx = function(d, idx){
				var x= scaleWidth*(d["y0"]+d["y"]/2)+_paddingX;
				return x;
			};
			_innerLabely = function(d, i){
				var y = (i * scaleHeight)+_symbolSize/2+8+_style.textOffset[1]+_paddingY;
				return y;
			};
		}
		else {
			//expand direction virtical
			_endStyle.x = function(d,idx){
				var x = (d[_style.fieldX] * scaleWidth)+_paddingX;
				return x;
			};
			_endStyle.y = function(d){
				var y= _drawAxisHeight-scaleHeight*(d["y0"]+d["y"]);
				return y;
			};
			_endStyle.width = _symbolSize > 0 ? _symbolSize : 0;
			_endStyle.height = function(d){
				var h = scaleHeight*d["y"];
				return h > 0 ? h : 0;
			};
			_startStyle.y = _drawAxisHeight;
			_startStyle.height = 0;
			_axisLabelx = function(d,i,j){
				var x = (i * scaleWidth)+_symbolSize/2+_style.textOffset[0]+_paddingX;
				return x;
			};
			_axisLabely = _drawAxisHeight+_style.textOffset[1];
			_innerLabelx = function(d,i,j){
				var x = (i * scaleWidth)+_symbolSize/2+_style.textOffset[0]+_paddingX;
				return x;
			};
			_innerLabely = function(d){
				var y= _drawAxisHeight-scaleHeight*(d["y0"]+(d["y"])/2)+8;
				return y;
			};
		}
		//animation setting
		var g = this.svg.selectAll(".box")
			.data(dataSet)
			.enter()
			.append("g")
			.attr("class", "chartShape");

		var rect = setAnimation(
			setPolygonStyle(
				g.selectAll("rect")
				.data(function(d){ return d; })
				.enter()
				.append("rect")
				.attr("class", "box")
			, _style)
		, _style, _startStyle, _endStyle);
		this.trigger = setEvent(rect,  _style);

		//label setting
		_options.axisStyleY.x = _axisLabelx;
		_options.axisStyleY.y = _axisLabely;
		setTextStyle(
			g.selectAll("text")
			.data(function(d){ return d; })
			.enter()
			.append("text")
			.attr("class", "chartLabel")
			.text(function (d, i) {
				return _options.axisStyleY.labels[i];
			})
		, _options.axisStyleY);
		if(isHorizon){
			setLineStyle(
				this.svg.append("line")
				.attr("class", "axisLine")
				.attr("x1" , _padding[3])
				.attr("y1" , _padding[0]-_options.axisStyleY.lineMargin[0])
				.attr("x2", _padding[3])
				.attr("y2", this.drawAxisHeight+_options.axisStyleY.lineMargin[1])
			, _options.axisStyleY);
		}
		else {
			setLineStyle(
				this.svg.append("line")
				.attr("class", "axisLine")
				.attr("x1" , _padding[3]-18)
				.attr("y1" , this.drawAxisHeight)
				.attr("x2", this.drawAxisWidth)
				.attr("y2", this.drawAxisHeight)
			, _options.axisStyleX);
		}
		if(_style.textSize <1) return;
		_style.x = _innerLabelx;
		_style.y = _innerLabely;
		_style.textAlign = "middle";
		//label setting
		setTextStyle(
			g.selectAll("text")
			.data(function(d){ return d; })
			.enter()
			.append("text")
			.attr("class", "chartLabel")
			.text(function (d, i) {
				return d["y"];
			})
		, _style);
	}
	//bubble chart draw
	// TODO bubble_chart.jsから移行。要リファクタリング。
	basicChart.prototype.bubbleChart = function(style) {
		_cache["drawData"].push({
			"type": "bubbleChart",
		});

		var g = style.selector;
		var data = this.data;
		var radiusScale = d3.scale.pow().exponent(0.25)
			.domain([0, 1])
			.range([2, 50]); // @todo Calculate the range based on the canvas size (w and h).
		var colors = d3.scale.category10();
		var dispatch = d3.dispatch("range");

		g.selectAll("#bubble_chart_group").remove();

		/**
		 * bubble_chart_groupはbubble chartの表示グループ(SVG g element)。
		 * bubble chart group = bubble chart area + 各菌の存在比率を表す円(バブル)
		 *
		 * 表示するランク、または、表示する菌の割合を設定するスライダとバブル・チャート表示の
		 * 相対位置はtranslateのパラメータで調整する。
		 */
		var bubble_chart_group = g
			.call(d3.behavior.zoom()
				.on("zoom", function (){
					bubble_chart_group.attr("transform", "translate(" + d3.event.translate + ")" + " scale(" + d3.event.scale + ")");
				}))
			.append("g")
			.attr("id", "bubble_chart_group");

		/**
		 * bubble_chart_areaはbubble chartを表示する領域。
		 *
		 * バブル・チャート表示領域の大きさはwidth/height属性の値で調整する。
		 */
		bubble_chart_group.append("rect")
			.attr("id", "bubble_chart_area")
			.attr("width", "100%")
			.attr("height", "100%")
			.attr("fill", "none");

			var bbox = g.node().getBBox();
			var width = bbox.width;
			var height = bbox.height;
			if(width == 0) {
				width = parseInt(bubble_chart_group.select("#bubble_chart_area").style("width"));
				height = parseInt(bubble_chart_group.select("#bubble_chart_area").style("height"));
				if(width == 0){
					width = 500;
					height = 300;
				}
			}

		var bubble = d3.layout.force()
			.nodes(data)
			.charge(function(t) {
				return - Math.pow(radiusScale(t.ratio), 2.0)/8;
			})
			.gravity(0.1)
			.size([width, height])
			.start();

		var nodes = bubble_chart_group.selectAll(".node")
			.data(data)
			.enter()
			.append("g")
			.attr("class", "node");

		nodes.append("circle")
			.attr("r", function(t) {
				return radiusScale(t.ratio);
			})
			.style("fill", function(t, i) {
				return colors(i);
			})
			.classed("unselected_node", true)
			.on("mouseover", style.onMouseover)
			.on("mousemove", style.onMousemove)
			.on("mouseout", style.onMouseout);

		bubble.on("tick", function(){
			nodes.attr("transform",
				function(t) {
					return "translate(" + t.x + "," + t.y + ")";
				});
		});
	}
	//chart option setting
	basicChart.prototype.setOption = function(key, setting) {
		_options[key] = setOption(_options[key], setting);
	}
	//chart event call
	basicChart.prototype.invoke = function(method) {
		if(this.trigger == null) return false;
		if(typeof this.trigger[method] != "function") return false;
		this.trigger[method]();
		return true;
	}
	//chart legend draw
	basicChart.prototype.drawLegend = function(legend, style) {
		if(!_options.legend.visible) return;
		var _legend = setOption(_options.legend, legend);
		var _style = setOption(_options.legendStyle, style);

		var x = 0;
		var y = 0;
		switch(_legend.left){
			case "right" :
			case "center":
				x = (_options.width|0) - (_legend.width|0);
				if(_legend.left == "center")	x /= 2;
				break;
		}
		switch(_legend.top){
			case "bottom" :
			case "center":
				y = (_options.height|0) - (_legend.height|0);
				if(_legend.top == "center")	y /= 2;
				break;
		}
		x += _legend.offset[0];
		y += _legend.offset[1];

		if(_legend.bgColor && _legend.bgColor != "" ){
			this.svg.append("rect")
			.attr("class", "legend_panel")
			.attr("width", _legend.width)
			.attr("height", _legend.height)
			.attr("x", x)
			.attr("y", y)
			.attr("rx", 20)
			.attr("ry", 20)
			.attr("stroke", _legend.lineColor)
			.attr("fill", _legend.bgColor)
			.attr("stroke-width", _legend.lineWidth);
		}

		var dy = _style.symbolSize+_style.symbolOffset[1];
		var h = dy*_legend.data.length+_style.padding[0]+_style.padding[2];
		for(var i=0, n=_legend.data.length;i<n;i++){
			var _idx = i;
			var _dx = x, _dy = y;
			this.svg.append("rect")
				.attr("class", "legend_symbol")
				.attr("width", _style.symbolSize)
				.attr("height", _style.symbolSize)
				.attr("x", x+_style.symbolOffset[0])
				.attr("y", y+_style.symbolOffset[1]+i*dy)
				.attr("fill", _legend.data[_idx]["color"]);
			this.svg.append("text")
				.attr("class", "legend_text")
				.attr("x", x+_style.symbolOffset[0]+_style.symbolOffset[1]+_style.textOffset[0])
				.attr("y", y+_style.symbolOffset[1]+_style.textOffset[1]+_style.textSize+i*dy)
				.text(""+_legend.data[_idx]["label"]);
		}
		this.svg.selectAll(".legend_symbol")
			.attr("stroke", _style.lineColor)
			.attr("stroke-width", _style.lineWidth);

		this.svg.selectAll(".legend_text")
			.attr("stroke", "none")
			.attr("text-anchor", "start")
			.attr("font-size", _style.textSize+"px")
			.attr("fill", _style.textColor);
	};
	//label format setting with d3.format rule
	function labelFormat(format, field) {
		var ret = function(d){
			var _d = d;
			if(field)  _d = d[field];
			return _d;
		};
		var _format = format;
		if(typeof format=="function"){
			//format is function
			return format;
		}
		if(typeof format=="object"){
			//format is json
			ret = function(d){
				var _d = d;
				if(field)  _d = d[field];
				if(_format[_d]) return _format[_d];
				return "";
			};
		}
		else if(typeof format=="string"){
			if(format.indexOf("%Y")>=0 ||
					format.indexOf("%m")>=0 ||
					format.indexOf("%M")>=0 ||
					format.indexOf("%d")>=0 ||
					format.indexOf("%H")>=0 ){
					return d3.time.format(format);
			}
			else if(format.indexOf("%v")>=0){
				ret = function(d){
					var _d = d;
					if(field)  _d = d[field];
					return _format.replace("%v", _d);
				};
			}
			else {
				ret = d3.format(_format);
			}
		}
		return ret;
	};
	//if src is null than return dst
	function ifnull(src, dst) {
		if(src) return src;
		return dst;
	}
	//text style setting
	function setTextStyle(g, style) {
		g.attr("transform", "rotate("+style.textAngle+")")
		.attr("text-anchor", style.textAlign)
		.attr("font-size", style.textSize)
		.attr("fill" , style.textColor)
		.attr("stroke", style.textColor)
		.attr("stroke-width", "0px")
		.attr("pointer-events", "none");
		if((style.x && style.x > 0) || typeof style.x == "function") 	g.attr("x", style.x);
		if((style.y && style.y > 0) || typeof style.y == "function") 	g.attr("y", style.y);

		if(style.animationTime && style.animationTime>0){
			g.attr("opacity", "0")
			.transition()
			.duration(style.animationTime)
			.attr("opacity", "1.0");
		}
		else {
			g.attr("opacity", "1.0");
		}
		return g;
	};
	//line style setting
	function setLineStyle(g, style) {
		g.attr("stroke", style.lineColor)
		.attr("shape-rendering", "auto")
		.attr("fill", "none")
		.attr("stroke-width", style.lineWidth+"px");
		return g;
	};
	//point symbol style setting
	function setSymbolStyle(g, style) {
		g.attr("stroke", style.lineColor)
		.attr("stroke-width", style.lineWidth+"px")
		.attr("shape-rendering", "auto")
		.attr("fill", style.symbolColor)
		.attr("cx", style.x)
		.attr("cy", style.y);
		return g;
	};
	//polygon style setting
	function setPolygonStyle(g, style) {
		g.attr("stroke", style.lineColor)
		.attr("shape-rendering", "auto")
		.attr("fill", style.symbolColor)
		.attr("opacity", style.opacity)
		.attr("stroke-width", style.lineWidth+"px");
		return g;
	};
	//animation setting
	function setAnimation(g, style, before, after) {
		g.transition()
		.delay(style.animationWait)
		.duration(style.animationTime)
		.ease(style.animationType)
		.each("start", function(){
			if(before && before!=null) d3.select(this).attr(before);
		})
		.attr(after)
		.each("end", function(){
		});
		return g;
	}
	//event setting
	function setEvent (g, style){
		var dispatch = d3.dispatch("click", "mousemove", "mouseout", "mouseover");

		if(typeof style.onClick == "function"){
			dispatch.on("click",  function(d,i,j,g) {
				var selecter = d3.select(this);
				style.onClick(d, i, j, this, style);
			});
			g.on("click", dispatch.click);
		}
		if(typeof style.onMouseover == "function"){
			dispatch.on("mouseover",  function(d,i,j) {
				var selecter = d3.select(this);
 				style.onMouseover(d, i, j, this, style);
			});
			g.on("mouseover", dispatch.mouseover);
		}
		if(typeof style.onMousemove == "function"){
			dispatch.on("mousemove",  function(d,i,j) {
				var selecter = d3.select(this);
 				style.onMousemove(d, i, j, this, style);
			});
			g.on("mousemove", dispatch.mousemove);
		}
		if(typeof style.onMouseout == "function"){
			dispatch.on("mouseout",  function(d,i,j) {
				var selecter = d3.select(this);
 				style.onMouseout(d, i, j, this, style);
			});
			g.on("mouseout", dispatch.mouseout);
		}

		return dispatch;
	}

	//option paramater join
	function setOption(src, dst){
		var _src = {};
		for(var key in src){
			_src[key] = src[key];
		}
		return $.extend(_src, dst);
	}
	// default options
	basicChart.DEFAULTS = {
		chartStyle : {
			fieldX : "x",
			fieldY : "y",
			fieldLabel : "y",
			labels : "%v",
			symbolStyle : "circle",
			symbolColor : "blue",
			symbolSize : 0.9,
			selectSymbolSize : 16,
			textColor :"#000",
			opacity : 1.0,
			textSize:12,
			lineColor : "blue",
			lineWidth : 1,
			textOffset : [0, -5],
			textAngle : 0,
			textAlign : "middle",
			padding : [0,0],
			animationType : "cubic-out",
			animationTime : 500,
			animationWait : 300,
			onClick : function(d, selecter){
				var tag = $(selecter).prop("tagName");
				console.log("basic chart click("+tag+")");
			},
			onMouseover : function(d, selecter){
				var tag = $(selecter).prop("tagName");
				console.log("basic chart mouseover("+tag+")");
			},
			onMousemove : function(d, selecter){
				var tag = $(selecter).prop("tagName");
				console.log("basic chart mousemove("+tag+")");
			},
			onMouseout : function(d, selecter){
				var tag = $(selecter).prop("tagName");
				console.log("basic chart mouseout("+tag+")");
			}
		},
		axisStyleX : {
			textColor :"#000",
			textSize:12,
			lineColor : "#000",
			lineWidth : 1,
			innerLineWidth : 1,
			innerLineColor : "#000",
			innerLineOpacity : 0.2,
			innerLineStyle : "",
			textOffset : [0, 16],
			textAngle : 0,
			textAlign : "middle",
			tickSize : [5, 10],
			lineMargin : [24,24],
			padding : [0,0],
			symbolColor : "blue",
			visible : true
		},
		axisStyleY : {
			textColor :"#000",
			textSize:12,
			lineColor : "#000",
			lineWidth : 1,
			innerLineWidth : 1,
			innerLineColor : "#000",
			innerLineOpacity : 0.2,
			innerLineStyle : "",
			textOffset : [-20, 0],
			textAngle : 0,
			textAlign : "end",
			tickSize : [5, 10],
			lineMargin : [24,24],
			padding : [0,0],
			visible : true
		},
		legendStyle :{
			padding : [8,8,8,8],
			symbolSize : 16,
			symbolOffset : [16,16,16,16],
			textOffset : [14,-3],
			textColor :"#000",
			textSize:16,
			lineColor : "#000",
			lineWidth : 1
		},
		legend : {
			top : "top",
			left: "right",
			offset : [10, 10],
			width : 180,
			height : 52,
			visible : true,
			data : [ {"label" : "C1", "color" : "#FCC"},{"label" : "C2", "color" : "#CCF"}],
			bgColor : "#F0F0F0",
			lineColor : "#000",
			lineWidth : 1
		},
		baseLine : {
			"stroke" : "#CCC",
			"stroke-width" : "3px",
			"stroke-dasharray" : "5, 2",
			"lineMargin" : [0,0],
			"value" : 6
		},
		padding : [20, 120, 40, 40]
	};
	// basicChart plugin
	$.fn.basicchart = function(option) {
		var args = Array.prototype.slice.call(arguments, 1);
		var results;
		this.each(function(){
			var $this = $(this), data = $this.data('util_basicchart'),	options;
			if (!data) {
				options = $.extend({}, basicChart.DEFAULTS, $this.data(), typeof option === 'object' && option);
				$this.data('util_basicchart', new basicChart($this, options));
			}
			else {
				if (typeof option === 'string' && option.charAt(0) !== '_' && typeof data[option] === 'function') {
					results = data[option].apply(data, args);
				}
				else if (typeof option === "object" || !option) {
					//options = $.extend({}, data.options, typeof option === 'object' && option);
					options = $.extend({}, basicChart.DEFAULTS, $this.data(), typeof option === 'object' && option);
					data._init.call(data, options);
				}
				else {
					console.log("basicchart5");
					$.error('Method ' + option + ' does not exist on listbasicChart.');
				}
			}
		});
		return (results != undefined ? results : this);
	};

})(jQuery);
