<?php
/*Plugin Name: jCalendar
Plugin URI: http://jeremy-fry.com/
Description: 
Author: Jeremy Fry
Version: 1.0.0
Author URI: http://www.jeremy-fry.com/
*/

if ( !class_exists( 'jCalendar' ) ) {
	define( 'JCALENDAR_VER', '1.0' );
	class jCalendar 
	{
		public static $service;
		protected static $path;
		protected static $tzOffset;
		protected static $client;
		public static $user;
		public static $pass;

		// Constructor
		public function jCalendar()
		{
			#set my include path
			self::$path = realpath(dirname(__FILE__));
			set_include_path(get_include_path() . PATH_SEPARATOR . self::$path);
			self::$tzOffset = "-05";
			require_once 'Zend/Loader.php';
			Zend_Loader::loadClass('Zend_Gdata');
			Zend_Loader::loadClass('Zend_Gdata_AuthSub');
			Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
			Zend_Loader::loadClass('Zend_Gdata_HttpClient');
			Zend_Loader::loadClass('Zend_Gdata_Calendar');
			
			#build the menu
			add_action('admin_menu', array( &$this, 'jcalendar_create_menu'));			
			#for inital load we won't have any user credintials.
			if(get_option('username') == ""){return;}
			self::$client = self::getClient();
			#add the custom event post type
			add_action( 'init', array( &$this, 'create_post_type' ) );
			add_action('add_meta_boxes', array( &$this, 'events_meta_box') );
			add_action('save_post', array( &$this, 'save_events_data') );
			add_action('trashed_post', array( &$this, 'remove_event_from_cal') );
			add_action('untrash_post', array( &$this, 'untrash_data') );	
			add_action('init', array( &$this, 'load_includes' ));

			session_start();
		}
		
		
		function load_includes() {
			wp_enqueue_script('jquery');

			
			wp_register_style( 'jcalendarcss',WP_PLUGIN_URL.'/'.dirname( plugin_basename( __FILE__ ) ).'/jcalendar.css');
			wp_enqueue_style('jcalendarcss');
			
			
			wp_register_script('datepicker', WP_PLUGIN_URL.'/'.dirname( plugin_basename( __FILE__ ) ).'/datepicker.js');
			wp_enqueue_script('datepicker');

			wp_enqueue_script( 'jcalendarjs',WP_PLUGIN_URL.'/'.dirname( plugin_basename( __FILE__ ) ).'/jcalendar.js',false, JCALENDAR_VER );
			wp_enqueue_script('jcalendarjs');
			if(get_option('view-style')=="tooltip"){
				wp_enqueue_script( 'jcalendarview',WP_PLUGIN_URL.'/'.dirname( plugin_basename( __FILE__ ) ).'/tooltip.js',false, JCALENDAR_VER );
			}else{
				wp_enqueue_script( 'jcalendarview',WP_PLUGIN_URL.'/'.dirname( plugin_basename( __FILE__ ) ).'/event-list.js',false, JCALENDAR_VER );
			}
			wp_enqueue_script('jcalendarview');
		}

		function create_post_type() {
		  register_post_type( 'event',
		    array(
		      'labels' => array(
		        'name' => __( 'Events' ),
		        'singular_name' => __( 'Event' ),
				'add_new_item' => 'Add New Event'
		      ),
		      'public' => true,
				'show_ui' => true, // UI in admin panel
				'_builtin' => false, // It's a custom post type, not built in!
				'_edit_link' => 'post.php?post=%d',
				'menu_position' => 6,
				'capability_type' => 'post',
				'rewrite' => array("slug" => "event"), // Permalinks format
				'supports' => array('title', 'editor', 'thumbnail', 'page-attributes')
		    )
		  );
		}
		
		#adds the box
		function events_meta_box() {
		    add_meta_box( 'event','Event Details', array( &$this, 'events_fields'), 'event' );
		}
		
		#my box content
		function events_fields() {
		  wp_nonce_field( plugin_basename(__FILE__), 'event-meta' );
		  $meta = get_post_meta($_GET['post']);
		  	?>
			<table id="jcal-settings" cellspacing=0>
				<tbody>
					<tr class="odd">
						<th><label for="start-date">Event Start Date</label></th>
						<th><label for="end-date">Event End Date</label></th>
						<th><label for="all-day">All Day?</label></th>
					</tr>
					<tr>
						<td><input type="text" id="start-date" name="start-date" value="<?=$meta['startDate'][0];?>" size="25" /></td>
						<td><input type="text" id="end-date" name="end-date" value="<?=$meta['endDate'][0];?>" size="25" /></td>
						<td>
							<input type="radio" class="allCheck" name="all-day" value="true"  <?php if($meta['all-day']=='true'){echo "checked"; }?>> Yes
							<input type="radio" class="allCheck" name="all-day" value=""  <?php if($meta['all-day']!='true'){echo "checked"; }?>>No<br>
						</td>
					</tr>
					<tr class="odd">
						<th><label class="time-pick" for="start-time">Event Start time</label></th>
						<th><label class="time-pick" for="end-time">Event End Time</label></th>
						<th><label for="featured">Featured?</label></th>

					</tr>
					<tr>
						<td>
							<select class="time-pick" id="start-time" name="start-time" />
								<optgroup label="AM">
							<?php
									$am = true;
									for($x=0;$x<24;$x++)
									{
										if($x == 12) 
										{
											$am = false; 
											echo "</optgroup><optgroup label='PM'>";
										
										}
										$dHour = ($x == 0) ? 12 : $x;
										$dHour = ($am || $x ==12) ? $dHour : $x-12;
										$y = str_pad($x,2,"0",STR_PAD_LEFT);	
									?>
										<option value="<?=$y;?>:00" <?=self::selected($y.":00",$meta['startTime'][0]);?>><?=$dHour;?>:00</option>
										<option value="<?=$y;?>:30" <?=self::selected($y.":30",$meta['startTime'][0]);?>><?=$dHour;?>:30</option>
									<?php
									}
							?>
								</optgroup>
							</select>
						</td>
						<td>
							<select class="time-pick" id="end-time" name="end-time" />
								<optgroup label="AM">
								<?php
										$am = true;
										for($x=0;$x<24;$x++)
										{
											if($x == 12) 
											{
												$am = false; 
												echo "</optgroup><optgroup label='PM'>";
											
											}
											$dHour = ($x == 0) ? 12 : $x;
											$dHour = ($am || $x ==12) ? $dHour : $x-12;
											$y = str_pad($x,2,"0",STR_PAD_LEFT);
										?>
											<option value="<?=$y;?>:00" <?=self::selected($y.":00",$meta['endTime'][0]);?>><?=$dHour;?>:00</option>
											<option value="<?=$y;?>:30" <?=self::selected($y.":30",$meta['endTime'][0]);?>><?=$dHour;?>:30</option>
										<?php
										}
								?>
								</optgroup>
							</select>
						</td>
						<td>
							<input type="radio" class="featured" name="featured" value="true" <?php if($meta['featured']=='true'){echo "checked"; }?>> Yes
							<input type="radio" class="featured" name="featured" value="" <?php if($meta['featured']!= 'true'){echo "checked"; }?>>No<br>
						</td>
					</tr>
					<tr class="odd">
						<th><label for="event-location">Event Location</label></th>
						<th><label>Repeats</label></th>
						<th><label>Repeats on</label></th>
					</tr>
					<tr>
						<td>
							<input type="text" id="event-location" name="event-location" value="<?=$meta['event-location'][0];?>" size="25" />
						</td>
						<td>
							<select id="repeat-selection">
								<option value="never">Never</option>
								<option value="daily">Daily</option>
								<option value="monthly">Monthly</option>
							</select>
						</td>
					
					</tr>
				</tbody>
			</table>
			<?php 
			require(WP_PLUGIN_DIR.'/'.dirname( plugin_basename( __FILE__ ) ).'/date-popups.php'); ?>
			<span class="clear"></span>
		  <?php
		}
		
		function selected($x,$y)
		{
			if($x==$y){
				return "selected='true'";
			}else{
				return;
			}
		}
		
		/* When the post is saved, saves our custom data */
		function save_events_data( $post_id ) {
			if(get_post_type($post_id) != "event")
				return;
			if ( !wp_verify_nonce( $_POST['event-meta'], plugin_basename(__FILE__) )) {
			 	return $post_id;
			}
			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
				return $post_id;
			if ( 'page' == $_POST['post_type'] ) {
				if ( !current_user_can( 'edit_page', $post_id ) )
			   	return $post_id;
			} else {
				if ( !current_user_can( 'edit_post', $post_id ) )
			   	return $post_id;
			}
			// OK, we're authenticated: we need to find and save the data
			$mypost = get_post($post_id);
			$startDate = date('Y-m-d', strtotime($_POST['start-date']));
			$startTime = $_POST['start-time'];
			$featured = $_POST['featured'];
			$endDate = date('Y-m-d', strtotime($_POST['end-date']));
			$endTime = $_POST['end-time'];
			$location = $_POST['event-location'];
			$allDay = $_POST['all-day'];
			update_post_meta($post_id,'startDate',$startDate);
			update_post_meta($post_id,'startTime',$startTime);
			update_post_meta($post_id,'featured',$featured);
			update_post_meta($post_id,'endDate',$endDate);
			update_post_meta($post_id,'endTime',$endTime);
			update_post_meta($post_id,'event-location',$location);
			update_post_meta($post_id,'all-day',$allDay);
			$eventID = get_post_meta($post_id,'event-id',true);
			$eventID = self::updateEvent(self::$client,$eventID,$mypost->post_title,$mypost->post_content, $location, $startDate, $startTime,$endDate, $endTime, $allDay);
			update_post_meta($post_id,'event-id',$eventID);
			return;
		}
		
		function untrash_data( $post_id ) {
			if(get_post_type($post_id) != "event")
				return;
			// OK, we're authenticated: we need to find and save the data
			$mypost = get_post($post_id);
			$startDate = get_post_meta($post_id,'startDate',true);
			$startTime = get_post_meta($post_id,'startTime',true);
			$endDate = get_post_meta($post_id,'endDate',true);
			$endTime = get_post_meta($post_id,'endTime',true);
			$location = get_post_meta($post_id,'event-location',true);
			$allDay = get_post_meta($post_id,'all-day',true);
			$featued =get_post_meta($post_id,'featured',true); 
			$eventID = null;
			$eventID = self::updateEvent(self::$client,$eventID,$mypost->post_title,$mypost->post_content, $location, $startDate, $startTime,$endDate, $endTime, $allDay);
			update_post_meta($post_id,'event-id',$eventID);
			return;
		}
		
		
		#handle data
		
		function getEvent($client, $eventId) 
		{ 
			$gdataCal = new Zend_Gdata_Calendar($client); 
			$query = $gdataCal->newEventQuery(); 
			$query->setUser('default'); 
			$query->setVisibility('private'); 
			$query->setProjection('full'); 
			$query->setEvent($eventId); 
			try { 
				$eventEntry = $gdataCal->getCalendarEventEntry($query); 
				return $eventEntry; 
			} catch (Zend_Gdata_App_Exception $e) {
				#var_dump($e); 
				return null; 
			} 
		}
		 
		function getClient()
		{
			$this->user = get_option('username');
			$this->pass = get_option('password');

			self::$service = Zend_Gdata_Calendar::AUTH_SERVICE_NAME; // predefined service name for calendar
			$client = Zend_Gdata_ClientLogin::getHttpClient($this->user,$this->pass,self::$service);
			return $client;
		}
		
		
		function updateEvent ($client, $eventId = "", $title,$desc, $where, $startDate, $startTime,$endDate, $endTime, $allDay) 
		{
			$gdataCal = new Zend_Gdata_Calendar($client);
		   #update event
			if($eventId != "" && $eventId != null)
			{
				if ($event = self::getEvent($client, $eventId)) {
					$event->title = $gdataCal->newTitle($title);
					$event->where = array($gdataCal->newWhere($where));
					$event->content = $gdataCal->newContent("$desc");
					
					$when = $gdataCal->newWhen();
					if($allDay)
					{
						$when->startTime = "{$startDate}";
						$when->endTime = "{$endDate}";
					}else{
						$when->startDate = "{$startDate}T{$startTime}:00.000".self::$tzOffset.":00";
						$when->endDate = "{$endDate}T{$endTime}:00.000".self::$tzOffset.":00";
					}
					$event->when = array($when);
					
					try {
						$event->save();
					} catch (Zend_Gdata_App_Exception $e) {
					#var_dump($e);
					return null;
				}
				return $eventId;
				} else {
				return null;
				}
			}else{
				#not previous id so create new
				$event = $gdataCal->newEventEntry();
		  
				$event->title = $gdataCal->newTitle($title);
				$event->where = array($gdataCal->newWhere($where));
				$event->content = $gdataCal->newContent("$desc");
				
				$when = $gdataCal->newWhen();
				if($allDay)
				{
					$when->startTime = "{$startDate}";
					$when->endTime = "{$endDate}";
				}else{
					$when->startTime = "{$startDate}T{$startTime}:00.000".self::$tzOffset.":00";
					$when->endTime = "{$endDate}T{$endTime}:00.000".self::$tzOffset.":00";
				}
				$event->when = array($when);
				
				// Upload the event to the calendar server
				// A copy of the event as it is recorded on the server is returned
				$createdEvent = $gdataCal->insertEvent($event);
			 	return substr($createdEvent->id->text,58);
			}
		}
		
		function remove_event_from_cal($post_id) {
			if(get_post_type($post_id) != "event")
				return;
			$eventID = get_post_meta($post_id,'event-id',true);
			if(!empty($eventID))
			{
				if ($event = self::getEvent(self::$client, $eventId)) {
					$event->delete();
				}
			}
		}
		
		function jcalendar_create_menu() {
			//create new top-level menu
			add_menu_page('jCalendar Plugin Settings', 'jCalendar Settings', 'administrator', __FILE__,array( &$this, 'jcalendar_settings_page'),plugins_url('icon.png', __FILE__));
			//call register settings function
			add_action( 'admin_init', array( &$this,'register_mysettings' ));
		}
		
		function register_mysettings() {
			//register our settings
			register_setting( 'jcalendar-settings-group', 'username' );
			register_setting( 'jcalendar-settings-group', 'password' );
			register_setting( 'jcalendar-settings-group', 'view-style');
		}
		
		function jcalendar_settings_page() {		
			if(get_option('view-style') == 'tooltip')
			{
				$tt = "checked";
			}else{
				$el = "checked";
			}
		?>
			<div class="wrap">
				<h2>Your Plugin Name</h2>
				
				<form method="post" action="options.php">
					<?php settings_fields( 'jcalendar-settings-group' ); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">Google Calendar Username</th>
							<td><input type="text" name="username" value="<?php echo get_option('username'); ?>" /></td>
						</tr>
					
						<tr valign="top">
							<th scope="row">Google Calendar Password</th>
							<td><input type="password" name="password" value="<?php echo get_option('password'); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row">View Style</th>
							<td>
								Event List: <input type="radio" name="view-style" value="event-list" <?php echo $el; ?>><br>
								Tooltip: <input type="radio" name="view-style" value="tooltip" <?php echo $tt; ?>><br>
							</td>
						</tr>
					</table>
					<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
					</p>
				
				</form>
			</div>
<?php }

		
	}#end class

	$jcal = new jCalendar();
}#end class check











