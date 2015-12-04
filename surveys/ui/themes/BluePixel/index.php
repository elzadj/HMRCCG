<?php
require_once('-template.php');

define('FORM_ID', 'pageForm');

$htmlLang = is_null($langCode) || $langCode === 'en' ? 'en-GB' : $langCode;

$answers  = isset($_SESSION['sc']['answers']) ? $_SESSION['sc']['answers'] : [];
$template = new Template($answers, $surveyID, '../surveys/', TRUE);

if (!is_null($langCode) && isset($config['translations'])) {
    $template->setLanguage($config['translations'], $langCode);
}

$buttons = [
    'exit'   => [
        'code'  => 'ui-exit',
        'title' => 'Exit'
    ],
    'back'   => [
        'code'  => 'ui-back',
        'title' => 'Back'
    ],
    'start'  => [
        'code'  => 'ui-start',
        'title' => 'Start'
    ],
    'next'   => [
        'code'  => 'ui-next',
        'title' => 'Next'
    ],
    'finish' => [
        'code'  => 'ui-finish',
        'title' => 'Finish'
    ],
];

## Translate button titles
foreach ($buttons as $k => $v) {
    if ($title = $template->translate($v['code'])) {
        $buttons[$k]['title'] = $title;
    }
}


?><!DOCTYPE HTML>
<html lang="<?php echo $htmlLang; ?>" class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title><?php echo config_var_exists($config, 'surveyTitle') ? html($config['surveyTitle']) : ''; ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<?php if ($pageType === 'thankyou' && config_var_exists($config, 'settings', 'outroTimeout') && (int)$config['settings']['outroTimeout'] > 0 && isset($url_menu) && !empty($url_menu)) { ?>
    <meta http-equiv="refresh" content="<?php echo $config['settings']['outroTimeout'] . ';url=' . $url_menu; ?>">
<?php } ?>

    <link type="text/plain" rel="author" href="humans.txt">

    <link rel="stylesheet" href="<?php echo $themeURL; ?>css/styles.css">

    <script src="<?php echo $jsURL; ?>head.min.js"></script>

</head>

<?php
$classes = array('container');

if (!empty($surveyID)) {
    $classes[] = html($surveyID);
}

if (isset($pageID)) {
    $page_data = $page->get_page_data($pageID);
    $catID     = $page_data['categoryID'];

    if (!empty($catID)) {
        $classes[] = html($catID);
    }
    if (!empty($pageID)) {
        $classes[] = html($pageID); 
    }
}
?>

