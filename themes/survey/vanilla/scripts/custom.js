/******************
    User custom JS
    ---------------

   Put JS-functions for your template here.
   If possible use a closure, or add them to the general Template Object "Template"
*/


$(document).on('ready pjax:scriptcomplete',function(){
    /** 
     * Code included inside this will only run once the page Document Object Model (DOM) is ready for JavaScript code to execute
     * @see https://learn.jquery.com/using-jquery-core/document-ready/
     */

    $(".multiple-short-txt").bind("keypress", function (e) {
        if (e.key === ' ' || e.key === 'Spacebar') {
            // ' ' is standard, 'Spacebar' was used by IE9 and Firefox < 37
            e.preventDefault();
            $('#' + e.target.id).closest('li').next('li').find('input:text').focus();
        }
    });
});

