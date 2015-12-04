<?php
$page = 'home-page';
require_once('-functions.php');
require_once('-head.php');

if (isset($sharedPractices) || !isset($practice->fft)) {
    $surveyURL = 'choose-practice.php?page=fft';
} else {
    $surveyURL = '../surveys/?sid=' . html($practice->fft) . '&amp;did=' . html($deviceID);
}

if (isset($sharedPractices)) {
    $hasSurveys = FALSE;
    $hasLinks = FALSE;
    $hasInfo = FALSE;
    $hasNews = FALSE;

    foreach ($sharedPractices as $p) {
        if (isset($p->surveys) && count($p->surveys)) {
            $hasSurveys = TRUE;
        }
        if (isset($p->links) && count($p->links)) {
            $hasLinks = TRUE;
        }
        if (isset($p->about)) {
            $hasInfo = TRUE;
        }
        if (file_exists('config/practices/' . $p->id . '/news.html')) {
            $hasNews = TRUE;
        }
    }
    $surveysURL  = $hasSurveys ? 'choose-practice.php?page=surveys' : NULL;
    $linksURL    = $hasLinks ? 'choose-practice.php?page=links' : NULL;
    $practiceURL = $hasInfo ? 'choose-practice.php?page=practice' : NULL;
    $newsURL     = $hasNews ? 'choose-practice.php?page=news' : NULL;

} else {
    $surveysURL  = isset($practice->surveys) && count($practice->surveys) ? 'surveys.php?id=' . html($deviceID) : NULL;
    $linksURL    = isset($practice->links) && count($practice->links) ? 'links.php?id=' . html($deviceID) : NULL;
    $practiceURL = isset($practice->about) ? 'practice-info.php?id=' . html($deviceID) : NULL;
    $newsURL     = file_exists('config/practices/' . $deviceID . '/news.html') ? 'news.php?id=' . html($deviceID) : NULL;
}
?>

    <main>
        <nav class="tiles">
            <div class="block<?php echo isset($surveysURL) || isset($newsURL) ? ' large' : ''; ?>">
                <div class="cell">
                    <div class="tile fft">
                        <a class="button" href="<?php echo $surveyURL; ?>">
                            <span>Would you recommend us?</span>
                            <img src="img/arrow-circle-right.svg" alt="">
                        </a>
                        <footer>
                            <p>Friends &amp; Family Test</p>
                        </footer>
                    </div>
                </div>
            </div>
            

            <?php if (isset($surveysURL) || isset($newsURL)) { ?>
            <div class="block">
                <?php if (isset($surveysURL)) { ?>
                <div class="cell">
                    <div class="tile surveys">
                        <a class="button" href="<?php echo $surveysURL; ?>">
                            <span>Other Surveys</span>
                            <img src="img/arrow-circle-right.svg" alt="">
                        </a>
                    </div>
                </div>
                <?php } ?>

                <?php if (isset($newsURL)) { ?>
                <div class="cell">
                    <div class="tile news">
                        <a class="button" href="<?php echo $newsURL; ?>">
                            <span>Latest News</span>
                            <img src="img/arrow-circle-right.svg" alt="">
                        </a>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php } ?>

            <?php if (isset($practiceURL) || isset($linksURL)) { ?>
            <div class="block">
                <?php if (isset($practiceURL)) { ?>
                <div class="cell">
                    <div class="tile info">
                        <a class="button" href="<?php echo $practiceURL; ?>">
                            <span>Our Practice</span>
                            <img src="img/arrow-circle-right.svg" alt="">
                        </a>
                    </div>
                </div>
                <?php } ?>

                <?php if (isset($linksURL)) { ?>
                <div class="cell">
                    <div class="tile links">
                        <a class="button" href="<?php echo $linksURL; ?>">
                            <span>Health Information</span>
                            <img src="img/arrow-circle-right.svg" alt="">
                        </a>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php } ?>
            
        </nav>
    </main>

<?php
require_once('-footer.php');
require_once('-scripts.php');
?>

<script>
(function ($, undefined) {
    'use strict';

    $('.tile').on('click', function () {
        var $tile = $(this),
            url   = $('a', $tile).eq(0).attr('href');

        window.location = url;
    });
})(jQuery);
</script>
    
<?php
require_once('-foot.php');
