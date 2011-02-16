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
	if($( "#start-date, #end-date, .recur-start, .recur-end" ).length>0){
		$( "#start-date, #end-date, .recur-start, .recur-end" ).datePicker({startDate:'01/01/1996',clickInput:true});
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

		var until = getFriendlyRecurrenceString(box);
		$("td.summary").html(until);
	});
	
	function getFriendlyRecurrenceString(box){
		var repeats = box.find("select[name=interval]").val();
		var nrepeat = box.find("input[name=repeats-nice]").val();
		var until = $("input[name=end]", box).eq(0).val();
		if($("input[name=end-opt]:checked", box).eq(0).val() != "never")
		{
			until = "Repeats every "+repeats+" "+nrepeat+" until "+until;
		}else{
			until = "Repeats every "+repeats+" "+nrepeat+"forever";
		}
		return until;
	}
	
	//calcuclate the string for saving the repeat
	$(".done-repeat").bind('click',function(){
		//we'll parse our parent div and build out the google string
		//ex of the google string: RRULE:FREQ=WEEKLY;BYDAY=Tu;UNTIL=20070904\r\n
		var el = $(this).parents('.ep-rec');
		var end = el.find('.recur-end').val().replace(/-/g,"");
		var interval = el.find('select[name=interval]').val();
		var repeats = el.find('input[name=repeats]').val();
		var recur = "RRULE:FREQ="+repeats+";INTERVAL="+interval+";";
		if(end != ""){
			recur += "UNTIL="+end;
		}

		$('input[name=recur-string]').attr('value',recur);
		$('.recur-view').text(getFriendlyRecurrenceString(el));
		$(".pops").hide();
		return false;
	});
	
	$(".done-cancel").bind('click',function(){
		$(".pops").hide();
		return false;
	});
});	
