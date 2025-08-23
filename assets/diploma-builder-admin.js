jQuery(document).ready(function($) {
    'use strict';
    
    // Hide modal on page load
    $('.modal').hide();
    
    // Handle diploma deletion
    $(document).on('click', '.delete-diploma', function(e) {
        e.preventDefault();
        
        const diplomaId = $(this).data('diploma-id');
        const row = $(this).closest('tr');
        
        if (!confirm(diploma_builder_admin.delete_confirm)) {
            return;
        }
        
        $.ajax({
            url: diploma_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_diploma',
                diploma_id: diplomaId,
                nonce: diploma_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                        showMessage(response.data.message, 'success');
                    });
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                showMessage(diploma_builder_admin.delete_error, 'error');
            }
        });
    });
    
    // Handle bulk deletion
    $('.bulk-delete-diplomas').on('click', function(e) {
        e.preventDefault();
        
        const diplomaIds = [];
        $('input[name="diploma_ids[]"]:checked').each(function() {
            diplomaIds.push($(this).val());
        });
        
        if (diplomaIds.length === 0) {
            showMessage(diploma_builder_admin.select_diplomas, 'error');
            return;
        }
        
        if (!confirm(diploma_builder_admin.bulk_delete_confirm.replace('%d', diplomaIds.length))) {
            return;
        }
        
        $.ajax({
            url: diploma_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bulk_delete_diplomas',
                diploma_ids: diplomaIds,
                nonce: diploma_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Remove deleted rows
                    $('input[name="diploma_ids[]"]:checked').closest('tr').fadeOut(300, function() {
                        $(this).remove();
                    });
                    showMessage(response.data.message, 'success');
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                showMessage(diploma_builder_admin.bulk_delete_error, 'error');
            }
        });
    });
    
    // Handle diploma preview
    $(document).on('click', '.view-diploma', function(e) {
        e.preventDefault();
        
        const diplomaId = $(this).data('diploma-id');
        
        // Show loading indicator
        $('#diploma-preview-content').html('<div class="loading">Loading...</div>');
        $('#diploma-preview-modal').show();
        
        $.ajax({
            url: diploma_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_diploma_preview',
                diploma_id: diplomaId,
                nonce: diploma_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#diploma-preview-content').html(response.data.html);
                } else {
                    $('#diploma-preview-content').html('<div class="error">Error: ' + response.data + '</div>');
                }
            },
            error: function() {
                $('#diploma-preview-content').html('<div class="error">' + diploma_builder_admin.preview_error + '</div>');
            }
        });
    });
    
    // Close modal
    $(document).on('click', '.modal-close, .modal', function(e) {
        if (e.target === this) {
            $('.modal').fadeOut(300);
        }
    });
    
    // Prevent modal closing when clicking inside
    $('.modal-content').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Handle select all
    $(document).on('change', '#cb-select-all-1', function() {
        $('input[name="diploma_ids[]"]').prop('checked', $(this).prop('checked'));
    });
    
    // Show message to user
    function showMessage(message, type) {
        // Remove existing messages
        $('.diploma-message').remove();
        
        const messageClass = type === 'success' ? 'updated' : 'error';
        const messageHTML = `
            <div class="diploma-message ${messageClass} notice is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `;
        
        $('.wrap h1').after(messageHTML);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $('.diploma-message').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Handle dismiss button for messages
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut(300, function() {
            $(this).remove();
        });
    });
    
});