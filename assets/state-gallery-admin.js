(function ($) {
    'use strict';

    var frame;

    function updateHiddenInput() {
        var ids = [];
        $('#state-gallery-images li').each(function () {
            ids.push($(this).data('id'));
        });
        $('#state-gallery-ids').val(ids.join(','));
    }

    $(document).on('click', '#state-gallery-add', function (e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Select State Images',
            button: { text: 'Add to Gallery' },
            multiple: true,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            var attachments = frame.state().get('selection').toJSON();
            var $list = $('#state-gallery-images');

            $.each(attachments, function (i, attachment) {
                var thumb = attachment.sizes && attachment.sizes.thumbnail
                    ? attachment.sizes.thumbnail.url
                    : attachment.url;

                if ($list.find('li[data-id="' + attachment.id + '"]').length === 0) {
                    $list.append(
                        '<li data-id="' + attachment.id + '">' +
                        '<img src="' + thumb + '" alt="">' +
                        '<button type="button" class="state-gallery-remove" title="Remove">&times;</button>' +
                        '</li>'
                    );
                }
            });

            updateHiddenInput();
        });

        frame.open();
    });

    $(document).on('click', '.state-gallery-remove', function (e) {
        e.preventDefault();
        $(this).closest('li').remove();
        updateHiddenInput();
    });

    // Sortable support
    $(function () {
        if ($.fn.sortable) {
            $('#state-gallery-images').sortable({
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
