<div class="daily-pop pops" style="display:none;">
	<table class="ep-rec">
		<tbody>
			<tr>
				<th>Repeats:</th>
				<td>Daily
					<input type="hidden" name="repeats" value="DAILY">
					<input type="hidden" name="repeats-nice" value="day(s)"></td>
			</tr><tr>
				<th>Repeat every:</th>
				<td>
					<select name='interval'>
						<?php for($x=1;$x<=30;$x++): ?>
						<option value="<?php echo $x; ?>"><?php echo $x; ?></option>
						<?php endfor; ?>
					</select>
					<label>days</label>
				</td>
			</tr><tr>
				<th>Starts on:</th>
				<td>
					<input class="recur-start" name='start'>
				</td>
			</tr><tr>
				<th>Ends on:</th>
				<td>
					<div><span>
						<input name="end-opt" type="radio" checked="" value="never">
						<label>Never</label>
					</span></div>
					<div>
						<span>
							<input name="end-opt" type="radio" value="until">
							<label>Until:</label>
						</span>
						<input class="recur-end" name="end">
					</div>
				</td>
			</tr><tr>
				<th>Summary:</th>
				<td class="summary">Every 1 day</td>
			</tr><tr>
				<td><button class="done-repeat">Done</button><button class="done-cancel">Cancel</button></td>
			</tr>
		</tbody>
	</table>
</div>