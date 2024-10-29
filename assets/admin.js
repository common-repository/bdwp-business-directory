jQuery(function ($) {
    $(document).ready(function() {
        let xhr;
        let $theme;
        let $templates = $('.bdwp-template-select');
        $searchbox = $('#bdwp-searchbox-city');
        $searchbox_state = $searchbox.prop('checked');
        $('#bdwp-set-default-city').on('change', function () {
            if(this.checked) {
                $searchbox.attr('disabled', 'disabled');
                $searchbox.prop('checked', false);

            } else {
                $searchbox.removeAttr('disabled');
                $searchbox.prop('checked', $searchbox_state);
            }
        }).trigger('change');
        $searchbox.on('click', function () {
            $searchbox_state = $searchbox.prop('checked');
        });
        $('#bdwp-theme').change(function () {
            $theme = $(this).children(":selected").val();
            $templates.each(function(){
                $(this).prop('disabled', true);
            });
            xhr = $.ajax(BDWP.ajax_url, {
                data: {'action': 'bdwp_pull_templates', 'bdwp_theme': $theme},
                success: function (response) {
                    let templates_data = JSON.parse(response);
                    let content = '<option value="0" selected>None</option>';
                    for (item in templates_data) {
                        content += `<option value="${templates_data[item].value}">${templates_data[item].title}</option>`;
                    }
                    $templates.each(function(){
                        $(this).html(content);
                    });
                    $templates.each(function(){
                        $(this).prop('disabled', false);
                    });
                }
            });

        });

        let frame,
            $iconUploaders = $('.bdwp-icon-uploader'),
            currentContainer,
            currentInput,
            $modal = $('#bdwp-modal-wrapper'),
            $faSelect = $('#fa-icon-name'),
            currentImg,
            currentIcon;

        $iconUploaders.each(function(){
            let $this = $(this);
            let $input = $this.find('.bdwp-selected');
            let $default = $this.find('.bdwp-default');
            let $addIcon = $this.find('.bdwp-upload-custom-icon');
            let $delIcon = $this.find( '.bdwp-delete-custom-icon');
            let $useFA = $this.find('.bdwp-use-font-icon');
            let $iconContainer = $this.find('img');
            let $faContainer = $this.find('[data-fa-icon]');

            // ADD IMAGE LINK
            $addIcon.on( 'click', function( event ){

                event.preventDefault();

                let container = $iconContainer;
                let input = $input;
                let icon = $faContainer;
                currentContainer = container;
                currentIcon = icon;
                currentInput = input;

                if ( frame ) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: 'Select or Upload Icon',
                    button: {
                        text: 'Use this media'
                    },
                    multiple: false
                });

                frame.on( 'select', function() {
                    let attachment = frame.state().get('selection').first().toJSON();
                    currentContainer.attr('src', attachment.url);
                    currentInput.val( attachment.url );
                    currentIcon.removeClass(currentIcon.data('fa-icon'));
                    currentIcon.data('fa-icon', '');
                });

                frame.open();
            });


            // DELETE IMAGE LINK
            $delIcon.on( 'click', function( event ){

                event.preventDefault();

                let container = $iconContainer;
                let input = $input;
                let icon = $faContainer;
                let defaultVal = $default.html();
                if (defaultVal.indexOf("fa-") === 0) {
                    container.attr('src', '');
                    icon.removeClass(icon.data('fa-icon'));
                    icon.data('fa-icon', defaultVal);
                    icon.addClass(defaultVal);
                } else {
                    container.attr('src', defaultVal);
                    icon.removeClass(icon.data('fa-icon'));
                    icon.data('fa-icon', '');
                }
                input.val( 'none' );

            });

            $useFA.on('click', function ( event ) {
                event.preventDefault();

                let input = $input;
                let img = $iconContainer;
                let icon = $faContainer;

                currentInput = input;
                currentImg = img;
                currentIcon = icon;
                $modal.css('visibility', 'visible');
                $('#fa-icon-select').prop( "disabled", true );
            });

            let targetVal;
            if ($input.val() === 'none') {
                targetVal = $default.html();
            } else {
                targetVal = $input.val();
            }
            if (targetVal.indexOf("fa-") === 0) {
                $iconContainer.attr('src', '');
                $faContainer.removeClass($faContainer.data('fa-icon'));
                $faContainer.data('fa-icon', targetVal);
                $faContainer.addClass(targetVal);
            } else {
                $iconContainer.attr('src', targetVal);
                $faContainer.removeClass($faContainer.data('fa-icon'));
                $faContainer.data('fa-icon', '');
            }
        });
        $('#fa-icon-select').on('click', function () {
            currentInput.val('fa-' + $faSelect.val());
            currentImg.attr('src', '');
            currentIcon.removeClass(currentIcon.data('fa-icon'));
            currentIcon.addClass('fa-' + $faSelect.val());
            currentIcon.data('fa-icon', 'fa-' + $faSelect.val());
            $modal.css('visibility', 'collapse');
        });
        $('#fa-icon-cancel').on('click', function () {
            $modal.css('visibility', 'collapse');
        });
        $('#fa-icon-name').on('select2:select', function () {
            $('#fa-icon-select').prop( "disabled", false );
        });
        $.getJSON(window.BDWP.fa_names, function (names) {
            $('#fa-icon-name').select2({
                data: names.results,
                placeholder: 'Select Font Awesome icon',
                width: '100%',
                templateResult: formatState,
                templateSelection: formatState
            });
        });

        var locationFields = {
            country: {
                element: $('#bdwp-default-country'),
                child: 'area'
            },
            area: {
                element: $('#bdwp-default-area'),
                child: 'prefecture'
            },
            prefecture: {
                element: $('#bdwp-default-prefecture'),
                child: 'city'
            },
            city: {
                element: $('#bdwp-default-city')
            }
        };

        function loadLocations(child, id = null) {
            $.post(
                window.BDWP.ajax_url,
                {
                    action: 'bdwp_fetch_locations',
                    bdwp_location_type: child,
                    bdwp_location_id: id
                },
                function(response) {
                    var options = $.parseJSON(response);
                    var html = '<option value="none" selected>none</option>';
                    $.each(options, function(index, option) {
                        html += `<option value="${option.value}">${option.title}</option>\n`;
                    });
                    locationFields[child].element.html(html);
                    locationFields[child].element.attr('disabled', false);
                    disableField(locationFields[child].child);
                }
            );
        }

        function disableField(field) {
            if (field) {
                locationFields[field].element.attr('disabled', true).val('none');
                if (locationFields[field].child) {
                    disableField(locationFields[field].child);
                }
            }
        }

        $.each( locationFields, function( type, field ) {
            if (field.child) {
                field.element.on('change', function() {
                    var child = field.child;
                    var id = field.element.val();
                    if (id === 'none') {
                        disableField(child);
                    } else {
                        loadLocations(child, id);
                    }
                });
            }
        });
        flag = false;
        $.each( locationFields, function( type, field ) {
            if (field.element.val() === 'none' && !flag) {
                disableField(field.child);
                flag = true;
            }
        });
    });
    function formatState (state) {
        if (!state.id) {
            return state.text;
        }
        let $state = $(
            '<span class="fa fa-' + state.id + '">&nbsp</span><span>' + state.text + '</span>'
        );
        return $state;
    }
});