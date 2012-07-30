var IJ_Post_Attachments;
(function($) {
	$(document).ready(function() {
		IJ_Post_Attachments = new InJoin_PostAttachments();
	});

	/**
	 * @class
	 * @constructor
	 */
	function InJoin_PostAttachments() {
		"use strict";

		var self = this;

		/**
		 * Container of the items list
		 * @type {jQuery}
		 */
		this.container = $("#ij-post-attachments");

		/**
		 * The URL of the plugin's directory
		 * @type {String}
		 */
		this.pluginUrl = userSettings.url + 'wp-content/plugins/ij-post-attachments/';

		/**
		 * The "Edit Media" title.
		 * @type {String}
		 */
		this.editMediaTitle = IJ_Post_Attachments_Vars.editMedia;

		/**
		 * Current post ID
		 * @type {Number}
		 */
		this.postID = IJ_Post_Attachments_Vars.postID;

		/**
		 * Defines whether we're dragging around or not
		 * @type {Boolean}
		 */
		this.dragging = false;

		/**
		 * Event fired on "sortstart"
		 * @type    {Function}
		 * @return  {void}
		 */
		this.onStartSorting = function() {
			self.dragging = true;
		};

		/**
		 * Event fired on "sortstop"
		 * @type    {Function}
		 * @return  {void}
		 */
		this.onStopSorting = function() {
			self.dragging = false;
		};

		/**
		 * Event fired on "sortupdate"
		 * @type    {Function}
		 * @return  {void}
		 */
		this.onUpdateSorting = function() {
			setTimeout(function() {
				if (!self.dragging) {
					var LI = $('li', self.container), i = 0,
						alignment = [],
						loader = $('<img />');

					for (; i < LI.length; i++) {
						alignment.push(LI.eq(i).data('attachmentid'));
					}

					loader
						.attr('src', userSettings.url + 'wp-admin/images/wpspin_light.gif')
						.addClass('loader')
						.appendTo(self.container.find('h3.hndle'));

					$.ajax({
						url : ajaxurl,
						data : {
							action : 'ij_realign',
							alignment : alignment
						}
					}).always(function() {
						self.container.find('h3.hndle img').remove();
					});
				}
			}, 1500);
		};

		/**
		 * Insert an attachment in the editor.
		 * @type    {Function}
		 * @return  {Boolean}
		 */
		this.insertAttachment = function() {
			var LI = $(this).parents().filter('li:first'),
				el;

			if (LI.data('mimetype').indexOf('image/') === 0) {
				el = $('<img />')
					.addClass('alignnone size-full wp-image-' + LI.data('attachmentid'))
					.attr({
						alt     : LI.data('alt'),
						src     : LI.data('url'),
						title   : LI.data('title')
					});
			} else {
				el = $('<a/>')
						.text(LI.data('title'))
						.attr('href', LI.data('url'));
			}

			send_to_editor(el[0].outerHTML);
			return false;
		};

		/**
		 * Opens the ThickBox with the media editing screen
		 * @type    {Function}
		 * @return  {Boolean}
		 */
		this.showAttachment = function() {
			var ID = $(this).parents().filter('li:first').data('attachmentid');

			// Because ThickBox removes everything after the TB_iframe parameter,
			// its better to keep it at the last position
			tb_show(
				self.editMediaTitle,
				ajaxurl + '?action=ij_attachment_edit&width=630&height=440&attachment_id=' + ID + '&post_id=' + self.postID + 'TB_iframe=1'
			);

			return false;
		};

		/**
		 * Deletes a media item
		 * @type    {Function}
		 * @return  {Boolean}
		 */
		this.removeAttachment = function() {
			$.ajax({
				url  : $(this).attr('href'),
				// The line below will make WP redirect to our plugin after the deletion.
				// That way, less data will be downloaded.
				data : { _wp_http_referer   : self.pluginUrl + 'ij-post-attachments.php' }
			}).done(function(ret) {
				if (!ret) {
					$(this.parentNode.parentNode).fadeOut(300, function() {
						$(this).remove();
					});
				}
			});
			return false;
		};

		/**
		 * Sets up the widgets and events
		 * @type    {Function}
		 * @return  {void}
		 */
		this.setup = function() {
			// Let's do some jQuerying!
			// Firstly, apply the sortable interaction.
			$("#ij-post-attachments > ul").sortable({
				update  : this.onUpdateSorting,
				start   : this.onStartSorting,
				stop    : this.onStopSorting
			}).disableSelection();

			// Apply the syoHint plugin
			$('li.ij-post-attachment').autoHint();

			// Bind the Insert Attachment behavior
			$('a.ij-post-attachment-insert').click(function() {
				self.insertAttachment.call(this);
				return false;
			});

			// Bind the Show Attachment behavior to the title and to the Edit links
			$('a.ij-post-attachment-edit').click(function() {
				self.showAttachment.call(this);
				return false;
			});

			// Bind the Remove Attachment behavior to the Remove link
			$('a.ij-post-attachment-delete').click(function() {
				self.removeAttachment.call(this);
				return false;
			});
		};

		this.setup();
	}

})(jQuery);