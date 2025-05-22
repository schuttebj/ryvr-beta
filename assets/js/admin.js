/**
 * Ryvr Admin JavaScript
 */

(function($) {
    'use strict';

    // Initialize Ryvr admin when the DOM is fully loaded
    $(document).ready(function() {
        // Add translation strings to ryvrData
        if (typeof ryvrData !== 'undefined') {
            ryvrData.i18n = {
                configuration: ' Configuration',
                errorLoadingConnector: 'Error loading connector data.',
                errorLoadingFields: 'Error loading authentication fields.',
                errorSavingCredentials: 'Error saving credentials.',
                errorTestingCredentials: 'Error testing credentials.',
                errorDeletingCredentials: 'Error deleting credentials.',
                confirmDeleteCredentials: 'Are you sure you want to delete these credentials?'
            };
        }
        
        // Initialize tabs if present
        if ($('.ryvr-tabs').length) {
            initTabs();
        }
    });
    
    /**
     * Initialize tabs
     */
    function initTabs() {
        $('.ryvr-tab-link').on('click', function(e) {
            e.preventDefault();
            
            var tabId = $(this).attr('href');
            
            // Hide all tab content
            $('.ryvr-tab-content').hide();
            
            // Remove active class from all tab links
            $('.ryvr-tab-link').removeClass('active');
            
            // Show the selected tab content
            $(tabId).show();
            
            // Add active class to the clicked tab link
            $(this).addClass('active');
            
            // Update URL hash
            if (history.pushState) {
                history.pushState(null, null, tabId);
            } else {
                location.hash = tabId;
            }
        });
        
        // Activate tab from URL hash
        if (window.location.hash) {
            $('.ryvr-tab-link[href="' + window.location.hash + '"]').trigger('click');
        } else {
            // Activate first tab by default
            $('.ryvr-tab-link:first').trigger('click');
        }
    }
})(jQuery); 