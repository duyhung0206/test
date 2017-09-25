/*
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *
 */

define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'mage/translate',
        'Magestore_Webpos/js/model/event-manager',
        'Magestore_Webpos/js/helper/alert',
        'Magestore_WebposVantiv/js/model/vantiv'
    ],
    function ($, ko, Component, __, Event, Alert, VantivService) {
        "use strict";
        return Component.extend({
            /**
             * Default data for UI component
             */
            defaults: {
                template: 'Magestore_WebposVantiv/container',
                container_selector: '#webpos_vantiv_integration_container',
                overlay_selector: '#webpos_vantiv_integration_overlay'
            },
            /**
             * Check flag to show iframe
             */
            visible: ko.observable(false),
            loading: VantivService.loading,
            /**
             * Initialize
             */
            initialize: function () {
                this._super();
                var self = this;
                self.initEvents();
            },
            /**
             * Init events
             */
            initEvents: function(){
                var self = this;
                Event.observer('open_vantiv_integration', function(event, data){
                    self.visible(true);
                });
                Event.observer('close_vantiv_integration', function(event, data){
                    self.visible(false);
                });
                self.visible.subscribe(function(value){
                    if(value){
                        $(self.container_selector).removeClass('hide');
                        $(self.overlay_selector).removeClass('hide');
                    }else{
                        $(self.container_selector).addClass('hide');
                        $(self.overlay_selector).addClass('hide');
                    }
                });
            },
            /**
             * Close popup
             */
            close: function(){
                var self = this;
                self.visible(false);
                VantivService.closeAuthorizeWindow(false, 'Payment could not be completed!');
            },

        });
    }
);