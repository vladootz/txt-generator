<?php
if (isset($_POST['download']) && $_POST['download'] === 'file') {
    $filename = $_POST['filename'];
    $i = 1;
    $total_fields = count($_POST)-5;
    $txt = '';
    while ($i < $total_fields) {
        $j = $i > 9 ? $i : ' '.$i;
        $key = isset($_POST['key'.$i]) ? $_POST['key'.$i] : '';
        $value = str_replace('|~|', ', ', $_POST['value'.$i]);
        if (strlen($key))
            $txt .= $j . ". " . $key . ": " . $value . "\n";
        $i++;
    }

    header('Content-type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '.txt"');
    echo $txt;
    exit();
}

require_once('db.php');
if (!file_exists('config.php')) {
    die('You need to setup the config file!');
}
require_once('config.php');
$lang = 'en';
if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], ['ro', 'en'])) {
    $lang = $_COOKIE['lang'];
}

// ajax functions
if (isset($_GET['ajax'])) {
    if ($_GET['ajax'] === 'insert_key') {

        // insert key
        DB::insert('keys', array(
            'lang' => $_POST['lang'],
            'key' => $_POST['key'],
            'value' => $_POST['value'],
        ));
        echo json_encode(['status' => 'success']);
    }
    if ($_GET['ajax'] === 'insert_value') {
        // search value
        $id = DB::queryFirstRow("SELECT `id` FROM `values` WHERE `lang` = %s AND `key` = %s AND `value` = %s", $_POST['lang'], $_POST['key'], $_POST['value']);

        if (is_null($id)) {
            // insert value
            DB::insert('values', array(
                'lang' => $_POST['lang'],
                'key' => $_POST['key'],
                'value' => $_POST['value'],
            ));
        }
        echo json_encode(['status' => 'success']);
    }
    die();
}

$dark = false;
if (isset($_COOKIE['dark']) && in_array($_COOKIE['dark'], ['true', 'false'])) {
    if ($_COOKIE['dark'] == 'true')
        $dark = true;
}

$keys = DB::query("SELECT * FROM `keys` WHERE `lang` = '{$lang}' ORDER BY `id`");
$values = DB::query("SELECT * FROM `values` WHERE `lang` = '{$lang}' ORDER BY `key`");

$vals = [];
foreach($values as $value) {
    if (!isset($vals[$value['key']])) {
        $vals[$value['key']] = [];
    }
    $vals[$value['key']][] = $value['value'];
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>TXT Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/skeleton.css">
    <link rel="stylesheet" href="css/selectize.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="<?php echo $dark ? 'css/dark.css' : ''; ?>" id="dark-theme" data-href="css/dark.css">
    <link rel="icon" type="image/ico" href="favicon.ico">
</head>
<body>
<form method="POST">
    <input type="hidden" name="download" value="file">
    <div class="container">
        <div><h1>TXT Generator</h1></div>
        <div>
            <input type="checkbox" id="theme-switcher"<?php echo $dark ? ' checked' : ''; ?> />
            <label for="theme-switcher">Dark theme</label>

            <select name="lang" id="lang">
                <option value="en"<?php echo $lang == 'en' ? ' selected' : ''; ?>>EN</option>
                <option value="ro"<?php echo $lang == 'ro' ? ' selected' : ''; ?>>RO</option>
            </select>
        </div>
    </div>
    <div class="container fields">
        <div>
            <h2>Key</h2>
        </div>
        <div>
            <h2>Value</h2>
        </div>
    </div>
<?php
    $i = 1; foreach($keys as $key):
        if (!isset($vals['value'.$i])) {
            $vals['value'.$i] = [];
        }
?>
    <div class="container fields">
        <div>
            <input type="text" name="<?php echo $key['key']; ?>" value="<?php echo $key['value']; ?>" tabindex="1<?php echo $i; ?>">
        </div>
        <div>
            <input type="text" name="value<?php echo $i; ?>" tabindex="<?php echo $i; $i++; ?>">
        </div>
    </div>
<?php endforeach; ?>
    </div>
    <div class="container new fields">
        <div>
            <input type="text" name="key<?php echo $i; ?>" placeholder="Add key" id="new_key">
        </div>
        <div>
            <input type="text" name="value<?php echo $i; ?>" placeholder="Add value">
        </div>
    </div>
    <div class="container generate fields">
        <div>
            <input type="text" name="filename" placeholder="filename">
        </div>
        <div>
            .txt
            <button type="submit" id="generate">Generate TXT</button><br/>
            <input type="checkbox" name="clear" id="clear"> <label for="clear">Clear fields after download</label>
        </div>
    </div>
</form>

    <div id="loading" class="lds-dual-ring"></div>

    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/cookie.js"></script>
    <script type="text/javascript" src="js/selectize.js"></script>
    <script type="text/javascript" src="js/main.js"></script>

    <script>
        window.addEventListener('load', function() {
            var loc = window.location;
            currentPath = loc['origin']+loc['pathname'];
            fields = <?php echo $i; ?>;
            language = '<?php echo $lang; ?>';
<?php foreach ($vals as $key => $values) :?>
if ($('input[name="<?php echo $key; ?>"]').length && $('input[name="key<?php echo ($i-1); ?>"]').attr('id') != 'new_key')
    var autocomplete<?php echo str_replace('value', '', $key); ?> = $('input[name="<?php echo $key; ?>"]').selectize({
        delimiter: '|~|',
        persist: true,
        closeAfterSelect: true,
        maxItems: null,
        options: [
            <?php foreach($values as $val): ?>
            {text: '<?php echo $val; ?>', value: '<?php echo $val; ?>'},
            <?php endforeach; ?>
        ],create: function(input) {
            // save to database
            $('#loading').show();
            var valKey = $(this)[0].$input[0].name;
            $.ajax({
                url: currentPath+'?ajax=insert_value',
                method: 'POST',
                dataType: 'json',
                data: {
                    lang: language,
                    key: valKey,
                    value: input
                }
            }).done(function() {
                $('#loading').hide();
            });
            return {
                value: input,
                text: input
            }
        }
    });
    autoc<?php echo str_replace('value', '', $key); ?> = autocomplete<?php echo str_replace('value', '', $key); ?>[0].selectize;
<?php endforeach; ?>
});
    </script>
</body>
</html>
