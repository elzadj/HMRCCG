<?php

# Debugging
if (isset($_GET['status'])) {
    var_dump($_SESSION['sc']['data'][$surveyID][$langCode]['rules']);
}


if (isset($page_data['categoryTitle'])) {
    echo '<div class="category-title"' . (isset($page_data['categoryTitle']['id']) ? ' data-textcode="' . $page_data['categoryTitle']['id'] . '"' : '') . '>' . "\n";
    echo '    <div class="label">' . html($template->translateMedia($page_data['categoryTitle'])['content']) . '</div>' . "\n";
    echo '</div>' . "\n";
}

?>
<form id="<?php echo FORM_ID; ?>" action="system/process_page.php" method="post" accept-charset="utf-8">
    <input type="hidden" name="sc_page_id" value="<?php echo html($pageID); ?>">
    <input type="hidden" name="sc_dir" value="fwd">

    <?php
    if (isset($page_data['pageTitle'])) {
        echo '<div class="page-title"' . (isset($page_data['pageTitle']['id']) ? ' data-textcode="' . $page_data['pageTitle']['id'] . '"' : '') . '>' . "\n";
        echo '    <div class="label">' . html($template->translateMedia($page_data['pageTitle'])['content']) . '</div>' . "\n";
        echo '</div>' . "\n";
    }

    foreach ($page_data['media'] as $media) {
        echo $template->getMediaHTML($media);
    }
    ?>

</form>


<!--Timeout dialog-->
<div id="dialogTimeout" class="dialog" title="Do you want to continue?">
    <p>You haven't interacted with the survey for over <span class="dialogTime"></span>.</p>
    <p>The survey will <span class="timeoutAction"></span> in <span class="secondsLeft"></span> seconds.</p>
</div>



