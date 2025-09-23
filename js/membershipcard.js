/**
 * Enhanced JavaScript for Membership Cards - Fixes secondary actions dropdown
 */

(function($) {
  'use strict';

  // Initialize when DOM is ready
  $(document).ready(function() {
    initializeDropdowns();
    initializeCardActions();
    initializeModals();
  });

  /**
   * Initialize dropdown functionality for secondary actions
   */
  function initializeDropdowns() {
    console.log('Initializing membership card dropdowns...');

    // Handle dropdown toggle clicks
    $(document).on('click', '.secondary-actions .dropdown-toggle', function(e) {
      e.preventDefault();
      e.stopPropagation();

      var $this = $(this);
      var $dropdown = $this.closest('.dropdown, .btn-group');
      var $menu = $dropdown.find('.dropdown-menu');

      // Close other open dropdowns
      closeAllDropdowns();

      // Toggle current dropdown
      if ($dropdown.hasClass('open')) {
        $dropdown.removeClass('open');
        $menu.removeClass('show');
      } else {
        $dropdown.addClass('open');
        $menu.addClass('show');

        // Position dropdown if needed
        positionDropdown($menu);
      }
    });

    // Handle dropdown item clicks
    $(document).on('click', '.secondary-actions .dropdown-menu a', function(e) {
      var href = $(this).attr('href');
      if (href === '#' || href === '') {
        e.preventDefault();
      }

      // Close dropdown after click
      setTimeout(function() {
        closeAllDropdowns();
      }, 100);
    });

    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
      if (!$(e.target).closest('.secondary-actions').length) {
        closeAllDropdowns();
      }
    });

    // Close dropdowns on escape key
    $(document).on('keydown', function(e) {
      if (e.keyCode === 27) { // ESC key
        closeAllDropdowns();
      }
    });

    // Prevent dropdown from closing when clicking inside menu
    $(document).on('click', '.secondary-actions .dropdown-menu', function(e) {
      e.stopPropagation();
    });
  }

  /**
   * Close all open dropdowns
   */
  function closeAllDropdowns() {
    $('.secondary-actions .dropdown, .secondary-actions .btn-group')
      .removeClass('open');
    $('.secondary-actions .dropdown-menu')
      .removeClass('show');
  }

  /**
   * Position dropdown menu to prevent overflow
   */
  function positionDropdown($menu) {
    setTimeout(function() {
      var menuOffset = $menu.offset();
      var menuWidth = $menu.outerWidth();
      var menuHeight = $menu.outerHeight();
      var windowWidth = $(window).width();
      var windowHeight = $(window).height();
      var scrollTop = $(window).scrollTop();

      // Check if menu extends beyond right edge
      if (menuOffset.left + menuWidth > windowWidth) {
        $menu.css({
          'left': 'auto',
          'right': '0'
        });
      }

      // Check if menu extends beyond bottom edge
      if (menuOffset.top + menuHeight > windowHeight + scrollTop) {
        $menu.css({
          'top': 'auto',
          'bottom': '100%',
          'margin-top': '0',
          'margin-bottom': '2px'
        });
      }
    }, 10);
  }

  /**
   * Initialize card action handlers
   */
  function initializeCardActions() {
    console.log('Initializing card actions...');

    // Preview card action
    $(document).on('click', 'a[onclick*="previewCard"]', function(e) {
      e.preventDefault();
      var onclick = $(this).attr('onclick');
      var cardId = onclick.match(/previewCard\((\d+)\)/);
      if (cardId && cardId[1]) {
        previewCard(cardId[1]);
      }
    });

    // Email card action
    $(document).on('click', 'a[onclick*="emailCard"]', function(e) {
      e.preventDefault();
      var onclick = $(this).attr('onclick');
      var cardId = onclick.match(/emailCard\((\d+)\)/);
      if (cardId && cardId[1]) {
        emailCard(cardId[1]);
      }
    });

    // Regenerate card action
    $(document).on('click', 'a[onclick*="regenerateCard"]', function(e) {
      e.preventDefault();
      var onclick = $(this).attr('onclick');
      var cardId = onclick.match(/regenerateCard\((\d+)\)/);
      if (cardId && cardId[1]) {
        regenerateCard(cardId[1]);
      }
    });

    // Delete card action
    $(document).on('click', 'a[onclick*="deleteCard"]', function(e) {
      e.preventDefault();
      var onclick = $(this).attr('onclick');
      var matches = onclick.match(/deleteCard\((\d+),\s*'([^']*)'/);
      if (matches && matches[1] && matches[2]) {
        deleteCard(matches[1], matches[2]);
      }
    });

    // Download button loading state
    $(document).on('click', '.btn[href*="download"]', function() {
      var $btn = $(this);
      $btn.addClass('loading');

      setTimeout(function() {
        $btn.removeClass('loading');
      }, 2000);
    });
  }

  /**
   * Initialize modal handlers
   */
  function initializeModals() {
    // Reset forms when modals are hidden
    $('.modal').on('hidden.bs.modal', function() {
      $(this).find('form')[0]?.reset();
      $(this).find('.loading-spinner').show();
      $(this).find('.alert').remove();
    });

    // Email form validation
    $('#email-card-form').on('submit', function(e) {
      e.preventDefault();
      sendCardEmail();
    });

    // Delete confirmation
    $('#confirm-delete-card').on('click', function() {
      if (window.deleteCardId) {
        performCardDeletion(window.deleteCardId);
      }
    });
  }

  /**
   * Enhanced preview card function with better error handling
   */
  window.previewCard = function(cardId) {
    if (!cardId) {
      console.error('No card ID provided for preview');
      return;
    }

    $('#card-preview-modal').modal('show');
    $('#card-preview-content').html(`
      <div class="loading-spinner">
        <i class="fa fa-spinner fa-spin fa-2x"></i>
        <p>Loading card preview...</p>
      </div>
    `);

    // Check if CRM.api3 is available
    if (typeof CRM !== 'undefined' && CRM.api3) {
      CRM.api3('MembershipCard', 'download', {
        card_id: cardId,
        format: 'png'
      }).done(function(result) {
        if (result.values && result.values[0]) {
          const card = result.values[0];
          $('#card-preview-content').html(`
            <img src="${card.image_data}" alt="Membership Card" class="img-responsive" style="max-width: 100%; height: auto;">
          `);
          $('#download-preview-card').off('click').on('click', function() {
            window.open(CRM.url('civicrm/membership-card/download', {card_id: cardId}), '_blank');
          });
        } else {
          showPreviewError('No card data received');
        }
      }).fail(function(error) {
        showPreviewError(error.error_message || 'Failed to load preview');
      });
    } else {
      // Fallback for when CRM.api3 is not available
      var previewUrl = '/civicrm/membership-card/preview?card_id=' + cardId;
      $('#card-preview-content').html(`
        <iframe src="${previewUrl}" style="width: 100%; height: 400px; border: 1px solid #ddd;"></iframe>
      `);
    }
  };

  /**
   * Show preview error
   */
  function showPreviewError(message) {
    $('#card-preview-content').html(`
      <div class="alert alert-danger">
        <i class="fa fa-exclamation-triangle"></i>
        Error loading preview: ${message}
      </div>
    `);
  }

  /**
   * Enhanced email card function
   */
  window.emailCard = function(cardId) {
    if (!cardId) {
      console.error('No card ID provided for email');
      return;
    }

    $('#email-card-id').val(cardId);
    $('#email-card-modal').modal('show');

    // Pre-fill member email if available
    var $cardItem = $(`.membership-card-item[data-card-id="${cardId}"]`);
    if ($cardItem.length) {
      // Try to extract email from card data if available
      var memberName = $cardItem.find('.card-member-name a').text().trim();
      $('#email-subject').val(`Your Membership Card - ${memberName}`);
    }
  };

  /**
   * Send card email with improved error handling
   */
  window.sendCardEmail = function() {
    var formData = {
      card_id: $('#email-card-id').val(),
      email_to: $('#email-to').val(),
      email_subject: $('#email-subject').val(),
      email_message: $('#email-message').val()
    };

    var $form = $('#email-card-form');
    var $submitBtn = $form.find('button[type="submit"], .btn-primary');

    $submitBtn.addClass('loading').prop('disabled', true);

    if (typeof CRM !== 'undefined' && CRM.api3) {
      CRM.api3('MembershipCard', 'email', formData)
        .done(function(result) {
          $('#email-card-modal').modal('hide');
          showAlert('Card emailed successfully', 'success');
          $form[0].reset();
        })
        .fail(function(error) {
          showAlert(error.error_message || 'Error sending email', 'error');
        })
        .always(function() {
          $submitBtn.removeClass('loading').prop('disabled', false);
        });
    } else {
      // Fallback AJAX request
      $.post('/civicrm/membership-card/email', formData)
        .done(function(result) {
          $('#email-card-modal').modal('hide');
          showAlert('Card emailed successfully', 'success');
          $form[0].reset();
        })
        .fail(function() {
          showAlert('Error sending email', 'error');
        })
        .always(function() {
          $submitBtn.removeClass('loading').prop('disabled', false);
        });
    }
  };

  /**
   * Enhanced regenerate card function
   */
  window.regenerateCard = function(cardId) {
    if (!cardId) {
      console.error('No card ID provided for regeneration');
      return;
    }

    if (confirm('Are you sure you want to regenerate this card? This will replace the existing card.')) {
      var $cardItem = $(`.membership-card-item[data-card-id="${cardId}"]`);
      $cardItem.addClass('loading');

      if (typeof CRM !== 'undefined' && CRM.api3) {
        CRM.api3('MembershipCard', 'get', {id: cardId})
          .done(function(result) {
            if (result.values && result.values[0]) {
              const card = result.values[0];

              CRM.api3('MembershipCard', 'generate', {
                membership_id: card.membership_id,
                template_id: card.template_id,
                force_regenerate: 1
              })
                .done(function(generateResult) {
                  showAlert('Card regenerated successfully', 'success');
                  setTimeout(function() {
                    window.location.reload();
                  }, 1000);
                })
                .fail(function(error) {
                  showAlert(error.error_message || 'Error regenerating card', 'error');
                  $cardItem.removeClass('loading');
                });
            }
          })
          .fail(function(error) {
            showAlert(error.error_message || 'Error getting card info', 'error');
            $cardItem.removeClass('loading');
          });
      } else {
        // Fallback
        setTimeout(function() {
          window.location.href = `/civicrm/membership-card/regenerate?card_id=${cardId}`;
        }, 500);
      }
    }
  };

  /**
   * Enhanced delete card function
   */
  window.deleteCard = function(cardId, memberName) {
    if (!cardId) {
      console.error('No card ID provided for deletion');
      return;
    }

    window.deleteCardId = cardId;
    $('#delete-card-message').text(`Are you sure you want to delete the membership card for ${memberName}?`);
    $('#delete-card-modal').modal('show');
  };

  /**
   * Perform card deletion
   */
  function performCardDeletion(cardId) {
    var $confirmBtn = $('#confirm-delete-card');
    $confirmBtn.addClass('loading').prop('disabled', true);

    if (typeof CRM !== 'undefined' && CRM.api3) {
      CRM.api3('MembershipCard', 'delete', {id: cardId})
        .done(function(result) {
          $('#delete-card-modal').modal('hide');
          showAlert('Card deleted successfully', 'success');
          $(`.membership-card-item[data-card-id="${cardId}"]`).fadeOut(300, function() {
            $(this).remove();
          });
        })
        .fail(function(error) {
          showAlert(error.error_message || 'Error deleting card', 'error');
        })
        .always(function() {
          $confirmBtn.removeClass('loading').prop('disabled', false);
        });
    } else {
      // Fallback
      window.location.href = `/civicrm/membership-cards?action=delete&id=${cardId}`;
    }
  }

  /**
   * Show alert messages
   */
  function showAlert(message, type) {
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';

    var $alert = $(`
      <div class="alert ${alertClass} alert-dismissible fade in" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
        <button type="button" class="close" data-dismiss="alert">
          <span>&times;</span>
        </button>
        <i class="fa ${icon}"></i> ${message}
      </div>
    `);

    $('body').append($alert);

    setTimeout(function() {
      $alert.fadeOut(300, function() {
        $(this).remove();
      });
    }, 5000);
  }

  /**
   * Export functionality
   */
  window.exportCards = function(format) {
    if (!format) return;

    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('export', format);
    window.open(currentUrl.toString(), '_blank');
  };

  // Debug function
  window.debugDropdowns = function() {
    console.log('Secondary actions elements:', $('.secondary-actions').length);
    console.log('Dropdown toggles:', $('.secondary-actions .dropdown-toggle').length);
    console.log('Dropdown menus:', $('.secondary-actions .dropdown-menu').length);

    $('.secondary-actions').each(function(i, el) {
      console.log(`Dropdown ${i}:`, {
        element: el,
        hasToggle: $(el).find('.dropdown-toggle').length > 0,
        hasMenu: $(el).find('.dropdown-menu').length > 0,
        isOpen: $(el).find('.dropdown, .btn-group').hasClass('open')
      });
    });
  };

  // Call debug in console if needed: debugDropdowns()

})(CRM.$ || jQuery);
