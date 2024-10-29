jQuery(function ($) {
    const $searchForm = $('*[data-bdwp-selectors="search-form"]');
    const $submit = $('*[data-bdwp-selectors="search-submit"]');
    const $loader = $('*[data-bdwp-selectors="listing-loader"]');
    const $listing = $('*[data-bdwp-selectors="listing-wrapper"]');
    const $pagination = $('*[data-bdwp-selectors="pagination-wrapper"]');
    const $listingCount = $('*[data-bdwp-selection="listing-count"]');
    let xhr;
    let xhrLocations;
    let form_data = $searchForm.serializeObject();
    let search_query = $searchForm.serialize();

    function fetch_branches(form_data) {
        $listingCount.html('');
        $listing.html('');
        $loader.show();
        let data = $.extend({}, { 'action': 'bdwp_fetch_branches' }, form_data);

        xhr = $.ajax(BDWP.ajax_url, {
            data: data,
            success: function (repsonse) {
                let branchData = JSON.parse(repsonse);
                if (branchData.error) {
                    let template = $('#warning').html();
                    let data = { 'listing-url': window.BDWP.listing_url, 'message': window.BDWP.warning };
                    $('*[data-bdwp-selectors="listing-content"]').html(Mustache.render(template, data));
                    $('.bdwp-map-container').remove();
                }
                let listingCount;
                let listingContent = '';

                if (branchData.branch_count > 0) {
                    listingCount = `Βρέθηκαν &nbsp; ${branchData.branch_count} &nbsp; αποτελέσματα. &nbsp;`;
                    if (branchData.last_page > 1) {
                        listingCount += `Σελίδα  ${branchData.current_page} &nbsp; από &nbsp; ${branchData.last_page}`;
                    }
                } else {
                    listingCount = 'Δεν βρέθηκε κανένα αποτέλεσμα.';
                }

                $listingCount.html(listingCount);

                $('.bdwp-template').each(function(){
                    Mustache.parse($(this).html());
                });

                let noTemplates = true;
                branchData.branches.forEach( function (branch) {
                    let template;
                    if (window.BDWP.service_map != null) {
                        let templateName = window.BDWP.service_map[branch.service];
                        if (templateName === undefined) {
                            templateName = window.BDWP.service_map['default'];
                        }
                        template = $('#' + templateName + '_branch_sort').html();
                    } else {
                        template = $(branch.template).html();
                    }

                    if (template != null) {
                        listingContent += Mustache.render(template, branch);
                        noTemplates = false;
                    }
                });
                if (branchData.branches.length === 0) noTemplates = false;
                if (noTemplates) {
                    let template = $('#warning').html();
                    let data = { 'listing-url': window.BDWP.listing_url, 'message': window.BDWP.warning };
                    $('*[data-bdwp-selectors="listing-content"]').html(Mustache.render(template, data));
                    $('.bdwp-map-container').remove();
                }
                $listing.html(listingContent);
                $pagination.html(branchData.pagination);
                if (window.BDWP.maps.length !== 0) {
                    $.each(window.BDWP.maps, function (index) {
                        window.BDWP.maps[index].locations = branchData.locations;
                    });
                    window.BDWP.initMap();
                }
                $loader.hide();
                window.dispatchEvent(pageRenderedEvent);
                $pagination.find('a').on('click', function () {
                    let page = $(this).data('bdwp-page');
                    let data = $.extend({}, form_data, {'bdwp_page': page});
                    let query = `?bdwp_page=${page}`;
                    if (search_query !== '') {
                        query = search_query + `&bdwp_page=${page}`;
                    }
                    let updated_url = window.BDWP.listing_url + query;
                    window.history.pushState('Listing page', '', updated_url);
                    fetch_branches(data);
                });
            }
        });
    }

    let initialPage = $('#bdwp_page').text();
    if (window.BDWP.query_args === undefined) {
        window.BDWP.query_args = {};
    } else {
        let categories = window.BDWP.query_args.category.terms.split(',');
        let country = window.BDWP.query_args.country;
        let area = window.BDWP.query_args.area;
        let prefecture = window.BDWP.query_args.prefecture;
        let city = window.BDWP.query_args.city;
        $('[name="bdwp_category[]"]').val(categories).trigger("change");
        $('[name="bdwp_country"]').val(country).trigger("change");
        $('[name="bdwp_area"]').val(area).trigger("change");
        $('[name="bdwp_prefecture"]').val(prefecture).trigger("change");
        $('[name="bdwp_city"]').val(city).trigger("change");
        search_query = '?bdwp_listing=true&' + $.param({
            'bdwp_category': categories,
            'bdwp_country': country,
            'bdwp_area': area,
            'bdwp_prefecture': prefecture,
            'bdwp_city': city
        });
        let updated_url = window.BDWP.listing_url + search_query;
        window.history.pushState('Listing page', '', updated_url);
    }
    fetch_branches($.extend({}, { 'bdwp_page': initialPage }, form_data, { 'query_args': window.BDWP.query_args }));

    $submit.on('click', function () {
        form_data = $searchForm.serializeObject();
        for (let field in form_data) {
            if (form_data[field] == -1) {
                form_data[field] = '';
            }
        }
        search_query = '?bdwp_listing=true&' + $searchForm.serialize();
        let updated_url = window.BDWP.listing_url + search_query;
        window.history.pushState('Listing page', '', updated_url);
        fetch_branches(form_data);
        return false;
    });
    $('*[data-bdwp-selectors="search-clear"]').on('click', function () {
        $('*[data-bdwp-selectors="select2"]').val(null).trigger("change");
        $('*[data-bdwp-selectors="select2country"]').val(null).trigger("change");
        $('*[data-bdwp-selectors="select2area"]').val(null).trigger("change");
        $('*[data-bdwp-selectors="select2prefecture"]').val(null).trigger("change");
        $('*[data-bdwp-selectors="select2city"]').val(null).trigger("change");
        form_data = undefined;
        search_query = '?bdwp_listing=true';
        window.history.pushState('Listing page', '', window.BDWP.listing_url + search_query);
        fetch_branches();
        return false;
    });

    function disableField($field) {
        $field.parent().hide();
        $field.val(null);
        if (!$field.is($lastLocation)) {
            let $fieldChild = $field.parent().next('.bdwp-field').find('*[data-bdwp-location]');
            disableField($fieldChild);
        }
    }

    var manualChange = true;
    var allSelected = false;
    var $locationFields = $('*[data-bdwp-location]');
    var $lastLocation = $($locationFields.last());
    $('.location-loader').each(function () {
       $(this).hide();
    });
    $locationFields.on('change', function() {
        let $self = $(this);
        let values = $self.select2('data');
        if (manualChange && values.length > 1) {
            manualChange = false;

            if (allSelected) {
                $self.val([values[1].id]).trigger('change');
                allSelected = false;
            } else {
                $.each(values, function () {
                    if (this.id === '-1') {
                        $self.val(['-1']).trigger('change');
                        allSelected = true;
                    }
                });
            }

            manualChange = true;
        }
        if (!$self.is($lastLocation)) {
            let $child = $self.parent().next().find('*[data-bdwp-location]');

            let currentVal = $self.select2('data')[0].id;
            if (currentVal && currentVal !== '-1') {

                let args = {
                    action: 'bdwp_fetch_locations',
                    bdwp_location_type: $child.data('bdwp-location'),
                    bdwp_location_id: currentVal
                };

                if(xhrLocations && xhrLocations.readyState != 4){
                    xhrLocations.abort();
                }

                let $loader = $self.parent().find('.location-loader');
                $loader.show();
                xhrLocations = $.post(
                    window.BDWP.ajax_url,
                    args,
                    function (response) {
                        var options = $.parseJSON(response);
                        var html = '<option></option><option value="-1">Select All</option>';
                        $.each(options, function(index, option) {
                            html += `<option value="${option.value}">${option.title}</option>\n`;
                        });
                        $child.html(html);
                        $child.parent().show();
                        $loader.hide();
                    }
                );
                let $grandChild = $child.parent().next().find('*[data-bdwp-location]');
                if (!$child.is($lastLocation)) {
                    disableField($grandChild);
                }
            } else {
                disableField($child);
            }
        }
    });
    let hiddenIndex = 0;
    $locationFields.each(function( index ) {
        if (!$(this).val()) {
            hiddenIndex = index + 1;
            return false;
        }
    });
    $locationFields.slice(hiddenIndex).each(function () {
        $(this).parent().hide();
    });
});