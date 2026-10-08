<?php
if($tpl['code'] == 200)
{
    ?>
	<div class="form-group" id="boxIcon">
		<label class="col-sm-3 control-label" for="o_accept_bookings"><?php __('lblPropertyImage'); ?></label>
		<div class="col-lg-5 col-sm-7">
			<span class="inline_block">
				<input type="file" name="image" id="image" class="pj-form-field" />
			</span>
		</div>
	</div>
	<?php
}else{
	echo $tpl['code'];
} 
?>