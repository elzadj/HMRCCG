<?php
$page = 'news-page';
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

        <h1>Latest News</h1>
        
        <?php
        $filepath = 'config/practices/' . $deviceID . '/news.html';

        if (file_exists($filepath)) {
            include ($filepath);
        } else {
            echo '<p>No recent news</p>';
        }
        ?>

    </main>
    
<?php
require_once('-footer.php');
require_once('-scripts.php');
require_once('-foot.php');
