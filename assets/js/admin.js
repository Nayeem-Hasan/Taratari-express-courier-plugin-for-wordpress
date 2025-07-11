jQuery(document).ready(function($) {
    $('.create-taratari-parcel').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const orderId = button.data('order-id');
        
        button.prop('disabled', true).text('Creating...');
        
        $.ajax({
            url: taratariAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'create_taratari_parcel',
                nonce: taratariAjax.nonce,
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    button.replaceWith(response.data.tracking_code);
                } else {
                    alert('Error: ' + response.data);
                    button.prop('disabled', false).text('Create Parcel');
                }
            },
            error: function() {
                alert('Server error occurred');
                button.prop('disabled', false).text('Create Parcel');
            }
        });
    });
});