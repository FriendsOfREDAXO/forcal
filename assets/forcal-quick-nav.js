// Forcal Quick Navigation Modal with FullCalendar
(function() {
    'use strict';
    
    let calendar = null;
    
    function initCalendar() {
        const calendarEl = document.getElementById('forcal-quick-calendar');
        if (!calendarEl || calendar) return;
        
        const apiUrl = rex.forcal_events_api_url || '';
        const locale = rex.locale || 'de';
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: locale,
            firstDay: 1,
            height: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: ''
            },
            events: apiUrl,
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                if (info.event.url) {
                    window.location.href = info.event.url;
                }
            },
            dateClick: function(info) {
                // Neuer Termin mit diesem Datum
                const url = rex.backendUrl + '?page=forcal/entries&func=add&startdate=' + info.dateStr;
                window.location.href = url;
            }
        });
        
        calendar.render();
    }
    
    function init() {
        // Initialize calendar when modal is shown
        $('#forcal-modal').on('shown.bs.modal', function() {
            if (!calendar) {
                initCalendar();
            } else {
                calendar.render();
            }
        });
    }
    
    // Use REDAXO's rex:ready event
    $(document).on('rex:ready', init);
})();
