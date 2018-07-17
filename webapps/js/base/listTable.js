;(function($, undefined){
	"use strict";
	var _sortText = "";
	var _table = [
		'<thead>',
		'</thead>',
		'<tbody>',
		'</tbody>',
	].join('');
	var _edit = '<button type="button" class="btn btn-outline-success btn-sm" accesskey="rowedit"><i class="fa fa-edit"></i></button>';
	var _copy = '<button type="button" class="btn btn-outline-primary btn-sm" accesskey="rowcopy"><i class="fa fa-clone"></i></button>';
	var _delete = '<button type="button" class="btn btn-outline-danger btn-sm" accesskey="rowdelete"><i class="fa fa-minus-circle"></i></button>';
	var _table = null;
	// definition
	function listTable(element, options) {
		this.element = element;
		this._table = $(_table);
		//$(this._table).prop("class", options.styleName);
		//$("table", this.element).prop("class", options.tableStyleName);
		this.publish(options);
	}
	//usage:if listTable show & update contents
	listTable.prototype.publish = function(options) {
		this.options = options;
		this.display = options.display;
		this.data = options.data;
		this.tempdata = null;
		this.filterVal = options.filterVal;
		this.sortField = options.sortField;
		this.maxPageSize = options.maxPageSize;
		this.currentSort = {};
		this.currentSortOrder = [];
		this.sortFieldLength = 0;
	};
	//usage:if listTable show & update contents
	listTable.prototype.refresh = function(data) {
		this.tempdata = null;

		$(this.element).show();
		if(!data) this.tempdata = _dataCopy(this.data);
		else this.tempdata = _dataCopy(data);

		if(!util.isEmpty(this.filterVal) && util.isFunction(this.options.onFilter)) this.tempdata = this.options.onFilter(this.tempdata, this.filterVal);
		if(util.isFunction(this.options.onSort)) this.tempdata = this.options.onSort(this.tempdata, this.getSortField());
		if(!this.tempdata || this.tempdata==null) return;
		_table = this.element.find('table');
		var _html = '';//'<thead>'+this.__getHeaderHtml()+'</thead>';
		_html += '<tbody>'+this.__getHeaderHtml()+this.__getBodyHtml()+'</tbody>';
		_table.html(_html);
		this.__eventSetting();
	};
	//inner method : get thead.innerHTML of this object
	listTable.prototype.__getHeaderHtml = function() {
		var header = '';
		//var classArray = [];
		header+='<tr>';
		for(var key in this.options.header){
			var type = this.options.header[key]["type"];
			if(!this.options.header[key]["text"] || this.options.header[key]["text"] == "") continue;
			if(type=="hidden") continue;
			if(type=="checkbox") {
				header += '<th class="'+this.options.header[key]["class"]+'"><input type="checkbox" class="_allcheck"/></th>';
			}
			else {
				header += '<th ';
				//if(!util.isEmpty(this.options.header[key]["class"])) header += 'class="'+this.options.header[key]["class"]+'"';
				header += '>';
				if(!util.isEmpty(this.options.header[key]["sort"])){
					header += '<a href="javascript:void(0);" class="_rowsort" field="'+this.options.header[key]["sort"]+'">';
					if(!this.currentSort[this.options.header[key]["sort"]]) this.currentSort[this.options.header[key]["sort"]] = "";
					this.sortFieldLength++;
				}
				header +=this.options.header[key]["text"];
				if(!util.isEmpty(this.options.header[key]["sort"])){
					switch(this.currentSort[this.options.header[key]["sort"]]){
						case "desc" :
							header += '　<i class="fa fa-sort-down float-right"></a>';
							break;
						case "asc" :
							header += '　<i class="fa fa-sort-up float-right"></a>';
							break;
						default:
							header += '　<i class="fa fa-sort float-right"></a>';
					}
				}
				header += '</th>';
			}
			//classArray.push(key);
		}
		header+='</tr>';
		return header;
	};

	//inner method : get tbody.innerHTML of this object
	listTable.prototype.__getBodyHtml = function() {
		var body = '';
		var n= this.tempdata.length;
		var m = (n+"").length;

		if(m > this.options.zeroPaddingSize) this.options.zeroPaddingSize = m;
		if(!util.isEmpty(this.maxPageSize) && n>this.maxPageSize) n = this.maxPageSize;
		for(var i=0;i<n;i++){
			var _tr_class = "";
			var _row = "";
			for(var key in this.options.header){
				if(!this.options.header[key]["field"]) this.options.header[key]["field"]=null;
				var fields = this.options.header[key]["field"];
				//if(isEmpty(fields)) continue;
				if(fields == null) fields = [null];
				if(typeof fields!= "object") fields = [ fields ];
				var vals = "";
				//fields is multiple then data(string) is concatenated to display
				var title = this.options.header[key]["title"];
				var settitle = false;
				var attribute = "";
				var cl = this.options.header[key]["class"];
				var _class = "";
				if(cl && cl!=null){
					_class = cl;
					if(this.tempdata[i][cl]) _class = this.tempdata[i][cl];
				}
				if(title && title!=null && this.tempdata[i][title]){
					attribute += ' title="'+this.tempdata[i][title]+'"';
					settitle = true;
				}
				var _visible = this.options.header[key]["visible"];
				var _isVisible = true;
				//if field have multiple outputs then visible propety setting
				//usage visible setting is visible : {field : exist of this.tempdata[i][field] , value : equal this.tempdata[i][field]
				if(_visible && _visible != null && _visible["field"] != null &&  _visible["value"] != null ){
					_isVisible = false;
					if(!this.tempdata[i][_visible["field"]]){
						if(typeof this.tempdata[i][_visible["field"]] == "number"){
							this.tempdata[i][_visible["field"]] = 0;
						}
						else {
							this.tempdata[i][_visible["field"]] = "";
						}
					}
					if(!_visible["fomula"]) _visible["fomula"] = "equal";
					switch(_visible["fomula"]){
						case "equal":
							if(this.tempdata[i][_visible["field"]] == _visible["value"]) _isVisible = true;
							break;
						case "not":
							if(this.tempdata[i][_visible["field"]] != _visible["value"]) _isVisible = true;
							break;
						case "greater":
							if(util.diffVal(this.tempdata[i][_visible["field"]], _visible["value"]) > 0) _isVisible = true;
							break;
						case "less":
							if(util.diffVal(this.tempdata[i][_visible["field"]], _visible["value"]) < 0) _isVisible = true;
							break;
					}
				}
				if(!_isVisible) continue;
				for(var j=0,m=fields.length;j<m;j++){
					var field = fields[j];

					var val=null;
					if(field==null) val = i+1;
					else if(!(field in this.tempdata[i])) val = "-";
					else if( this.tempdata[i][field]===0) val = "0";
					else if(util.isEmpty(this.tempdata[i][field])) val = "-";
					else val = this.tempdata[i][field];

					if(!this.options.header[key]["type"]) this.options.header[key]["type"]=null;
					var type = this.options.header[key]["type"];

					if(type=="checkbox"){
						var err = "";
						if(this.options.header[key]["error"]) err = this.options.header[key]["error"];
						if(err && err!="" && this.tempdata[i][err] && this.tempdata[i][err]!="") {
							type="link";
							val = "エラー";
						}
					}
					switch(type){
						case "link" :
							var _link = "";
							_link = '<a href="javascript:void(0);"';
							for(var attr in this.options.header[key]){
								if(attr=="type") continue;
								if(attr=="title" && settitle) continue;
								if(!this.options.header[key][attr] || this.options.header[key][attr]=="") continue;
								var attrVal = this.options.header[key][attr];
								if(attr=="field") attr = "name";
								_link += " "+attr+'="'+attrVal+'"';
							}
							_link +='>'+val+'</a>';
							val = _link;
							break;
						case "hidden" :
						case "checkbox" :
							var _link = "";
							_link = '<input type="'+type+'"';
							//type is checkbox or radio then attribute must have name
							for(var attr in this.options.header[key]){
								if(attr=="type") continue;
								if(attr=="title" && settitle) continue;
								if(!this.options.header[key][attr] || this.options.header[key][attr]=="") continue;
								var attrVal = this.options.header[key][attr];
								_link += " "+attr+'="'+attrVal+'"';
							}
							_link +=' value="'+val+'"/>';
							val = _link;
							break;
						case "number" :
							val = this.zeroPadding(val);
							break;
						case "percent" :
							val = (((val*100000)|0)/1000+"%");
							break;
						case "rowstyle":
							_tr_class = _class;
							_class="";
							break;
						case "filesize" :
							val = util.setFileUnit(val);
							break;
						case "edit_copy_delete" :
							val = _edit+_copy+_delete;
							break;
						case "edit_delete" :
							val = _edit+_delete;
							break;
						case "edit" :
							val = _edit;
							break;
						case "delete" :
							val = _delete;
							break;
					}
					vals += val;
				}
				//if(_class!="") attribute += ' class="'+_class+'"';
				if(type!="hidden") _row += '<td'+attribute+'>'+vals+'</td>';
				else  _row += vals;
			}
			body += '<tr ';
			if(_tr_class!="") body += 'class="'+_tr_class+'"';
			body += '><input type="hidden" value="'+i+'" id="__index__" />';
			body += _row;
			body += '</tr>';
		}
		return body;
	};
	//inner method : setting event of inner form
	listTable.prototype.__eventSetting = function() {
		_table.find('button').unbind("click");
		//_table.find('button.btn[accesskey*=row]').unbind("click");
		_table.find('input[type=checkbox]').unbind("change");
		_table.find('._rowsort').unbind("click");

		var _self = this;
		if(_table.find('a:not(.btn)').length){
			_table.find('a:not(.btn)').on("click", function(event){
				event.preventDefault();
				var idx = $("#__index__", $(this).parent().parent()).val();
				if (idx >= 0) {
					_self.options.onLinkClick.call(undefined, $(this), _self.tempdata[idx]);
				}
			});
		}
		if(_table.find('button.btn').length){
			_table.find('button.btn').on("click", function(event){
				event.preventDefault();
				var idx = $("#__index__", $(this).parent().parent()).val();
				if (idx >= 0) {
					_self.options.onButtonClick.call(undefined, $(this), _self.tempdata[idx]);
				}
			});
		}
		if(_table.find('input[type=checkbox]').length){
			_table.find('input[type=checkbox]').on("change", function(event){
				event.preventDefault();
				_checkboxChange();
			});
			_table.find('._allcheck').unbind("change");
			_table.find('._allcheck').on("change", function(event){
				event.preventDefault();
				var checked = $(this).prop('checked');
				_self.selectAll(!checked);
			});

		}
		if(this.sortFieldLength>0){
			_table.find('a._rowsort').on("click", function(event){
				event.preventDefault();
				var field = $(this).attr("field");
				var sort = _self.currentSort[field];
				console.log("field["+field+"]sort["+sort+"]");
				switch(sort){
					case "desc" :
						sort = "asc";
						break;
					default:
						sort = "desc";
				}
				_self.currentSort[field] = sort;
				_self.currentSortOrder.unshift(field);
				if(_self.currentSortOrder.length > this.sortFieldLength) _self.currentSortOrder.splice(_self.currentSortOrder.length-1, 1);
				_self.refresh();
			});
		}
		//$("#listTable table").html(header+body);
	};

	function _checkboxChange(){
		$("tr", _table).removeClass("selected");
		$("tr:has(input:checked)", _table).addClass("selected");
	}

	//usage:if listTable hiden & dispose
	listTable.prototype.remove = function() {
		this.element.find('table').empty();
	};
	//usage:if listTable visible change
	listTable.prototype.visible = function(f) {
		if(this.display != f){
			if(!f) {
				$(this.element).hide();
				this.remove();
			}
			else {
				$(this.element).show();
				this.refresh();
			}
		}
		this.display = f;
	};
	listTable.prototype.filter = function(r) {
		this.filterVal = r;
		if(this.display) this.refresh();
	};
	//fieldFormat:listTable fieldType=taxon
	listTable.prototype.taxonName = function(val) {
		if(val.indexOf(";")<0) return val;
		var vals = val.split(";");
		var result = "";
		for(var i=0,n=vals.length;i<n;i++){
			var c = vals[i].substring(0,1);
			if(c=="k") continue; //kingdom名称を省略
			if(!vals[i] || vals[i]=="") continue;
			result += '<span class="'+c+'">'+vals[i]+';</span>';
		}
		return result;
	};
	//fieldFormat:listTable fieldType=number
	listTable.prototype.zeroPadding = function(val, maxlen) {
		if(!maxlen) maxlen = this.options.zeroPaddingSize;
		var len, diff, i;
		val = '' + val;
		len = val.length;
		if (len >= maxlen) {
			return val;
		}
		diff = maxlen - len;
		for (i = 0; i < diff; i++) {
			val = '0' + val;
		}
		return val;
	}
	//if you want to get data of this
	listTable.prototype.getData = function(index, field) {
		if(index && index>=0 && index<this.tempdata.length){
			if(field && typeof field =="string") this.tempdata[index][field];
			else if(field && typeof field =="object") {
				//this case is field's type is Array
				var result = {};
				for(var f=0,m= field.length;f<m;f++){
					result[field[f]] = this.tempdata[index][field[f]];
				}
				return result;
			}
		}
		else {
			if(field && typeof field =="string") {
				var result = [];
				for(var i=0,n=this.tempdata.length;i<n;i++){
					result[result.length] = this.tempdata[i][field];
				}
				return result;
			}
			else if(field && typeof field =="object") {
				//this case is field's type is Array
				var result = [];
				for(var i=0,n=this.tempdata.length;i<n;i++){
					var row = {};
					for(var f=0,m= field.length;f<m;f++){
						row[field[f]] = this.tempdata[i][field[f]];
					}
					result[result.length] = row;
				}
				return result;
			}
		}
		return this.tempdata;
	};
	//if you want to search data of this
	listTable.prototype.existData = function(index, field, value) {
		if(!value) return -1;
		if(field && typeof field =="string" && typeof value =="string") {
			var target = this.getData(index, field);
			for(var i=0;i<target.length;i++){
				if(target[i]==value){
					return i;
				}
			}
		}
		else {
			//field is associative array
			var _field = [];
			for(var key in value){
				_field.push(key);
			}
			var target = this.getData(index, _field);
			for(var i=0;i<target.length;i++){
				var _exist = true;
				for(var key in value){
					if(!target[i][key]) return -1;
					if(value[key]!=target[i][key]) _exist = false;
				}
				if(_exist) return i;
			}
		}
		return -1;
	};
	//if you want to all select or all no select
	listTable.prototype.selectAll = function(unchecked) {
		var checked = true;
		if(unchecked) checked = false;
		_table.find('._allcheck').prop('checked', checked);
		_table.find('input:checkbox').prop('checked', checked);
		_checkboxChange();

	};
	//if you want to select of this searched data
	listTable.prototype.selectData = function(index, field, value, unchecked) {
		var rowNo = this.existData(index, field, value);
		if(rowNo>=0) {
			var check = true;
			if(unchecked) check = false;
			$("td>input[type='checkbox']:eq("+rowNo+")", _table).each(function(i){
				$(this).prop("checked", check);
			});
			_checkboxChange();
			return true;
		}
		return false;
	};
	//if you want to get data of this.selected or checked
	listTable.prototype.getSelectData = function(field) {
		var selectRow = [];
		var result = [];
		$("input[type='checkbox']:checked", _table).each(function(i){
			var rowNo = $("#__index__", $(this).parent().parent()).val();
			if(util.isInteger(rowNo)){
				selectRow.push(rowNo);
			}
		});
		if(!field){
			var result = [];
			for(var i=0,n=selectRow.length;i<n;i++){
				var idx = selectRow[i];
				result[result.length] = this.tempdata[idx];
			}
		}
		else if(field && typeof field =="string") {
			var result = [];
			for(var i=0,n=selectRow.length;i<n;i++){
				var idx = selectRow[i];
				result[result.length] = this.tempdata[idx][field];
			}
		}
		else if(field && typeof field =="object") {
			//this case is field's type is Array
			var result = [];
			for(var i=0,n=selectRow.length;i<n;i++){
				var idx = selectRow[i];
				var row = {};
				for(var f=0,m= field.length;f<m;f++){
					row[field[f]] = this.tempdata[idx][field[f]];
				}
				result[result.length] = row;
			}
		}
		return result;
	};
	listTable.prototype.getSortField = function() {
		var ret = {};
		for(var i=0;i<this.currentSortOrder.length;i++){
			if(ret[this.currentSortOrder[i]]) continue;
			ret[this.currentSortOrder[i]] = this.currentSort[this.currentSortOrder[i]];
		}
		return ret;
	};
	// default options
	listTable.DEFAULTS = {
		"header" : {"no" : {"text" : "No", "class" : "f1", "field" : null},
					"name" : {"text" : "名称", "class" : "f2", "field" : "name"},
					"val" : {"text" : "値", "class" : "f3", "field" : "val"}
		},
		"data" : [
			{"name" : "AAA", "val" : 101},
			{"name" : "BBB", "val" : 102},
			{"name" : "CCC", "val" : 103},
		],
		"styleName" : "table",
		"tableStyleName" : "list-chart",
		"filterVal" : "",
		"zeroPaddingSize" : 3,
		"sortField" : "val",
		"display" : false,
		"maxPageSize" : null,
		"onFilter" : function(data, filter){
			return data;
		},
		"onLinkClick" : function(button, data){
			return data;
		},
		"onButtonClick" : function(button, data){
			return data;
		},
		"onSort" : function(data, sortField){
			try {
				data.sort(function(lhs, rhs){
					return rhs[sortField] - lhs[sortField];
				});
			}
			catch(e) {}
			return data;
		}
	};
	function _dataCopy(data){
		var _tempdata = new Array(data.length);
		for(var i=0,n=data.length;i<n;i++){
			_tempdata[i] = {};
			for(var key in data[i]){
				_tempdata[i][key] = data[i][key];
			}
		}
		return _tempdata;
	}

	// listTable plugin
	$.fn.listtable = function(option) {
		var args = Array.prototype.slice.call(arguments, 1);
		var results;
		this.each(function(){
			var $this = $(this),
				data = $this.data('util_listtable'),
				options;
			if (!data) {
				options = $.extend({}, listTable.DEFAULTS, $this.data(), typeof option === 'object' && option);
				$this.data('util_listtable', new listTable($this, options));
			} else {
				if (typeof option === 'string' && option.charAt(0) !== '_' && typeof data[option] === 'function') {
					results = data[option].apply(data, args);
				}
				else if (typeof option === "object" || !option) {
					options = $.extend({}, data.options, typeof option === 'object' && option);
					data.publish.call(data, options);
				}
				else {
					$.error('Method ' + option + ' does not exist on listchart.');
				}
			}
		});
		return (results != undefined ? results : this);
	};
})(jQuery);
