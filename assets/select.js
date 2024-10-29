jQuery(function ($) {
    $(document).ready(function() {
        $('*[data-bdwp-selectors="select2"]').select2({
            tags: true,
            tokenSeparators: [',', ';'],
            placeholder: "",
            allowClear: true
        });

        $('*[data-bdwp-selectors="select2country"]').select2({
            placeholder: "Select country",
            allowClear: true
        });

        $('*[data-bdwp-selectors="select2area"]').select2({
            placeholder: "Select area",
            allowClear: true
        });

        $('*[data-bdwp-selectors="select2prefecture"]').select2({
            placeholder: "Select prefecture",
            allowClear: true
        });

        $('*[data-bdwp-selectors="select2city"]').select2({
            tags: true,
            tokenSeparators: [',', ';'],
            placeholder: "Select city",
            allowClear: true
        });
    });
});