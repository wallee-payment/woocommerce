jQuery(function($) {

    var wc_wallee_checkout = {
	payment_methods : {},
	validated : false,

	init : function() {
	    var parent = this;
	    // Payment methods
	    $('form.checkout').off('click.wallee').on('click.wallee',
		    'input[name="payment_method"]', {
			self : this
		    }, this.payment_method_click);

	    if ($(document.body).hasClass('woocommerce-order-pay')) {
		$('#order_review').off('click.wallee').on('click.wallee',
			'input[name="payment_method"]', {
			    self : this
			}, this.payment_method_click);
	    }
	    this.register_ajax_prefilter();
	},

	payment_method_click : function(event) {
	    var self = event.data.self;
	    var current_method = self.get_selected_payment_method();
	    if (!self.is_supported_method(current_method)) {
		return;
	    }
	    var configuration = $("#wallee-method-configuration-"
		    + current_method);
	    self.register_method(configuration.data("method-id"), configuration
		    .data("configuration-id"), configuration
		    .data("container-id"));
	    self.handle_description_for_empty_iframe(current_method);
	},

	handle_description_for_empty_iframe : function(method_id) {

	    var current_method = this.get_selected_payment_method();
	    if (!this.is_supported_method(current_method)) {
		return;
	    }
	    if (current_method != method_id) {
		return;
	    }
	    var configuration = $("#wallee-method-configuration-"
		    + current_method);
	    var description = configuration.data("description-available");

	    //Hide iFrame by moving it (display:none leads to issues)
	    var item = $('form.checkout').find(
		    'input[name="payment_method"]:checked').closest(
		    'li.wc_payment_method').find('div.payment_box');
	    var form = item.find('#payment-form-' + method_id);
		form.css('display', '');
	    if ((!description || description == "false")
		    && this.payment_methods[method_id]['height'] == 0) {
		item.css('position', 'absolute');
		item.css('left', '-100000px');
	    } else if (this.payment_methods[method_id]['height'] == 0) {
		form.css('position', 'absolute');
		form.css('left', '-100000px');

	    } else {
		item.css('position', '');
		item.css('left', '');
		form.css('position', '');
		form.css('left', '');
		item.slideDown(250);
	    }
	},

	register_ajax_prefilter : function() {
	    var self = this;
	    $.ajaxPrefilter(
		    "json",
		    function(options, originalOptions, jqXHR) {
			if (options.url == wc_checkout_params.checkout_url
				&& self.is_supported_method(self.get_selected_payment_method())) {
			    var original_success = options.success;
			    options.success = function(data, textStatus, jqXHR) {
				if (self.process_order_created(data, textStatus, jqXHR)) {
				    return;
				}
				if (typeof original_success == 'function') {
				    original_success(data, textStatus,jqXHR);
				} else if (typeof original_success != "undefined"
						&& original_success.constructor === Array) {
        			    	original_success.forEach(function(original_function) {
        					if (typeof original_function == 'function') {
        					    original_function(data, textStatus, jqXHR);
        					}
        				});
				}
			    };
			}
		   });
	},

	is_supported_method : function(method_id) {
	    return method_id && (method_id.indexOf('wallee_') == 0);
	},

	get_selected_payment_method : function() {
	    return $('form.checkout').find(
		    'input[name="payment_method"]:checked').val();
	},

	register_method : function(method_id, configuration_id, container_id) {

	    if (typeof this.payment_methods[method_id] != 'undefined'
		    && $('#' + container_id).find("iframe").length > 0) {
		return;
	    }
	    var self = this;
	    
	    //Create visible div and add iframe to it. otherwise some browsers have issues reporting the correct height
	    var tmp_container_id = 'tmp-'+container_id;
	    $('<div>').attr('id', tmp_container_id).appendTo('body');

	    this.payment_methods[method_id] = {
		configuration_id : configuration_id,
		container_id : tmp_container_id,
		handler : window.IframeCheckoutHandler(configuration_id),
		height : 0
	    };
	    this.payment_methods[method_id].handler
		    .setValidationCallback(function(validation_result) {
			self.process_validation(method_id, validation_result);
		    });
	    this.payment_methods[method_id].handler
		    .setHeightChangeCallback(function(height) {
			self.payment_methods[method_id]['height'] = height;
			self.handle_description_for_empty_iframe(method_id)
		    });

	    this.payment_methods[method_id].handler
		    .create(self.payment_methods[method_id].container_id);
	    
	    $('#'+container_id).replaceWith($('#'+tmp_container_id));
	    $('#'+tmp_container_id).attr('id', container_id);

	    
	    var form = $('form.checkout');
	    form.off('checkout_place_order_' + method_id + '.wallee').on(
		    'checkout_place_order_' + method_id + '.wallee',
		    function() {
			if (!self.validated) {
			    form.block({
				message : null,
				overlayCSS : {
				    background : '#fff',
				    opacity : 0.6
				}
			    });

			    self.payment_methods[method_id].handler.validate();
			    return false;
			} else {
			    return true;
			}
		    });

	},

	process_order_created : function(data, textStatus, jqXHR) {
	    this.validated = false;
	    if (typeof data.wallee == 'undefined') {
		return false;
	    }
	    this.payment_methods[this.get_selected_payment_method()].handler
		    .submit();
	    return true;
	},

	process_validation : function(method_id, validation_result) {
	    if (validation_result.success) {
		this.validated = true;
		$('form.checkout').submit();
		return;
	    } else {
		var form = $('form.checkout');
		form.unblock();
		if (validation_result.error_messages) {
		    wc_checkout_form
			    .submit_error(this
				    .format_error_messages(validation_result.error_messages));
		} else {
		    $('html, body').animate(
			    {
				scrollTop : ($(
				'#'+ this.payment_methods[method_id].container_id)
						.offset().top - 20)
			    }, 1000);
		}
	    }
	},

	format_error_messages : function(messages) {
	    var formatted_message;
	    if (typeof messages == 'object') {
		formatted_message = messages.join("\n");
	    } else if (messages.constructor === Array) {
		formatted_message = messages.join("\n").stripTags().toString();
	    } else {
		formatted_message = messages
	    }

	    return formatted_message;
	}

    }

    wc_wallee_checkout.init();

});