(function( $ ) {
	'use strict';

	jQuery(document).ready(function ($) {

		$(document).on('click', '.sip-rswc-load-more-btn', function(e) {
		    e.preventDefault();
		    var $btn = $(this);
		    var productId = $btn.data('product-id');
		    var offset = $btn.data('offset');
		    var limit = $btn.data('limit');
		    var ajaxUrl = sip_rswc_ajax.ajax_url;
		    var nonce = sip_rswc_ajax.nonce;
		    var $container = $('.commentlist-' + productId);

		    $btn.prop('disabled', true).text('Loading...');

		    $.ajax({
		        url: ajaxUrl,
		        type: 'POST',
		        dataType: 'json',
		        data: {
		            action: 'sip_rswc_load_more_reviews',
		            product_id: productId,
		            offset: offset,
		            limit: limit,
		            nonce: nonce
		        },
		        success: function(response) {
		            if (response.success && response.data.html) {
		                $container.append(response.data.html);
		                $btn.data('offset', offset + limit);
		                if (response.data.btn || response.data.count < 1 ) {
		                    $btn.remove();
		                } else {
		                    $btn.prop('disabled', false).text(response.data.txt);
		                }
		            } else {
		                $btn.remove();
		            }
		        }
		    });
		});

		$(document).on('click', '.sip-rswc-rating-data-count', function (e) {
		    e.preventDefault();

		    var $table = $(this).closest('.sip-rswc-rating-table');
		    var ratingValue = $(this).data('count');
		    var productId = $table.data('product-id');
		    var ajaxUrl = sip_rswc_ajax.ajax_url;
		    var nonce = sip_rswc_ajax.nonce;
		    var loader = sip_rswc_ajax.loader;
		    var $reviewsContainer = $('.commentlist-' + productId);

		    var load_more_btn = $('.sip-rswc-load-more-btn');
		    load_more_btn.remove();

		    if (!productId || !ajaxUrl) {
		        console.warn('Missing product or AJAX data attributes.');
		        return;
		    }

		    $reviewsContainer.addClass('loading').html('<img src="' + loader + '" alt="Loading...">');

		    $.ajax({
		        url: ajaxUrl,
		        type: 'POST',
		        dataType: 'json',
		        data: {
		            action: 'sip_rswc_filter_reviews_by_rating',
		            product_id: productId,
		            rating: ratingValue,
		            nonce: nonce
		        },
		        success: function (response) {

		            if (response.success && response.data.html) {
		                $reviewsContainer.html(response.data.html);
		            } else {
		                $reviewsContainer.html('<li><p>No reviews found for ' + ratingValue + ' stars.</p></li>');
		            }
		        },
		        complete: function () {
		            $reviewsContainer.removeClass('loading');
		        }
		    });
		});
	});
})( jQuery );