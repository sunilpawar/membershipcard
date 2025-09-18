/**
 * JavaScript for Membership Card Templates page
 * Handles preview, duplicate, and delete functionality
 */
CRM.$(function($) {
  'use strict';

  var CardTemplates = {

    /**
     * Initialize the template actions
     */
    init: function() {
      this.bindEvents();
      this.initModals();
    },

    /**
     * Bind event handlers
     */
    bindEvents: function() {
      var self = this;

      // Preview template
      $(document).on('click', '.preview-template', function(e) {
        e.preventDefault();
        var templateId = $(this).data('template-id');
        var previewUrl = $(this).data('preview-url');
        self.previewTemplate(templateId, previewUrl);
      });

      // Duplicate template
      $(document).on('click', '.duplicate-template', function(e) {
        e.preventDefault();
        var templateId = $(this).data('template-id');
        var duplicateUrl = $(this).data('duplicate-url');
        self.duplicateTemplate(templateId, duplicateUrl);
      });

      // Delete template
      $(document).on('click', '.delete-template:not(.disabled)', function(e) {
        e.preventDefault();
        var templateId = $(this).data('template-id');
        var templateName = $(this).data('template-name');
        var deleteUrl = $(this).data('delete-url');
        var usageCount = $(this).data('usage-count');
        self.showDeleteConfirmation(templateId, templateName, deleteUrl, usageCount);
      });

      // Modal close buttons
      $(document).on('click', '.ui-dialog-titlebar-close, .cancel-delete', function() {
        self.closeModals();
      });

      // Confirm delete button
      $(document).on('click', '.confirm-delete', function() {
        var deleteUrl = $(this).data('delete-url');
        if (deleteUrl) {
          self.confirmDelete(deleteUrl);
        }
      });

      // ESC key to close modals
      $(document).keyup(function(e) {
        if (e.keyCode === 27) { // ESC key
          self.closeModals();
        }
      });
    },

    /**
     * Initialize modal dialogs
     */
    initModals: function() {
      // Initialize preview modal
      $('#template-preview-modal').hide();
      $('#delete-confirmation-modal').hide();
    },

    /**
     * Preview a template
     */
    previewTemplate: function(templateId, previewUrl) {
      var self = this;

      // Show loading state
      $('#template-preview-content').html(
        '<div class="preview-loading">' +
        '<i class="crm-i fa-spinner fa-spin"></i> ' + ts('Loading preview...') +
        '</div>'
      );

      // Show modal
      this.showModal('#template-preview-modal');

      // Load preview data
      $.ajax({
        url: previewUrl,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            self.displayPreview(response);
          } else {
            self.showPreviewError(response.error || 'Unknown error occurred');
          }
        },
        error: function(xhr, status, error) {
          self.showPreviewError('Failed to load preview: ' + error);
        }
      });
    },

    /**
     * Display the preview content
     */
    displayPreview: function(response) {
      var previewHtml = '<div class="template-preview-container">';
      previewHtml += '<h3>' + CRM.utils.escapeHtml(response.template.name) + '</h3>';
      previewHtml += '<div class="preview-description">' + CRM.utils.escapeHtml(response.template.description || '') + '</div>';
      previewHtml += '<div class="preview-info">';
      previewHtml += '<span class="info-item">Dimensions: ' + response.template.card_width + ' × ' + response.template.card_height + ' px</span>';
      previewHtml += '</div>';
      previewHtml += '<div class="preview-card-wrapper">';
      previewHtml += response.previewHtml;
      previewHtml += '</div>';
      previewHtml += '<div class="preview-note">';
      previewHtml += '<small><em>This is a preview with sample data. Actual cards will show real membership information.</em></small>';
      previewHtml += '</div>';
      previewHtml += '</div>';

      $('#template-preview-content').html(previewHtml);
    },

    /**
     * Show preview error
     */
    showPreviewError: function(errorMessage) {
      var errorHtml = '<div class="preview-error">';
      errorHtml += '<i class="crm-i fa-exclamation-triangle"></i> ';
      errorHtml += CRM.utils.escapeHtml(errorMessage);
      errorHtml += '</div>';
      $('#template-preview-content').html(errorHtml);
    },

    /**
     * Duplicate a template
     */
    duplicateTemplate: function(templateId, duplicateUrl) {
      var self = this;

      // Show loading message
      CRM.status({
        start: ts('Duplicating template...'),
        success: ts('Template duplicated successfully')
      });

      $.ajax({
        url: duplicateUrl,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            self.showMessage(response.message, 'success');
            // Refresh the page to show the new template
            if (response.redirect) {
              window.location.href = response.redirect;
            } else {
              window.location.reload();
            }
          } else {
            self.showMessage(response.error || 'Failed to duplicate template', 'error');
          }
        },
        error: function(xhr, status, error) {
          self.showMessage('Failed to duplicate template: ' + error, 'error');
        }
      });
    },

    /**
     * Show delete confirmation modal
     */
    showDeleteConfirmation: function(templateId, templateName, deleteUrl, usageCount) {
      var message;

      if (usageCount > 0) {
        message = 'Cannot delete template "' + templateName + '". It is currently being used by ' + usageCount + ' membership card(s).';
        $('.confirm-delete').hide();
      } else {
        message = 'Are you sure you want to delete the template "' + templateName + '"? This action cannot be undone.';
        $('.confirm-delete').show().data('delete-url', deleteUrl);
      }

      $('.delete-message').text(message);
      this.showModal('#delete-confirmation-modal');
    },

    /**
     * Confirm and execute template deletion
     */
    confirmDelete: function(deleteUrl) {
      var self = this;

      // Close the confirmation modal
      this.closeModals();

      // Show loading message
      CRM.status({
        start: ts('Deleting template...'),
        success: ts('Template deleted successfully')
      });

      $.ajax({
        url: deleteUrl,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            self.showMessage(response.message, 'success');
            // Refresh the page to remove the deleted template
            if (response.redirect) {
              window.location.href = response.redirect;
            } else {
              window.location.reload();
            }
          } else {
            self.showMessage(response.error || 'Failed to delete template', 'error');
          }
        },
        error: function(xhr, status, error) {
          self.showMessage('Failed to delete template: ' + error, 'error');
        }
      });
    },

    /**
     * Show a modal dialog
     */
    showModal: function(modalSelector) {
      var $modal = $(modalSelector);
      var $overlay = $('<div class="ui-widget-overlay ui-front"></div>');

      // Add overlay
      $('body').append($overlay);

      // Position and show modal
      $modal.css({
        position: 'fixed',
        top: '50%',
        left: '50%',
        transform: 'translate(-50%, -50%)',
        'z-index': 1001,
        display: 'block'
      });

      // Store overlay reference
      $modal.data('overlay', $overlay);
    },

    /**
     * Close all modals
     */
    closeModals: function() {
      $('.ui-dialog').each(function() {
        var $modal = $(this);
        var $overlay = $modal.data('overlay');

        if ($overlay) {
          $overlay.remove();
        }

        $modal.hide();
      });
    },

    /**
     * Show success/error message
     */
    showMessage: function(message, type) {
      var $messagesContainer = $('#action-messages');
      var messageClass = type === 'success' ? 'success' : 'error';

      var messageHtml = '<div class="action-message ' + messageClass + '">';
      messageHtml += CRM.utils.escapeHtml(message);
      messageHtml += '</div>';

      $messagesContainer.html(messageHtml).show();

      // Auto-hide after 5 seconds
      setTimeout(function() {
        $messagesContainer.fadeOut();
      }, 5000);

      // Scroll to top to ensure message is visible
      $('html, body').animate({ scrollTop: 0 }, 300);
    },

    /**
     * Update usage count for a template
     */
    updateUsageCount: function(templateId, newCount) {
      var $usageSpan = $('.usage-count[data-template-id="' + templateId + '"]');
      var $deleteLink = $('a.delete-template[data-template-id="' + templateId + '"]');

      if (newCount > 0) {
        $usageSpan.html('<span class="badge badge-info">' + newCount + ' ' + ts('cards') + '</span>');
        $deleteLink.addClass('disabled').attr('title', ts('Cannot delete - template is in use'));
      } else {
        $usageSpan.html('<span class="badge badge-secondary">' + ts('Unused') + '</span>');
        $deleteLink.removeClass('disabled').attr('title', ts('Delete Template'));
      }
    }
  };

  // Initialize when DOM is ready
  CardTemplates.init();

  // Export to global scope for potential external use
  window.CRM.MembershipCardTemplates = CardTemplates;
});

// Utility function to escape HTML
if (!CRM.utils) {
  CRM.utils = {};
}

if (!CRM.utils.escapeHtml) {
  CRM.utils.escapeHtml = function(unsafe) {
    return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  };
}
