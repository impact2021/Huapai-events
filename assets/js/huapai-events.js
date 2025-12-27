/**
 * Huapai Events Frontend JavaScript
 */
jQuery(document).ready(function($) {
    // Handle Read More / Read Less toggle
    $('.huapai-read-more-btn').on('click', function() {
        var btn = $(this);
        var eventDesc = btn.closest('.huapai-event-description');
        var shortDesc = eventDesc.find('.huapai-description-short');
        var fullDesc = eventDesc.find('.huapai-description-full');
        var isExpanded = btn.attr('aria-expanded') === 'true';
        
        if (isExpanded) {
            // Collapse
            shortDesc.show();
            fullDesc.hide();
            btn.text('Read More').attr('aria-expanded', 'false');
        } else {
            // Expand
            shortDesc.hide();
            fullDesc.show();
            btn.text('Read Less').attr('aria-expanded', 'true');
        }
    });
});
