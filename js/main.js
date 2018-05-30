var addTagsInput = function(el, onChange) {
	if(typeof onChange === "undefined") onChange = function(){ }

	$(el).tagsInput({
		width:'auto',
		height:'auto',
		/*'width':'289px',
		'height':'40px',*/
		'defaultText':'+ ' + keywordLang,
		autocomplete_url:'/upload/autocomplete_keywords',
		autocomplete:{width:'100px',autoFill:true},
		onChange: onChange
	});
};

$(function(){
	window.setTimeout(function() {
		if($(".alert-success").length > 0)
			$(".alert-success").alert('close');
	}, 2000);

	//$(".datepicker").datepick({dateFormat: 'dd-mm-yyyy'});
	
	$('.keywords').each(function(){
		addTagsInput($(this));
	});
	
	/*var setImageSize = function() {
		var $img = $(".max-height-image");
		if($img.data('height') > $(window).height() - 100)
			$img.css('height', ($(window).height() - 100) + 'px');
	};*/
	
	$('.show-more-keywords').on('click',function(){	
		$("a", $(this).prev()).removeClass('no-display');
		$(this).hide();
		
		return false;
	});
	
	$('.select-all-checkboxes').on('click',function(e){		
		e.preventDefault();
		console.log($(this).attr('data-select'));
		if($(this).attr('data-select')==1){
			$(".file-checkbox").each(function(){ 
				this.checked = false;					
			});
			$(this).attr('data-select',0);
		}else{
			$(".file-checkbox").each(function(){ 
				this.checked = true;					
			});
			$(this).attr('data-select',1);
		}
		
		$(".file-checkbox").trigger('change');
	});
	
	var totalSelected = 0;
	$(".file-checkbox").on('change', function(){
		totalSelected = 0;
		var ids = [];
		$(".file-checkbox:checked").each(function(){ totalSelected++; ids.push($(this).val()); });
		
		$("#bulk-selected").html((totalSelected == 1 ? '1 bestand' : (totalSelected + ' bestanden')));
		
		
		$(".js-delete").attr('href', '/images/delete/' + ids.join(",") + '/1');
		$(".bulk-mail").attr('href', '/images/share/' + ids.join(",") + '');
		
		$(".bulk-add-keywords").on('click', function(){
			$('#bulk-add-keywords-modal').modal('show');
			return false;
		});
		$("#bulk-add-keywords-modal").find('input[name=image_ids]').val(ids.join(","));
		
		if(totalSelected > 0) {
			$(".bulk").removeClass('hide');
		} else {
			$(".bulk").addClass('hide');
		}
	});
	
	var setEvents = function() {
		var image_id = $(".image-container").data('image_id');
		//window.location.hash = '#!' + image_id;
	
		$(".js-expand-options").click(function(){
			$("#options").toggle();
			return false;
		});
		
		$(".js-download-button").on('click', function(){
			var size = $('input[name=size]:checked').val();			
			if(size != 'original') {
				var width = size.split("x")[0];
				var height = size.split("x")[1];
			}
			if(size == 'custom') {
				var width = $('input[name=size_width]').val();
				var height = $('input[name=size_height]').val();
			}
			
			var href = $(this).data('href');
			
			window.location.href = href + ((size == 'original') ? '' : '?width=' + width + '&height=' + height);
			return false;
		});
		
		$(".js-edit").on('click', function(){
			$("#edit-form").toggle();
			$("#edit-form").on('submit', function(){
				if($("#edit-form input[type='file']").val() != '')
					$(".js-edit-submit").html('Bezig met uploaden...');
			});
			$("#info-container").toggle();
			
			return false;
		});
		
		//var ratio = parseFloat($("#options").data('ratio'));
		var locked = true;
		$("#aspect-ratio-lock").on('click', function(){
			$(this).css('opacity', (locked ? .3 : 1));
			locked = !locked;
			return false;
		});
		
		$("input[name=size_width]").on('change keyup', function(){
			$("input[name=size]").prop('checked', true);
			var r = $("input[name=size_width]").data('width') / $("input[name=size_width]").val();
			if(locked) $("input[name=size_height]").val(Math.round(parseFloat($("input[name=size_height]").data('height')) / r));
		});
		
		//addTagsInput("#edit-keywords");

		$.get('/images/thumb', {image_id: image_id, size: '700x', output: 0}, function(url){
			if(url.indexOf('/uploads/') == -1) {
				console.log('Afbeelding kon niet worden geladen: ' + url);
				return;
			}
		
			//console.log(url);
			var img = new Image();
			
			img.onload = function() {			
				$(".image").attr('src', img.src);				
			};			
			img.src = url;
		});
		
		if($('.image').hasClass('show-large')){
			// Alleen orginele foto ophalen als de ingelogde persoon hem ook mag downloaden
			$.get('/images/fullsize',{image_id: image_id},function(url){					
				$('.image').attr('data-href',url);
			});
		}
		
	};
	if(window.location.href.indexOf('/info/') != -1) setEvents();
	
	/*var loadImageInfo = function(image_id){	
		//$("#image-info").html('').addClass('loading').show()

		//$(".thumbnail").removeClass('active');
		//$(".thumbnail[data-image_id=" + image_id + "]").addClass('active');
		
		var items = [];
		var i = 0;
		var toOpen = false;
		$(".image_loop div").each(function(){
			var img_id = $(this).find('a').data('image_id');
			items.push({
				src: '/images/info/' + img_id + '?popup=1',
				type: 'ajax'
			});
			if(img_id == image_id) {
				toOpen = i;
			}
			i++;
		});
		
		if(items.length == 0) {
			items.push({
				src: '/images/info/' + image_id + '?popup=1',
				type: 'ajax'
			});
			toOpen = 0;
		}

		$.magnificPopup.open({
			items: items,
			gallery: {
			enabled: true
			},
			callbacks: {
				ajaxContentAdded: function() {
					setEvents();
				},
			},
			closeBtnInside:true,
			type: 'ajax' // this is default type
		}, 0);

		if(toOpen)
			$.magnificPopup.instance.goTo(toOpen);
	};*/
	
	var setupThumbnailClick = function(){
		var thumbs = [];
		$(".js-file.ajax-load").each(function(){
			var $this = $(this);
			var image_id = $this.parents('.thumbnail').data('image_id');

			thumbs.push({
				'element': $this,
				'image_id': image_id
			});
		});
		
		var i = 0;
		var createThumb = function(q){
			var thumb = thumbs[q];

			$.get('/images/thumb', {image_id: thumb.image_id}, function(data){
				thumb.element.attr('src', data);
				
				i++;
				if(thumbs.length > i) {
					createThumb(i);
				}
			});
		};
		
		if(thumbs.length > 0)
			createThumb(0);
	};
	setupThumbnailClick();
	
	$(".delete-warning").on('click', function(e){
		e.preventDefault();

		$('#delete-button').attr('href', $(this).attr('href'));			
		$('#delete-modal').modal('show');
		
	});
	
	$('.typeahead').typeahead({
		source: function (query, process) {
			$.get('/images/typeahead', { q: query }, function(data) {
				process(data);
			});
		},
		onselect: function (obj) {
			$(".search-button").parents('form').submit();
		}
	}).on('change', function(){
		$(".search-button").parents('form').submit();
	});
	
	$(".thumbnail, .thumbnail-popover").each(function(){
		var placement = "bottom";
		if($(this).data('placement')) placement = $(this).data('placement');
		$(this).popover({
			trigger: "hover",
			delay: { 
			   show: "300", 
			   hide: "100"
			},
			html:true,
			placement: function(tip, element) {
				return placement;
			},
			content:function(){
				return '<span class="loading">'+loadingLang+'... </span><img src="/images/thumb?image_id=' + $(this).data('image_id') + '&size=700x&output=1" style="max-width: 350px; max-height: 400px; display: none;" onload="$(this).parent().find(\'.loading\').hide(); $(this).show();" />';
			}
		});
	});
	
	$(".dropdown-login").on('click', function(){
		setTimeout(function(){
			$('input#username').focus();
		}, 200);
	});
});