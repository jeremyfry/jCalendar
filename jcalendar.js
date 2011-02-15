jQuery(function ($) {

	//admin panel
	$(".allCheck").bind('click',function(){
		if($(this).attr('value') == "true")
		{
			$(".time-pick").hide();
		}else{
			$(".time-pick").show();
		}
	});
	Date.format = 'yyyy-mm-dd';
	if($( "#start-date, #end-date" ).length>0){
		$( "#start-date, #end-date" ).datePicker({startDate:'01/01/1996',clickInput:true});
	}

	$("#repeat-selection").bind('change',function(){
		var popDiv = $(this).attr('value');
		
		if(popDiv == 'never'){
			$(".pops").hide();
		}
		$("."+popDiv+"-pop").show();
	});
	
	//javascript for picking the repeat dates
	$(".daily-pop :input").bind('change',function(){
		var box = $(this).parents('.daily-pop');
		var repeats = box.find("select[name=repeats]").val();
		var until = $("input[name=end]", box).eq(0).val();
		if($("input[name=end-opt]:checked", box).eq(0).val() != "never")
		{
			until = "Repeats every "+repeats+" day(s) until "+until;
		}else{
			until = "Repeats every "+repeats+" day(s) forever";
		}
		
		$("td.summary").html(until);
	});
});	
