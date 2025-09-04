/**
 * Membership Cards List Page JavaScript
 */

(function($) {
  'use strict';

  // Initialize page when DOM is ready
  $(document).ready(function() {
    initializeFilters();
    initializeActions();
    initializeModals();
    initializeTooltips();
    initializeCardInteractions();
  });

  /**
   * Initialize filter functionality
   */
  function initializeFilters() {
    // Auto-submit form on filter change (optional)
    $('.filters-form select').on('change', function() {
      // Uncomment to auto-submit on change
      // $(this).closest('form').submit();
    });

    // Clear individual filters
    $('.clear-filter').on('click', function(e) {
      e.preventDefault();
      const filterName = $(this).data('filter');
      $(`select[name="${filterName}"]`).val('').trigger('change');
    });

    // Filter form validation
    $('.filters-form').on('submit', function(e) {
      // Add any validation logic here if needed
    });

    // Save filter preferences
    saveFilterPreferences();
    loadFilterPreferences();
  }

  /**
   * Initialize action buttons and dropdowns
   */
  function initializeActions() {
    // Export functionality
    window.exportCards = function(format) {
      const currentUrl = new URL(window.location.href);
      currentUrl.searchParams.set('export', format);

      // Show loading indicator
      CRM.status({}, {
        start: function() {
          return 'Preparing export...';
        },
        success: function() {
          return 'Export ready!';
        }
      });

      // Create download link
      const link = document.createElement('a');
      link.href = currentUrl.toString();
      link.download = `membership-cards-${format}-${new Date().toISOString().split('T')[0]}`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    };

    // Bulk actions
    initializeBulkActions();
  }

  /**
   * Initialize bulk actions functionality
   */
  function initializeBulkActions() {
    let selectedCards = new Set();

    // Add selection checkboxes (if implementing bulk selection)
    if ($('.card-selection').length > 0) {
      // Select all functionality
      $('#select-all-cards').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.card-selection').prop('checked', isChecked);
        updateBulkActions();
      });

      // Individual card selection
      $(document).on('change', '.card-selection', function() {
        const cardId = $(this).val();
        if ($(this).is(':checked')) {
          selectedCards.add(cardId);
        } else {
          selectedCards.delete(cardId);
        }
        updateBulkActions();
      });
    }

    function updateBulkActions() {
      const count = selectedCards.size;
      const $bulkActions = $('.bulk-actions');

      if (count > 0) {
        $bulkActions.show();
        $bulkActions.find('.selected-count').text(count);
      } else {
        $bulkActions.hide();
      }
    }
  }

  /**
   * Initialize modal dialogs
   */
  function initializeModals() {
    // Card preview modal
    initializePreviewModal();

    // Email card modal
    initializeEmailModal();

    // Delete confirmation modal
    initializeDeleteModal();
  }

  /**
   * Initialize card preview modal
   */
  function initializePreviewModal() {
    window.previewCard = function(cardId) {
      const $modal = $('#card-preview-modal');
      const $content = $('#card-preview-content');

      $modal.modal('show');
      $content.html(`
        <div class="loading-spinner">
          <i class="fa fa-spinner fa-spin fa-2x"></i>
          <p>Loading card preview...</p>
        </div>
      `);

      // Load preview via API
      CRM.api3('MembershipCard', 'download', {
        card_id: cardId,
        format: 'png'
      }).done(function(result) {
        if (result.values && result.values[0]) {
          const card = result.values[0];
          $content.html(`
            <div class="card-preview-container">
              <img src="${card.image_data}" alt="Membership Card" class="img-responsive">
              <div class="preview-info">
                <h5>${card.member_name || 'Membership Card'}</h5>
                <p class="text-muted">Generated: ${formatDate(card.created_date)}</p>
              </div>
            </div>
          `);

          // Setup download button
          $('#download-preview-card').off('click').on('click', function() {
            downloadCard(cardId);
          });
        }
      }).fail(function(error) {
        $content.html(`
          <div class="alert alert-danger">
            <i class="fa fa-exclamation-triangle"></i>
            <strong>Error loading preview:</strong> ${error.error_message || 'Unknown error'}
          </div>
        `);
      });
    };
  }

  /**
   * Initialize email card modal
   */
  function initializeEmailModal() {
    window.emailCard = function(cardId) {
      $('#email-card-id').val(cardId);
      $('#email-card-modal').modal('show');

      // Reset form
      $('#email-card-form')[0].reset();

      // Load member's email address
      loadMemberEmail(cardId);
    };

    // Send email functionality
    window.sendCardEmail = function() {
      const formData = {
        card_id: $('#email-card-id').val(),
        email_to: $('#email-to').val(),
        email_subject: $('#email-subject').val(),
        email_message: $('#email-message').val()
      };

      // Validate form
      if (!formData.email_subject.trim()) {
        CRM.alert('Please enter an email subject', 'Validation Error', 'error');
        return;
      }

      // Show loading state
      const $btn = $(event.target);
      const originalText = $btn.html();
      $btn.html('<i class="fa fa-spinner fa-spin"></i> Sending...').prop('disabled', true);

      CRM.api3('MembershipCard', 'email', formData)
        .done(function(result) {
          $('#email-card-modal').modal('hide');
          CRM.alert('Card emailed successfully!', 'Success', 'success');

          // Reset form
          $('#email-card-form')[0].reset();
        })
        .fail(function(error) {
          CRM.alert(error.error_message || 'Error sending email', 'Error', 'error');
        })
        .always(function() {
          $btn.html(originalText).prop('disabled', false);
        });
    };

    function loadMemberEmail(cardId) {
      // Load member's email address to pre-populate field
      CRM.api3('MembershipCard', 'get', {
        id: cardId,
        'api.Membership.get': {
          'id': '$value.membership_id',
          'api.Contact.get': {'id': '$value.contact_id'}
        }
      }).done(function(result) {
        if (result.values && result.values[0]) {
          const contact = result.values[0]['api.Membership.get'].values[0]['api.Contact.get'].values[0];
          if (contact && contact.email) {
            $('#email-to').val(contact.email);
          }
        }
      });
    }
  }

  /**
   * Initialize delete confirmation modal
   */
  function initializeDeleteModal() {
    let deleteCardId = null;

    window.deleteCard = function(cardId, memberName) {
      deleteCardId = cardId;
      $('#delete-card-message').text(`Are you sure you want to delete the membership card for ${memberName}?`);
      $('#delete-card-modal').modal('show');
    };

    $('#confirm-delete-card').on('click', function() {
      if (deleteCardId) {
        const $btn = $(this);
        const originalText = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Deleting...').prop('disabled', true);

        // Perform deletion
        window.location.href = CRM.url('civicrm/membership-cards', {
          action: 'delete',
          id: deleteCardId
        });
      }
    });

    // Reset on modal close
    $('#delete-card-modal').on('hidden.bs.modal', function() {
      deleteCardId = null;
      $('#confirm-delete-card').html('<i class="fa fa-trash"></i> Delete Card').prop('disabled', false);
    });
  }

  /**
   * Initialize tooltips
   */
  function initializeTooltips() {
    // Initialize Bootstrap tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Add tooltips to action buttons
    $('.btn[title]').tooltip();

    // Custom tooltips for status badges
    $('.badge').each(function() {
      const $badge = $(this);
      const status = $badge.text().toLowerCase();

      let tooltipText = '';
      switch(status) {
        case 'active':
          tooltipText = 'Membership is current and valid';
          break;
        case 'expired':
          tooltipText = 'Membership has expired';
          break;
        case 'pending':
          tooltipText = 'Membership is pending approval';
          break;
        default:
          tooltipText = `Membership status: ${status}`;
      }

      $badge.attr('title', tooltipText).tooltip();
    });
  }

  /**
   * Initialize card interactions
   */
  function initializeCardInteractions() {
    // Card hover effects
    $('.membership-card-item').on('mouseenter', function() {
      $(this).addClass('card-hovered');
    }).on('mouseleave', function() {
      $(this).removeClass('card-hovered');
    });

    // Quick actions on card click
    $('.card-preview').on('click', function(e) {
      if (!$(e.target).closest('.card-actions').length) {
        const cardId = $(this).closest('.membership-card-item').data('card-id');
        if (cardId) {
          previewCard(cardId);
        }
      }
    });

    // Keyboard navigation
    $(document).on('keydown', function(e) {
      if (e.key === 'Escape') {
        $('.modal').modal('hide');
      }
    });
  }

  /**
   * Regenerate card functionality
   */
  window.regenerateCard = function(cardId) {
    if (!confirm('Are you sure you want to regenerate this card? This will replace the existing card.')) {
      return;
    }

    // Show loading indicator
    CRM.status({
      start: 'Regenerating card...',
      success: 'Card regenerated successfully!'
    });

    // Get card info first
    CRM.api3('MembershipCard', 'get', {id: cardId})
      .done(function(result) {
        if (result.values && result.values[0]) {
          const card = result.values[0];

          // Regenerate card
          CRM.api3('MembershipCard', 'generate', {
            membership_id: card.membership_id,
            template_id: card.template_id,
            force_regenerate: 1
          })
            .done(function(generateResult) {
              CRM.alert('Card regenerated successfully!', 'Success', 'success');

              // Refresh the card in the UI
              refreshCardDisplay(cardId);
            })
            .fail(function(error) {
              CRM.alert(error.error_message || 'Error regenerating card', 'Error', 'error');
            });
        }
      })
      .fail(function(error) {
        CRM.alert(error.error_message || 'Error loading card information', 'Error', 'error');
      });
  };

  /**
   * Download card functionality
   */
  function downloadCard(cardId, format = 'png') {
    const downloadUrl = CRM.url('civicrm/membership-card/download', {
      card_id: cardId,
      format: format
    });

    // Create temporary link for download
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = `membership-card-${cardId}.${format}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  /**
   * Refresh card display after updates
   */
  function refreshCardDisplay(cardId) {
    const $card = $(`.membership-card-item[data-card-id="${cardId}"]`);
    if ($card.length) {
      // Add visual feedback
      $card.addClass('card-updating');

      setTimeout(function() {
        // In a real implementation, you would reload the card data
        // For now, just remove the updating class
        $card.removeClass('card-updating');

        // Optionally reload the entire page
        // window.location.reload();
      }, 1000);
    }
  }

  /**
   * Save filter preferences to localStorage
   */
  function saveFilterPreferences() {
    $('.filters-form').on('change', 'select', function() {
      const preferences = {};
      $('.filters-form select').each(function() {
        const name = $(this).attr('name');
        const value = $(this).val();
        if (value) {
          preferences[name] = value;
        }
      });

      localStorage.setItem('membershipcard_filters', JSON.stringify(preferences));
    });
  }

  /**
   * Load filter preferences from localStorage
   */
  function loadFilterPreferences() {
    try {
      const preferences = JSON.parse(localStorage.getItem('membershipcard_filters') || '{}');

      Object.keys(preferences).forEach(function(name) {
        const $select = $(`.filters-form select[name="${name}"]`);
        if ($select.length) {
          $select.val(preferences[name]);
        }
      });
    } catch (e) {
      // Ignore localStorage errors
      console.warn('Could not load filter preferences:', e);
    }
  }

  /**
   * Utility function to format dates
   */
  function formatDate(dateString) {
    if (!dateString) return '';

    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch (e) {
      return dateString;
    }
  }

  /**
   * Search functionality
   */
  function initializeSearch() {
    let searchTimeout;

    $('#card-search').on('input', function() {
      const query = $(this).val().toLowerCase();

      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(function() {
        filterCards(query);
      }, 300);
    });

    function filterCards(query) {
      $('.membership-card-item').each(function() {
        const $card = $(this);
        const memberName = $card.find('.card-member-name').text().toLowerCase();
        const membershipType = $card.find('.detail-value').first().text().toLowerCase();

        const matches = memberName.includes(query) ||
          membershipType.includes(query) ||
          query === '';

        $card.toggle(matches);
      });

      // Update count
      const visibleCount = $('.membership-card-item:visible').length;
      $('.current-count').text(visibleCount);
    }
  }

  /**
   * Advanced filtering
   */
  function initializeAdvancedFilters() {
    // Status filter buttons
    $('.status-filter-btn').on('click', function() {
      const status = $(this).data('status');

      // Update active button
      $('.status-filter-btn').removeClass('active');
      $(this).addClass('active');

      // Filter cards
      if (status === 'all') {
        $('.membership-card-item').show();
      } else {
        $('.membership-card-item').each(function() {
          const $card = $(this);
          const hasStatus = $card.hasClass(`card-${status}`);
          $card.toggle(hasStatus);
        });
      }
    });
  }

  /**
   * Animation and visual effects
   */
  function initializeAnimations() {
    // Stagger card animations on page load
    $('.membership-card-item').each(function(index) {
      $(this).css('animation-delay', `${index * 0.1}s`);
    });

    // Smooth scroll to top
    $('.scroll-to-top').on('click', function(e) {
      e.preventDefault();
      $('html, body').animate({scrollTop: 0}, 500);
    });

    // Loading states for actions
    $('.card-actions .btn').on('click', function() {
      const $btn = $(this);
      if (!$btn.hasClass('no-loading')) {
        const originalText = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);

        // Restore after 2 seconds (or handle in specific action)
        setTimeout(function() {
          $btn.html(originalText).prop('disabled', false);
        }, 2000);
      }
    });
  }

  /**
   * Responsive behavior
   */
  function initializeResponsive() {
    let resizeTimeout;

    $(window).on('resize', function() {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(function() {
        adjustLayoutForViewport();
      }, 250);
    });

    function adjustLayoutForViewport() {
      const viewportWidth = $(window).width();

      // Adjust grid columns for different screen sizes
      if (viewportWidth < 768) {
        $('.cards-grid').addClass('mobile-layout');
      } else {
        $('.cards-grid').removeClass('mobile-layout');
      }

      // Adjust modal sizes
      if (viewportWidth < 576) {
        $('.modal-dialog').addClass('modal-fullscreen-sm-down');
      } else {
        $('.modal-dialog').removeClass('modal-fullscreen-sm-down');
      }
    }

    // Initial adjustment
    adjustLayoutForViewport();
  }

  /**
   * Accessibility enhancements
   */
  function initializeAccessibility() {
    // Add ARIA labels
    $('.membership-card-item').attr('role', 'article');
    $('.card-actions .btn').each(function() {
      const $btn = $(this);
      if (!$btn.attr('aria-label')) {
        const text = $btn.text().trim() || $btn.attr('title');
        if (text) {
          $btn.attr('aria-label', text);
        }
      }
    });

    // Keyboard navigation for cards
    $('.membership-card-item').attr('tabindex', '0').on('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        const cardId = $(this).data('card-id');
        if (cardId) {
          previewCard(cardId);
        }
      }
    });

    // Focus management for modals
    $('.modal').on('shown.bs.modal', function() {
      $(this).find('[autofocus], .btn-primary').first().focus();
    });
  }

  /**
   * Error handling
   */
  function initializeErrorHandling() {
    // Global AJAX error handler
    $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
      console.error('AJAX Error:', thrownError);

      // Don't show error for cancelled requests
      if (jqXHR.statusText !== 'abort') {
        CRM.alert('An error occurred while processing your request. Please try again.', 'Error', 'error');
      }
    });

    // Handle API errors gracefully
    window.handleApiError = function(error, context) {
      console.error(`API Error in ${context}:`, error);

      let message = 'An unexpected error occurred.';
      if (error.error_message) {
        message = error.error_message;
      } else if (error.responseJSON && error.responseJSON.error_message) {
        message = error.responseJSON.error_message;
      }

      CRM.alert(message, 'Error', 'error');
    };
  }

  // Initialize all functionality
  function initializeAll() {
    initializeSearch();
    initializeAdvancedFilters();
    initializeAnimations();
    initializeResponsive();
    initializeAccessibility();
    initializeErrorHandling();
  }

  // Call initialization when page is ready
  $(document).ready(function() {
    setTimeout(initializeAll, 100);
  });

})(CRM.$);

/**
 * Global utility functions
 */

// Format currency values
window.formatCurrency = function(amount, currency = 'USD') {
  if (typeof amount !== 'number') {
    amount = parseFloat(amount) || 0;
  }

  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency
  }).format(amount);
};

// Calculate days between dates
window.daysBetween = function(date1, date2) {
  const oneDay = 24 * 60 * 60 * 1000;
  const firstDate = new Date(date1);
  const secondDate = new Date(date2);

  return Math.round(Math.abs((firstDate - secondDate) / oneDay));
};

// Debounce function for performance
window.debounce = function(func, wait, immediate) {
  let timeout;
  return function executedFunction() {
    const context = this;
    const args = arguments;

    const later = function() {
      timeout = null;
      if (!immediate) func.apply(context, args);
    };

    const callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);

    if (callNow) func.apply(context, args);
  };
};
