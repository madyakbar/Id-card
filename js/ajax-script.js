jQuery(document).ready(function($) {
    var croppieInstance;

    function initializeCroppie(event) {
        $('#croppie-container').html(''); // Clear previous content
        croppieInstance = new Croppie($('#croppie-container')[0], {
            viewport: { width: 200, height: 200, type: 'square' },
            boundary: { width: 300, height: 300 }
        });
        croppieInstance.bind({ url: event.target.result });

        $('#croppieModal').modal('show'); // Show modal
    }

    $('#upload_image').on('change', function() {
        var reader = new FileReader();
        reader.onload = function(event) {
            initializeCroppie(event);
        }
        reader.readAsDataURL(this.files[0]);
    });

    $('#croppieModal').on('hidden.bs.modal', function () {
        if (croppieInstance) {
            croppieInstance.destroy(); // Destroy Croppie instance
            croppieInstance = null; // Reset Croppie instance
        }
    });

    $('#set-cropped-image').on('click', function() {
        croppieInstance.result('base64').then(function(base64) {
            $('#cropped_image').val(base64);
            $('#image_preview').html('<img src="' + base64 + '" alt="Cropped Image">'); // Display cropped image
            $('#croppieModal').modal('hide'); // Hide modal
        });
    });

    $('#personal-id-form').on('submit', function(e) {
    e.preventDefault();

    var formData = {
        action: 'submit_personal_id_form',
        first_name: $('#first_name').val(),
        last_name: $('#last_name').val(),
        email: $('#email').val(),
        mobile: $('#mobile').val(),
        address: $('#address').val(),
        image: $('#cropped_image').val(),
        theme: $('input[name="theme"]:checked').val(),
        security: $('#personal-id-form [name="security"]').val() // Add the nonce here
    };

    $.post(ajax_object.ajax_url, formData, function(response) {
        if (response.success) {
            var user = response.data;
            var userDetailsHtml = '';
            
            if (user.theme === 'theme1') {
                userDetailsHtml = '<div class="main__c">' +
                    '<div class="card">' +
                    '<h2 class="text-white text-center">ID-CARD</h2>' +
                    '<div class="img-bx">' +
                    '<img src="' + user.image_url + '" alt="' + user.first_name + ' ' + user.last_name + '">' +
                    '</div>' +
                    '<div class="content">' +
                    '<div class="detail">' +
                    '<h2>' + user.first_name + ' ' + user.last_name + '</h2>' +
                    '<p>' + user.email + '</p>' +
                    '<p>' + user.mobile + '</p>' +
                    '<p>' + user.address + '</p>' +
                    '<p>Personal URL: <a href="' + user.custom_url + '" target="_blank">' + user.custom_url + '</a></p>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            } else if(user.theme === 'theme2') {
                userDetailsHtml = '<div class="card-container mx-auto text-center" style="box-shadow: none;">' +
                    '<h2 class="text-white">ID-CARD</h2>' +
                    '<img src="' + user.image_url + '" alt="' + user.first_name + ' ' + user.last_name + '">' +
                    '<h2 class="text-white pt-2">' + user.first_name + ' ' + user.last_name + '</h2>' +
                    '<p>' + user.email + '</p>' +
                    '<p>' + user.mobile + '</p>' +
                    '<p>' + user.address + '</p>' +
                    '<p>Personal URL: <a href="' + user.custom_url + '" target="_blank">' + user.custom_url + '</a></p>' +
                    '</div>';
            } else if(user.theme === 'theme3') {
                userDetailsHtml = '<div class="id-card">' +
                    '<h2 class="text-white text-center">ID-CARD</h2>' +
                    '<div class="header">' +
                    '<img src="' + user.image_url + '" alt="' + user.first_name + ' ' + user.last_name + '">' +
                    '</div>' +
                    '<div class="content_3">' +
                    '<h2>' + user.first_name + ' ' + user.last_name + '</h2>' +
                    '<p>' + user.email + '</p>' +
                    '<p>' + user.mobile + '</p>' +
                    '<p>' + user.address + '</p>' +
                    '<p>Personal URL: <a href="' + user.custom_url + '" target="_blank">' + user.custom_url + '</a></p>' +
                    '</div>' +
                    '</div>';
            }

            $('#user-details').html(userDetailsHtml);
            $('#userDetailsModal').modal('show'); // Show the modal with user details
        } else {
            console.error('Form submission failed:', response.data.message);
            alert('Form submission failed: ' + response.data.message);
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('AJAX request failed:', textStatus, errorThrown);
        alert('AJAX request failed. Please check the console for more details.');
    });
});

});

jQuery(document).ready(function($) {
    $('#save-id-card').on('click', function() {
        let cardSelector = '';
        if ($('.card').length) {
            cardSelector = '.card';
        } else if ($('.card-container').length) {
            cardSelector = '.card-container';
        } else if ($('.id-card').length) {
            cardSelector = '.id-card';
        }

        if (cardSelector) {
            html2canvas(document.querySelector(cardSelector)).then(function(canvas) {
                canvas.toBlob(function(blob) {
                    var formData = new FormData();
                    formData.append('image', blob);
                    formData.append('action', 'save_id_card_image');
                    
                    // Get the user ID from the browser URL
                    var url = window.location.href;
                    //console.log(url);
                    var parts = url.split('/');
                    //console.log('Split:-', parts);
                    
                    // Find the index of the segment containing the user ID
                    var userIdIndex = parts.indexOf('details') + 1;
                    
                    // Extract the user ID segment
                    var userIdSegment = parts[userIdIndex];
                    // console.log('User_id segment:-', userIdSegment);
                    
                    // Split the segment to extract only the numerical part of the user ID
                    var userIdParts = userIdSegment.split('-');
                    var userId = userIdParts[userIdParts.length - 1];
                    // console.log('User_id :-', userId);


                    
                    formData.append('user_id', userId); // Add user ID to the form data

                    $.ajax({
                        url: ajax_object.ajax_url,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log('Image saved successfully:', response);
                        },
                        error: function(error) {
                            console.error('Error saving image:', error);
                        }
                    });
                });
            });
        }
    });
});





