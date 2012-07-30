	<form action="<?php echo $url; ?>" method="post" style="padding:0 10px 10px" class="media-item">
		<div class="submit"><input type="submit" class="button-primary" value="<?php _e('Update Media'); ?>" /></div>
		<input type="hidden" name="action" value="editattachment" />
		<input type="hidden" name="attachment_id" value="<?php echo $id; ?>" />
		<?php
		wp_nonce_field('media-form');
		wp_original_referer_field(true, 'current');

		echo get_media_item($id, array(
			'toggle'        => false,
			'show_title'    => false
		));
		?>
		<div class="submit"><input type="submit" class="button-primary" value="<?php _e('Update Media'); ?>" /></div>
	</form>