jQuery(document).ready(function($) {
    // Handle tab switching
    $('.huapai-tab-button').on('click', function() {
        var tab = $(this).data('tab');
        
        // Update button states
        $('.huapai-tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Update content visibility
        $('.huapai-tab-content').removeClass('active');
        $('#huapai-tab-' + tab).addClass('active');
    });
    
    // Media Library Image Picker
    var mediaUploader;
    
    $('#upload_image_button').on('click', function(e) {
        e.preventDefault();
        
        // If the uploader object has already been created, reopen it
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        // Create the media uploader
        mediaUploader = wp.media({
            title: 'Choose Event Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });
        
        // When an image is selected, run a callback
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            
            // Set the URL in the input field
            $('#featured_image').val(attachment.url);
            
            // Update the preview - create elements safely to avoid XSS
            var img = $('<img>').attr({
                'src': attachment.url,
                'style': 'max-width: 200px; height: auto; display: block;'
            });
            $('#image_preview_container').empty().append(img);
        });
        
        // Open the uploader
        mediaUploader.open();
    });
    
    // Update preview when URL is manually changed
    $('#featured_image').on('change', function() {
        var url = $(this).val();
        if (url) {
            // Create elements safely to avoid XSS
            var img = $('<img>').attr({
                'src': url,
                'style': 'max-width: 200px; height: auto; display: block;'
            });
            $('#image_preview_container').empty().append(img);
        } else {
            $('#image_preview_container').empty();
        }
    });
});
