;(function($, undefined){
	"use strict";
	var _sortText = "";
	//data-formt : id, value, title, imgurl, limit-ratio, limit-date
	var _row_template = [
		'<li class="active">',
			'<input type="hidden value="#__index__# name="__index__" id="__index__" />',
			'<input type="hidden value="#ID# name="ID" />',
			'<div class="panel-badge">',
				'<span class="right badge badge-danger float-right mx-1 text-lg">#value#</span>',
			'</div>',
			'<h5><i class="fa fa-tag mr-1"></i>#title#</h5>',
			'<div class="mailbox-attachment-icon has-img">',
				'<img src="#imgurl#" alt="Attachment">',
				'<h4 class="icon-label w-100 h-100 hide">',
					'<div class="text-success text-xl"><i class="fa fa-check-circle"></i></div>',
				'</h4>',
			'</div>',
			'<div class="mailbox-attachment-info">',
				'<div class="progress my-1">',
					'<div class="progress-bar bg-primary" role="progressbar" aria-valuenow="#limit_ratio#" aria-valuemin="0" aria-valuemax="100" style="width: #limit_ratio#%">',
						'<span class="sr-only"></span>',
					'</div>',
				'</div>',
				'<span class="mailbox-attachment-size">',
				'	<i class="fa fa-calendar-times mr-1"></i>#limit_date#',
					'#button#',
				'</span>',
			'</div>',
		'</li>',
	].join('');
	var _edit = '<button href="javascript:void(0);" class="btn btn-default btn-sm float-right"><i class="fa fa-edit"></i></button>';
	var _copy = '<button href="javascript:void(0);" class="btn btn-default btn-sm float-right"><i class="fa fa-utensils"></i></button>';
	var _delete = '<button href="javascript:void(0);" class="btn btn-default btn-sm float-right"><i class="fa fa-trash-alt"></i></button>';
	var _table = null;
	// definition
	function cardTable(element, options) {
		this.element = element;
		this.publish(options);
	}
	//usage:if cardTable show & update contents
	cardTable.prototype.publish = function(options) {
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
	//usage:if cardTable show & update contents
	cardTable.prototype.refresh = function(data) {
		this.tempdata = null;

		$(this.element).show();
		if(!data) this.tempdata = _dataCopy(this.data);
		else this.tempdata = _dataCopy(data);

		if(!util.isEmpty(this.filterVal) && util.isFunction(this.options.onFilter)) this.tempdata = this.options.onFilter(this.tempdata, this.filterVal);
		if(util.isFunction(this.options.onSort)) this.tempdata = this.options.onSort(this.tempdata, this.getSortField());
		if(!this.tempdata || this.tempdata==null) return;
		_table = this.element.find('ul');
		var _html = '';
		_html += ''+this.__getHeaderHtml()+this.__getBodyHtml()+'';
		_table.html(_html);
		this.__eventSetting();
	};
	//inner method : get thead.innerHTML of this object
	cardTable.prototype.__getHeaderHtml = function() {
		return "";
	};

	//inner method : get tbody.innerHTML of this object
	cardTable.prototype.__getBodyHtml = function() {
		var body = '';
		var n= this.tempdata.length;
		var m = (n+"").length;

		if(m > this.options.zeroPaddingSize) this.options.zeroPaddingSize = m;
		if(!util.isEmpty(this.maxPageSize) && n>this.maxPageSize) n = this.maxPageSize;
		for(var i=0;i<n;i++){
			var _tr_class = "";
			var _row = _row_template;
			_row = _row.replace('#__index__#', i);
			for(var key in this.options.header){
				if(!this.options.header[key]["field"]) this.options.header[key]["field"]=null;
				var fields = this.options.header[key]["field"];
				var text = this.options.header[key]["text"];
				if(fields == null) fields = [null];
				if(typeof fields!= "object") fields = [ fields ];
				var vals = "";
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

					switch(type){
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
				_row = _row.replaceAll('#'+text+'#', vals);
			}
			body += _row;
		}
		return body;
	};
	//inner method : setting event of inner form
	cardTable.prototype.__eventSetting = function() {
		_table.find('button').unbind("click");
		//_table.find('button.btn[accesskey*=row]').unbind("click");
		_table.find('input[type=checkbox]').unbind("change");
		_table.find('._rowsort').unbind("click");

		var _self = this;
		if(_table.find('a:not(.btn)').length){
			_table.find('a:not(.btn)').on("click", function(event){
				event.preventDefault();
				var idx = $("#__index__", $(this).parent().parent().parent()).val();
				if (idx >= 0) {
					_self.options.onLinkClick.call(undefined, $(this), _self.tempdata[idx]);
				}
			});
		}
		if(_table.find('button.btn').length){
			_table.find('button.btn').on("click", function(event){
				event.preventDefault();
				var idx = $("#__index__", $(this).parent().parent().parent()).val();
				if (idx >= 0) {
					_self.options.onButtonClick.call(undefined, $(this), _self.tempdata[idx]);
				}
			});
		}
		if(_table.find('div.mailbox-attachment-icon').length){
			_table.find('div.mailbox-attachment-icon').on("click", function(event){
				event.preventDefault();
				var idx = $("#__index__", $(this).parent().parent().parent()).val();
				if (idx >= 0) {
					_self.options.onButtonClick.call(undefined, $(this), _self.tempdata[idx]);
				}
			});
		}
	};

	function _checkboxChange(){
	}

	//usage:if cardTable hiden & dispose
	cardTable.prototype.remove = function() {
		this.element.find('ul').empty();
	};
	//usage:if cardTable visible change
	cardTable.prototype.visible = function(f) {
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
	cardTable.prototype.filter = function(r) {
		this.filterVal = r;
		if(this.display) this.refresh();
	};
	//if you want to get data of this
	cardTable.prototype.getData = function(index, field) {
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
	cardTable.prototype.existData = function(index, field, value) {
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
	cardTable.prototype.selectAll = function(unchecked) {
		var checked = true;
		if(unchecked) checked = false;
		_table.find('._allcheck').prop('checked', checked);
		_table.find('input:checkbox').prop('checked', checked);
		_checkboxChange();

	};
	//if you want to select of this searched data
	cardTable.prototype.selectData = function(index, field, value, unchecked) {
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
	cardTable.prototype.getSelectData = function(field) {
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
	cardTable.prototype.getSortField = function() {
		var ret = {};
		for(var i=0;i<this.currentSortOrder.length;i++){
			if(ret[this.currentSortOrder[i]]) continue;
			ret[this.currentSortOrder[i]] = this.currentSort[this.currentSortOrder[i]];
		}
		return ret;
	};
	// default options
	cardTable.DEFAULTS = {
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

	// cardTable plugin
	$.fn.cardTable = function(option) {
		var args = Array.prototype.slice.call(arguments, 1);
		var results;
		this.each(function(){
			var $this = $(this),
				data = $this.data('util_cardTable'),
				options;
			if (!data) {
				options = $.extend({}, cardTable.DEFAULTS, $this.data(), typeof option === 'object' && option);
				$this.data('util_cardTable', new cardTable($this, options));
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
