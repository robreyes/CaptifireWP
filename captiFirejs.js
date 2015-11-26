jQuery(document).ready(function(){
	
	jQuery("input[type='submit']").click(function(){
		
		if(jQuery("#captifire_page_type option:selected").val() == "home"  || jQuery("#captifire_page_type option:selected").val() == "error_404"){
			
			jQuery.post(
				ajaxurl, 
				{
					'action': 'captifire_homeCheck',
					'data':   jQuery("#captifire_page_type option:selected").val(),
					'page_title':   jQuery("#my_meta_box_select option:selected").val(),
					'page_path':   jQuery("#my_meta_box_text").val()
				}, 
				function(response){
					console.log("hello nasa homepage na ako" + response.slice(0, -1));
					return true;
				}
			);
			
			// jQuery.ajax({
				// action: 'captifire_homeCheck',
				// method: "post",
				// url: ajaxurl,
				// data: { 'home': jQuery("#captifire_page_type option:selected").val() },
				// success: function(response){
					
					// console.log("hello nasa homepage na ako" + response);
					
				// }
			// });
			
			return false;
			
		}else{
			
		}
		
	});
	
});

