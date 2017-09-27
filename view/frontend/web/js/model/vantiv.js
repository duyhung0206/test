/*
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *
 */

define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'Magestore_Webpos/js/helper/alert',
        'Magestore_Webpos/js/helper/price',
        'Magestore_Webpos/js/helper/general',
        'Magestore_Webpos/js/model/event-manager',
        'Magestore_Webpos/js/view/checkout/checkout/payment_selected',
        'Magestore_Webpos/js/view/checkout/checkout/payment_popup',
        'Magestore_Webpos/js/view/checkout/checkout/payment',
        'Magestore_Webpos/js/model/checkout/checkout',
        'Magestore_Webpos/js/model/checkout/cart',
        'mage/storage',
        'Magestore_Webpos/js/model/checkout/cart/items',
        'Magestore_Webpos/js/model/checkout/cart/totals-factory',
        'Magestore_Webpos/js/action/notification/add-notification',
    ],
    function ($, ko, __, Alert, PriceHelper, Helper, Event, SelectedPayment, PopupPayment, Payment, CheckoutModel, CartModel, storage, Items, TotalsFactory, addNotification) {
        "use strict";

        var VantivService = {
            /**
             * Payment method code
             */
            PAYMENT_METHOD_CODE: 'mercuryhosted',
            /**
             * authorize window
             */
            authorizeWindow: ko.observable(),
            /**
             * Paypal authorize url
             */
            authorizeUrl: ko.observable(''),
            /**
             * Flag to check the popup has been blocked
             */
            blocked: ko.observable(false),
            loading: ko.observable(false),
            /**
             * Initialize
             */
            method: "rest",
            storeCode: window.webposConfig.storeCode,
            version: 'V1',
            serviceUrl: ':method/:storeCode/:version',

            initialize: function () {
                console.log('WebposVantiv');
                var self = this;
                self.initCallbacks();
                self.initEvents();

                return self;
            },
            /**
             * Show message by process response
             * @param response
             */
            showMessage: function(response){
                if(response && response.message){
                    var priority = (response.success)?'success':'danger';
                    var title = (response.success)?'Message':'Error';
                    Alert({
                        priority: priority,
                        title: __(title),
                        message: __(response.message)
                    });
                }
            },
            /**
             * Open authorize window
             * @param url
             */
            openAuthorizeWindow: function(url){
                console.log('openAuthorizeWindow');
                var self = this;
                if(self.authorizeWindow()){
                    self.authorizeWindow().close();
                }
                self.authorizeUrl(url);
                var authorizeWindow = window.open(url, 'authorize_window', 'status=1,width=700,height=700');
                return authorizeWindow;
            },
            /**
             * Close authorize window
             */
            closeAuthorizeWindow: function(success, message){
                console.log('closeAuthorizeWindow');
                var self = this;
                if(self.authorizeWindow()){
                    self.authorizeWindow().close();
                    self.authorizeWindow('');
                }
                Event.dispatch('close_vantiv_integration', '');
                self.showMessage({
                    success:success,
                    message:message
                });
            },
            /**
             * Close vantiv integration popup
             */
            cancel: function(){
                Event.dispatch('close_vantiv_integration', '');
            },
            /**
             * Update reference number and place order
             * @param responseText
             */
            success: function(responseText){
                var self = this;
                Event.dispatch('close_vantiv_integration', '');
            },
            /**
             * Set init object to call from childs window
             */
            initCallbacks: function(){
                var self = this;
                if(typeof window.vantivService == 'undefined'){
                    window.vantivService = {
                        cancel:self.cancel,
                        success:$.proxy(self.success, self)
                    };
                }
            },
            /**
             * Init some events, change event when place order
             */
            initEvents: function(){
                var self = this;
                CheckoutModel.selectedPayments.subscribe(function(){
                    $('#checkout_button').unbind('click');
                    $('#checkout_button').click(function(){
                        $.proxy(self.placeOrder(), self);
                    });
                });
                Event.observer('webpos_place_order_before', function (event, data) {
                    self.placeOrderBefore(data);
                });
                Event.observer('webpos_place_order_after', function (event, data) {
                    self.placeOrderAfter(data);
                });
            },
            /**
             * Get paypal method from selected payment list
             * @returns {*}
             */
            getSelectedPaypalMethod: function(){
                var self = this;
                var payments = CheckoutModel.selectedPayments();
                var vantiv = ko.utils.arrayFirst(payments, function(method){
                    return method.code == self.PAYMENT_METHOD_CODE;
                });
                return vantiv;
            },
            /**
             * Rewrite place order function
             * @returns {boolean}
             */
            placeOrder: function(){
                console.log('placeorder');
                var self = this;
                var vantiv = self.getSelectedPaypalMethod();
                if(vantiv){
                    Event.dispatch('open_vantiv_integration', '');
                    self.start();
                }else{
                    Event.dispatch('start_place_order', '');
                }
                return false;
            },
            createUrl: function(url, params) {
                var completeUrl = this.serviceUrl + url;
                return this.bindParams(completeUrl, params);
            },
            bindParams: function(url, params) {
                params.method = this.method;
                params.storeCode = this.storeCode;
                params.version = this.version;

                var urlParts = url.split("/");
                urlParts = urlParts.filter(Boolean);

                $.each(urlParts, function(key, part) {
                    part = part.replace(':', '');
                    if (params[part] != undefined) {
                        urlParts[key] = params[part];
                    }
                });
                return urlParts.join('/');
            },
            /**
             * Start create payment
             */
            start: function(){
                var self = this;
                $('#vantiv-iframe').empty();
                var quoteParams = CartModel.getQuoteInitParams();
                var urlLocation = window.location.href.split("webpos")[0];
                // $.mage.redirect('http://127.0.0.1/vantiv/webposvantiv/index/redirect?guestemail=null&quoteId='+quoteParams.quote_id);
                var frame = $('<iframe src="'+urlLocation+'webposvantiv/index/redirect?guestemail=null&quoteId='+quoteParams.quote_id+'" style="padding-bottom:30px" frameBorder="0" height="100%" width="100%"></iframe>');
                self.loading(true);

                $('#vantiv-iframe').append(frame);
                frame.load(function(){
                    self.loading(false);
                });
            },

            /**
             * Add params to save data to order
             * @param data
             */
            placeOrderBefore: function(data){
                console.log(data);
                console.log('placeOrderBefore');
            },
            /**
             * Reset invoice data
             * @param data
             */
            placeOrderAfter: function(data){
                console.log(data);
                console.log('placeOrderAfter');
            },

        };
        return VantivService.initialize();
    }
);