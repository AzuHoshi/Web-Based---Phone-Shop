$(function() {
    $('form :input:not(button):first').focus();
    
    $('[data-confirm]').on('click', function(e) {
        if (!confirm($(this).data('confirm') || 'Are you sure?')) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });
    
    $('input[type="file"]').on('change', function(e) {
        var file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $(this).closest('.form').find('.preview').remove();
                $(this).closest('.form').find('button[type="submit"]').before('<div class="preview"><img src="'+e.target.result+'" style="max-width:100px; margin-top:10px"></div>');
            }.bind(this);
            reader.readAsDataURL(file);
        }
    });
});