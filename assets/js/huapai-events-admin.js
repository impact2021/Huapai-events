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
            url: huapaiEventsAdmin.ajaxurl,
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
                        // For TinyMCE editor - try multiple times if not ready
                        var setDescription = function(attempt) {
                            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('event_content')) {
                                tinyMCE.get('event_content').setContent(data.description);
                            } else if (attempt < 3) {
                                // Retry after a short delay if TinyMCE not ready
                                setTimeout(function() {
                                    setDescription(attempt + 1);
                                }, 200);
                            } else {
                                // Fallback to textarea
                                $('#event_content').val(data.description);
                            }
                        };
                        setDescription(0);
                    }
                    
                    if (data.image) {
                        $('#featured_image').val(data.image);
                    }
                    
                    if (data.date) {
                        // Convert ISO date to datetime-local format
                        // Handle timezone properly by using the local time from the ISO string
                        try {
                            var date = new Date(data.date);
                            if (!isNaN(date.getTime())) {
                                // Format to datetime-local (YYYY-MM-DDTHH:MM)
                                var year = date.getFullYear();
                                var month = String(date.getMonth() + 1).padStart(2, '0');
                                var day = String(date.getDate()).padStart(2, '0');
                                var hours = String(date.getHours()).padStart(2, '0');
                                var minutes = String(date.getMinutes()).padStart(2, '0');
                                var datetimeLocal = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
                                $('#event_date').val(datetimeLocal);
                            }
                        } catch (e) {
                            // If date parsing fails, just skip setting the date
                            console.warn('Failed to parse date:', data.date);
                        }
                    }
                    
                    statusDiv.html('<div class="notice notice-success"><p>Event data fetched successfully! Please review and modify if needed.</p></div>');
                } else {
                    statusDiv.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                // Build a more informative error message
                // Priority: specific HTTP errors > JSON response errors > generic errors
                var errorMessage = 'Failed to fetch event data';
                
                // Helper function to check if error message is meaningful
                var isMeaningfulError = function(err) {
                    return err && err !== 'error';
                };
                
                // Helper function to build generic error message with status
                var buildStatusError = function() {
                    return 'Error ' + xhr.status + ': ' + (xhr.statusText || 'Unknown error');
                };
                
                if (xhr.status === 0) {
                    // Network connectivity issue or request was aborted
                    if (isMeaningfulError(error)) {
                        errorMessage = 'Error: ' + error;
                    } else {
                        errorMessage = 'Network error: Unable to connect. Please check your internet connection.';
                    }
                } else if (xhr.status === 404) {
                    errorMessage = 'Error 404: The requested resource was not found.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Error 500: Internal server error. Please try again later.';
                } else if (xhr.status === 403) {
                    errorMessage = 'Error 403: Access denied.';
                } else if (xhr.responseText) {
                    // Try to extract a meaningful error message from the response
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            // Use the server error message (will be safely inserted as text)
                            errorMessage = response.data.message;
                        } else {
                            errorMessage = buildStatusError();
                        }
                    } catch (e) {
                        // If parsing fails, use status text
                        errorMessage = buildStatusError();
                    }
                } else if (isMeaningfulError(error)) {
                    errorMessage = 'Error: ' + error;
                } else if (xhr.status) {
                    errorMessage = buildStatusError();
                }
                
                // Use .text() to safely insert the error message and prevent XSS
                statusDiv.html('<div class="notice notice-error"><p></p></div>').find('p').text(errorMessage);
            },
            complete: function() {
                // Re-enable button
                button.prop('disabled', false).text('Fetch Event Data');
            }
        });
    });
});
