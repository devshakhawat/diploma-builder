(function ($) {
    'use strict';

    var frame;

    function updateHiddenInput() {
        var ids = [];
        $('#emblem-gallery-images li').each(function () {
            ids.push($(this).data('id'));
        });
        $('#emblem-gallery-ids').val(ids.join(','));
    }

    $(document).on('click', '#emblem-gallery-add', function (e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Select Emblem Images',
            button: { text: 'Add to Gallery' },
            multiple: true,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            var attachments = frame.state().get('selection').toJSON();
            var $list = $('#emblem-gallery-images');

            $.each(attachments, function (i, attachment) {
                var thumb = attachment.sizes && attachment.sizes.thumbnail
                    ? attachment.sizes.thumbnail.url
                    : attachment.url;

                if ($list.find('li[data-id="' + attachment.id + '"]').length === 0) {
                    $list.append(
                        '<li data-id="' + attachment.id + '">' +
                        '<img src="' + thumb + '" alt="">' +
                        '<button type="button" class="emblem-gallery-remove" title="Remove">&times;</button>' +
                        '</li>'
                    );
                }
            });

            updateHiddenInput();
        });

        frame.open();
    });

    $(document).on('click', '.emblem-gallery-remove', function (e) {
        e.preventDefault();
        $(this).closest('li').remove();
        updateHiddenInput();
    });

    // Sortable support
    $(function () {
        if ($.fn.sortable) {
            $('#emblem-gallery-images').sortable({
                items: 'li',
                cursor: 'move',
                tolerance: 'pointer',
                update: function () {
                    updateHiddenInput();
                }
            });
        }
    });
})(jQuery);
