<?php
$use_twitter_reply = 0;
if (is_user_logged_in() and !current_user_can('manage_options')) {
	foreach (Social::$services as $key => $service) {
		if (count($service->accounts())) {
			if ($key == 'twitter') {
				$use_twitter_reply = 1;
			}
			break;
		}
	}
}
?>
<form action="<?php echo site_url('/wp-comments-post.php'); ?>" method="post" id="<?php echo esc_attr($args['id_form']); ?>">
<input type="hidden" id="use_twitter_reply" name="use_twitter_reply" value="0" />
<input type="hidden" id="in_reply_to_status_id" name="in_reply_to_status_id" value="" />
<?php comment_id_fields(); ?>
<?php if (!is_user_logged_in()): ?>
<div class="social-sign-in-links social-clearfix">
	<?php foreach (Social::$services as $key => $service): ?>
	<a class="social-<?php echo $key; ?> social-imr social-login comments" href="<?php echo Social_Helper::authorize_url($key); ?>" id="<?php echo $key; ?>_signin"><?php _e('Sign in with '.$service->title(), Social::$i18n); ?></a>
	<?php endforeach; ?>
</div>
<div class="social-divider">
	<span><?php _e('or', Social::$i18n); ?></span>
</div>
<?php endif; ?>
<div class="social-sign-in-form">
	<?php if (!is_user_logged_in()): ?>
	<div class="social-input-row">
		<label for="social-sign-in-name"><?php _e('Name', Social::$i18n); ?></label>
		<input class="social-input-text" type="text" id="social-sign-in-name" name="author" />
	</div>
	<div class="social-input-row">
		<label for="social-sign-in-email"><?php _e('Email', Social::$i18n); ?></label>
		<input class="social-input-text" type="text" id="social-sign-in-email" name="email" />
		<em id="social-email-notice">We'll kept this private</em>
	</div>
	<div class="social-input-row">
		<label for="social-sign-in-website"><?php _e('Website', Social::$i18n); ?></label>
		<input class="social-input-text" type="text" id="social-sign-in-website" name="url" />
	</div>
	<?php endif; ?>
	<div class="social-input-row">
		<label for="social-sign-in-comment"><?php _e('Comment', Social::$i18n); ?></label>
		<textarea id="social-sign-in-comment" name="comment"></textarea>
	</div>
	<div class="social-input-row">
		<button type="submit" class="social-input-submit" style="float:left;"><span><?php _e('Post It', Social::$i18n); ?></span></button>
		<?php if (is_user_logged_in()): ?>
			<?php if (current_user_can('manage_options')): ?>
				<span style="float:left;margin:4px 10px;">via</span>
				<select id="post_accounts" name="<?php echo Social::$prefix; ?>post_account" style="float:left;">
					<option value=""><?php _e('WordPress Account', Social::$i18n); ?></option>
					<?php foreach (array_merge(Social::$services, Social::$global_services) as $key => $service): ?>
						<?php
							$accounts = Social::$services[$key]->accounts();
							if (isset(Social::$global_services[$key])) {
								foreach (Social::$global_services[$key]->accounts() as $id => $account) {
									$accounts[$id] = $account;
								}
							}
						?>
						<?php if (count($accounts)): ?>
						<optgroup label="<?php _e(ucfirst($key), Social::$i18n); ?>">
							<?php foreach ($accounts as $account): ?>
							<option value="<?php echo $account->user->id; ?>"><?php echo $service->profile_name($account); ?></option>
							<?php endforeach; ?>
						</optgroup>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
				<div id="post_to" style="display:none">
					<label for="post_to_service">
						<input type="checkbox" name="post_to_service" id="post_to_service" value="1" />
						Post to <span></span>
					</label>
				</div>
			<?php else: ?>
				<?php foreach (Social::$services as $key => $service): ?>
					<?php if (count($service->accounts())): ?>
					<?php $account = reset($service->accounts()); ?>
					<span style="float:left;margin:4px 10px;"><?php _e('via', Social::$i18n); ?></span>
					<div style="float:left;margin-top:5px;">
						<span class="social-<?php echo $key; ?>-icon">
							<i></i>
							<?php echo $service->profile_name($account); ?>.
							(<?php echo $service->disconnect_url($account); ?>)
						</span>
					</div>
					<div id="post_to">
						<label for="post_to_service">
							<input type="checkbox" name="post_to_service" id="post_to_service" value="1" />
							Post to <?php echo $service->title(); ?>
						</label>
					</div>
					<input type="hidden" name="<?php echo Social::$prefix; ?>post_account" value="<?php echo $account->user->id; ?>" />
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endif; ?>
		<?php cancel_comment_reply_link(__('Cancel reply', Social::$i18n)); ?>
		<div style="clear:both;"></div>
	</div>
</div>
</form>