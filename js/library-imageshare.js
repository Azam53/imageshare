/*
*	ImageShare Plugin
* 	Refresh Media - 2015
*/


(function($) {
	$.fn.imageshare = function( options ) {	
		// Check if user is logged in
		var client = checkLogin();
		if(client === false ){			
			alert('U dient eerst in te loggen op image-share');
			return false;
		}
		
		// Merge defaults and options, without modifying defaults.
		var settings = $.extend( {} ,$.fn.imageshare.defaults,options);		
		/*if(settings.multiple) console.log('Multiple image selector is true');*/
		
		return {
			openImageShare: function(){
				console.log('Komt hier!');
				$('div').first().append('<div style="margin-left:auto;margin-right:auto;width:1000px;margin-top:10px;"><iframe style="width:1000px;height:800px;margin-left:auto;margin-right:auto;" id="imageshare-iframe" src="https://image-share.nl/login?embed=1"></iframe></div>');
			}/*,
			selectImage: function(){
				console.log('SELECT EEN IMAGE!');
			}*/
		}
	}
	
	$.fn.imageshare.defaults = {
		allowed 	: ['jpg','jpeg','png','gif'],
		multiple 	: 'false',
	};
	
	function checkLogin(){
		$.ajax({
			url: "https://image-share.nl/api/checklogin",			
			success: function(data){
				return data;
			}
		});
	}

}(jQuery));


$(document).ready(function(){
	$('.files').click(function(){
		$('div').first().append('<div style="margin-left:auto;margin-right:auto;width:1000px;margin-top:10px;"> '+		
		'<iframe style="width:1000px;height:800px;margin-left:auto;margin-right:auto;" id="imageshare-iframe" src="https://image-share.nl/login?embed=1"></iframe>'+
		'</div>');
		
	});
			
	$('#send-img').click(function(e){
		alert('Clicked!');
		
		
		$(".files").imageshare();
		
		return false;
	});
});

/*
ImageShare

embed.js ->
	<script src="https://image-share.nl/js/embed.js"></script>
	<script>
	$(".files").imageshare({
		allowed: ['jpg'],
		multiple: true/false
	}, function(files){
		console.log(files);
	});
	</script>
	
	embed.js contents ->
	container -> iframe toevoegen -> https://image-share.nl/login?allowed=jpg&OPTIONS
	pickt in imageshare -> postmessage naar de embed.js -> callt de callback met de files die je selecteerd
	


*/