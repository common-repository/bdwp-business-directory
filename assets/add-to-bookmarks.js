jQuery(function($) {
	$(function() {
		$('*[data-bdwp-selectors="bookmark"]').click(function() {
            // Mozilla Firefox Bookmark
			if (window.sidebar && window.sidebar.addPanel) {
				window.sidebar.addPanel(document.title, window.location.href, '');
			// IE Favorite
			} else if (window.external && ('AddFavorite' in window.external)) {
				window.external.AddFavorite(location.href, document.title);
			// Opera Hotlist
			} else if (window.opera && window.print) {
				this.title = document.title;
				return true;
			// webkit - safari/chrome
			} else {
				alert('Press ' + (navigator.userAgent.toLowerCase().indexOf('mac') != -1 ? 'Command/Cmd' : 'CTRL') + ' + D to bookmark this page.');
			}
		});
	});
});