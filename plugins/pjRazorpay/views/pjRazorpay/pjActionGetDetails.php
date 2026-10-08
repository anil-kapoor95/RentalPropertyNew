<?php
if (isset($tpl['arr']) && !empty($tpl['arr']))
{
	?>
	<div class="form pj-form">
		<p>
			<label class="title bold"><?php __('plugin_razorpay_foreign_id'); ?></label>
			<span class="left"><?php echo $tpl['arr']['foreign_id']; ?></span>
		</p>
		<p>
			<label class="title bold"><?php __('plugin_razorpay_razorpay_id'); ?></label>
			<span class="left"><?php echo $tpl['arr']['razorpay_id']; ?></span>
		</p>
		<p>
			<label class="title bold"><?php __('plugin_razorpay_order_id'); ?></label>
			<span class="left"><?php echo $tpl['arr']['order_id']; ?></span>
		</p>
		<p>
			<label class="title bold"><?php __('plugin_razorpay_amount'); ?></label>
			<span class="left"><?php echo $tpl['arr']['amount']; ?></span>
		</p>
		<p>
			<label class="title bold"><?php __('plugin_razorpay_currency'); ?></label>
			<span class="left"><?php echo $tpl['arr']['currency']; ?></span>
		</p>
		<p>
			<label class="title bold"><?php __('plugin_razorpay_description'); ?></label>
			<span class="left"><?php echo $tpl['arr']['description']; ?></span>
		</p>
		<p>
			<label class="title bold"><?php __('plugin_razorpay_created'); ?></label>
			<span class="left"><?php echo $tpl['arr']['created']; ?></span>
		</p>
	</div>
	<?php
}
?>
