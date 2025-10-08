/**
 * Management System - Main JavaScript
 * Handles all interactive elements of the website
 */

document.addEventListener('DOMContentLoaded', function() {
    // =============================================
    // Mobile menu toggle and submenu handling
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mainNav = document.getElementById('mainNav');
    const navLinks = document.querySelectorAll('.has-submenu > .nav-link');
    
    // Toggle mobile menu
    if (mobileMenuToggle && mainNav) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isExpanded = this.getAttribute('aria-expanded') === 'true' || false;
            this.setAttribute('aria-expanded', !isExpanded);
            mainNav.classList.toggle('active');
            
            // Toggle body scroll when menu is open
            document.body.style.overflow = isExpanded ? '' : 'hidden';
            
            // Close all submenus when toggling mobile menu
            if (!isExpanded) {
                document.querySelectorAll('.submenu').forEach(submenu => {
                    submenu.classList.remove('active');
                });
            }
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!mobileMenuToggle.contains(e.target) && !mainNav.contains(e.target)) {
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
            mobileMenuToggle.classList.remove('active');
            mainNav.classList.remove('active');
            document.body.classList.remove('menu-open');
            document.body.style.overflow = '';
            
            // Close all submenus
            document.querySelectorAll('.submenu').forEach(menu => {
                menu.classList.remove('active');
                const parentLink = menu.previousElementSibling;
                if (parentLink) {
                    parentLink.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
    
    // =============================================
    // User Menu Toggle
    // =============================================
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userMenuToggle && userDropdown) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isExpanded = this.getAttribute('aria-expanded') === 'true' || false;
            this.setAttribute('aria-expanded', !isExpanded);
            userDropdown.classList.toggle('show');
            
            // Close mobile menu if open
            if (mainNav && mainNav.classList.contains('active')) {
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
                mobileMenuToggle.classList.remove('active');
                mainNav.classList.remove('active');
                document.body.classList.remove('menu-open');
                document.body.style.overflow = '';
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenuToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                userMenuToggle.setAttribute('aria-expanded', 'false');
                userDropdown.classList.remove('show');
            }
        });
    }
    
    // =============================================
    // Navigation Menu
    // =============================================
    // Submenu Toggle for Mobile
    // Handle submenu toggles on mobile
    navLinks.forEach(link => {
        const submenu = link.nextElementSibling;
        if (submenu && submenu.classList.contains('submenu')) {
            // Toggle submenu on mobile
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 991.98) { // Mobile view
                    e.preventDefault();
                    e.stopPropagation();
                    const isExpanded = link.getAttribute('aria-expanded') === 'true' || false;
                    link.setAttribute('aria-expanded', !isExpanded);
                    submenu.classList.toggle('active');
                    
                    // Close other open submenus
                    document.querySelectorAll('.submenu').forEach(menu => {
                        if (menu !== submenu) {
                            menu.classList.remove('active');
                            const parentLink = menu.previousElementSibling;
                            if (parentLink) {
                                parentLink.setAttribute('aria-expanded', 'false');
                            }
                        }
                    });
                }
            });
        }
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!mainNav.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
            mainNav.classList.remove('active');
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
            
            // Close all submenus
            document.querySelectorAll('.submenu').forEach(menu => {
                menu.classList.remove('active');
                const parentLink = menu.previousElementSibling;
                if (parentLink) {
                    parentLink.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
    
    // =============================================
    // Window Resize Handler
    // =============================================
    function handleResize() {
        if (window.innerWidth > 991.98) {
            // Reset mobile menu state on desktop
            if (mobileMenuToggle) {
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
            }
            if (mainNav) {
                mainNav.classList.remove('active');
            }
            // Close all submenus when resizing to desktop
            document.querySelectorAll('.submenu').forEach(submenu => {
                submenu.classList.remove('active');
                const parentLink = submenu.previousElementSibling;
                if (parentLink) {
                    parentLink.setAttribute('aria-expanded', 'false');
                }
            });
            document.body.style.overflow = '';
        }
    }
    
    // Debounce resize handler
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(handleResize, 250);
    });
    
    // Handle keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Close all menus when pressing Escape
            if (mainNav.classList.contains('active')) {
                mainNav.classList.remove('active');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
            
            // Close all submenus
            document.querySelectorAll('.submenu').forEach(menu => {
                menu.classList.remove('active');
                const parentLink = menu.previousElementSibling;
                if (parentLink) {
                    parentLink.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
    
    // =============================================
    // Back to Top Button
    // =============================================
    const backToTopButton = document.getElementById('backToTop');
    
    if (backToTopButton) {
        // Show/hide button on scroll
        window.addEventListener('scroll', throttle(function() {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('show');
            } else {
                backToTopButton.classList.remove('show');
            }
        }, 150));
        
        // Smooth scroll to top
        backToTopButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
            this.blur();
        });
    }
    
    // =============================================
    // Form Validation
    // =============================================
    const forms = document.querySelectorAll('form[data-validate]');
    
    if (forms.length > 0) {
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!validateForm(this)) {
                    e.preventDefault();
                }
            });
        });
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Handle flash messages
    const flashMessages = document.querySelectorAll('.alert');
    
    if (flashMessages.length > 0) {
        flashMessages.forEach(message => {
            // Auto-hide success messages after 5 seconds
            if (message.classList.contains('alert-success')) {
                setTimeout(() => {
                    fadeOut(message);
                }, 5000);
            }
            
            // Add close button functionality
            const closeButton = message.querySelector('.btn-close');
            if (closeButton) {
                closeButton.addEventListener('click', function() {
                    fadeOut(message);
                });
            }
        });
    }
    
    // Handle image preview for file inputs
    const fileInputs = document.querySelectorAll('.file-input');
    
    fileInputs.forEach(input => {
        const preview = document.getElementById(input.dataset.preview);
        
        if (preview) {
            input.addEventListener('change', function() {
                const file = this.files[0];
                
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
        }
    });
    
    // Handle data tables if any
    const dataTables = document.querySelectorAll('.data-table');
    
    if (dataTables.length > 0 && $.fn.DataTable) {
        dataTables.forEach(table => {
            $(table).DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Persian.json'
                },
                responsive: true,
                order: [],
                pageLength: 25,
                dom: '<"table-responsive"t>ip',
                initComplete: function() {
                    this.api().columns().every(function() {
                        const column = this;
                        const header = $(column.header());
                        
                        // Add search input for each column
                        if (header.data('search') !== false) {
                            const input = $('<input type="text" class="form-control form-control-sm" placeholder="جستجو...">')
                                .appendTo(header.empty())
                                .on('keyup change', function() {
                                    if (column.search() !== this.value) {
                                        column.search(this.value).draw();
                                    }
                                });
                        }
                    });
                }
            });
        });
    }
    
    // Handle AJAX forms
    const ajaxForms = document.querySelectorAll('form[data-ajax]');
    
    ajaxForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('[type="submit"]');
            const originalButtonText = submitButton ? submitButton.innerHTML : '';
            
            // Show loading state
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> در حال ارسال...';
            }
            
            fetch(this.action || window.location.href, {
                method: this.method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Handle response
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.message) {
                    showAlert(data.message, data.status || 'success');
                    
                    // Reset form if needed
                    if (data.resetForm) {
                        this.reset();
                    }
                    
                    // Reload page if needed
                    if (data.reload) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('خطا در ارسال اطلاعات. لطفا مجددا تلاش کنید.', 'error');
            })
            .finally(() => {
                // Reset button state
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            });
        });
    });
    
    // Handle delete confirmations
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('آیا از حذف این مورد اطمینان دارید؟ این عمل قابل بازگشت نیست.')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    });
    
    // Handle print buttons
    const printButtons = document.querySelectorAll('[data-print]');
    
    printButtons.forEach(button => {
        button.addEventListener('click', function() {
            const printSection = document.querySelector(this.dataset.print);
            
            if (printSection) {
                const printContents = printSection.innerHTML;
                const originalContents = document.body.innerHTML;
                
                document.body.innerHTML = `
                    <!DOCTYPE html>
                    <html dir="rtl" lang="fa">
                    <head>
                        <meta charset="UTF-8">
                        <title>چاپ سند</title>
                        <link rel="stylesheet" href="assets/css/print.css" media="print">
                        <style>
                            @page { size: A4; margin: 1cm; }
                            body { font-family: 'Sahel', Tahoma, sans-serif; font-size: 12pt; }
                            .no-print { display: none !important; }
                            .print-only { display: block !important; }
                            table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
                            th, td { border: 1px solid #dee2e6; padding: 0.5rem; text-align: right; }
                            th { background-color: #f8f9fa; font-weight: bold; }
                            .text-center { text-align: center; }
                            .text-right { text-align: right; }
                            .text-left { text-align: left; }
                            .mt-3 { margin-top: 1rem; }
                            .mb-3 { margin-bottom: 1rem; }
                            .p-3 { padding: 1rem; }
                            .border { border: 1px solid #dee2e6; }
                            .table-responsive { overflow-x: auto; }
                        </style>
                    </head>
                    <body onload="window.print(); window.close();">
                        ${printContents}
                    </body>
                    </html>
                `;
                
                window.print();
                document.body.innerHTML = originalContents;
                window.location.reload();
            }
        });
    });
    
    // Handle file upload preview
    const fileUploads = document.querySelectorAll('.custom-file-input');
    
    fileUploads.forEach(upload => {
        upload.addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'فایلی انتخاب نشده';
            const label = this.nextElementSibling;
            
            if (label && label.classList.contains('custom-file-label')) {
                label.textContent = fileName;
            }
            
            // Show preview if it's an image
            if (this.files[0] && this.files[0].type.startsWith('image/')) {
                const preview = document.querySelector(this.dataset.preview);
                
                if (preview) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            }
        });
    });
    
    // Handle password visibility toggle
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input && input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
                this.setAttribute('aria-label', 'مخفی کردن رمز عبور');
            } else if (input) {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
                this.setAttribute('aria-label', 'نمایش رمز عبور');
            }
        });
    });
    
    // Handle date pickers
    const datePickers = document.querySelectorAll('.datepicker');
    
    if (datePickers.length > 0 && $.fn.persianDatepicker) {
        datePickers.forEach(picker => {
            $(picker).persianDatepicker({
                format: 'YYYY/MM/DD', // Format of the date
                autoClose: true, // Close the date picker after selecting a date
                initialValue: false, // Don't set initial value
                calendarType: 'persian', // Use Persian calendar
                timePicker: {
                    enabled: picker.dataset.timepicker === 'true'
                },
                toolbox: {
                    calendarSwitch: {
                        enabled: false
                    }
                },
                observer: true,
                altField: picker.dataset.altField || null
            });
        });
    }
    
    // Handle select2 if available
    if ($.fn.select2) {
        $('.select2').select2({
            dir: 'rtl',
            language: 'fa',
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
    
    // Handle summernote if available
    if ($.fn.summernote) {
        $('.summernote').summernote({
            height: 300,
            lang: 'fa-IR',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onInit: function() {
                    // Fix RTL in summernote
                    $('.note-editable').attr('dir', 'rtl');
                },
                onChange: function(contents, $editable) {
                    // Update the hidden input with the editor's content
                    $(this).val(contents);
                }
            }
        });
    }
});

