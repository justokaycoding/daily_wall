(function($){


  $(document).on("click",".dailyWall input[type='submit']",function(e) {
      e.preventDefault();
      let text = $('.form__field').val();
      if(text.length == 0) return;
      $.post(
      	ajax_url,
      	{
      		'action': 'js_callback_action',
          'text': text,
      	},
      	function( response ){
          $('.form__field').val('');
          $( ".word_cloud" ).html( response );
      	}
      );

  });

})(jQuery);
