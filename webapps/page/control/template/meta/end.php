<!-- end.php start -->
<script>
$(function(){
  $('a[href^="#"][scroll]').click(function() {
    var href= $(this).attr("href");
    var scroll= $(this).attr("scroll");
    var target = $(href == "#" || href == "" ? 'html' : href);
    var position = target.offset().top;
    $('body,html').animate({scrollTop:position}, scroll, 'swing', function(){
    });
  });
  $('.toggle-btn').click(function() {
    //指定したidを閉じたり開いたり
    var id = $(this).attr("target");
    var _btn = $(this);
    $('#'+id).slideToggle("fast", function(){
      if($(this).is(":hidden")){
        $(".toggle-btn-open", _btn).show();
        $(".toggle-btn-close", _btn).hide();
      }
      else {
        $(".toggle-btn-open", _btn).hide();
        $(".toggle-btn-close", _btn).show();
      }
    });
  });
});
</script>
</body>
</html>
<!-- end.php end -->
