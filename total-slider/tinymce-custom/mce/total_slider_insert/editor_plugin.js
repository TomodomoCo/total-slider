(function() {
	tinymce.create('tinymce.plugins.TotalSliderPlugin', {
		init : function(ed, url) {
		
			// Register commands
			ed.addCommand('mceTotalSliderInsert', function() {
				ed.windowManager.open({
					file : url + '/admin.php?page=total-slider',
					width : 300,
					height : 300,
					inline : 1
				}, {
					plugin_url : url
				});
			});

			// Register buttons
			ed.addButton('total_slider_insert', {title : 'Insert Slider', cmd : 'mceTotalSliderInsert', image: url + '../../../img/total-slider-icon-16.png' });
		},

		getInfo : function() {
			return {
				longname : 'Total Slider Insert plugin',
				author : 'Peter Upfold for Van Patten Media',
				authorurl : 'http://www.totalslider.com/',
				infourl : 'http://www.totalslider.com/',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('total_slider_insert', tinymce.plugins.TotalSliderPlugin);
})();