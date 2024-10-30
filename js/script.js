jQuery(document).ready(function ($) {
    // Tout sélectionner ou tout désélectionner en cliquant sur le bouton "tout sélectionner"

    $("#traductions-select-all, #traductions-select-all-foot").click(function () {
        $("#update-traductions input[type='checkbox']").prop('checked', $(this).prop('checked'));
    })

    // Désélectionner "update-traductions" si une autre case est désélectionnée

    $("#update-traductions input[type='checkbox']").click(function () {
        if (!$(this).prop('checked')) {
            $("#traductions-select-all, #traductions-select-all-foot").prop('checked', false);
        } else if ($("#update-traductions input[type='checkbox']:not(:checked)").length === 0) {
            $("#traductions-select-all, #traductions-select-all-foot").prop('checked', true);
        }
    });
});

