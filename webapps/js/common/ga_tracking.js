var _url = location.href;
if(_url.indexOf("https://mykinso.com")>=0){
	createGA('UA-61694376-2');
}
else if(_url.indexOf("https://pro.mykinso.com")>=0){
	createGA('UA-61694376-6');
}
function createGA(code){
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
	ga('create', code, 'auto');
	ga('send', 'pageview');
}
