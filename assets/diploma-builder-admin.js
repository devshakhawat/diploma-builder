jQuery(document).ready(function($) {
    'use strict';
    
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
                    $('#diploma-preview-modal').fadeIn(300);
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                showMessage(diploma_builder_admin.preview_error, 'error');
            }
        });
    });
    
    // Close modal
    $('.modal-close, .modal').on('click', function(e) {
        if (e.target === this) {
            $('.modal').fadeOut(300);
        }
    });
    
    // Prevent modal closing when clicking inside
    $('.modal-content').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Handle select all
    $('#cb-select-all-1').on('change', function() {
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
    
    // Initialize localization strings
    if (typeof diploma_builder_admin === 'undefined') {
        window.diploma_builder_admin = {
            delete_confirm: 'Are you sure you want to delete this diploma?',
            delete_error: 'Error deleting diploma. Please try again.',
            bulk_delete_confirm: 'Are you sure you want to delete %d diplomas?',
            bulk_delete_error: 'Error deleting diplomas. Please try again.',
            select_diplomas: 'Please select at least one diploma to delete.',
            preview_error: 'Error loading diploma preview. Please try again.'
        };
    }
});