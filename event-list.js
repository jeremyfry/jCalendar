jQuery(function ($) {

	//date lister
	if($("#jcal").length > 0)
	{
	
		function displayEventsForDate(e)
		{
			//clicked on a non date
			if(typeof($(this).attr('date')) == "undefined"){ return; }
			var ele = $(this);
			var hasEvents = $(this).hasClass('has-events');
			var noEvent = "<li class='title'><h2>"+$(this).attr('date')+"</h2></li><li>No events on this day</li>";
			$('.active').removeClass('active');
			$(this).addClass('active');
			$("#event-list li").each(function(){
				var delay = $("#event-list li").index($(this)) * 30;
				$(this).delay(delay).animate({left:'-80px',opacity:0},'fast');
			});
			$("#event-list").delay(300).fadeOut('fast',function(){
				$(this).children('ul').html('');
				if(!hasEvents)
				{
					$(this).children('ul').html(noEvent);
				}
				var id = -1;
		
				//loop through until we get the next element
				while(id == -1)
				{
					id = $("#jcal td ul").index(ele.find('ul'));
					var current = $("#jcal td").index(ele);
					ele = $("#jcal td").eq(current+1);
					//we've reached the end of the month
					if(current > 30){break;}
				}
		
				if(id != -1)
				{
					var uls = $("#jcal td ul");
					for(var x = id; x<= id+4; x++)
					{
						$("#event-list ul").append(uls.eq(x).html());
					}
				}
				$(this).find('li').css({left:"-80px",opacity:0});
				$(this).show().find('li').each(function(){
					var delay = $("#event-list li").index($(this)) * 30;
					$(this).delay(delay).animate({left:'0px',opacity:1},'fast');
				});
			});
		}
		$("#jcal td").live('click',displayEventsForDate);
		
		$("#jcal th.next,#jcal th.previous").live('click',function(){
			var self = $(this).children('a');
			$("#jcal th.full").html('Loading...');
			$.post('/wp-content/plugins/jCalendar/get-month.php',{"c":self.attr('href')}, function(data) {
				$('#jcal-wrap').children().fadeOut('fast',function(){
				  	$('#jcal-wrap').html(data).children().fadeIn();
				  	$("#jcal td.today").eq(0).trigger('click');
				});
			});		
			return false;
		});
		
		
		$("#jcal td.today").eq(0).trigger('click');
		
		$("#event-list .expands").live('click',function(){
			$(this).removeClass('expands').children('span').show();
			$(this).text($(this).text().replace('&hellp;'));
		});
	}
});