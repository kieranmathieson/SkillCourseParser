(function (Drupal, $) {
    "use strict";
    Drupal.behaviors.j1 = {
        firstTime: true,
        csrfToken: null,
        attach: function (context, settings) {
            if ( this.firstTime ) {
                console.log('Dog log!');
                //Get the CSRF token.
                console.log(Drupal.url('rest/session/token'));
                console.log(settings.best);
                console.log(settings.sessionId);
                console.log('CRSF:', settings.csrfToken);
            }
            $('.example', context).click(function () {
                $(this).next('ul').toggle('show');
            });

            getCsrfToken();

            this.firstTime = false;
        }
    };
// Our code here.
}) (Drupal, jQuery);

function getCsrfToken() {
    jQuery
        .get(Drupal.url('rest/session/token'))
        .done(function (data) {
            var csrfToken = data;
            console.log(csrfToken);
        });
}
