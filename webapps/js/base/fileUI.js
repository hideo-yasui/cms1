/**
* fileUIプラグイン
* input file向けカスタムプラグイン
* uitlを利用
* ### js側利用方法
*
* 	var _upfile = $("input[name=upfile]").fileUI({options});
*	 _upfile.fileUI("click");
* @namespace
* @class fileUI
**/
;(function($, undefined){
	"use strict";
	// definition
	function fileUI(element, options) {
		this.formId = options.formId;
		this.element = element;
		this.files = null;
		this.publish(options);
	}
	/**
	* optionsより、fileUI用event設定
	* @method publish
	* @param {JSON} options
	* @return {void} return nothing
	*/
	fileUI.prototype.publish = function(options) {
		var _self = this;
		$(this.element).unbind("change");
		$(this.element).change(function(e){
			var files = $(this).get(0).files;
			_self.setFile(files);
			_self.change(e);
		});

		if(!util.isEmpty(options.dragdrop)){
			var dragdrop = $(options.dragdrop);
			if(!util.isEmpty(options.formId)) {
				dragdrop = $(options.dragdrop, $("#"+this.formId))
			}
			dragdrop.unbind('dragover');
			dragdrop.unbind('drop');
			dragdrop.on('dragover', function (e){
				$(this).addClass("dragover");
				e.stopPropagation();
				e.preventDefault();
			});
			dragdrop.on('drop', function (e){
				$(this).removeClass("dragover");
				e.preventDefault();
				var files = e.originalEvent.dataTransfer.files;
				_self.setFile(files);
				_self.change(e);
			});
		}
		if(util.isFunction(options.onChange)){
			this.onChange = options.onChange;
		}
		if(util.isFunction(options.onPreview)){
			this.onPreview = options.onPreview;
		}
	};

	/**
	* &lt;input type=file&gt;clickイベントによりファイル参照表示
	* @method click
	* @return {void} return nothing
	*/
	fileUI.prototype.click = function() {
		$(this.element).click();
	};
	fileUI.prototype.clear = function() {
		this.setFile(null);
		$(this.element).val("");
		this.change();
	};
	/**
	* 参照ファイル変更イベント
	* options.onChangeが設定されている場合、実行する
	* @method change
	* @return {void} return nothing
	*/
	fileUI.prototype.change = function(e) {
		var _file =$(this.element);
		var filetype = _file.attr("filetype");
		var formname = _file.attr("name");
		var accesskey = _file.attr("accesskey"); //upload or import
		//指定時
		if(util.isFunction(this.onChange)) this.onChange($(this.element), this.getFile());
	};
	/**
	* 参照ファイル設定
	* dragdropと&lt;input type=file&gt;とのIF
	* @method setFile
	* @return {void} return nothing
	*/
	fileUI.prototype.setFile = function(files) {
		this.files = files;
		if(files != null && files.name && !util.isEmpty(files.name) && util.isEmpty($(this.element).val())){
			$(this.element).val(files.name);
			if(files.length==1){
				var file=files[0];
				if(file.type.indexOf("image") >= 0){
					var self = this;
					var reader = new FileReader();
					reader.onload = (function(file){
						return function(e){
							if(util.isFunction(self.onPreview)) self.onPreview($(self.element),e.target.result, file.name);
						};
					})(file);
					reader.readAsDataURL(file);
				}
			}
		}
	};
	/**
	* 参照ファイル取得
	* <pre>dragdropと&lt;input type=file&gt;とのIF</pre>
	* @private
	* @method _getFile
	* @param [index] {Int}
	* 参照ファイルNo(デフォルト=0)
	* @return {object} file変数
	*/
	fileUI.prototype._getFile = function(index) {
		var _index = 0;
		if(this.files == null) return null;
		if(util.isInteger(index) && index >=0 && _index < this.files[_index].length) {
			_index = index;
		}
		return this.files[_index];
	};
	/**
	* 参照ファイル情報取得
	* @method getFile
	* @param [index] {Int}
	* 参照ファイルNo(デフォルト=0)
	* @return {JSON}  ファイルの情報
	*/
	fileUI.prototype.getFile = function(index) {
		var fval = this._getFile(index);
		var type="", name = "", size = "", int_size=0;
		if(fval != null && !util.isEmpty(fval.name)){
			type = util.getExtension(fval);
			name = util.getFileName(fval.name);
			size = util.getFileSize(fval, true);
			int_size = util.getFileSize(fval, false);
		}
		return {"type" : type, "name" : name, "size" : size, "sizeVal" : int_size, "file" : fval};
	};
	/**
	* fileUI default options
	*
	* @property DEFAULTS
	* @default {JSON}
	* @param [dragdrop] {String}
	* 指定domに対し、drag&dropイベントを設定する
	* @param [onChange] {Function}
	* ファイル変更時に指定されているcallback関数を実行する
	*/
	fileUI.DEFAULTS = {
		"formId" : "",
		"dragdrop" : ".dragdropupload",
		"onChange" : function(element, fileData){},
		"onPreview" : function(element, src, name){}
	};

	// fileUI plugin
	$.fn.fileUI = function(option) {
		var args = Array.prototype.slice.call(arguments, 1);
		var results;
		this.each(function(){
			var $this = $(this),
				data = $this.data('util_fileUI'),
				options;
			if (!data) {
				options = $.extend({}, fileUI.DEFAULTS, $this.data(), typeof option === 'object' && option);
				$this.data('util_fileUI', new fileUI($this, options));
			} else {
				if (typeof option === 'string' && option.charAt(0) !== '_' && typeof data[option] === 'function') {
					results = data[option].apply(data, args);
				}
				else if (typeof option === "object" || !option) {
					options = $.extend({}, data.options, typeof option === 'object' && option);
					data.publish.call(data, options);
				}
				else {
					$.error('Method ' + option + ' does not exist on fileUI.');
				}
			}
		});
		return (results != undefined ? results : this);
	};
})(jQuery);
