// Configure toastr
if (typeof toastr !== 'undefined') {
    toastr.options = {
        closeButton: true,
        debug: false,
        newestOnTop: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        preventDuplicates: false,
        onclick: null,
        showDuration: '300',
        hideDuration: '1000',
        timeOut: '5000',
        extendedTimeOut: '1000',
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut'
    };
    
    // Override error toast timeout to 10 seconds
    const originalError = toastr.error;
    toastr.error = function(message, title, options) {
        const errorOptions = $.extend({}, toastr.options, options || {}, {
            timeOut: '10000',
            extendedTimeOut: '2000'
        });
        return originalError.call(this, message, title, errorOptions);
    };
}

// Get CSRF token from meta tag
function getCsrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
}

// Setup AJAX defaults
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': getCsrfToken()
    }
});

function getPagePart() {
    const parts = window.location.pathname.split('/').filter(Boolean);
    return {
        isHome: parts.length === 0,
        urlParts: parts[0] || null,
        secondPart: parts[1] || null
    };
}

/**************************************************************
 * Login Page
 **************************************************************/
if (getPagePart().urlParts === 'login') {
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        // Disable submit button
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Logging in...');
        
        $.ajax({
            url: '/api/login',
            method: 'POST',
            data: {
                username: $('#username').val(),
                password: $('#password').val(),
                remember: $('#remember').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Login successful');
                    setTimeout(function() {
                        window.location.href = '/';
                    }, 500);
                } else {
                    toastr.error(response.message || 'Login failed');
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'An error occurred during login';
                toastr.error(message);
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
}

/**************************************************************
 * Upload Page
 **************************************************************/
if (getPagePart().urlParts === 'upload') {
    $('#upload-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        const $progressContainer = $('#upload-progress');
        const $progressBar = $('#upload-progress-bar');
        
        // Check if file is selected
        const fileInput = document.getElementById('file');
        if (!fileInput.files || !fileInput.files.length) {
            toastr.error('Please select a file to upload');
            return;
        }
        
        // Show progress container
        $progressContainer.removeClass('d-none');
        $progressBar.css('width', '0%').attr('aria-valuenow', 0);
        
        // Disable submit button
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');
        
        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('description', $('#description').val() || '');
        formData.append('_token', getCsrfToken());
        
        $.ajax({
            url: '/api/files/upload',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        $progressBar.css('width', percentComplete + '%').attr('aria-valuenow', percentComplete);
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    $progressBar.css('width', '100%').attr('aria-valuenow', 100);
                    toastr.success(response.message || 'File uploaded successfully');
                    setTimeout(function() {
                        window.location.href = response.data.file.url;
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Upload failed');
                    $submitBtn.prop('disabled', false).html(originalText);
                    $progressContainer.addClass('d-none');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'An error occurred during upload';
                toastr.error(message);
                $submitBtn.prop('disabled', false).html(originalText);
                $progressContainer.addClass('d-none');
            }
        });
    });
}

/**************************************************************
 * Files Page - Delete functionality
 **************************************************************/
if (getPagePart().urlParts === 'files') {
    // Handle delete buttons in list view
    $(document).on('click', '.delete-file-btn', function(e) {
        e.preventDefault();
        const fileId = $(this).data('file-id');
        const $row = $(this).closest('tr');
        
        if (!confirm('Are you sure you want to delete this file?')) {
            return;
        }
        
        $.ajax({
            url: '/api/files/' + fileId,
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'File deleted successfully');
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    toastr.error(response.message || 'Delete failed');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'An error occurred while deleting the file';
                toastr.error(message);
            }
        });
    });
    
    // Handle delete form in detail view
    $('#delete-file-form').on('submit', function(e) {
        e.preventDefault();
        const fileId = $(this).data('file-id');
        
        if (!confirm('Are you sure you want to delete this file?')) {
            return;
        }
        
        $.ajax({
            url: '/api/files/' + fileId,
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'File deleted successfully');
                    setTimeout(function() {
                        window.location.href = '/files';
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Delete failed');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'An error occurred while deleting the file';
                toastr.error(message);
            }
        });
    });
}

/**************************************************************
 * Admin Page - User Management
 **************************************************************/
if (getPagePart().urlParts === 'admin' && getPagePart().secondPart === 'users') {
    // Handle create user form
    $('#create-user-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        // Disable submit button
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');
        
        const formData = {
            username: $('#username').val(),
            email: $('#email').val(),
            password: $('#password').val(),
            roles: $('input[name="roles[]"]:checked').map(function() {
                return $(this).val();
            }).get()
        };
        
        $.ajax({
            url: '/api/users',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'User created successfully');
                    setTimeout(function() {
                        window.location.href = response.data.user.url;
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Failed to create user');
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'An error occurred while creating the user';
                toastr.error(message);
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle edit user form
    $('#edit-user-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        const userId = $form.data('user-id');
        
        // Disable submit button
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        
        const formData = {
            username: $('#username').val(),
            email: $('#email').val(),
            password: $('#password').val(),
            roles: $('input[name="roles[]"]:checked').map(function() {
                return $(this).val();
            }).get()
        };
        
        // Remove password if empty
        if (!formData.password) {
            delete formData.password;
        }
        
        $.ajax({
            url: '/api/users/' + userId,
            method: 'PUT',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'User updated successfully');
                    // Update displayed username/email if changed
                    if (response.data.user.username) {
                        $('#username').val(response.data.user.username);
                    }
                    if (response.data.user.email) {
                        $('#email').val(response.data.user.email);
                    }
                    $submitBtn.prop('disabled', false).html(originalText);
                } else {
                    toastr.error(response.message || 'Failed to update user');
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'An error occurred while updating the user';
                toastr.error(message);
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle delete user buttons
    $(document).on('click', '.delete-user-btn', function(e) {
        e.preventDefault();
        const userId = $(this).data('user-id');
        const $row = $(this).closest('tr');
        
        if (!confirm('Are you sure you want to delete this user?')) {
            return;
        }
        
        $.ajax({
            url: '/api/users/' + userId,
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'User deleted successfully');
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    toastr.error(response.message || 'Delete failed');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'An error occurred while deleting the user';
                toastr.error(message);
            }
        });
    });
}

