function accordion_taskrules(id = 'accordion_rules', openall = true) {
    jQuery(function () {
        const $accordion = $("#" + id); // $accordion est une variable JS, pas PHP

        $accordion.accordion({
            collapsible: true,
            active: false, // tout fermé à l'init
            heightStyle: "content"
        });

        if (openall) {
            // Ouvre tous les panneaux manuellement
            $accordion.find('.ui-accordion-content').each(function () {
                $(this).show().addClass('ui-accordion-content-active');
                $(this).prev('.ui-accordion-header')
                    .addClass('ui-accordion-header-active ui-state-active ui-corner-top')
                    .removeClass('ui-corner-all');
            });
        }
    });
}
