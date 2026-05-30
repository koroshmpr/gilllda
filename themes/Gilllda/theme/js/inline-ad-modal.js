(function($) {
	tinymce.PluginManager.add('inline_ad_button', function(editor, url) {

		// Add a button to the editor toolbar
		editor.addButton('inline_ad_button', {
			text: '📣 تبلیغ جدید', // Using text and emoji ensures it never breaks like icon fonts do
			tooltip: 'افزودن تبلیغ بین محتوا',
			onclick: function() {

				editor.windowManager.open({
					title: 'تنظیمات تبلیغ',
					width: 650,
					height: 250,
					body: [
						// 1. Link Field with WordPress Link Picker
						{
							type: 'container',
							label: 'لینک مقصد',
							layout: 'flex',
							direction: 'row',
							align: 'center',
							spacing: 5,
							items: [
								{ type: 'textbox', name: 'link', id: 'inline_ad_link', flex: 1 },
								{ type: 'button', text: '🔗 جستجوی صفحات سایت', onclick: function() {
										var inputNode = document.getElementById('inline_ad_link');

										if (typeof wpLink !== 'undefined') {
											// Backup original wpLink functions
											var originalUpdate = wpLink.update;
											var originalClose = wpLink.close;

											// Hijack the update function to grab the URL instead of inserting HTML
											wpLink.update = function() {
												var attrs = wpLink.getAttrs();
												inputNode.value = attrs.href; // Send URL to our textbox
												wpLink.close();
											};

											// Restore original functions when closed so we don't break WP
											wpLink.close = function() {
												wpLink.update = originalUpdate;
												wpLink.close = originalClose;
												originalClose.apply(wpLink, arguments);
											};

											// wpLink requires a textarea to attach to, create a hidden one if missing
											if (!$('#wp-link-dummy').length) {
												$('body').append('<textarea id="wp-link-dummy" style="display:none;"></textarea>');
											}
											wpLink.open('wp-link-dummy');
										} else {
											alert('پیوندساز وردپرس در دسترس نیست.');
										}
									}}
							]
						},
						// 2. Image Field with WordPress Media Gallery
						{
							type: 'container',
							label: 'تصویر',
							layout: 'flex',
							direction: 'row',
							align: 'center',
							spacing: 5,
							items: [
								{ type: 'textbox', name: 'image', id: 'inline_ad_image', flex: 1 },
								{ type: 'button', text: '🖼️ گالری تصاویر', onclick: function() {
										var inputNode = document.getElementById('inline_ad_image');

										var frame = wp.media({
											title: 'انتخاب تصویر تبلیغ',
											multiple: false,
											library: { type: 'image' },
											button: { text: 'انتخاب این تصویر' }
										});

										frame.on('select', function() {
											var attachment = frame.state().get('selection').first().toJSON();
											inputNode.value = attachment.url;
										});

										frame.open();
									}}
							]
						},
						// 3. Text Field
						{
							type: 'textbox',
							name: 'text',
							id: 'inline_ad_text',
							label: 'متن دکمه',
							value: 'مشاهده و خرید'
						}
					],
					onsubmit: function(e) {
						// Extract values directly from the DOM IDs we assigned
						var linkVal = document.getElementById('inline_ad_link').value;
						var imageVal = document.getElementById('inline_ad_image').value;
						var textVal = document.getElementById('inline_ad_text').value;

						editor.insertContent('[inline_ad link="' + linkVal + '" image="' + imageVal + '" text="' + textVal + '"]');
					}
				});
			}
		});
	});
})(jQuery);