if (!class_exists( 'jCalendarDisplay' ) ) {

	class jCalendarDisplay extends jCalendar
	{
		protected static $calendar;
		protected static $output;
		protected $events;
		protected $today;
		protected $nextMonth;
		protected $prevMonth;
		protected $className;
		protected $limit;
		protected $eventPosts;
		protected $featuredPosts;
		function jCalendarDisplay($content)
		{
			self::$calendar = new Zend_Gdata_Calendar(parent::$client);
			add_filter('the_content',array( &$this, 'displayCalendar' ));
		}
		
		function displayCalendar($content)
		{
			
			if (preg_match('/({CALENDAR)(.*)}/U',$content,$matches))
			{
				#match 0 is our full replace
				#match 2 is our settings
				if(preg_match_all("/DATE=(.*)\s/U",$content,$date)===false){
					$date = "today";
				}else{	
					$date = $date[1][0];
				}
				if(preg_match_all("/CLASS=([a-zA-Z0-9]+)[\s}]/",$content,$classMatch)===false){ 
					$this->className = "test"; 
				}else{
					$this->className = $classMatch[1][0];
				}
				if(preg_match_all("/LIMIT=([0-9]*)\s/",$content,$limitMatch)===false){ 
					$this->limit = 3; 
				}else{
					$this->limit = $classMatch[1][0];
				}
				$this->today = getdate(strtotime($date));
				$this->nextMonth = getdate(strtotime($date." +1 Month"));
				$this->prevMonth = getdate(strtotime($date." -1 Month"));
				
				#grab our posts so we can link them back to the post pages
				$this->featuredPosts = get_posts('post_type=event&meta_key=featured&meta_value=true');
				global $wpdb;
				$querystr = "
				SELECT wposts.*
				FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta
				WHERE wposts.ID = wpostmeta.post_id
				AND wpostmeta.meta_key = 'startDate'
				AND MONTH(wpostmeta.meta_value) = ".$this->today['mon']."
				AND wposts.post_status = 'publish'
				AND wposts.post_type = 'event'
				ORDER BY wposts.post_date DESC";
				$eposts = $wpdb->get_results($querystr, OBJECT);
				foreach($eposts as $post){
					$event = new stdClass();
					$event->eventID = get_post_meta($post->ID,'event-id',true);
					$event->ID = $post->ID;
					$event->link = $post->guid;
					$this->eventPosts[] = $event;
					unset($event);
				}
				
				self::createCalendar();

				$content = str_replace($matches[0],self::$output,$content);
			}
			return $content;
		}
		
		function moreText($text){
			$words = explode(' ', $text);
			if (count($words)> 41) {
				$teaser = array_slice($words,0,41);
				$teaser = implode(" ",$teaser)."&hellip; ";
				$full = array_slice($words,42);
				$full = $teaser."<span>".implode(" ",$full)."</span>";				
			}
			return $full;
		}
		
		#checks if there is an event on the current day. Returns html of the td
		function getCalendarDay($day)
		{
			if($day == $this->today['mday']){$today = "today";}
			$curDay = getDate(strtotime($this->today['year']."-".$this->today['mon']."-".$day));
			$curDay = $curDay['weekday'];
			$list = "";
			foreach($this->events as $event)
			{
				foreach($event->when as $when)
				{
					if(substr($when->startTime, 0, 10) == $this->today['year']."-".str_pad($this->today['mon'],2,"0",STR_PAD_LEFT)."-".str_pad($day,2,"0",STR_PAD_LEFT))
					{
						$link = $this->getEventLink((string)$event->id);
						if($link){
							$list .="<li><h3><a href='".$link."'>".(string)$event->title."</a></h3><p class='expands' >".$this->moreText($event->content->text)."</p></li>";
						}else{
							$list .="<li><h3>".(string)$event->title->text."</h3><p class='expands' >".$this->moreText($event->content->text)."</p></li>";
						}
					}
				}
			}
			if(!empty($list))
			{ 
				$td = "<td class='has-events $today' date='".$curDay.",  ".$this->today['month']." ".$day."'>$day <div><ul><li class='title'><h2>".$curDay.",  ".$this->today['month']." ".$day."</h2></li>";
				$td .="$list</ul></div></td>";
				return  $td;
			}else{
				return "<td class='$today' date='".$curDay.",  ".$this->today['month']." ".$day."'>$day</td>";
			}
		}
		
		function createCalendar()
		{
			self::getEventsForMonth($this->today['mon']);
			$firstDay = getdate(mktime(0,0,0,$this->today['mon'],1,$this->today['year']));
			$lastDay  = getdate(mktime(0,0,0,$this->today['mon']+1,0,$this->today['year']));
			$nurl = urlencode('{CALENDAR DATE='.$this->nextMonth['mon'].'/01/'.$this->nextMonth['year'].' CLASS='.$this->className.' }');
			$purl = urlencode('{CALENDAR DATE='.$this->prevMonth['mon'].'/01/'.$this->prevMonth['year'].' CLASS='.$this->className.' }');

			ob_start();
			?>
	<div id="jcal-wrap">
		<div id="featured-event" class="<?=$this->className;?>"><?=$this->getFeaturedEvents();?></div>
		<table id="jcal" class="<?=$this->className;?>" cellspacing="0" limit="<?=$this->limit;?>">
			<thead>
				<tr>
					<th class='previous'><a href='<?=$purl;?>'>&laquo;</a></th>
					<th class='full' colspan='5'><?=$this->today['month'];?></th>
					<th class='next'><a href='<?=$nurl;?>'>&raquo;</a></th>
				</tr>
				<tr>
					<th>Sun</th><th>Mon</th><th>Tue</th>
					<th>Wed</th><th>Thu</th><th>Fri</th>
					<th>Sat</th>
				</tr>
			</thead>
			<tbody>
				<tr>
<?php 		#first row
				for($i=0;$i<$firstDay['wday'];$i++){
			   	echo "<td>&nbsp;</td>";
				}
				$actday = 0;
				for($i=$firstDay['wday'];$i<7;$i++)
				{
					$actday++;
					echo self::getCalendarDay($actday);
				}
				echo "
				</tr>";
				
				#full weeks of the month
				$fullWeeks = floor(($lastDay['mday']-$actday)/7);
			   for ($i=0;$i<$fullWeeks;$i++)
			   { 	
					echo "<tr>";
		 			for ($j=0;$j<7;$j++)
					{
						$actday++;
						echo self::getCalendarDay($actday);
					}
					echo "
					</tr>";
			    }
			
				#display the end of the calendar
				if ($actday < $lastDay['mday'])
				{
					echo '<tr>';
					
					for ($i=0; $i<7;$i++){
						$actday++;					
						if ($actday <= $lastDay['mday']){
							echo self::getCalendarDay($actday);
						}else {
							echo '<td>&nbsp;</td>';
						}
					}
				}
				echo "
				</tr>
				</tbody></table>
				<div class='".$this->className."' id='event-list'><ul></ul></div>
				</div>
				";
				self::$output = ob_get_clean();
		}#end create calendar
		
		public function display($content){
			echo $this->displayCalendar($content);
		}
		
		public function getEventsForMonth($month){
			$user = get_option('username');

			$query = self::$calendar->newEventQuery();
			$query->setOrderby('starttime');
			$query->setUser($user);
			$query->setVisibility('private');
			$query->setProjection('full');
			$firstDay = date('Y-m-d',mktime(0,0,0,$this->today['mon'],1,$this->today['year']));
			$lastDay  = date('Y-m-d',mktime(0,0,0,$this->today['mon']+1,0,$this->today['year']));
			$query->setStartMin($firstDay);
			$query->setStartMax($lastDay);

			$this->events = self::$calendar->getCalendarEventFeed($query);
		}
		
		protected function getFeaturedEvents(){
			foreach($this->featuredPosts as $post){
				$list .= '<h3><a href="'.$post->guid.'">'.$post->post_title.'</a></h3><p>'.$post->post_content.'</p>';
			}
			return $list;
		}
		
		protected function getEventLink($id){
			foreach($this->eventPosts as $post){
				if(strpos($id,$post->eventID) !== false){
					return $post->link;
				}
			}
			return false;
		}
	}#end Class
	$cal = new jCalendarDisplay();
}#end Class Check


// create custom plugin settings menu


