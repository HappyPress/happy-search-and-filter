jQuery(function ($) {
    // Only proceed if wrapper present
    if (!$('.hsf-filter-wrapper').length) {
        return;
    }

    // Create UI elements if not present
    var $panel = $('<div class="hsf-saved-filters"><h4>Saved Filters</h4><ul class="hsf-saved-list"></ul><button class="button save-filter-btn">Save Current Filter</button></div>');
    $('.hsf-filter-wrapper').append($panel);

    var $list = $panel.find('.hsf-saved-list');

    function renderList(filters) {
        $list.empty();
        if (!filters || !filters.length) {
            $list.append('<li class="hsf-none">No saved filters.</li>');
            return;
        }
        filters.forEach(function (f) {
            var $li = $('<li></li>').text(f.name).attr('data-id', f.id);
            var $del = $('<span class="dashicons dashicons-trash" style="cursor:pointer;margin-left:6px;"></span>');
            $li.append($del);
            $list.append($li);
        });
    }

    function fetchFilters() {
        $.post(hsfSaved.ajaxUrl, { action: 'hsf_get_filters' }, function (resp) {
            if (resp.success) {
                renderList(resp.data);
            }
        });
    }

    fetchFilters();

    // Save filter
    $panel.on('click', '.save-filter-btn', function () {
        var name = prompt(hsfSaved.i18n.promptName);
        if (!name) return;
        var params = $('#hsf-search-form').serializeArray().reduce(function (acc, item) {
            acc[item.name] = item.value;
            return acc;
        }, {});
        $.post(hsfSaved.ajaxUrl, {
            action: 'hsf_save_filter',
            nonce: hsfSaved.nonce,
            name: name,
            params: params
        }, function (resp) {
            if (resp.success) {
                renderList(resp.data);
            }
        });
    });

    // Apply filter on click
    $panel.on('click', 'li', function (e) {
        if ($(e.target).hasClass('dashicons-trash')) return; // ignore delete icon click
        var id = $(this).data('id');
        var filters = $(this).closest('.hsf-saved-filters').data('filters') || [];
    });

    // Delete
    $panel.on('click', '.dashicons-trash', function (e) {
        e.stopPropagation();
        var id = $(this).parent().data('id');
        $.post(hsfSaved.ajaxUrl, {
            action: 'hsf_delete_filter',
            nonce: hsfSaved.nonce,
            id: id
        }, function () {
            fetchFilters();
        });
    });
}); 