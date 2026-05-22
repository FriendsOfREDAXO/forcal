(function() {
    tinymce.PluginManager.add('forcal_event', function(editor, url) {
        editor.ui.registry.addButton('forcal_insert', {
            text: 'ForCal Event',
            icon: 'calendar',
            onAction: function() {
                editor.insertContent('<p><strong>ForCal Event Placeholder</strong></p>');
            }
        });
    });
})();
