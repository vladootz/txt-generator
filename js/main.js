window.addEventListener('load', function() {

    // theme switcher
    document.querySelector('#theme-switcher').addEventListener('change', function(e) {
        var darkCssEl = document.querySelector('#dark-theme');
        if (this.checked) {
            var darkCss = darkCssEl.getAttribute('data-href');
            darkCssEl.href = darkCss;
            Cookies.set('dark', 'true');
        } else {
            darkCssEl.removeAttribute('href');
            Cookies.set('dark', 'false');
        }
    });

    // language switcher
    document.querySelector('#lang').addEventListener('change', function() {
        Cookies.set('lang', this.value);
        window.location.reload();
    });

    // insert new key
    $('#new_key').on('keyup', function(e) {
        if (e.keyCode === 13) {
            // clone
            $(this).removeAttr('id');
            $(this).parent().parent().removeClass('new');
            $(this).parent().parent().clone().insertBefore($(this).parent().parent());

            // cleanup
            fields++;
            $(this).attr('name', 'key'+fields);
            $(this).parent().next().find('input').attr('name', 'value'+fields);
            $(this).attr('id', 'new_key');
            $(this).parent().parent().addClass('new');
            var insertKey = $(this).val();
            $(this).val('');

            $('#loading').show();
            $.ajax({
                url: currentPath+'?ajax=insert_key',
                method: 'POST',
                dataType: 'json',
                data: {
                    lang: language,
                    key: 'key'+(fields-1),
                    value: insertKey
                }
            }).done(function() {
                $('#loading').hide();
            });
        }
    });

    // generate file
    $('#generate').on('click', function(e) {
        // check fields
        var error = false;
        var filename = $('input[name="filename"]').val();
        if (filename.trim() == '') {
            error = true;
            var msg = 'Please write a filename.';
            if (lang == 'ro')
                msg = 'Scrieți un nume de fișier.';
        }

        if (error) {
            alert(msg);
            e.preventDefault();
            return false;
        } else {
            setTimeout(function(){
                if ($('#clear').prop('checked')) {
                    $('input[name="filename"]').val('');
                    var i = 1;
                    while (i < fields) {
                        eval('autoc'+i+'.clear()');
                        i++;
                    }
                }
            }, 500);
        }
    });
})