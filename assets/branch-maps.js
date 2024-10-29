jQuery(function($) {
	$(document).ready(function() {
        window.dispatchEvent(pageRenderedEvent);
		(function ($) {
			$.each(['show', 'hide'], function (i, ev) {
				var el = $.fn[ev];
				$.fn[ev] = function () {
					this.trigger(ev);
					return el.apply(this, arguments);
				};
			});
		})(jQuery);

		if ($('#full.bdwp-map-canvas').length) {
            $('*[data-bdwp-selectors="full-map"]').on('show', function () {
                setTimeout(function () {
                    window.BDWP.initMapOnce('full');
                }, 50);
            });
		}
        if ($('#mini.bdwp-map-canvas').length) {
            $('*[data-bdwp-selectors="mini-map"]').on('show', function () {
                setTimeout(function () {
                    window.BDWP.initMapOnce('mini');
                }, 50);
            });
        }
	});
});