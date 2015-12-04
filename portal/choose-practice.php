<?php
$page = 'list-page';
require_once('-functions.php');
require_once('-head.php');

if (!isset($sharedPractices) || !count($sharedPractices)) {
    $sharedPractices = $practices;
}

if (!isset($_GET['page'])) {
    exit('no page specified');
}
?>

    <main>
        <div class="page-header">
            <a class="button back" href=".?id=<?php echo html($deviceID); ?>">
                <img src="img/arrow-circle-left.svg" alt="">
                <span>Back</span>
            </a>
        </div>

        <h1>Choose a practice</h1>
        <ul class="buttons">
            <?php
            uasort($sharedPractices, function ($a, $b) {
                return $a->title > $b->title;
            });

            foreach ($sharedPractices as $practice) {
                $url = NULL;
                if (!empty($practice->title)) {
                    $loc = rawurlencode(
                        isset($practice->id) ? $practice->id : $practice->title
                    );

                    switch ($_GET['page']) {
                        case 'fft':
                            if (isset($practice->fft)) {
                                $url = '../surveys/?sid=' . html($practice->fft) . '&amp;did=' . html($deviceID);
                            }
                            break;

                        case 'surveys':
                            $url = 'surveys.php?id=' . html($deviceID) . '&as=' . html($practice->id);
                            break;

                        case 'links':
                            $url = 'links.php?id=' . html($deviceID) . '&as=' . html($practice->id);
                            break;

                        case 'practice':
                            $url = 'practice-info.php?id=' . html($deviceID) . '&as=' . html($practice->id);
                            break;

                        case 'news':
                            $url = 'news.php?id=' . html($deviceID) . '&as=' . html($practice->id);
                            break;
                    }

                    if (isset($url)) {
            ?>
            <li>
                <a class="button" href="<?php echo $url; ?>">
                    <span><?php echo html($practice->title); ?></span>
                    <img src="img/arrow-circle-right.svg" alt="">
                </a>
            </li>
            <?php } } } ?>
        </ul>
    </main>
    
<?php
require_once('-footer.php');
require_once('-scripts.php');
require_once('-foot.php');
