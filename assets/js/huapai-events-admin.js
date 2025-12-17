jQuery(document).ready(function($) {
    // Handle fetch event data button click
    $('#huapai_fetch_event_data').on('click', function(e) {
        e.preventDefault();
        
        var url = $('#fb_event_url').val();
        var button = $(this);
        var statusDiv = $('#huapai_fetch_status');
        
        if (!url) {
            statusDiv.html('<div class="notice notice-error"><p>Please enter a URL first.</p></div>');
            return;
        }
        
        // Disable button and show loading state
        button.prop('disabled', true).text('Fetching...');
        statusDiv.html('<div class="notice notice-info"><p>Fetching event data...</p></div>');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'huapai_fetch_url_data',
                url: url,
                nonce: huapaiEventsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    // Fill in the form fields
                    if (data.title) {
                        $('#event_title').val(data.title);
                    }
                    
                    if (data.description) {
                        // For TinyMCE editor
                        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('event_content')) {
                            tinyMCE.get('event_content').setContent(data.description);
                        } else {
                            $('#event_content').val(data.description);
                        }
                    }
                    
                    if (data.image) {
                        $('#featured_image').val(data.image);
                    }
                    
                    if (data.date) {
                        // Convert ISO date to datetime-local format
                        var date = new Date(data.date);
                        if (!isNaN(date.getTime())) {
                            var year = date.getFullYear();
                            var month = String(date.getMonth() + 1).padStart(2, '0');
                            var day = String(date.getDate()).padStart(2, '0');
                            var hours = String(date.getHours()).padStart(2, '0');
                            var minutes = String(date.getMinutes()).padStart(2, '0');
                            var datetimeLocal = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
                            $('#event_date').val(datetimeLocal);
                        }
                    }
                    
                    statusDiv.html('<div class="notice notice-success"><p>Event data fetched successfully! Please review and modify if needed.</p></div>');
                } else {
                    statusDiv.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                statusDiv.html('<div class="notice notice-error"><p>Error: ' + error + '</p></div>');
            },
            complete: function() {
                // Re-enable button
                button.prop('disabled', false).text('Fetch Event Data');
            }
        });
    });
});
