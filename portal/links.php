<?php
$page = 'links-page';
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

        <h1>Useful links</h1>
        <?php if (isset($practice->links) && count($practice->links)) { ?>
        <ul class="buttons">
            <?php foreach ($practice->links as $link) { ?>
            <li>
                <a class="button" href="<?php echo $link->url; ?>">
                    <span>
                        <?php echo $link->title; ?>
                        <?php echo isset($link->subtitle) ? '<span>' . $link->subtitle . '</span>' : ''; ?>
                    </span>
                    <img src="img/arrow-circle-right.svg" alt="">
                </a>
            </li>
            <?php } ?>
        </ul>
        <?php } else { ?>
        <p>No links to show.</p>
        <?php } ?>
    </main>
    
<?php
require_once('-footer.php');
require_once('-scripts.php');
require_once('-foot.php');
