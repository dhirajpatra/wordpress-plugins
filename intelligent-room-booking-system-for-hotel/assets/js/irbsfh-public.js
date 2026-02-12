/**
 * Intelligent Room Booking System for Hotel - Public JavaScript
 *
 * @package IntelligentRoomBookingSystemForHotel
 */

(function ($) {
    'use strict';

    /**
     * Calendar Handler
     */
    var IRBSFHCalendar = {
        currentMonth: null,
        currentYear: null,
        selectedDate: null,
        selectedRoom: null,

        init: function () {
            var self = this;
            var today = new Date();
            this.currentMonth = today.getMonth();
            this.currentYear = today.getFullYear();

            // Calendar navigation
            $(document).on('click', '.irbsfh-calendar-prev', function (e) {
                e.preventDefault();
                self.previousMonth();
            });

            $(document).on('click', '.irbsfh-calendar-next', function (e) {
                e.preventDefault();
                self.nextMonth();
            });

            // Date selection
            $(document).on('click', '.irbsfh-calendar-table td.irbsfh-date-available', function () {
                var date = $(this).data('date');
                self.selectDate(date);
            });

            // Room selection
            $(document).on('click', '.irbsfh-room-card', function () {
                var roomId = $(this).data('room-id');
                self.selectRoom(roomId);
            });

            // Initialize if calendar exists
            if ($('.irbsfh-calendar-container').length) {
                this.loadCalendar();
            }
        },

        previousMonth: function () {
            this.currentMonth--;
            if (this.currentMonth < 0) {
                this.currentMonth = 11;
                this.currentYear--;
            }
            this.loadCalendar();
        },

        nextMonth: function () {
            this.currentMonth++;
            if (this.currentMonth > 11) {
                this.currentMonth = 0;
                this.currentYear++;
            }
            this.loadCalendar();
        },

        loadCalendar: function () {
            var self = this;
            var $container = $('.irbsfh-calendar-container');

            $.ajax({
                url: irbsfhPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'irbsfh_load_calendar',
                    nonce: irbsfhPublic.nonce,
                    month: this.currentMonth,
                    year: this.currentYear,
                    room_id: this.selectedRoom
                },
                beforeSend: function () {
                    $container.addClass('loading');
                },
                success: function (response) {
                    if (response.success) {
                        $container.html(response.data.html);
                    } else {
                        self.showMessage('error', response.data.message);
                    }
                },
                error: function () {
                    self.showMessage('error', irbsfhPublic.errorMessage);
                },
                complete: function () {
                    $container.removeClass('loading');
                }
            });
        },

        selectDate: function (date) {
            this.selectedDate = date;

            // Update visual selection
            $('.irbsfh-calendar-table td').removeClass('irbsfh-date-selected');
            $('.irbsfh-calendar-table td[data-date="' + date + '"]').addClass('irbsfh-date-selected');

            // Update hidden form field if exists
            $('#irbsfh_booking_date').val(date);

            // Check availability for selected room and date
            if (this.selectedRoom) {
                this.checkAvailability();
            }

            // Trigger custom event
            $(document).trigger('irbsfh:dateSelected', [date]);
        },

        selectRoom: function (roomId) {
            this.selectedRoom = roomId;

            // Update visual selection
            $('.irbsfh-room-card').removeClass('selected');
            $('.irbsfh-room-card[data-room-id="' + roomId + '"]').addClass('selected');

            // Update hidden form field if exists
            $('#irbsfh_room_id').val(roomId);

            // Reload calendar with room availability
            this.loadCalendar();

            // Trigger custom event
            $(document).trigger('irbsfh:roomSelected', [roomId]);
        },

        checkAvailability: function () {
            var self = this;

            $.ajax({
                url: irbsfhPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'irbsfh_check_availability',
                    nonce: irbsfhPublic.nonce,
                    room_id: this.selectedRoom,
                    date: this.selectedDate
                },
                success: function (response) {
                    if (response.success) {
                        if (!response.data.available) {
                            self.showMessage('warning', response.data.message);
                        }
                    }
                }
            });
        },

        showMessage: function (type, message) {
            var $message = $('<div class="irbsfh-message irbsfh-message-' + type + '">' + message + '</div>');
            $('.irbsfh-calendar-container').before($message);

            setTimeout(function () {
                $message.fadeOut(function () {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    /**
     * Booking Form Handler
     */
    var IRBSFHBookingForm = {
        init: function () {
            var self = this;

            // Form validation on submit
            $(document).on('submit', '.irbsfh-booking-form', function (e) {
                e.preventDefault();
                self.submitForm($(this));
            });

            // Real-time validation
            $(document).on('blur', '.irbsfh-booking-form input, .irbsfh-booking-form select, .irbsfh-booking-form textarea', function () {
                self.validateField($(this));
            });

            // Clear error on focus
            $(document).on('focus', '.irbsfh-booking-form input, .irbsfh-booking-form select, .irbsfh-booking-form textarea', function () {
                $(this).closest('.irbsfh-form-group').removeClass('has-error');
                $(this).siblings('.irbsfh-form-error').remove();
            });
        },

        validateField: function ($field) {
            var value = $field.val().trim();
            var type = $field.attr('type');
            var required = $field.prop('required');
            var $group = $field.closest('.irbsfh-form-group');
            var error = '';

            // Clear previous errors
            $group.removeClass('has-error');
            $field.siblings('.irbsfh-form-error').remove();

            // Required field check
            if (required && !value) {
                error = irbsfhPublic.requiredField;
            }
            // Email validation
            else if (type === 'email' && value && !this.isValidEmail(value)) {
                error = irbsfhPublic.invalidEmail;
            }
            // Phone validation
            else if (type === 'tel' && value && !this.isValidPhone(value)) {
                error = irbsfhPublic.invalidPhone;
            }

            // Show error if found
            if (error) {
                $group.addClass('has-error');
                $field.after('<span class="irbsfh-form-error">' + error + '</span>');
                return false;
            }

            return true;
        },

        validateForm: function ($form) {
            var self = this;
            var isValid = true;

            $form.find('input[required], select[required], textarea[required]').each(function () {
                if (!self.validateField($(this))) {
                    isValid = false;
                }
            });

            return isValid;
        },

        submitForm: function ($form) {
            var self = this;

            // Validate form
            if (!this.validateForm($form)) {
                this.showFormMessage($form, 'error', irbsfhPublic.validationError);
                return;
            }

            var formData = $form.serialize();
            var $submitBtn = $form.find('button[type="submit"]');

            $.ajax({
                url: irbsfhPublic.ajaxUrl,
                type: 'POST',
                data: formData + '&action=irbsfh_submit_booking&nonce=' + irbsfhPublic.nonce,
                beforeSend: function () {
                    $submitBtn.prop('disabled', true).html('<span class="irbsfh-loading"></span> ' + irbsfhPublic.submitting);
                },
                success: function (response) {
                    if (response.success) {
                        self.showFormMessage($form, 'success', response.data.message);
                        $form[0].reset();

                        // Redirect if URL provided
                        if (response.data.redirect) {
                            setTimeout(function () {
                                window.location.href = response.data.redirect;
                            }, 2000);
                        }
                    } else {
                        self.showFormMessage($form, 'error', response.data.message);
                    }
                },
                error: function () {
                    self.showFormMessage($form, 'error', irbsfhPublic.errorMessage);
                },
                complete: function () {
                    $submitBtn.prop('disabled', false).html(irbsfhPublic.submitLabel);
                }
            });
        },

        showFormMessage: function ($form, type, message) {
            // Remove existing messages
            $form.find('.irbsfh-message').remove();

            // Add new message
            var $message = $('<div class="irbsfh-message irbsfh-message-' + type + '">' + message + '</div>');
            $form.prepend($message);

            // Scroll to message
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 500);

            // Auto-remove after 5 seconds
            if (type === 'success') {
                setTimeout(function () {
                    $message.fadeOut(function () {
                        $(this).remove();
                    });
                }, 5000);
            }
        },

        isValidEmail: function (email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        isValidPhone: function (phone) {
            var cleaned = phone.replace(/[\s\-\(\)\+]/g, '');
            var regex = /^\d{7,15}$/;
            return regex.test(cleaned);
        }
    };

    /**
     * User Dashboard
     */
    var IRBSFHDashboard = {
        init: function () {
            var self = this;

            // Cancel booking
            $(document).on('click', '.irbsfh-cancel-booking', function (e) {
                e.preventDefault();
                var bookingId = $(this).data('booking-id');

                if (confirm(irbsfhPublic.confirmCancel)) {
                    self.cancelBooking(bookingId);
                }
            });
        },

        cancelBooking: function (bookingId) {
            $.ajax({
                url: irbsfhPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'irbsfh_cancel_booking',
                    nonce: irbsfhPublic.nonce,
                    booking_id: bookingId
                },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert(irbsfhPublic.errorMessage);
                }
            });
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function () {
        IRBSFHCalendar.init();
        IRBSFHBookingForm.init();
        IRBSFHDashboard.init();

        // Trigger custom event
        $(document).trigger('irbsfh:ready');
    });

})(jQuery);