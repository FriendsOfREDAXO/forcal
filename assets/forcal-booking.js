/**
 * forCal Booking UI – zeigt/versteckt das Kapazitätsfeld basierend auf der gewählten Kategorie
 */
$(document).on('rex:ready', function () {
    var bookingCategoryIds = rex.forcal_booking_categories || [];

    function toggleCapacityField() {
        var $catSelect = $('.forcal_category_select');
        if ($catSelect.length === 0) return;

        var selectedCatId = parseInt($catSelect.val(), 10);
        var isBooking = bookingCategoryIds.indexOf(selectedCatId) !== -1;
        var $wrapper = $('.forcal-booking-fields');

        if (isBooking) {
            $wrapper.slideDown(200);
        } else {
            $wrapper.slideUp(200);
        }
    }

    // Initial prüfen
    toggleCapacityField();

    // Bei Kategorie-Wechsel prüfen
    $(document).on('change', '.forcal_category_select', function () {
        toggleCapacityField();
    });
});
