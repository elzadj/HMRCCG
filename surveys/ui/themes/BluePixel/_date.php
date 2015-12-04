<form id="<?php echo FORM_ID; ?>" action="system/process_date.php" method="post" accept-charset="utf-8">

	<div class="sc_question">
		<div class="fieldset sc_text">
			<div class="title">Date and time of completion</div>
			<div class="inputerrors"></div>
			<?php
			$t    = time();
			$dd   = date('d', $t);
			$mm   = date('m', $t);
			$yyyy = date('Y', $t);
			$hour = date('H', $t);
			$min  = date('i', $t);
			?>
			<p>
				Date:
				<input class="shortfield" type="number" min="1" max="31" name="manual_time_day" value="<?php echo $dd; ?>" />
				/
				<input class="shortfield" type="number" min="1" max="12" name="manual_time_month" value="<?php echo $mm; ?>" />
				/
				<input class="yearfield" type="number" min="2010" max="<?php echo $yyyy; ?>" name="manual_time_year" value="<?php echo $yyyy; ?>" />
			</p>
			<p>
				Time:
				<input class="shortfield" type="number" min="0" max="23" name="manual_time_hour" value="<?php echo $hour; ?>" />
				.
				<input class="shortfield" type="number" min="0" max="59" name="manual_time_min" value="<?php echo $min; ?>" />
			</p>
		</div>
	</div>	<!--END sc_question-->

</form>
