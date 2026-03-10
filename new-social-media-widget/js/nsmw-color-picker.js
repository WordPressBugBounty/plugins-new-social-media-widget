jQuery(document).ready(function ($) {
    function initNsmwWidget(widget) {
        // Initialize color picker
        widget.find('.div_bg_color, .div_bg_hover_color, .icon_color, .icon_hover_color').wpColorPicker({
            change: function (e, ui) {
                $(e.target).val(ui.color.toString()).trigger('change');
            },
            clear: function (e, ui) {
                $(e.target).trigger('change');
            }
        });

        // Initialize sortable
        widget.find('.nsmw-social-media-urls-sortable').sortable({ revert: true });

        // Repeater Add
        widget.find('.nsmw-add-repeater-item').off('click').on('click', function (e) {
            e.preventDefault();
            var list = $(this).closest('.nsmw-section-content').find('.nsmw-repeater-list');
            var template = $(this).closest('.nsmw-section-content').find('.nsmw-repeater-template').html();
            var newIndex = list.children('.nsmw-repeater-item').length + '_' + Math.floor(Math.random() * 10000);

            var newItem = template.replace(/__INDEX__/g, newIndex);
            list.append(newItem);

            // Re-bind sortable if needed (already bound to parent, so it automatically applies to children)
        });

        // Repeater Remove
        widget.off('click', '.nsmw-remove-repeater-item').on('click', '.nsmw-remove-repeater-item', function (e) {
            e.preventDefault();
            $(this).closest('.nsmw-repeater-item').remove();
        });

        // Handle Style Type changes
        widget.find('.style_type').on('change', function () {
            var style_type = widget.find('.style_type:checked').val();
            var displaySettings = $(this).closest('.nsmw-display-settings');

            displaySettings.find('.layout_default, .layout_style_one, .layout_style_two').removeClass('select_style');

            if (style_type === 'default') {
                displaySettings.find('.layout_default').addClass('select_style');
                displaySettings.find('.effect_type, .effect_color').show();
            } else if (style_type === 'style_one') {
                displaySettings.find('.layout_style_one').addClass('select_style');
                displaySettings.find('.effect_type').hide();
                displaySettings.find('.effect_color').show();
            } else if (style_type === 'style_two') {
                displaySettings.find('.layout_style_two').addClass('select_style');
                displaySettings.find('.effect_type, .effect_color').hide();
            }
        });

        // Trigger initial state
        widget.find('.style_type:checked').trigger('change');

        // Handle Effect Type changes
        widget.find('.nsmw-effect-type-select').on('change', function () {
            var effecttype = $(this).val();
            var displaySettings = $(this).closest('.nsmw-display-settings');

            if (effecttype === "none") {
                displaySettings.find('.nsmwts-wrap, .nsmwhe-wrap').hide();
            } else if (effecttype === "transform") {
                displaySettings.find('.nsmwts-wrap').show();
                displaySettings.find('.nsmwhe-wrap').hide();
            } else if (effecttype === "hover") {
                displaySettings.find('.nsmwts-wrap').hide();
                displaySettings.find('.nsmwhe-wrap').show();
            }
        });

        // Trigger initial state
        widget.find('.nsmw-effect-type-select').trigger('change');
    }

    // Run on document ready for already loaded widgets
    $('.nsmw-admin-wrapper').each(function () {
        initNsmwWidget($(this));
    });

    // Run when a widget is added or updated in the WP admin
    $(document).on('widget-added widget-updated', function (event, widget) {
        if (widget.find('.nsmw-display-settings').length > 0) {
            initNsmwWidget(widget);
        }
    });

    // Handle the custom toggle buttons for the accordion sections
    $(document).on('click', '.nsmw-section-toggle', function (e) {
        e.preventDefault();
        $(this).toggleClass('active');
        $(this).next('.nsmw-section-content').slideToggle();
    });
});
