/**
 * Intelligent Room Booking System for Hotel - Admin JavaScript
 *
 * @package IntelligentRoomBookingSystemForHotel
 */

(function ($) {
    'use strict';

    /**
     * Bookings Management
     */
    var IRBSFHAdmin = {
        init: function () {
            var self = this;

            // Confirm booking
            $(document).on('click', '.irbsfh-confirm-booking', function (e) {
                e.preventDefault();
                var bookingId = $(this).data('booking-id');
                self.confirmBooking(bookingId);
            });

            // Deny booking
            $(document).on('click', '.irbsfh-deny-booking', function (e) {
                e.preventDefault();
                var bookingId = $(this).data('booking-id');

                if (confirm(irbsfhAdmin.confirmDeny)) {
                    self.denyBooking(bookingId);
                }
            });

            // Delete booking
            $(document).on('click', '.irbsfh-delete-booking', function (e) {
                e.preventDefault();
                var bookingId = $(this).data('booking-id');

                if (confirm(irbsfhAdmin.confirmDelete)) {
                    self.deleteBooking(bookingId);
                }
            });

            // Bulk actions
            $(document).on('change', '#irbsfh-bulk-action', function () {
                var action = $(this).val();
                if (action) {
                    self.handleBulkAction(action);
                }
            });

            // Select all checkboxes
            $(document).on('change', '#irbsfh-select-all', function () {
                $('.irbsfh-booking-checkbox').prop('checked', $(this).prop('checked'));
            });

            // Status filter
            $(document).on('change', '#irbsfh-status-filter', function () {
                var status = $(this).val();
                self.filterByStatus(status);
            });

            // Room filter
            $(document).on('change', '#irbsfh-room-filter', function () {
                var roomId = $(this).val();
                self.filterByRoom(roomId);
            });

            // Date range filter
            $(document).on('click', '#irbsfh-apply-date-filter', function (e) {
                e.preventDefault();
                self.applyDateFilter();
            });
        },

        confirmBooking: function (bookingId) {
            var self = this;

            $.ajax({
                url: irbsfhAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'irbsfh_admin_confirm_booking',
                    nonce: irbsfhAdmin.nonce,
                    booking_id: bookingId
                },
                beforeSend: function () {
                    self.showLoader();
                },
                success: function (response) {
                    if (response.success) {
                        self.showNotice('success', response.data.message);
                        self.updateBookingRow(bookingId, 'confirmed');
                    } else {
                        self.showNotice('error', response.data.message);
                    }
                },
                error: function () {
                    self.showNotice('error', irbsfhAdmin.errorMessage);
                },
                complete: function () {
                    self.hideLoader();
                }
            });
        },

        denyBooking: function (bookingId) {
            var self = this;

            $.ajax({
                url: irbsfhAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'irbsfh_admin_deny_booking',
                    nonce: irbsfhAdmin.nonce,
                    booking_id: bookingId
                },
                beforeSend: function () {
                    self.showLoader();
                },
                success: function (response) {
                    if (response.success) {
                        self.showNotice('success', response.data.message);
                        self.updateBookingRow(bookingId, 'cancelled');
                    } else {
                        self.showNotice('error', response.data.message);
                    }
                },
                error: function () {
                    self.showNotice('error', irbsfhAdmin.errorMessage);
                },
                complete: function () {
                    self.hideLoader();
                }
            });
        },

        deleteBooking: function (bookingId) {
            var self = this;

            $.ajax({
                url: irbsfhAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'irbsfh_admin_delete_booking',
                    nonce: irbsfhAdmin.nonce,
                    booking_id: bookingId
                },
                beforeSend: function () {
                    self.showLoader();
                },
                success: function (response) {
                    if (response.success) {
                        self.showNotice('success', response.data.message);
                        $('#booking-row-' + bookingId).fadeOut(function () {
                            $(this).remove();
                        });
                    } else {
                        self.showNotice('error', response.data.message);
                    }
                },
                error: function () {
                    self.showNotice('error', irbsfhAdmin.errorMessage);
                },
                complete: function () {
                    self.hideLoader();
                }
            });
        },

        handleBulkAction: function (action) {
            var self = this;
            var bookingIds = [];

            $('.irbsfh-booking-checkbox:checked').each(function () {
                bookingIds.push($(this).val());
            });

            if (bookingIds.length === 0) {
                alert(irbsfhAdmin.noSelection);
                return;
            }

            var confirmMsg = action === 'delete' ? irbsfhAdmin.confirmBulkDelete : irbsfhAdmin.confirmBulkAction;

            if (!confirm(confirmMsg)) {
                return;
            }

            $.ajax({
                url: irbsfhAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'irbsfh_admin_bulk_action',
                    nonce: irbsfhAdmin.nonce,
                    bulk_action: action,
                    booking_ids: bookingIds
                },
                beforeSend: function () {
                    self.showLoader();
                },
                success: function (response) {
                    if (response.success) {
                        self.showNotice('success', response.data.message);
                        location.reload();
                    } else {
                        self.showNotice('error', response.data.message);
                    }
                },
                error: function () {
                    self.showNotice('error', irbsfhAdmin.errorMessage);
                },
                complete: function () {
                    self.hideLoader();
                }
            });
        },

        updateBookingRow: function (bookingId, newStatus) {
            var $row = $('#booking-row-' + bookingId);
            var $statusBadge = $row.find('.irbsfh-status-badge');

            // Update status badge
            $statusBadge.removeClass('pending confirmed cancelled')
                .addClass(newStatus)
                .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));

            // Update action buttons
            var $actions = $row.find('.irbsfh-actions');
            if (newStatus === 'confirmed') {
                $actions.find('.irbsfh-confirm-booking').remove();
            } else if (newStatus === 'cancelled') {
                $actions.find('.irbsfh-confirm-booking, .irbsfh-deny-booking').remove();
            }
        },

        filterByStatus: function (status) {
            var currentUrl = window.location.href;
            var url = new URL(currentUrl);

            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }

            window.location.href = url.toString();
        },

        filterByRoom: function (roomId) {
            var currentUrl = window.location.href;
            var url = new URL(currentUrl);

            if (roomId) {
                url.searchParams.set('room_id', roomId);
            } else {
                url.searchParams.delete('room_id');
            }

            window.location.href = url.toString();
        },

        applyDateFilter: function () {
            var startDate = $('#irbsfh-date-from').val();
            var endDate = $('#irbsfh-date-to').val();
            var currentUrl = window.location.href;
            var url = new URL(currentUrl);

            if (startDate) {
                url.searchParams.set('date_from', startDate);
            } else {
                url.searchParams.delete('date_from');
            }

            if (endDate) {
                url.searchParams.set('date_to', endDate);
            } else {
                url.searchParams.delete('date_to');
            }

            window.location.href = url.toString();
        },

        showLoader: function () {
            if (!$('#irbsfh-admin-loader').length) {
                $('body').append('<div id="irbsfh-admin-loader" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999999;display:flex;align-items:center;justify-content:center;"><div class="irbsfh-loading" style="width:50px;height:50px;border-width:5px;"></div></div>');
            }
        },

        hideLoader: function () {
            $('#irbsfh-admin-loader').remove();
        },

        showNotice: function (type, message) {
            // Remove existing notices
            $('.irbsfh-notice').remove();

            var $notice = $('<div class="irbsfh-notice ' + type + '">' + message + '</div>');
            $('.irbsfh-admin-wrap').prepend($notice);

            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 500);

            // Auto-remove after 5 seconds
            setTimeout(function () {
                $notice.fadeOut(function () {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    /**
     * Email Template Editor
     */
    var IRBSFHEmailEditor = {
        init: function () {
            // Insert template tag on click
            $(document).on('click', '.irbsfh-email-template-vars li', function () {
                var tag = $(this).text();
                var $textarea = $(this).closest('.irbsfh-setting-input').find('textarea');

                // Insert at cursor position
                var cursorPos = $textarea.prop('selectionStart');
                var textBefore = $textarea.val().substring(0, cursorPos);
                var textAfter = $textarea.val().substring(cursorPos);

                $textarea.val(textBefore + tag + textAfter);

                // Move cursor after inserted tag
                var newPos = cursorPos + tag.length;
                $textarea[0].setSelectionRange(newPos, newPos);
                $textarea.focus();
            });

            // Make template vars clickable
            $('.irbsfh-email-template-vars li').css('cursor', 'pointer');
        }
    };

    /**
     * Room Management
     */
    var IRBSFHRoomManager = {
        init: function () {
            var self = this;

            // Add new room
            $(document).on('click', '#irbsfh-add-room', function (e) {
                e.preventDefault();
                self.showRoomModal();
            });

            // Edit room
            $(document).on('click', '.irbsfh-edit-room', function (e) {
                e.preventDefault();
                var roomId = $(this).data('room-id');
                self.editRoom(roomId);
            });

            // Delete room
            $(document).on('click', '.irbsfh-delete-room', function (e) {
                e.preventDefault();
                var roomId = $(this).data('room-id');

                if (confirm(irbsfhAdmin.confirmDeleteRoom)) {
                    self.deleteRoom(roomId);
                }
            });

            // Save room (modal)
            $(document).on('click', '#irbsfh-save-room', function (e) {
                e.preventDefault();
                self.saveRoom();
            });

            // Close modal
            $(document).on('click', '.irbsfh-modal-close, .irbsfh-modal-overlay', function (e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });
        },

        showRoomModal: function (roomData) {
            var modalHtml = this.getRoomModalHtml(roomData);
            $('body').append(modalHtml);
        },

        editRoom: function (roomId) {
            var self = this;

            $.ajax({
                url: irbsfhAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'irbsfh_get_room',
                    nonce: irbsfhAdmin.nonce,
                    room_id: roomId
                },
                success: function (response) {
                    if (response.success) {
                        self.showRoomModal(response.data.room);
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert(irbsfhAdmin.errorMessage);
                }
            });
        },

        saveRoom: function () {
            var self = this;
            var formData = $('#irbsfh-room-form').serialize();

            $.ajax({
                url: irbsfhAdmin.ajaxUrl,
                type: 'POST',
                data: formData + '&action=irbsfh_save_room&nonce=' + irbsfhAdmin.nonce,
                success: function (response) {
                    if (response.success) {
                        IRBSFHAdmin.showNotice('success', response.data.message);
                        self.closeModal();
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert(irbsfhAdmin.errorMessage);
                }
            });
        },

        deleteRoom: function (roomId) {
            $.ajax({
                url: irbsfhAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'irbsfh_delete_room',
                    nonce: irbsfhAdmin.nonce,
                    room_id: roomId
                },
                success: function (response) {
                    if (response.success) {
                        IRBSFHAdmin.showNotice('success', response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert(irbsfhAdmin.errorMessage);
                }
            });
        },

        getRoomModalHtml: function (roomData) {
            roomData = roomData || {};

            return '<div class="irbsfh-modal-overlay">' +
                '<div class="irbsfh-modal">' +
                '<div class="irbsfh-modal-header">' +
                '<h2>' + (roomData.id ? irbsfhAdmin.editRoom : irbsfhAdmin.addRoom) + '</h2>' +
                '<button class="irbsfh-modal-close">&times;</button>' +
                '</div>' +
                '<div class="irbsfh-modal-body">' +
                '<form id="irbsfh-room-form">' +
                '<input type="hidden" name="room_id" value="' + (roomData.id || '') + '">' +
                '<div class="irbsfh-form-group">' +
                '<label>Room Name <span class="required">*</span></label>' +
                '<input type="text" name="room_name" value="' + (roomData.name || '') + '" required>' +
                '</div>' +
                '<div class="irbsfh-form-group">' +
                '<label>Booking Title</label>' +
                '<input type="text" name="booking_title" value="' + (roomData.booking_title || 'Room') + '" placeholder="e.g., Room, Court, Chalet">' +
                '</div>' +
                '<div class="irbsfh-form-group">' +
                '<label>Max Bookings Per User</label>' +
                '<input type="number" name="max_bookings" value="' + (roomData.max_bookings_per_user || 3) + '" min="1">' +
                '</div>' +
                '<div class="irbsfh-form-group">' +
                '<label>Description</label>' +
                '<textarea name="description">' + (roomData.description || '') + '</textarea>' +
                '</div>' +
                '</form>' +
                '</div>' +
                '<div class="irbsfh-modal-footer">' +
                '<button type="button" class="button" onclick="jQuery(\'.irbsfh-modal-overlay\').remove()">Cancel</button>' +
                '<button type="button" id="irbsfh-save-room" class="button button-primary">Save Room</button>' +
                '</div>' +
                '</div>' +
                '</div>';
        },

        closeModal: function () {
            $('.irbsfh-modal-overlay').remove();
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function () {
        IRBSFHAdmin.init();
        IRBSFHEmailEditor.init();
        IRBSFHRoomManager.init();

        // Initialize datepicker if available
        if ($.fn.datepicker) {
            $('.irbsfh-datepicker').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        }
    });

})(jQuery);