jQuery(function($) {
	$.getJSON(window.BDWP.tabs_url, function (bdwpDataMap) {

			var relations = [];
			$('*[data-bdwp-tabs="pool"]').each(function (poolIndex) {
					relations[poolIndex] = {'triggers': [], 'targets': []};
					$(this).find(`[data-${bdwpDataMap.triggerMarker}]`).each(function(triggerIndex){
							relations[poolIndex].triggers[triggerIndex] = this;
							relations[poolIndex].targets[triggerIndex] = [];
							$(`[data-${bdwpDataMap.targetMarker}]`).each(function(targetIndex) {
									let target = this;
									let triggers = $(this).data(bdwpDataMap.targetMarker).split(' ');
									$(triggers).each(function () {
											if (this == $(relations[poolIndex].triggers[triggerIndex]).data(bdwpDataMap.triggerMarker)) {
													relations[poolIndex].targets[triggerIndex][targetIndex] = target;
											}
									});
							});
					});
					relations[poolIndex].triggers.forEach(function (trigger, triggerIndex) {
							relations[poolIndex].targets[triggerIndex].forEach(function (target) {
									if ($(trigger).hasClass(`${bdwpDataMap.cssClass}`)) {
											$(target).data('visible', true);
									} else if (!$(target).data('visible')) {
											$(target).hide();
									}
							});
					});
			});
			$(relations).each(function(poolIndex){
					$(this.triggers).each(function (triggerIndex) {
							$(this).on('click', (function () {
									var tabs = relations[poolIndex];
									var localIndex = triggerIndex;
									function toggleTab () {
											$(tabs.targets).each(function () {
													$(this).each(function(){
															$(this).hide();
													});
											});
											$(tabs.triggers).each(function () {
													$(this).removeClass(`${bdwpDataMap.cssClass}`);
											});
											$(tabs.triggers[localIndex]).addClass(`${bdwpDataMap.cssClass}`);
											$(tabs.targets[localIndex]).each(function () {
													$(this).show();
											});
									}
									return function() {
											toggleTab();
									}
							})());
					});
			});
			$(`[data-${bdwpDataMap.remoteClass}]`).each(function () {
					$(this).on('click', function () {
							$(`[data-${bdwpDataMap.triggerMarker}='${$(this).data(bdwpDataMap.remoteClass)}']`).click();
					})
			})
			if (location.hash) {
				$(location.hash).click();
			}
	});
});