var IJ_Post_Attachments;
(function($) {
	$(document).ready(function() {
		IJ_Post_Attachments = new InJoin_PostAttachments();
	});

	function InJoin_PostAttachments() {
		var self = this;

		/**
		 * @type {jQuery}
		 */
		this.container = $("#ij-post-attachments");

		/**
		 * The URL of the plugin's directory
		 * @type {String}
		 */
		this.pluginUrl = $("#ij-post-attachments-pluginurl").text();

		/**
		 * The "Edit Media" title.
		 * @type {String}
		 */
		this.editMediaTitle = $("#ij-post-attachments-editmedia").text();

		/**
		 * Defines whether we're dragging around or not
		 * @type {Boolean}
		 */
		this.dragging = false;

		this.onStartSorting = function() {
			self.dragging = true;
		};

		this.onStopSorting = function() {
			self.dragging = false;
		};

		this.onUpdateSorting = function() {
			setTimeout(function() {
				if (!self.dragging) {
					var LI = $('li', self.container), i = 0,
						alignment = [];
					for (; i < LI.length; i++)
						alignment.push(LI.eq(i).data('attachmentid'));

					$.ajax({
						url : self.pluginUrl + 'ij-post-attachments.php',
						data : { alignment : alignment }
					});
				}
			}, 500);
		};

		/**
		 * @return  {Boolean}
		 */
		this.showAttachment = function() {
			var ID = $(this).parents().filter('li:first').data('attachmentid');

			// Because ThickBox removes everything after the TB_iframe parameter,
			// its better to keep it at the last position
			tb_show(self.editMediaTitle, self.pluginUrl + 'ij-post-attachments.php?width=630&height=440&attachment_id=' + ID + '&TB_iframe=1');

			return false;
		};

		/**
		 * @return  {Boolean}
		 */
		this.removeAttachment = function() {
			jQuery.ajax({
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

		// Let's do some jQuerying finally!
		$("#ij-post-attachments > ul").sortable({
			update  : this.onUpdateSorting
		}).disableSelection();

		$('li.ij-post-attachment').autoHint();

		$('a.ij-post-attachment-edit').click(function() {
			self.showAttachment.call(this);
			return false;
		});
		$('a.ij-post-attachment-delete').click(function() {
			self.removeAttachment.call(this);
			return false;
		});
	}
})(jQuery);