/**
* javascript 汎用処理プラグイン
* @namespace
* @class util
**/
;(function(root, undefined) {
	"use strict";

	var _utils = {
		isJson : function(val){
			var res = this.toJson(val);
			if(res!==null){
				return true;
			}
			return false;
		},
		toJson : function(val){
			if(!this.isEmpty(val) && val.indexOf(":")<0) return null;
			try {
				var _r = JSON.parse(val);
				return _r;
			}
			catch(e){
			}
			return null;
		},
		/**
		* 整数変換 :
		* @method toInt
		* @param val {Object}
		* @return val {Int}
		*/
		toInt: function(val) {
			return (val | 0);
		},

		/**
		* 文字列変換
		* @method toString
		* @param val {Object}
		* @return val {String}
		*/
		toString: function(val) {
			if (this.isEmpty(val) || val == null) {
				return '';
			}
			if (val.toString) {
				return val.toString();
			}
			return '' + val;
		},

		/**
		* 関数チェック
		* @method isFunction
		* @param func {Object}
		* @return {Boolean}
		*/
		isFunction: function(func) {
			return (typeof func === 'function');
		},

		/**
		* 先頭にゼロ付加
		* @method leftPadZero
		* @param val {Int}
		* @param maxlen {Int} 桁数
		* @return {String}
		*/
		leftPadZero: function(val, maxlen) {
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
		},
		/**
		* 画像ファイル判定
		* @method isImageFile
		* @param url {String}
		* @return {Boolean}
		*/
		isImageFile: function(url) {
			if(url){
				var urllen = url.length;
				if(urllen<3) return false;
				var chk = ["bmp","png","gif","jpeg","jpg"];
				for(var i=0,n=chk.length;i<n;i++){
					var l = chk[i].length;
					var ext = url.substring(urllen-l,urllen);
					if(ext==chk[i]){
						return true;
					}
				}
			}
			return false;
		},


		/**
		* 空チェック
		* @method isEmpty
		* @param val {Object}
		* @return {Boolean}
		*/
		isEmpty: function(val) {
			if(!val) return true;
			if(val==null) return true;
			if(typeof val == "number" && val==0) return false;
			if(typeof val=="object") return false;
			if(val==undefined) return true;
			if(val=="") return true;
			return false;
		},
		/**
		* 数値チェック
		* @method isInteger
		* @param val {Object}
		* @return {Boolean}
		*/
		isInteger: function(val) {
			if(this.isEmpty(val)) return false;
			var _val = val+"";
			if(_val.substring(0,1)=="-") _val = _val.substring(1, _val.length);
			if(_val.match(/^[0-9]+$/)){
				return true;
			}
			return false;
		},
		/**
		* 小数値チェック
		* @method isFloat
		* @param val {Object}
		* @return {Boolean}
		*/
		isFloat: function(val) {
			if(this.isEmpty(val)) return false;
			var _val = val+"";
			if(_val.substring(0,1)=="-") _val = _val.substring(1, _val.length);
			if(_val.match(/^[0-9]+\.[0-9]+$/)){
				return true;
			}
			return false;
		},

		/*
		* 数字チェック
		*/
		isNumeric: $.isNumeric,

		/**
		* 半角アルファベットチェック
		* @method isAlpha
		* @param val {Object}
		* @param [onlyUpper] {Boolean} 　大文字のみかどうかチェック
		* @param [onlyLower] {Boolean} 　小文字のみかどうかチェック
		* @return {Boolean}
		*/
		isAlpha: function(val,onlyUpper,onlyLower) {
			if(onlyUpper) return !this.isEmpty(val) && /^[A-Z]+$/.test(val);
			if(onlyLower) return !this.isEmpty(val) && /^[a-z]+$/.test(val);
			return !this.isEmpty(val) && /^[a-zA-Z]+$/.test(val);
		},
		/**
		* 半角英数チェック
		* @method isAlphanumeric
		* @param val {Object}
		* @return {Boolean}
		*/
		isAlphanumeric: function(val) {
			return !this.isEmpty(val) && /^[a-zA-Z0-9]+$/.test(val);
		},

		/**
		* 半角文字チェック
		* @method isHankakuChar
		* @param chara {String}
		* @return {Boolean}
		*/
		isHankakuChar: function (chara) {
			var c = chara;
			if(!$.isNumeric(c)) c = c.charCodeAt(0);
			return (c >= 0x0 && c < 0x81) || (c == 0xf8f0) || (c >= 0xff61 && c < 0xffa0) || (c >= 0xf8f1 && c < 0xf8f4);
		},

		/**
		* 半角文字列チェック
		* @method isHankaku
		* @param val {String}
		* @return {Boolean}
		*/
		isHankaku: function(val) {
			var i, len, c;
			if (this.isEmpty(val)) {
				return true;
			}
			len = val.length;
			for (i = 0; i < len; i++) {
				c = val.charCodeAt(i);
				if (!this.isHankakuChar(c)) {
					return false;
				}
			}
			return true;
		},

		/**
		* 全角文字列チェック
		* @method isHankaku
		* @param val {String}
		* @return {Boolean}
		*/
		isZenkaku: function(val) {
			var i, len, c;
			if (this.isEmpty(val)) {
				return true;
			}
			len = val.length;
			for (i = 0; i < len; i++) {
				c = val.charCodeAt(i);
				if (this.isHankakuChar(c)) {
					return false;
				}
			}
			return true;
		},
		/**
		* カナチェック
		* @method isKana
		* @param val {String}
		* @return {Boolean}
		*/
		isKana: function(val) {
			if(this.isZenkaku(val)){
				if (val.replace_all("　", "").match(/^[\u30A0-\u30FF]+$/)) return true;
			}
			else {
				if (val.match(/^[\uFF65-\uFF9F]+$/))    return true;
			}
			return false;
		},
		/**
		* かなチェック（ひらがな）
		* @method isHiragana
		* @param val {String}
		* @return {Boolean}
		*/
		isHiragana: function(val) {
			if(this.isZenkaku(val)){
				var str = "あいうえおわいうえおん";
				if (str.match(/^[\u3040-\u309F]+$/)) return true;
			}
			return false;
		},
		/**
		* 時間チェック hh:mm
		* hh : 00～23
		* mm : 00～59
		* @method isTime
		* @param val {String}
		* @return {Boolean}
		*/
		isTime: function(val) {
			return !this.isEmpty(val) && /^([0-1][0-9]|2[0-3]):([0-5][0-9])$/.test(val);
		},
		/**
		* メール形式チェック
		* @method isMail
		* @param val {String}
		* @return {Boolean}
		*/
		isMail: function(val) {
			if(this.isEmpty(val)) return false;
			if(val.indexOf('@')<0) return false;
			if(val.indexOf('..')>=0) return false;
			if(val.indexOf('.@')>=0) return false;
			//別名が指定されている可能性
			if(val.indexOf('<')>=0) return false;
			if(val.indexOf('>')>=0) return false;
			if(this.isZenkaku(val)) return false;
			var part = val.split("@");
			if(part.length!=2) return false;
			//domainに.がない
			if(part[1].indexOf('.')<0) return false;
			return true;
		},
		/**
		* 日付形式チェック
		* @method isDate
		* @param val {String}
		* @param [delimita] {String}
		* @return {Boolean}
		*/
		isDate: function(val, delimita) {
			if(this.isEmpty(val)) return false;
			var _delimita =delimita;
			if(this.isEmpty(_delimita)) _delimita = "/";
			if(val.indexOf(_delimita)<0 && val.length!=8) return false;
			if(val.length==8) val = val.substring(0,4)+_delimita+val.substring(4,6)+_delimita+val.substring(6,8);
			var date = new Date(val);
			var _convDate = (date.getFullYear() +_delimita+ this.leftPadZero(date.getMonth() + 1, 2) +_delimita+ this.leftPadZero(date.getDate(), 2));
			if(val == _convDate) return true;
			return false;
		},

		/**
		* 文字列フォーマット
		*     format("The format example : {0}, {1}, {0}", "ABC", "123");
		* @method format
		* @param str {String}
		* @param {JSON} args
		*/
		format: function(str, args) {
			var rep_fn, _args, self = this;
			if ($.isArray(args)) {
				rep_fn = function(m, k) { return args[self.toInt(k)]; };
			} else if (typeof args === 'object') {
				rep_fn = function(m, k) { return args[k]; };
			} else {
				_args = arguments;
				rep_fn = function(m, k) { return _args[ self.toInt(k) + 1 ]; };
			}

			return str.replace(/\{(\w+)\}/g, rep_fn);
		},

		/**
		* 現在のクライアント日付文字列を返却
		* 引数の指定がある場合は、現在日時との差を返却する
		* フォーマット： yyyy/MM/dd
		* @method nowDate
		* @param diffYear{Int}
		* @param diffMonth {Int}
		* @param diffDay {Int}
		* @return {String}dateString
		*/
		nowDate: function(diffYear, diffMonth, diffDay) {
			var date = new Date();
			var dy =0, dm = 0, dd = 0;
			if(this.isInteger(diffYear)) dy = diffYear|0;
			if(this.isInteger(diffMonth)) dm = diffMonth|0;
			if(this.isInteger(diffDay)) dd = diffDay|0;
			var year = date.getFullYear()+dy;
			var month = date.getMonth()+dm;
			var day = date.getDate()+dd;

			if(month<0) month=0;
			else if(month>11) month=11;

			if(day<0) day=1;
			else if(day>31) day=31;

			month++;
			var strdate = "";
			for(var i=0;i<10;i++){
				strdate = ( year+ '/' + this.leftPadZero(month, 2) + '/' + this.leftPadZero(day, 2));
				if(this.isDate(strdate)) break;
				day--;
			}
			return strdate;
		},

		/**
		* 現在のクライアント日付文字列を生成する
		* フォーマットは： yyyyMMdd
		* @method nowShortDate
		* @return dateString (String)
		*/
		nowShortDate: function() {
			var date = new Date();
			return (date.getFullYear() + this.leftPadZero(date.getMonth() + 1, 2) + this.leftPadZero(date.getDate(), 2));
		},

		/**
		* 現在の時刻文字列を生成する
		* フォーマットは： hhmmss
		* @method nowShortTime
		* @return dateString (String)
		*/
		nowShortTime: function() {
			var date = new Date();
			return this.leftPadZero(date.getHours()) + this.leftPadZero(date.getMinutes()) + this.leftPadZero(date.getSeconds());
		},

		/**
		* 現在のクライアント日時文字列を生成する
		* フォーマットは： yyyy/MM/dd hh:mi:ss
		* @method nowDateTime
		* @return dateString (String)
		*/
		nowDateTime: function() {
			var date = new Date();
			/*
			var ret =    this.nowDate()+" "+this.leftPadZero(date.getHours()) + ":" + this.leftPadZero(date.getMinutes()) + ":" + this.leftPadZero(date.getSeconds());
			*/
			var ret =    this.nowDate() + " " + this.leftPadZero(date.getHours(), 2) + ":" + this.leftPadZero(date.getMinutes(), 2) + ":" + this.leftPadZero(date.getSeconds(), 2);
			return ret;
		},

		/**
		* 空白、/、:をトリムする
		*    yyyy/mm/dd hh:mm:ss → yyyymmddhhmmss
		*    yyyy/mm/dd → yyyymmdd
		*    hh:mm:ss → hhmmss
		* @method trimDateTime
		* @param val {String}
		* @return {String}
		*/
		trimDateTime: function(val) {
			if (!val) {
				return "";
			}
			return val.replace(/(\s|\/|:)/g, '');
		},

		/**
		* 年齢を生年月日と現在時刻から計算する
		* @method calculateAge
		* @param    year    {Int}
		* @param    month {Int}
		* @param    day     {Int}
		* @return {Int} age
		*/
		calculateAge: function(year, month, day) {
			// 桁揃え
			month = ('0' + month).slice(-2);
			day     = ('0' + day).slice(-2);

			var birthInt = parseInt("" + year + month + day); // String to Int
			var todayStr = this.nowShortDate();
			var todayInt = parseInt(todayStr, 10);
			return parseInt((todayInt - birthInt) / 10000);
		},

		/**
		* 文字列かどうかチェック
		* @method isString
		* @param val {Object}
		* @return {Boolean} true(success) or false(failed)

		*/
		isString: function(val) {
			return $.type(val) === "string";
		},
		/**
		* ブラウザ情報を取得
		* opera | ie | chrome | firefox | safari
		* @method getAgent
		* @return {String} AgentName
		*/
		getAgent : function(){
			var userAgent = window.navigator.userAgent.toLowerCase();
			var appVersion = window.navigator.appVersion.toLowerCase();
			if (userAgent.indexOf('opera') != -1) {
				return 'opera';
			}
			else if (userAgent.indexOf("msie") != -1) {
				if (appVersion.indexOf("msie 6.") != -1) {
					return 'ie6';
				}
				else if (appVersion.indexOf("msie 7.") != -1) {
					return 'ie7';
				}
				else if (appVersion.indexOf("msie 8.") != -1) {
					return 'ie8';
				}
				else if (appVersion.indexOf("msie 9.") != -1) {
					return 'ie9';
				}
				else if (appVersion.indexOf("msie 10.") != -1) {
					return 'ie10';
				}
				else if (appVersion.indexOf("msie 11.") != -1) {
					return 'ie11';
				}
				return 'ie';
			}
			else if (userAgent.indexOf('chrome') != -1) {
				return 'chrome';
			}
			else if (userAgent.indexOf('firefox') != -1) {
				return 'firefox';
			}
			else if (userAgent.indexOf('safari') != -1) {
				return 'safari';
			}
			return "";
		},
		/**
		* ファイル変数よりタイプを取得し返却
		* @method getExtension
		* @param _file {Object}
		* @return {String}

 extensition
		*/
		getExtension: function (_file) {
			var fileType = "";
			if(_file.type){
				fileType = _file.type;
			}
			else if (_file.prop && _file.prop('files')) {
				fileType = _file.prop('files')[0].type;
			}
			if(fileType==""){
				//取得できない場合どうするか・・@2016.11.4 yasui
			}
			return fileType;
		},
		/**
		* ファイルパスよりファイル名を取得し返却
		* @method getFileName
		* @param filepth {String}
		* @return {String} filename
		*/
		getFileName: function(filepath) {
			var splitwk;
			var filename;
			if(filepath.indexOf('/') >= 0){
				splitwk = filepath.split('/');
				filename = splitwk[splitwk.length-1];
			}
			else if(filepath.indexOf("\\") >= 0){
				splitwk = filepath.split("\\");
				filename = splitwk[splitwk.length-1];
			}
			else {
				filename = filepath;
			}
			return filename;
		},
		/**
		* ファイル変数よりファイルサイズを取得し返却
		* @method getFileSize
		* @param _file {Object}
		* @param [isUnit] {Boolean} 単位付き表示にする場合、true
		* @return {String} filesize+fileUnit
		*/
		/**
		* ファイル変数よりファイルサイズを取得し返却
		* @method getFileSize
		* @param _file {Object}
		* @return {Int} filesize
		*/
		getFileSize: function(_file, isUnit) {
/*
			if (!_file.length || !_file.val()) {
				return '';
			}
*/
			var file_size = 0;
			if(_file.size){
				file_size = _file.size;
			}
			else if (_file.prop('files')) {
				file_size = _file.prop('files')[0].size;
			}
			if (file_size && isUnit) {
				return this.setFileUnit(file_size);
			}
			return file_size;
		},
		/**
		* ファイルサイズを単位付きにする
		* @method setFileUnit
		* @param    file_size {Int}
		* @return {Stirng} filesize+fileUnit
		*/
		setFileUnit: function(file_size) {
			// 単位
			var unit = ['byte', 'KB', 'MB', 'GB', 'TB','PB','EB','ZB','YB'];
			var str;
			for (var i=0,n=unit.length;i<n;i++) {
				if (file_size < 1024 || i == unit.length - 1) {
					if (i == 0) {
						// カンマ付与
						var integer = file_size.toString().replace(/([0-9]{1,3})(?=(?:[0-9]{3})+$)/g, '$1,');
						str = integer +    unit[ i ];
					}
					else {
						// 小数点第2位は切り捨て
						file_size = Math.floor(file_size * 100) / 100;
						// 整数と小数に分割
						var num = file_size.toString().split('.');
						// カンマ付与
						var integer = num[0].replace(/([0-9]{1,3})(?=(?:[0-9]{3})+$)/g, '$1,');
						if (num[1]) {
							file_size = integer + '.' + num[1];
						}
						str = file_size +    unit[i];
					}
					break;
				}
				file_size = file_size / 1024;
			}
			return str;
		},
		/*
			* ブラウザがIEであるかをチェックする
			*/
		isIE: function() {
			return /(msie|trident|edge)/i.test(navigator.userAgent);
		},
		/**
		* セッションストレージにデータを保存
		* 30000文字を単位に分割保存する
		* 保存時は、文字列圧縮する
		* @method setLocalData
		* @param name {String}    保存時のkey
		* @param val {String}    保存時のvalue
		* @return {Boolean} true(success) or false(failed)
		*/
		setLocalData: function(name, val) {
			if(this.isEmpty(val)) {
				val="";
			}
			try {
				var a = (typeof val);
				if(a == "object"){
					val = JSON.stringify(val);
				}
				if (val != "") {
					val = utf16to8(val);
					val = zip_deflate(val);
				}
				var len = val.length;
				var _savedVal = this.getLocalData(name);
				if(_savedVal == null ) _savedVal = "";
				/*
				var size = sessionStorage.getItem("l");
				size = this.toInt(size) + len - _savedVal.length;
				sessionStorage.setItem("l", size);
				*/
				var _maxString = 30000;
				for(var i=0;i<100;i++){
					var _name = name+"["+i+"]";
					var _size = _maxString;
					if(_size*(i+1) > len) _size = len;
					var _val = val.substring(i*_maxString , _size);
					sessionStorage.setItem(_name, _val);
					if(len < _maxString*(i+1)) break;
				}
				return true;
			}
			catch(e){
			}
			return false;
		},
		/**
		* セッションストレージからデータを取得
		* 圧縮文字列を解凍し、分割保存状態を連結し返却
		* @method getLocalData
		* @param name {String}    保存時のkey
		* @return (Object)
		*/
		getLocalData: function(name) {
			try {
				var result = "";
				for(var i=0;i<100;i++){
					var _name = name+"["+i+"]";
					try {
						var _result = sessionStorage.getItem(_name);
						if(_result == null) break;
						var _val = sessionStorage.getItem(_name);
						result += _val;
					}
					catch(e) {
						break;
					}
				}
				if(result == "") return null;
				try{
					result = zip_inflate(result);
					result = utf8to16(result);
					result = JSON.parse(result);
				}
				catch(e){
					return null;
				}
				return result;
			}
			catch(e){
			}
			return null;
		},
		/**
		* セッションストレージをクリア
		* @method clearLocalData
		* @return {Boolean} true(success) or false(failed)
		*/
		clearLocalData: function() {
			try {
				sessionStorage.clear();
				localStorage.clear();
			}
			catch(e){
				return false;
			}
			return true;
		},
		/**
		* 全角変換　半角文字列を全角に変換する
		* @method convZenkaku
		* @param str {String} (半角)
		* @return {String} str (全角)
		*/
		convZenkaku : function(str){
			var self = this;
			var ret = "";
			for(var i=0,n=str.length;i<n;i++){
				var tmpStr = str.substring(i,i+1);
				if (!self.isHankakuChar(tmpStr)) {
					ret+=tmpStr;
				}
				else if(tmpStr==" ") ret+="　";
				else {
					ret+=String.fromCharCode( tmpStr.charCodeAt(0) + 0xFEE0 );
				}
			}
			// 文字コードシフトで対応できない文字の変換
			ret = ret.replace(/”/g, "\"")
			.replace(/'/g, "’")
			.replace(/`/g, "‘")
			.replace(/\\/g, "￥")
			.replace(/ /g, "　")
			.replace(/~/g, "～");
			var result = "";
			for(var i=0; i<ret.length; i++){
				var len=escape(ret.charAt(i)).length;
				if(len<4) continue;
				result += ret.charAt(i);
			}
			return result;
		},
		/**
		* 半角変換　全角文字列を半角に変換する
		* @method convHankaku
		* @param str {String} (全角)
		* @return {String} str (半角)
		*/
		convHankaku : function(str){
			var self = this;
			var ret = "";
			for(var i=0,n=str.length;i<n;i++){
				var tmpStr = str.substring(i,i+1);
				if (self.isHankakuChar(tmpStr)) {
					ret+=tmpStr;
				}
				else {
					ret+=String.fromCharCode( tmpStr.charCodeAt(0) - 0xFEE0 );
				}
			}
			// 文字コードシフトで対応できない文字の変換
			ret = ret.replace(/”/g, "\"")
			.replace(/’/g, "'")
			.replace(/‘/g, "`")
			.replace(/￥/g, "\\")
			.replace(/　/g, " ")
			.replace(/～/g, "~");

			var result = "";
			for(var i=0; i<ret.length; i++){
				var len=escape(ret.charAt(i)).length;
				if(len>=4) continue;
				result += ret.charAt(i);
			}
			return result;
		},
		/**
		* 半角カナ→全角カナ変換
		* @method convZenkaku
		* @param str {String} (半角カナ)
		* @return {String} str (全角カナ)
		*/
		convZenkakuKana : function(str){
			var kanaMap = {
				'ｶﾞ': 'ガ', 'ｷﾞ': 'ギ', 'ｸﾞ': 'グ', 'ｹﾞ': 'ゲ', 'ｺﾞ': 'ゴ',
				'ｻﾞ': 'ザ', 'ｼﾞ': 'ジ', 'ｽﾞ': 'ズ', 'ｾﾞ': 'ゼ', 'ｿﾞ': 'ゾ',
				'ﾀﾞ': 'ダ', 'ﾁﾞ': 'ヂ', 'ﾂﾞ': 'ヅ', 'ﾃﾞ': 'デ', 'ﾄﾞ': 'ド',
				'ﾊﾞ': 'バ', 'ﾋﾞ': 'ビ', 'ﾌﾞ': 'ブ', 'ﾍﾞ': 'ベ', 'ﾎﾞ': 'ボ',
				'ﾊﾟ': 'パ', 'ﾋﾟ': 'ピ', 'ﾌﾟ': 'プ', 'ﾍﾟ': 'ペ', 'ﾎﾟ': 'ポ',
				'ｳﾞ': 'ヴ', 'ﾜﾞ': 'ヷ', 'ｦﾞ': 'ヺ',
				'ｱ': 'ア', 'ｲ': 'イ', 'ｳ': 'ウ', 'ｴ': 'エ', 'ｵ': 'オ',
				'ｶ': 'カ', 'ｷ': 'キ', 'ｸ': 'ク', 'ｹ': 'ケ', 'ｺ': 'コ',
				'ｻ': 'サ', 'ｼ': 'シ', 'ｽ': 'ス', 'ｾ': 'セ', 'ｿ': 'ソ',
				'ﾀ': 'タ', 'ﾁ': 'チ', 'ﾂ': 'ツ', 'ﾃ': 'テ', 'ﾄ': 'ト',
				'ﾅ': 'ナ', 'ﾆ': 'ニ', 'ﾇ': 'ヌ', 'ﾈ': 'ネ', 'ﾉ': 'ノ',
				'ﾊ': 'ハ', 'ﾋ': 'ヒ', 'ﾌ': 'フ', 'ﾍ': 'ヘ', 'ﾎ': 'ホ',
				'ﾏ': 'マ', 'ﾐ': 'ミ', 'ﾑ': 'ム', 'ﾒ': 'メ', 'ﾓ': 'モ',
				'ﾔ': 'ヤ', 'ﾕ': 'ユ', 'ﾖ': 'ヨ',
				'ﾗ': 'ラ', 'ﾘ': 'リ', 'ﾙ': 'ル', 'ﾚ': 'レ', 'ﾛ': 'ロ',
				'ﾜ': 'ワ', 'ｦ': 'ヲ', 'ﾝ': 'ン',
				'ｧ': 'ァ', 'ｨ': 'ィ', 'ｩ': 'ゥ', 'ｪ': 'ェ', 'ｫ': 'ォ',
				'ｯ': 'ッ', 'ｬ': 'ャ', 'ｭ': 'ュ', 'ｮ': 'ョ',
				'｡': '。', '､': '、', 'ｰ': 'ー', '｢': '「', '｣': '」', '･': '・'
			};
			var reg = new RegExp('(' + Object.keys(kanaMap).join('|') + ')', 'g');
			return str.replace(reg, function (match) {
						return kanaMap[match];
					})
					.replace(/ﾞ/g, '゛')
					.replace(/ﾟ/g, '゜');
		},
		/**
		* 全角ひらがな→全角カナ変換
		* @method convZenkakuHiraganaToKana
		* @param str {String} (全角ひらがな)
		* @return {String} str (全角カナ)
		*/
		convZenkakuHiraganaToKana : function(str){
			str = str.replace(/[ぁ-ゔ]/g, function (s) {
						return String.fromCharCode(s.charCodeAt(0) + 0x60);
					})
					.replace(/ﾞ/g, '゛')
					.replace(/ﾟ/g, '゜')
					.replace(/(ウ゛)/g, 'ヴ')
					.replace(/(ワ゛)/g, 'ヷ')
					.replace(/(ヰ゛)/g, 'ヸ')
					.replace(/(ヱ゛)/g, 'ヹ')
					.replace(/(ヲ゛)/g, 'ヺ')
					.replace(/(ゝ゛)/g, 'ヾ')
					.replace(/ゝ/g, 'ヽ')
					.replace(/ゞ/g, 'ヾ')
					.replace(/ゕ/g, 'ヵ')
					.replace(/ゖ/g, 'ヶ');
			return str;
		},
		/**
		* パラメータ比較差分値を取得
		* @method diffVal
		* @param a {Object}
		* @param b {Object}
		* @param [type] {String}    (比較するタイプ date | float | int)
		* @return {Int} diff(差値）
		*/
		diffVal : function(a, b,type){
			var _a = a, _b=b;
			var ret = 0;
			var type="";
			if(this.isDate(a+"") && this.isDate(b+"")) type="date";
			if((this.isInteger(a+"") || this.isFloat(a+"")) && (this.isInteger(b+"") || this.isFloat(b+""))) type="float";
			switch(type){
				case "date" :
					_a = Date.parse(a);
					_b = Date.parse(b);
					ret = _a-_b;
					break;
				case "float":
					_a = parseFloat(a);
					_b = parseFloat(b);
					ret = _a-_b;
					break;
				default :
					if(_a > _b) ret = 1;
					else ret=-1;
					break;
			}
			return ret;
		},
		/**
		* カンマを含む文字列型の数値に対応した parseFloat
		* @method parseFloat
		* @param    strNum {Strign}
		* @return {Float}
		*/
		parseStrToFloat : function(strNum){
			// strNumが数値の場合も文字列化して処理
			strNum = (strNum + '').replace(/,/g, '');
			return parseFloat(strNum);
		},
		/**
		* カンマを含む文字列型の数値に対応した parseFloat
		* @method parseFloat
		* @param    strNum {Strign}
		* @return {Float}
		*/
		convJsonToQueryString : function(data){
			var ret = "";
			var ishash = false;
			for(var key in data){
				if(key==="DELETE_FLAG") continue;
				if(key==="ADD_TIME") continue;
				if(key==="UPD_TIME") continue;
				if(this.isEmpty(data[key])) continue;
				if(this.isFunction(data[key])) continue;
				if($.isArray(data[key])) continue;
				if(!this.isHankaku(data[key])) continue;
				//if((data[key]+'').indexOf(' ')>=0) continue;
				if(typeof data[key] == "object") continue;
				if(key=="#") {
					ishash = true;
					continue;
				}
				ret += "&"+key+"="+data[key];
			}
			ret = ret.substring(1, ret.length);
			if(ishash) ret = ret + "#" + data["#"];
			return ret;
		},
		convQueryStringToJson : function(){
			var _urls = (document.URL+"").split('/');
			var _pageCode = _urls[_urls.length-1].replace("#","&#=");
			var queryParam = ($(location).attr("search")+"&").split("&");
			_pageCode = _pageCode.replace('"',"");
			_pageCode = _pageCode.replace(/\?.*$/,"");
			var ret = {};
			for(var i=0,n=queryParam.length;i<n;i++){
				if(queryParam[i].indexOf("=")<0) continue;
				var q = queryParam[i].split("=");
				if(q.length!=2) continue;
				if(q[0].substring(0,1)=="?") q[0] = q[0].substring(1, q[0].length);
				ret[q[0]]=q[1];
			}
			return ret;
		}
	}

	root.util = $.extend({}, root.util, _utils);

})(this);

String.prototype.replace_all = function (org, dest){
	if(this.split){
		return this.split(org).join(dest);
	}
	else {
		return ;
	}
};
//console.log対応していない場合のエラー回避
if(!window.console) {
	window.console = { log: function(msg){
	} };
}
