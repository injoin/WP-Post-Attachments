	<p>
		<a href="<?php echo admin_url('media-upload.php?post_id=' . $post->ID); ?>&TB_iframe=1&width=640&height=693"
			onclick="return false" class="button-primary thickbox">
			<?php _e('Add Media'); ?>
		</a>
	</p>
	<?php if ($attachments->have_posts()): ?>
	<div id="ij-post-attachments" class="ij-post-attachment-list">
		<ul class="alignleft">
			<?php while ($attachments->have_posts()): $atchment = $attachments->next_post(); ?>
			<li class="ij-post-attachment"
			    data-mimetype="<?php echo $atchment->post_mime_type; ?>"
			    data-alt="<?php echo esc_attr(get_post_meta(611, '_wp_attachment_image_alt', true)); ?>"
			    data-attachmentid="<?php echo $atchment->ID; ?>"
			    data-url="<?php echo wp_get_attachment_url($atchment->ID); ?>"
			    data-title="<?php echo esc_attr($atchment->post_title); ?>">

				<!--Item title-->
				<div class="ij-post-attachment-title" title="<?php echo esc_attr($atchment->post_title); ?>">
					<a href="<?php echo wp_get_attachment_url($atchment->ID); ?>" class="ij-post-attachment-edit">
						<strong><?php echo (strlen($atchment->post_title) > 22) ? (substr(esc_html($atchment->post_title), 0, 22) . '...') : esc_html($atchment->post_title); ?></strong>
					</a>
				</div>

				<!--Item body-->
				<div style="padding:1px 5px 5px">
					<div class="ij-post-attachment-type">
						<?php echo strtoupper(str_replace('image/', '', get_post_mime_type($atchment->ID))); ?>
					</div>
					<?php echo wp_get_attachment_image($atchment->ID, array(80, 60), true); ?>
					<div style="float:left">
						<a href="#" class="ij-post-attachment-insert"><?php _e('Insert'); ?></a><br />
						<a href="<?php echo wp_get_attachment_url($atchment->ID); ?>" class="ij-post-attachment-edit"><?php _e('Edit'); ?></a><br />
						<a href="<?php echo wp_nonce_url(admin_url('post.php') . '?action=delete&post=' . $atchment->ID, 'delete-attachment_' . $atchment->ID); ?>" class="ij-post-attachment-delete"><?php _e('Remove'); ?></a>
					</div>
				</div>
			</li>
			<?php endwhile; ?>
		</ul>
		<div class="clear"></div>
	</div>
	<?php else: ?>
	<p><?php _e('No media attachments found.'); ?></p>
	<?php endif; ?>
