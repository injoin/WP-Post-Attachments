<?php
/**
 * InJoin Post Attachments
 * This is a simple WordPress plugin that will list the attachments of a post while editing it.
 *
 * @filesource
 * @since       0.0.1a
 * @version     0.1.0
 * @package     InJoin
 * @subpackage  Post Attachments
 */

/*
Plugin Name: IJ Post Attachments
Plugin URI: http://www.injoin.com.br
Description: This is a simple WordPress plugin that will list the attachments of a post while editing it.
Author: Gustavo Henke
Version: 0.1.0
Author URI: http://www.injoin.com.br
*/

//<editor-fold desc="Constants">
define('IJ_POST_ATTACHMENTS_DIR', dirname(__FILE__));
define('IJ_POST_ATTACHMENTS_URL', plugin_dir_url(__FILE__));
define('IJ_POST_ATTACHMENTS_VER', '0.1.0');
//</editor-fold>

class IJ_Post_Attachments
{

	//<editor-fold desc="Properties">
	/**
	 * List of methods that are WP actions.
	 *
	 * @since   0.0.1a
	 * @var     array
	 */
	private $actions = array(
		'add_meta_boxes', 'admin_print_styles', 'admin_enqueue_scripts',
		'wp_ajax_ij_realign', 'wp_ajax_ij_attachment_edit'
	);

	/**
	 * The singleton instance of this class
	 *
	 * @since   0.0.1a
	 * @var     IJ_Post_Attachments
	 */
	private static $instance;
	//</editor-fold>

	//<editor-fold desc="Basic methods">
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
	static public function getInstance()
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
	//</editor-fold>

	//<editor-fold desc="Metabox">
	/**
	 * Add the plugin meta box
	 *
	 * @since   0.0.1a
	 * @return  void
	 */
	public function add_meta_boxes()
	{
		add_meta_box(
			'ij-post-attachments', __('Images and Attachments'),
			array($this, 'printMetaBox'), null, 'normal', 'high'
		);
	}

	/**
	 * Enqueue the JS files needed by the plugin
	 *
	 * @since	0.0.1a
	 * @return	void
	 */
	public function admin_enqueue_scripts()
	{
		global $hook_suffix;
		if ($hook_suffix != 'post.php')
			return;

		wp_enqueue_script('syoHint', IJ_POST_ATTACHMENTS_URL . 'scripts/jquery.syoHint.js', array('jquery'), '1.0.10');
		wp_enqueue_script(
			'ij-post-attachments', IJ_POST_ATTACHMENTS_URL . 'scripts/ij-post-attachments.js',
			array('syoHint', 'jquery-ui-sortable'), IJ_POST_ATTACHMENTS_VER
		);

		wp_localize_script('ij-post-attachments', 'IJ_Post_Attachments_Vars', array(
			'editMedia' => __('Edit Media'),
			'postID'    => isset($_GET['post']) ? $_GET['post'] : 0
		));
	}

	/**
	 * Enqueue the plugin CSS
	 *
	 * @since	0.0.1a
	 * @return	void
	 */
	public function admin_print_styles()
	{
		global $hook_suffix;
		if ($hook_suffix == 'post.php')
			wp_enqueue_style('ij-post-attachments', IJ_POST_ATTACHMENTS_URL . 'styles/ij-post-attachments.css', array(), IJ_POST_ATTACHMENTS_VER);
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
			'post_status'   => 'any',
			'orderby'       => 'menu_order',
			'order'         => 'ASC'
		));

		include IJ_POST_ATTACHMENTS_DIR . '/html/metabox.php';
	}
	//</editor-fold>

	//<editor-fold desc="Attachment Edition Screen">
	/**
	 * Output the script/link tags needed by the edit iframe
	 *
	 * @since   0.0.1a
	 * @return  void
	 */
	public function attachmentEditHeadIframe()
	{
		global $wp_scripts;
		wp_default_scripts($wp_scripts);

		// I don't know if all these scripts are really needed by the media edit screen.
		// They're just there, so they'll be here too :P
		include IJ_POST_ATTACHMENTS_DIR . '/html/attachmentEditHead.php';

		// Add the needed vars to set the thumbnail :)
		$wp_scripts->localize('set-post-thumbnail', 'post_id', $_GET['post_id']);
		$wp_scripts->print_extra_script('set-post-thumbnail');
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
		include IJ_POST_ATTACHMENTS_DIR . '/html/attachmentEditIframe.php';
	}

	/**
	 * Initialize the attachment edit pop-up.
	 *
	 * @since   0.0.1a
	 * @return  void
	 */
	public function wp_ajax_ij_attachment_edit()
	{
		add_action('admin_head-media-upload-popup', array($this, 'attachmentEditHeadIframe'));
		wp_iframe(array($this, 'attachmentEditIframe'));

		// Without the line below, the WP AJAX caller (admin-ajax.php) would print an '0'
		// at the end of the request.
		die;
	}
	//</editor-fold>

	//<editor-fold desc="Attachment sorting">
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

		for ($i = 0; $i < $count; $i++) {
			if (!is_numeric($alignment[$i]))
				continue;

			$attachment = get_post($alignment[$i]);
			$attachment->menu_order = $i;
			wp_update_post($attachment);
		}
	}
	//</editor-fold>

}

$IJ_Post_Attachments = IJ_Post_Attachments::getInstance();
