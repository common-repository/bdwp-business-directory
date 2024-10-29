jQuery(function ($) {
    let bdwpModalShownEvents = [],
        bdwpModalClosedEvents = [];
    $(document).ready(function () {
        let $gallerySlides = $('[data-bdwp-gallery]');
        if ($gallerySlides.length) {
            function galleryRefresh(slide) {
                $galleryCurrentSlide = slide;
                let src = $galleryCurrentSlide.find('img').attr('src');
                $galleryImg.attr('src', src);
            }
            function galleryNext() {
                if ($galleryCurrentSlide.next().length) {
                    return $galleryCurrentSlide.next();
                } else {
                    return $galleryCurrentSlide.siblings().first();
                }
            }
            function galleryPrev() {
                if ($galleryCurrentSlide.prev().length) {
                    return $galleryCurrentSlide.prev();
                } else {
                    return $galleryCurrentSlide.siblings().last();
                }
            }
            $('body').append('<div data-bdwp-modal="bdwp-default-gallery">' +
                '<div id="bdwp-prev-btn">❮</div>' +
                '<div id="bdwp-gallery-content"><img src="" alt="image"/></div>' +
                '<div id="bdwp-next-btn"><div data-bdwp-selectors="modal-close">X</div>❯</div>' +
                '</div>');
            let $gallery = $('[data-bdwp-modal="bdwp-default-gallery"]'),
                $galleryImg = $gallery.find('img'),
                $galleryCurrentSlide,
                $galleryPrev = $('#bdwp-prev-btn'),
                $galleryNext = $('#bdwp-next-btn');
            $gallerySlides.click(function () {
                galleryRefresh($(this));
            });
            $galleryImg.click(function (event) {
                event.stopPropagation();
                galleryRefresh(galleryNext());
            });
            $galleryPrev.click(function () {
                galleryRefresh(galleryPrev());
            });
            $galleryNext.click(function () {
                galleryRefresh(galleryNext());
            });
            $('#bdwp-gallery-content').click(function () {
                let id = modalId;
                $('[data-bdwp-modal-wrapper="bdwp-default-gallery"]').slideUp(200);
                $(window).trigger(bdwpModalClosedEvents[id]);
            });
        }
        let $modals = $('[data-bdwp-modal]'),
            modalId;
        $modals.each(function(){
            let $self = $(this);
            modalId = $self.data('bdwp-modal');
            bdwpModalShownEvents[modalId] = $.Event(modalId + 'ModalShown');
            bdwpModalClosedEvents[modalId] = $.Event(modalId + 'ModalClosed');
            $('body').append('<div data-bdwp-modal-wrapper="' + modalId + '" style="display: none;"></div>');
            let $wrapper = $('[data-bdwp-modal-wrapper="' + modalId + '"]');
            let $closeBtns = $self.find('*[data-bdwp-selectors="modal-close"]');
            $self.appendTo($wrapper);
            $self.attr('style', '');
            $wrapper.slideUp();
            $wrapper.click(function (event) {
                event.preventDefault();
                let id = modalId;
                $(this).slideUp(200);
                $(window).trigger(bdwpModalClosedEvents[id]);
            });
            $closeBtns.click( function (event) {
                event.preventDefault();
                let id = modalId;
                $wrapper.slideUp(200);
                $(window).trigger(bdwpModalClosedEvents[id]);
            });
            $wrapper.on('mousewheel DOMMouseScroll', function (event) {
                event.stopPropagation();
                event.preventDefault();
            });
            $(this).click(function (event) {
                event.stopPropagation();
            });
        });
    });
    $(window).on('bdwpPageRendered', function () {
        $('[data-bdwp-modal-trigger]').click(function(event){
            event.preventDefault();
            let $self = $(this);
            let wrapperId = $self.data('bdwp-modal-trigger');
            let $wrapper = $('[data-bdwp-modal-wrapper="' + wrapperId + '"]');
            if ($wrapper.length) {
                $wrapper.slideDown(200);
                bdwpModalShownEvents[wrapperId].eTrigger = $self;
                $(window).trigger(bdwpModalShownEvents[wrapperId]);
            }
        });
    });
});