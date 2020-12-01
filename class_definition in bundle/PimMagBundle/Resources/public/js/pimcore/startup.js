pimcore.registerNS("pimcore.plugin.PimMagBundle");

pimcore.plugin.PimMagBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.PimMagBundle";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params, broker) {
        // alert("PimMagBundle ready!");
    },
    postOpenObject: function (object, type) {

    if (object.data.general.o_className == 'ShopProduct') {
        var endPoint = '/admin/backend-demo';
        var messageBoxTitle = t('Ready to sync?');
        var messageBoxTextSuccess = t('This product is successfuly synced');
        var messageBoxTextError = t('Syncing encountered some error');
        console.log(object);
        var openMessageBox = function() {
            Ext.MessageBox.show({
                title: "Sync Dialogue",
                msg: messageBoxTitle,
                buttons: Ext.Msg.YESNO,
                buttonText: {
                    yes: 'Yes!',
                    no: 'No!'
                },
                fn: function(btn, text){
                if(btn == 'yes'){
                    Ext.Ajax.request({
                        url: endPoint,
                        method: "post",
                        params: {
                            'product_id' : object.id
                        },
                        success: function(response){
                            var res = Ext.decode(response.responseText);
                            console.log(res);
                            if(res) {
                                pimcore.helpers.showNotification(t("success"), messageBoxTextSuccess, "success");
                            } else{
                                pimcore.helpers.showNotification(t("error"), messageBoxTextError, "error");
                            }
                        }
                    });
                }
                }
            })
        };

        object.toolbar.add({
            text: t('Sync To Magento'),
            iconCls: 'pimcore_icon_reload',
            scale: 'small',
            handler: openMessageBox
        });
        pimcore.layout.refresh();
    }
    }
});

var PimMagBundlePlugin = new pimcore.plugin.PimMagBundle();
