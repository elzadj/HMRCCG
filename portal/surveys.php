<?php
$page = 'surveys-page';
require_once('-functions.php');
require_once('-head.php');
?>

    <main>
        <div class="page-header">
            <a class="button back" href=".?id=<?php echo html($deviceID); ?>">
                <img src="img/arrow-circle-left.svg" alt="">
                <span>Back</span>
            </a>
        </div>

        <h1>Surveys</h1>
        <?php if (isset($practice->surveys) && count($practice->surveys)) { ?>
        <ul class="buttons">
            <?php foreach ($practice->surveys as $survey) { ?>
            <li>
                <a class="button" href="<?php echo $survey->url; ?>">
                    <span>
                        <?php echo $survey->title; ?>
                        <?php echo isset($survey->subtitle) ? '<span>' . $survey->subtitle . '</span>' : ''; ?>
                    </span>
                    <img src="img/arrow-circle-right.svg" alt="">
                </a>
            </li>
            <?php } ?>
        </ul>
        <?php } else { ?>
        <p>No surveys to show.</p>
        <?php } ?>
    </main>
    
<?php
require_once('-footer.php');
require_once('-scripts.php');
require_once('-foot.php');
