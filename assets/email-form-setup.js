jQuery(function ($) {
    $(document).ready(function() {
        let $form = $('*[data-bdwp-modal="mail-form"]');
        let $formMessage = $('*[data-bdwp-selectors="email-form-modal-message"]');
        let $formHeaderMessage = $('*[data-bdwp-selectors="email-form-header-message"]');
        let $formFields = $form.find('*[data-bdwp-selectors="email-form-field"]');
        let $branchIdField = $('[name="branch_id"]');
        let $formModal = $('*[data-bdwp-selectors="email-form-modal"]');
        let $formModalOk = $('*[data-bdwp-selectors="email-form-modal-ok"]');

        $form.attr('novalidate', 'novalidate');
        $(window).on('mail-formModalShown', function (event) {
            $branchIdField.attr('value', event.eTrigger.data('email-id'));
            $formHeaderMessage.html('Παρακαλώ, συμπληρώστε την ακόλουθη φόρμα');
            $formHeaderMessage.removeClass('success');
            $formHeaderMessage.removeClass('failed');
            $formModal.hide();
        });
        $(window).on('mail-formModalClosed', function () {
            $formFields.each(function () {
                $(this).attr('value', '');
            });
            $form.find('textarea').val('');

            $formMessage.html('');
            $formMessage.removeClass('success');
            $formMessage.removeClass('failed');
            $('*[data-bdwp-selectors="email-form-error"]').html('');
        });
        $formModalOk.on('click', function () {
            $formModal.hide();
            $('*[data-bdwp-selectors="email-form-content"]').show();
            if ($formMessage.hasClass('success')) {
                $('*[data-bdwp-selectors="modal-close"]').click();
            }
        });
        if ($('#bdwp-mail-form').length) {
            let formValidator = new Validator('bdwp-mail-form');
            formValidator.addValidation('name', 'req', 'Παρακαλώ εισάγετε το όνομά σας');
            formValidator.addValidation('name', 'maxlen=40', 'Το μέγιστο μέγεθος είναι 40 χαρακτήρες');
            formValidator.addValidation('subject', 'req', 'Παρακαλώ εισάγετε ένα θέμα');
            formValidator.addValidation('subject', 'maxlen=200', 'Το θέμα είναι πολύ μεγάλο');
            formValidator.addValidation('email', 'req', 'Παρακαλώ εισάγετε το email σας');
            formValidator.addValidation('email', 'email', 'Παρακαλώ, εισάγετε έγκυρη διεύθυνση email');
            formValidator.addValidation('email', 'maxlen=50', 'Το email είναι πολύ μεγάλο');
            formValidator.addValidation('message', 'req', 'Παρακαλώ εισάγετε το μήνυμά σας');
            formValidator.addValidation('message', 'maxlen=5000', 'Το μήνυμα είναι πολύ μεγάλο');
            formValidator.addValidation('message', 'minlen=10', 'Το μήνυμα είναι πολύ σύντομο');
            formValidator.EnableOnPageErrorDisplay();
            formValidator.EnableMsgsTogether();
        }
    });
    $(window).on('formValidated', function (e) {
        let formData = {
            'action': 'bdwp_send_mail',
            'form_data': ''
        };

        let $form = $('#bdwp-mail-form');
        let $formLoader = $('*[data-bdwp-selectors="email-form-loader"]');
        let $formMessage = $('*[data-bdwp-selectors="email-form-modal-message"]');
        let $formHeaderMessage = $('*[data-bdwp-selectors="email-form-header-message"]');
        let $formModal = $('*[data-bdwp-selectors="email-form-modal"]');

        formData.form_data = $form.serialize();

        $form.find('[type="submit"]').prop('disabled', true);
        $formLoader.show();
        jQuery.post(BDWP.ajax_url, formData, function(response) {
            $formLoader.hide();
            $form.find('[type="submit"]').prop('disabled', false);
            if (response === '{"status":"success"}') {
                $('*[data-bdwp-selectors="email-form-content"]').hide();
                $formModal.show();
                $formMessage.html('Παρακαλούμε ελέγξτε το email σας για να επιβεβαιώσετε την αποστολή του μηνύματος.');
                $formMessage.addClass('success');
                $formMessage.removeClass('failed');
                $formHeaderMessage.html('');
                $formHeaderMessage.addClass('success');
                $formHeaderMessage.removeClass('failed');
            } else {
                $formHeaderMessage.html('Υπήρξε πρόβλημα στην αποστολή του email.');
                $formHeaderMessage.addClass('failed');
                $formHeaderMessage.removeClass('success');
            }
        });
    });
});