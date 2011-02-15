<div class="daily-pop pops" style="display:none;">
	<table class="ep-rec">
		<tbody>
			<tr>
				<th>Repeats:</th>
				<td>Daily</td>
			</tr><tr>
				<th>Repeat every:</th>
				<td>
					<select name='repeats'>
						<?php for($x=1;$x<=30;$x++): ?>
						<option value="<?php echo $x; ?>"><?php echo $x; ?></option>
						<?php endfor; ?>
					</select>
					<label>days</label>
				</td>
			</tr><tr>
				<th>Starts on:</th>
				<td>
					<input name='start'>
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
						<input name="end">
					</div>
				</td>
			</tr><tr>
				<th>Summary:</th>
				<td class="summary">Every 1 day</td>
			</tr>
		</tbody>
	</table>
	<button>Done</button>
	<button>Cancel</button>
</div>