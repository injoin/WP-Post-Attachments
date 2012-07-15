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
	 * List of methods that are WP actions.
	 *
	 * @since   0.0.1a
	 * @var     array
	 */
	private $actions = array(
		'add_meta_boxes', 'admin_print_styles', 'admin_enqueue_scripts',
		'wp_ajax_ij_realign'
	);

	/**
	 * The URL to the plugin directory
	 *
	 * @since   0.0.1a
	 * @var     string
	 */
	private $pluginURL;

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

		$this->pluginURL = plugin_dir_url(__FILE__);
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

	public function admin_enqueue_scripts()
	{
		global $hook_suffix;
		if ($hook_suffix == 'post.php')
		{
			wp_enqueue_script('syoHintUtils', $this->pluginURL . 'scripts/utils.js', array(), '1.0.10');
			wp_enqueue_script('syoHint', $this->pluginURL . 'scripts/jquery.syoHint.js', array('jquery', 'syoHintUtils'), '1.0.10');
			wp_enqueue_script('ij-post-attachments', $this->pluginURL . 'scripts/ij-post-attachments.js', array('syoHint', 'jquery-ui-sortable'), '0.0.1');

			wp_localize_script('ij-post-attachments', 'IJ_Post_Attachments_Vars', array('editMedia' => __('Edit Media')));
		}
	}

	public function admin_print_styles()
	{
		global $hook_suffix;
		if ($hook_suffix == 'post.php')
			wp_enqueue_style('ij-post-attachments', $this->pluginURL . 'styles/ij-post-attachments.css', array(), '0.0.1');
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
		?>
		<p>
			<a href="<?php echo admin_url('media-upload.php?post_id=' . $post->ID); ?>&TB_iframe=1&width=640&height=693"
				onclick="return false" class="button-primary thickbox">
				<?php _e('Add Media'); ?>
			</a>
		</p>
		<?php
		$attachments = new WP_Query(array(
			'post_parent'   => $post->ID,
			'post_type'     => 'attachment',
			'post_status'   => 'any',
			'orderby'       => 'menu_order',
			'order'         => 'ASC'
		));

		if ($attachments->have_posts())
		{
			?>
			<div id="ij-post-attachments" class="ij-post-attachment-list">
				<ul>
				<?php while ($attachments->have_posts()): $atchment = $attachments->next_post(); ?>
					<li class="ij-post-attachment"
					    data-mimetype="<?php echo $atchment->post_mime_type; ?>"
					    data-alt="<?php echo get_post_meta(611, '_wp_attachment_image_alt', true); ?>"
					    data-attachmentid="<?php echo $atchment->ID; ?>"
					    data-url="<?php echo wp_get_attachment_url($atchment->ID); ?>"
						data-title="<?php echo $atchment->post_title; ?>">
						<div class="ij-post-attachment-title" title="<?php echo $atchment->post_title; ?>">
							<a href="<?php echo wp_get_attachment_url($atchment->ID); ?>" class="ij-post-attachment-edit">
								<strong><?php echo (strlen($atchment->post_title) > 16) ? (substr($atchment->post_title, 0, 16) . '...') : $atchment->post_title; ?></strong>
							</a>
							(<?php echo strtoupper(array_pop(explode('.', get_attached_file($atchment->ID)))); ?>)
						</div>
						<?php echo wp_get_attachment_image($atchment->ID, array(80, 60), true); ?>
						<div style="float:left">
							<a href="#" class="ij-post-attachment-insert"><?php _e('Insert'); ?></a><br />
							<a href="<?php echo wp_get_attachment_url($atchment->ID); ?>" class="ij-post-attachment-edit"><?php _e('Edit'); ?></a><br />
							<a href="<?php echo wp_nonce_url(admin_url('post.php') . '?action=delete&post=' . $atchment->ID, 'delete-attachment_' . $atchment->ID); ?>" class="ij-post-attachment-delete"><?php _e('Remove'); ?></a>
						</div>
					</li>
				<?php endwhile; ?>
				</ul>
				<div class="clear"></div>
			</div>
			<?php
		}
		else
		{
			// Nothing found, let's go the easier way
			echo "<p>" . __('No media attachments found.') . "</p>";
		}
	}

	/**
	 * Initialize the attachment edit pop-up.
	 *
	 * @since   0.0.1a
	 * @param   int $ID
	 * @return  void
	 */
	public function showAttachmentEdit($ID)
	{
		add_action('admin_head-media-upload-popup', array($this, 'attachmentEditHeadIframe'));
		wp_iframe(array($this, 'attachmentEditIframe'));
	}

	/**
	 * Output the script/link tags needed by the edit iframe
	 *
	 * @since   0.0.1a
	 * @return  void
	 */
	public function attachmentEditHeadIframe()
	{
		// I don't know if all these scripts are really needed by the media edit screen.
		// They're just there, so they'll be here too :P
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
		$url    = admin_url('media.php');
		$id     = $_REQUEST['attachment_id'];
		?>
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
		<?php
	}

	/**
	 * Re-align attachments.
	 *
	 * @since   0.0.1a
	 * @return  void
	 */
	public function wp_ajax_ij_realign()
	{
		header('Content-Type: text/plain');

		$alignment = $_REQUEST['alignment'];
		if (!is_array($alignment))
			$alignment = array_map('trim', explode(',', $alignment));

		$alignment = array_values($alignment);
		$count = count($alignment);

		for ($i = 0; $i < $count; $i++)
		{
			if (!is_numeric($alignment[$i]))
				continue;

			$attachment = get_post($alignment[$i]);
			$attachment->menu_order = $i;
			wp_update_post($attachment);
		}
	}

}

// Direct access + editing image
require_once(dirname(__FILE__) . '/../../../wp-load.php');
$IJ_Post_Attachments = IJ_Post_Attachments::getInstance();
if (strpos(str_replace('\\', '/', __FILE__), $_SERVER['PHP_SELF']) && isset($_REQUEST['attachment_id']))
{
	require_once(dirname(__FILE__) . '/../../../wp-admin/admin.php');
	$IJ_Post_Attachments->showAttachmentEdit($_REQUEST['attachment_id']);
}