<body class="<?php echo implode(' ', $classes); ?>">
        
    <header class="header" role="banner">
        <div class="left">
        <?php if (!empty($url_menu)) { ?>
            <div class="button-cancel" data-textcode="<?php echo $buttons['exit']['code']; ?>">
                <a class="label" href="<?php echo html($url_menu); ?>"><?php echo html($buttons['exit']['title']); ?></a>
            </div>
        <?php } ?>
        </div>

        <div class="right">
            <img
                alt=""
                sizes="(min-width:401px) 250px,
                    150px"
                src="<?php echo $themeURL; ?>img/logo-150w.jpg"
                srcset="<?php echo $themeURL; ?>img/logo-150w.jpg 150w,
                    <?php echo $themeURL; ?>img/logo-250w.jpg 250w">
        </div>
    </header>


    <header class="survey-titles" role="banner">
    <?php if (config_var_exists($config, 'surveyTitle')) { ?>
        <h1 class="title"><?php echo html($config['surveyTitle']); ?></h1>
    <?php } if (config_var_exists($config, 'surveySubtitle')) { ?>
        <h2 class="subtitle"><?php echo html($config['surveySubtitle']); ?></h2>
    <?php } ?>
    </header>
    
    <div class="frame" id="frame">
        <section class="page <?php echo $pageType; ?>">
            <?php
            switch ($pageType) {
                case 'intro':
                    echo '<h1 class="fft-logo-ek">Friends &amp; Family Test by Elephant Kiosks</h1>' . "\n";
                    echo markdown($content, FALSE);
                    break;

                case 'date':
                    include_once('_date.php');
                    break;

                case 'survey':
                    include_once('_survey.php');
                    break;
                
                case 'thankyou':
                    echo markdown($content, FALSE);
                    break;
            }
            ?>
        </section>


        <nav class="page-footer" id="page-footer" role="navigation">
            <div class="left">
                <?php if ($pageType === 'survey' && ($pageID !== $firstPageID || !is_null($introPath))) { ?>
                <div class="button-back" data-textcode="<?php echo $buttons['back']['code']; ?>">
                    <button class="label" form="<?php echo FORM_ID; ?>" id="btnPrev" type="button"><?php echo html($buttons['back']['title']); ?></button>
                </div>
                <?php } ?>
            </div>

            <div class="right">
                <?php if ($pageType === 'intro') { ?>
                <div class="button-start" data-textcode="<?php echo $buttons['start']['code']; ?>">
                    <a class="label" href=".?pagetype=survey"><?php echo html($buttons['start']['title']); ?></a>
                </div>
                <?php } elseif ($pageType === 'date') { ?>
                <div class="button-start" data-textcode="<?php echo $buttons['start']['code']; ?>">
                    <button class="label" form="<?php echo FORM_ID; ?>" id="btnNext" type="button"><?php echo html($buttons['start']['title']); ?></button>
                </div>
                <?php } elseif ($pageType === 'survey' && !$page->is_last_page($pageID)) { ?>
                <div class="button-next" data-textcode="<?php echo $buttons['next']['code']; ?>">
                    <button class="label" form="<?php echo FORM_ID; ?>" id="btnNext" type="button"><?php echo html($buttons['next']['title']); ?></button>
                </div>
                <?php } elseif ($pageType === 'survey') { ?>
                <div class="button-finish" data-textcode="<?php echo $buttons['finish']['code']; ?>">
                    <button class="label" form="<?php echo FORM_ID; ?>" id="btnNext" type="button"><?php echo html($buttons['finish']['title']); ?></button>
                </div>
                <?php } elseif ($pageType === 'thankyou') { ?>
                <div class="button-next" data-textcode="<?php echo $buttons['next']['code']; ?>">
                    <a class="label" href="<?php echo html($url_menu); ?>"><?php echo html($buttons['next']['title']); ?></a>
                </div>
                <?php } ?>
            </div>
        </nav>

        
    </div> <!--/frame-->
    

    <?php if ($pageType === 'survey') { ?>
        <div class="progress-bar" id="progress" data-value="<?php echo floor($page->calculate_progress($pageID)); ?>">
            <div></div>
        </div>
    <?php } ?>


    <?php if ($device_type === DTYPE_HANDHELD) { ?>
    <div id="battery-level"></div>
    <?php } ?>

    
    <!-- Dependencies -->
    <script src="<?php echo $jsURL; ?>dependencies.min.js"></script>
    
    <!-- Configuration -->
    <script>
    var survey = {
            surveyID: '<?php echo $surveyID; ?>',
            deviceID: '<?php echo $deviceID; ?>',
            config:    <?php echo json_encode($config, JSON_PRETTY_PRINT); ?>,

            configVarExists: function () {
                    var args = Array.prototype.slice.call(arguments),   // Convert arguments into an array
                        conf = JSON.parse(JSON.stringify(this.config)); // Clone config object,
                        i    = 0,
                        len  = args.length;

                    for (i = 0; i < len; i+=1) {
                        if (conf[args[i]] !== undefined) {
                            conf = conf[args[i]];
                        } else {
                            return false;
                        }
                    }

                    return true;
                }
        };
    </script>

    <!-- Custom scripts -->
    <script src="<?php echo $themeURL; ?>js/scripts.min.js"></script>

</body>
</html>
