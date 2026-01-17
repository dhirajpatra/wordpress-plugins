/**
 * Advanced Booking System - Public JavaScript
 *
 * @package AdvancedBookingSystem
 */

(function($) {
    'use strict';

    /**
     * Calendar Handler
     */
    var ABSCalendar = {
        currentMonth: null,
        currentYear: null,
        selectedDate: null,
        selectedRoom: null,

        init: function() {
            var self = this;
            var today = new Date();
            this.currentMonth = today.getMonth();
            this.currentYear = today.getFullYear();

            // Calendar navigation
            $(document).on('click', '.abs-calendar-prev', function(e) {
                e.preventDefault();
                self.previousMonth();
            });

            $(document).on('click', '.abs-calendar-next', function(e) {
                e.preventDefault();
                self.nextMonth();
            });

            // Date selection
            $(document).on('click', '.abs-calendar-table td.abs-date-available', function() {
                var date = $(this).data('date');
                self.selectDate(date);
            });

            // Room selection
            $(document).on('click', '.abs-room-card', function() {
                var roomId = $(this).data('room-id');
                self.selectRoom(roomId);
            });

            // Initialize if calendar exists
            if ($('.abs-calendar-container').length) {
                this.loadCalendar();
            }
        },

        previousMonth: function() {
            this.currentMonth--;
            if (this.currentMonth < 0) {
                this.currentMonth = 11;
                this.currentYear--;
            }
            this.loadCalendar();
        },

        nextMonth: function() {
            this.currentMonth++;
            if (this.currentMonth > 11) {
                this.currentMonth = 0;
                this.currentYear++;
            }
            this.loadCalendar();
        },

        loadCalendar: function() {
            var self = this;
            var $container = $('.abs-calendar-container');

            $.ajax({
                url: absPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'abs_load_calendar',
                    nonce: absPublic.nonce,
                    month: this.currentMonth,
                    year: this.currentYear,
                    room_id: this.selectedRoom
                },
                beforeSend: function() {
                    $container.addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                    } else {
                        self.showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    self.showMessage('error', absPublic.errorMessage);
                },
                complete: function() {
                    $container.removeClass('loading');
                }
            });
        },

        selectDate: function(date) {
            this.selectedDate = date;
            
            // Update visual selection
            $('.abs-calendar-table td').removeClass('abs-date-selected');
            $('.abs-calendar-table td[data-date="' + date + '"]').addClass('abs-date-selected');
            
            // Update hidden form field if exists
            $('#abs_booking_date').val(date);
            
            // Check availability for selected room and date
            if (this.selectedRoom) {
                this.checkAvailability();
            }

            // Trigger custom event
            $(document).trigger('abs:dateSelected', [date]);
        },

        selectRoom: function(roomId) {
            this.selectedRoom = roomId;
            
            // Update visual selection
            $('.abs-room-card').removeClass('selected');
            $('.abs-room-card[data-room-id="' + roomId + '"]').addClass('selected');
            
            // Update hidden form field if exists
            $('#abs_room_id').val(roomId);
            
            // Reload calendar with room availability
            this.loadCalendar();

            // Trigger custom event
            $(document).trigger('abs:roomSelected', [roomId]);
        },

        checkAvailability: function() {
            var self = this;

            $.ajax({
                url: absPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'abs_check_availability',
                    nonce: absPublic.nonce,
                    room_id: this.selectedRoom,
                    date: this.selectedDate
                },
                success: function(response) {
                    if (response.success) {
                        if (!response.data.available) {
                            self.showMessage('warning', response.data.message);
                        }
                    }
                }
            });
        },

        showMessage: function(type, message) {
            var $message = $('<div class="abs-message abs-message-' + type + '">' + message + '</div>');
            $('.abs-calendar-container').before($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    /**
     * Booking Form Handler
     */
    var ABSBookingForm = {
        init: function() {
            var self = this;

            // Form validation on submit
            $(document).on('submit', '.abs-booking-form', function(e) {
                e.preventDefault();
                self.submitForm($(this));
            });

            // Real-time validation
            $(document).on('blur', '.abs-booking-form input, .abs-booking-form select, .abs-booking-form textarea', function() {
                self.validateField($(this));
            });

            // Clear error on focus
            $(document).on('focus', '.abs-booking-form input, .abs-booking-form select, .abs-booking-form textarea', function() {
                $(this).closest('.abs-form-group').removeClass('has-error');
                $(this).siblings('.abs-form-error').remove();
            });
        },

        validateField: function($field) {
            var value = $field.val().trim();
            var type = $field.attr('type');
            var required = $field.prop('required');
            var $group = $field.closest('.abs-form-group');
            var error = '';

            // Clear previous errors
            $group.removeClass('has-error');
            $field.siblings('.abs-form-error').remove();

            // Required field check
            if (required && !value) {
                error = absPublic.requiredField;
            }
            // Email validation
            else if (type === 'email' && value && !this.isValidEmail(value)) {
                error = absPublic.invalidEmail;
            }
            // Phone validation
            else if (type === 'tel' && value && !this.isValidPhone(value)) {
                error = absPublic.invalidPhone;
            }

            // Show error if found
            if (error) {
                $group.addClass('has-error');
                $field.after('<span class="abs-form-error">' + error + '</span>');
                return false;
            }

            return true;
        },

        validateForm: function($form) {
            var self = this;
            var isValid = true;

            $form.find('input[required], select[required], textarea[required]').each(function() {
                if (!self.validateField($(this))) {
                    isValid = false;
                }
            });

            return isValid;
        },

        submitForm: function($form) {
            var self = this;

            // Validate form
            if (!this.validateForm($form)) {
                this.showFormMessage($form, 'error', absPublic.validationError);
                return;
            }

            var formData = $form.serialize();
            var $submitBtn = $form.find('button[type="submit"]');

            $.ajax({
                url: absPublic.ajaxUrl,
                type: 'POST',
                data: formData + '&action=abs_submit_booking&nonce=' + absPublic.nonce,
                beforeSend: function() {
                    $submitBtn.prop('disabled', true).html('<span class="abs-loading"></span> ' + absPublic.submitting);
                },
                success: function(response) {
                    if (response.success) {
                        self.showFormMessage($form, 'success', response.data.message);
                        $form[0].reset();
                        
                        // Redirect if URL provided
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 2000);
                        }
                    } else {
                        self.showFormMessage($form, 'error', response.data.message);
                    }
                },
                error: function() {
                    self.showFormMessage($form, 'error', absPublic.errorMessage);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html(absPublic.submitLabel);
                }
            });
        },

        showFormMessage: function($form, type, message) {
            // Remove existing messages
            $form.find('.abs-message').remove();

            // Add new message
            var $message = $('<div class="abs-message abs-message-' + type + '">' + message + '</div>');
            $form.prepend($message);

            // Scroll to message
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 500);

            // Auto-remove after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        },

        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        isValidPhone: function(phone) {
            var cleaned = phone.replace(/[\s\-\(\)\+]/g, '');
            var regex = /^\d{7,15}$/;
            return regex.test(cleaned);
        }
    };

    /**
     * User Dashboard
     */
    var ABSDashboard = {
        init: function() {
            var self = this;

            // Cancel booking
            $(document).on('click', '.abs-cancel-booking', function(e) {
                e.preventDefault();
                var bookingId = $(this).data('booking-id');
                
                if (confirm(absPublic.confirmCancel)) {
                    self.cancelBooking(bookingId);
                }
            });
        },

        cancelBooking: function(bookingId) {
            $.ajax({
                url: absPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'abs_cancel_booking',
                    nonce: absPublic.nonce,
                    booking_id: bookingId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(absPublic.errorMessage);
                }
            });
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        ABSCalendar.init();
        ABSBookingForm.init();
        ABSDashboard.init();

        // Trigger custom event
        $(document).trigger('abs:ready');
    });

})(jQuery);