/**
 * Show alert message
 * @param {string} message - The message to display
 * @param {string} type - The type of alert (success, error, warning, info)
 * @param {number} timeout - Time in milliseconds before auto-hiding (0 to disable)
 */
function showAlert(message, type = 'info', timeout = 5000) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.role = 'alert';
    
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="بستن"></button>
    `;
    
    const container = document.querySelector('.alerts-container') || document.body;
    container.prepend(alert);
    
    // Auto-hide after timeout
    if (timeout > 0) {
        setTimeout(() => {
            fadeOut(alert);
        }, timeout);
    }
    
    return alert;
}

/**
 * Fade out element
 * @param {HTMLElement} element - The element to fade out
 * @param {number} duration - Duration of the fade out animation in milliseconds
 */
function fadeOut(element, duration = 300) {
    element.style.transition = `opacity ${duration}ms`;
    element.style.opacity = '0';
    
    setTimeout(() => {
        element.remove();
    }, duration);
}

/**
 * Validate form fields
 * @param {HTMLFormElement} form - The form to validate
 * @returns {boolean} True if form is valid, false otherwise
 */
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        // Skip hidden fields and disabled fields
        if (field.offsetParent === null || field.disabled) {
            return;
        }
        
        // Reset error state
        const formGroup = field.closest('.form-group') || field.closest('.mb-3');
        
        if (formGroup) {
            formGroup.classList.remove('has-error');
            
            const errorElement = formGroup.querySelector('.invalid-feedback');
            if (errorElement) {
                errorElement.remove();
            }
        }
        
        // Validate required fields
        if (field.hasAttribute('required') && !field.value.trim()) {
            isValid = false;
            showFieldError(field, 'این فیلد اجباری است.');
        }
        
        // Validate email format
        if (field.type === 'email' && field.value && !isValidEmail(field.value)) {
            isValid = false;
            showFieldError(field, 'لطفا یک ایمیل معتبر وارد کنید.');
        }
        
        // Validate number fields
        if ((field.type === 'number' || field.getAttribute('inputmode') === 'numeric') && field.value) {
            const min = parseFloat(field.getAttribute('min') || '-Infinity');
            const max = parseFloat(field.getAttribute('max') || 'Infinity');
            const value = parseFloat(field.value);
            
            if (isNaN(value) || value < min || value > max) {
                isValid = false;
                showFieldError(field, `لطفا یک عدد بین ${min} و ${max} وارد کنید.`);
            }
        }
        
        // Validate file types
        if (field.type === 'file' && field.files.length > 0) {
            const allowedTypes = field.getAttribute('accept');
            
            if (allowedTypes) {
                const file = field.files[0];
                const fileType = file.type;
                const allowedTypesArray = allowedTypes.split(',').map(type => type.trim());
                
                if (!allowedTypesArray.some(type => fileType.match(type.replace('*', '.*')))) {
                    isValid = false;
                    showFieldError(field, 'نوع فایل مجاز نیست.');
                }
            }
        }
    });
    
    return isValid;
}

/**
 * Show error message for a form field
 * @param {HTMLElement} field - The form field
 * @param {string} message - The error message
 */
function showFieldError(field, message) {
    const formGroup = field.closest('.form-group') || field.closest('.mb-3');
    
    if (formGroup) {
        formGroup.classList.add('has-error');
        
        let errorElement = formGroup.querySelector('.invalid-feedback');
        
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'invalid-feedback d-block';
            formGroup.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
        
        // Scroll to the first error
        if (field === document.querySelectorAll('[required]')[0]) {
            field.focus({ preventScroll: true });
            window.scrollTo({
                top: field.offsetTop - 100,
                behavior: 'smooth'
            });
        }
    }
}

/**
 * Validate email address
 * @param {string} email - The email to validate
 * @returns {boolean} True if email is valid, false otherwise
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}

/**
 * Format number with commas
 * @param {number|string} number - The number to format
 * @returns {string} Formatted number
 */
function formatNumber(number) {
    return new Intl.NumberFormat('fa-IR').format(number);
}

/**
 * Debounce function
 * @param {Function} func - The function to debounce
 * @param {number} wait - The time to wait in milliseconds
 * @returns {Function} The debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}

/**
 * Throttle function
 * @param {Function} func - The function to throttle
 * @param {number} limit - The time limit in milliseconds
 * @returns {Function} The throttled function
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            
            setTimeout(() => {
                inThrottle = false;
            }, limit);
        }
    };
}

// Export functions for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        showAlert,
        fadeOut,
        validateForm,
        isValidEmail,
        formatNumber,
        debounce,
        throttle
    };
}
