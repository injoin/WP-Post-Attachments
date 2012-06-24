<?php
/**
 * InJoin Post Attachments
 * This is a simple WordPress plugin that will list the attachments of a post while editing it.
 *
 * @filesource
 * @since       0.0.1a
 * @version     0.0.1a
 * @package     InJoin
 * @subpackage  Post Attachments
 */

/*
Plugin Name: IJ Post Attachments
Plugin URI: http://www.injoin.com.br
Description: This is a simple WordPress plugin that will list the attachments of a post while editing it.
Author: Gustavo Henke
Version: 0.0.1a
Author URI: http://www.injoin.com.br
*/

class IJ_Post_Attachments
{

	/**
	 * @since   0.0.1a
	 * @var     array
	 */
	private $actions = array('add_meta_boxes');

	/**
	 * The singleton instance of this class
	 *
	 * @since   0.0.1a
	 * @var     IJ_Post_Attachments
	 */
	private static $instance;

	/**
	 * Constructor
	 *
	 * @since   0.0.1a
	 * @return  IJ_Post_Attachments
	 */
	private function __construct()
	{
		foreach ($this->actions as $action)
			add_action($action, array($this, $action));
	}

	/**
	 * Singleton access for the class
	 *
	 * @static
	 * @since   0.0.1a
	 * @return  IJ_Post_Attachments
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
			self::$instance = new IJ_Post_Attachments();

		return self::$instance;
	}

	/**
	 * Disallows cloning this class
	 *
	 * @since   0.0.1a
	 * @throws  Exception
	 * @return  void
	 */
	public function __clone()
	{
		throw new Exception("Clone is disallowed.");
	}

	/**
	 * Add the plugin meta box
	 *
	 * @since   0.0.1a
	 * @return  void
	 */
	public function add_meta_boxes()
	{
		add_meta_box('ij-post-attachments', __('Images and Attachments'), array($this, 'printMetaBox'), null, 'normal', 'high');
	}

	/**
	 * Create the meta box below the post editor and list the files
	 *
	 * @since   0.0.1a
	 * @param   object $post
	 * @return  void
	 */
	public function printMetaBox($post)
	{
		$attachments = new WP_Query(array(
			'post_parent'   => $post->ID,
			'post_type'     => 'attachment',
			'post_status'   => 'any'
		));

		if ($attachments->have_posts())
		{
			?>
			<div class="ij-post-attachment-list">
				<ul>
				<?php while ($attachments->have_posts()): $attachments->the_post(); ?>
					<li class="ij-post-attachment">
						<a href="<?php echo wp_get_attachment_thumb_url(get_the_ID()); ?>" onclick="return ShowTB_Attachment(this, <?php the_ID(); ?>);" class="ij-post-attachment-title">
							<strong><?php the_title(); ?></strong>
						</a>
						<?php echo wp_get_attachment_image(get_the_ID(), array(80, 60), true); ?>
						<div style="float:left">
							<?php echo strtoupper(array_pop(explode('.', get_attached_file(get_the_ID())))); ?>
							<br />
							<a href="<?php echo wp_get_attachment_thumb_url(get_the_ID()); ?>" onclick="return ShowTB_Attachment(this, <?php the_ID(); ?>);"><?php _e('Edit'); ?></a><br />
							<a href="<?php echo wp_nonce_url(admin_url('post.php') . '?action=delete&post=' . get_the_ID(), 'delete-attachment_' . get_the_ID()); ?>" onclick="return Remove_Attachment(this)"><?php _e('Remove'); ?></a>
						</div>
					</li>
				<?php endwhile; ?>
				</ul>
				<div class="clear"></div>
			</div>
			<script type="text/javascript">
			function ShowTB_Attachment(obj, ID) {
				tb_show('<?php _e('Edit Media'); ?>',  '<?php echo plugin_dir_url(__FILE__); ?>ij-post-attachments.php?width=630&height=440&attachment_id=' + ID + '&TB_iframe=1');

				return false;
			}

			function Remove_Attachment(obj) {
				jQuery.ajax({
					url  : jQuery(obj).attr('href'),
					data : { _wp_http_referer   : '<?php echo plugin_dir_url(__FILE__); ?>ij-post-attachments.php' }
				}).done(function(ret) {
					if (!ret)
						jQuery(obj.parentNode).fadeOut(300, function() {
							jQuery(this).remove();
						});
				});
				return false;
			}
			</script>
			<style type="text/css">
				.ij-post-attachment {
					float: left;

					width: 160px;
					max-width: 160px;
					height: 102px;
					max-height: 102px;

					margin: 0 10px 10px 0;
				}

				.ij-post-attachment-title {
					display: block;
					margin-bottom: 3px;
				}

				.ij-post-attachment img {
					float: left;
					margin-right: 5px;
				}
			</style>
			<?php

			wp_reset_postdata();
		}
		else
		{
			// Nothing found, let's go the easier way
			echo "<p>" . __('No media attachments found.') . "</p>";
		}
	}

	/**
	 * @since   0.0.1a
	 * @param   int $ID
	 * @return  void
	 */
	public function showAttachmentEdit($ID)
	{
		add_action('admin_head-media-upload-popup', array($this, 'attachmentIframePopup'));
		wp_iframe(array($this, 'attachmentEditIframe'));
	}

	/**
	 * Output the script/link tags needed by the edit iframe
	 *
	 * @since   0.0.1a
	 * @return  void
	 */
	public function attachmentIframePopup()
	{
		?>
		<link rel="stylesheet" type="text/css" href="<?php echo site_url('wp-includes/js/imgareaselect/imgareaselect.css'); ?>" />
		<script type="text/javascript" src="<?php echo admin_url('load-scripts.php?load=jquery-color,imgareaselect,image-edit,wp-ajax-response'); ?>"></script>
		<?php
	}

	/**
	 * Echoes the content of the edit iframe
	 *
	 * @since   0.0.1a
	 * @return  void
	 */
	public function attachmentEditIframe()
	{
		$url = admin_url('media.php');
		$id = $_REQUEST['attachment_id'];

		echo "<form action=\"{$url}\" method=\"post\" style=\"padding:0 10px 10px\" class=\"media-item\">";
		echo "<div class=\"submit\"><input type=\"submit\" class=\"button-primary\" value=\"" . __('Update Media') . "\" /></div>";
		echo "<input type=\"hidden\" name=\"action\" value=\"editattachment\" />";
		echo "<input type=\"hidden\" name=\"attachment_id\" value=\"{$id}\" />";
		wp_nonce_field('media-form');
		wp_original_referer_field(true, 'current');
		echo get_media_item($id, array(
			'toggle'        => false,
			'show_title'    => false
		));
		echo "<div class=\"submit\"><input type=\"submit\" class=\"button-primary\" value=\"" . __('Update Media') . "\" /></div>";
		echo "</div>";
	}

}

// Direct access + editing image
require_once(dirname(__FILE__) . '/../../wp-load.php');
$IJ_Post_Attachments = IJ_Post_Attachments::getInstance();
if (isset($_REQUEST['attachment_id']) && strpos(str_replace('\\', '/', __FILE__), $_SERVER['PHP_SELF']))
{
	require_once(dirname(__FILE__) . '/../../wp-admin/admin.php');
	$IJ_Post_Attachments->showAttachmentEdit($_REQUEST['attachment_id']);
}