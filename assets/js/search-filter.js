jQuery(document).ready(function ($) {
    $('#hsf-search-form').on('submit', function (e) {
        e.preventDefault();

        var data = {
            action: 'hsf_search',
            nonce: hsf_search_params.nonce,
            keyword: $('#hsf_keyword').val(),
            category: $('#hsf_category').val(),
            location: $('#hsf_location').val(),
            date_range: $('#hsf_date_range').val()
        };

        $.post(hsf_search_params.ajax_url, data, function (response) {
            if (response.success) {
                $('#hsf-search-results').html('');
                $.each(response.data, function (index, item) {
                    $('#hsf-search-results').append('<div class="hsf-result-item"><a href="' + item.link + '">' + item.title + '</a><p>' + item.excerpt + '</p></div>');
                });
            } else {
                $('#hsf-search-results').html('<p>' + response.data + '</p>');
            }
        });
    });
});
