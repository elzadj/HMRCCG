<?php
require_once('_functions.php');

# Vars
$cmsPDO = getCMSPDO();
$scPDO  = getSCPDO();

# Input
$locationID = isset($_GET['lid'])  ? $_GET['lid']   : NULL;
$from       = isset($_GET['from']) ? $_GET['from']  : NULL;
$to         = isset($_GET['to'])   ? $_GET['to']    : NULL;


# Get locations
$locations = getLocations($cmsPDO);

if (!isset($locationID) && count($locations) === 1) {
    $locationID = $locations[0]->id;
}


# Overview page
if (OVERVIEW_PAGE !== FALSE || isset($locationID)) {
    # Results
    ## Get Date objects
    if (isset($from)) {
        $d = explode('/', $from);
        $f = $d[2] . '-' . $d[1] . '-' . $d[0];
        $fromDate = new DateTime($f, new DateTimeZone('UTC'));
    } else {
        $fromDate = new DateTime('now', new DateTimeZone('UTC'));
        $fromDate->sub(new DateInterval('P1M'));
    }

    if (isset($to)) {
        $d = explode('/', $to);
        $t = $d[2] . '-' . $d[1] . '-' . $d[0];
        $toDate = new DateTime($t, new DateTimeZone('UTC'));
    } else {
        $toDate = new DateTime('now', new DateTimeZone('UTC'));
        //$toDate->sub(new DateInterval('P1D'));
    }

    ### Round up 'to date' to next midnight
    //$toDate->add(new DateInterval('P1D'));
    $toDate->setTime(23, 59, 59);
    //exit((string)$toDate->getTimestamp());


    /*## Redirect if no from or to
    if (!isset($_GET['from']) || !isset($_GET['to'])) {
        $vars = [];
        if (isset($locationID)) {
            $vars['lid'] = rawurlencode($locationID);
        }
        $vars['from'] = $fromDate->format('d/m/Y');
        $vars['to']   = $toDate->format('d/m/Y');

        header('Location:.?' . http_build_query($vars));
        exit();
    }*/

    if (defined(EARLIEST_DATE) && EARLIEST_DATE) {
        $earliestDate = new DateTime(EARLIEST_DATE, new DateTimeZone('UTC'));
    }

    ## Format dates
    $fromHTML = $fromDate->format('d/m/Y');
    $toHTML   = $toDate->format('d/m/Y');

    $interval = $fromDate->diff($toDate, TRUE);
    $dateRange = $interval->format('%a days');


    ## Location info
    if (isset($locationID)) {
        $location = getLocation($cmsPDO, $locationID);
    }
    $surveyID = isset($location) ? $location->survey_id : NULL;


    ## Get FFT totals
    $fftData = getFFTData($scPDO, $locations, $fromDate->getTimestamp(), $toDate->getTimestamp(), $surveyID);
    $responseCount = array_sum($fftData);


    if (isset($location)) {
        ## Comments
        $comments = getComments($cmsPDO, $surveyID);

        ## Improvements
        $improvements = isset($location->improvements) ? explode('~|~', $location->improvements) : [];
    }
}

?>
<!DOCTYPE html>
<html lang="en-GB" class="no-js">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title><?php echo ucfirst(PHRASING_UNIT); ?> results</title>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans:400,600">
    <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/ui-lightness/jquery-ui.css" />

    <script src="js/modernizr.js"></script>
</head>


<body class="container">

    <header class="site-header">
        <h1><?php echo html(CUSTOMER_NAME); ?></h1>
        <?php if (defined('CUSTOMER_LOGO')) { echo '<img src="config/' . html(CUSTOMER_LOGO) . '" alt="">'; } ?>
    </header>

    <div class="content">
        
        <?php if (!defined('DISPLAY_MENU') || DISPLAY_MENU !== FALSE) { ?>
        <aside class="locations-filter">
            <h3>Choose a <?php echo PHRASING_UNIT; ?></h3>
            
            <nav id="locations" class="locations" role="navigation">
                <?php
                if (OVERVIEW_PAGE !== FALSE) {
                    $selected = !isset($location) ? ' class="selected"' : '';
                    echo '<a' . $selected . ' href=".">All ' . PHRASING_UNIT . 's</a>';
                }

                foreach ($locations as $loc) {
                    $selected = isset($location) && $location->id === $loc->id ? ' class="selected"' : '';
                    echo '<a' . $selected . ' href="?lid=' . $loc->id . '">' . html($loc->name) . '</a>';
                }
                ?>
            </nav>
        </aside>
        <?php } ?>

        
        <main class="results" role="main">
            
            <?php if (OVERVIEW_PAGE !== FALSE || isset($location)) { ?>
            <h1 class="location-name"><?php echo isset($location) ? html($location->name) : ucfirst(PHRASING_GROUP) . ' Overview'; ?></h1>

            <section class="overview">
                <h2 class="section-heading">Survey Results</h2>
                
                <form action="." id="dates-filter" class="dates-filter" method="get">
                    <?php if (isset($location)) { echo '<input name="lid" type="hidden" value="' . $location->id . '">'; } ?>
                    <h3>Filter data by date range</h3>
                    
                    <div class="field">
                        <label for="from">From</label>
                        <input type="text" id="from" name="from" value="<?php echo $fromHTML; ?>">
                    </div>
                    <div class="field">
                        <label for="to">To</label>
                        <input type="text" id="to" name="to" value="<?php echo $toHTML; ?>">
                    </div>
                    <div class="field">
                        <button type="submit">Update</button>
                    </div>
                </form>
                
                <p class="framing"><?php echo isset($location) ? PHRASING_FFT_FRAME_UNIT : PHRASING_FFT_FRAME_GROUP; ?></p>
                <h3 class="question-title">“<?php echo PHRASING_FFT_QUESTION; ?>”</h3>

                <table class="fft-data">
                    <tfoot>
                        <tr>
                            <th>Total responses</th>
                            <td><strong><?php echo $responseCount; ?></strong> in <?php echo $dateRange; ?></td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <th>Extremely likely</th>
                            <td id="xlikely"><?php echo $fftData['extremely likely']; ?></td>
                        </tr>
                        <tr>
                            <th>Likely</th>
                            <td id="likely"><?php echo $fftData['likely']; ?></td>
                        </tr>
                        <tr>
                            <th>Neither likely nor unlikely</th>
                            <td id="neither"><?php echo $fftData['neither likely nor unlikely']; ?></td>
                        </tr>
                        <tr>
                            <th>Unlikely</th>
                            <td id="unlikely"><?php echo $fftData['unlikely']; ?></td>
                        </tr>
                        <tr>
                            <th>Extremely unlikely</th>
                            <td id="xunlikely"><?php echo $fftData['extremely unlikely']; ?></td>
                        </tr>
                        <tr>
                            <th>Don't know</th>
                            <td id="unsure"><?php echo $fftData['don\'t know']; ?></td>
                        </tr>
                    </tbody>
                </table>

                <noscript>
                    Enable JavaScript to see these results in a chart.
                </noscript>

                <p class="response-count"><?php echo $responseCount; ?> responses in <?php echo $dateRange; ?></p>
                <div id="chart"></div>
            </section>
            
            <?php if (isset($comments) && count($comments)) { ?>
            <section class="comments">
                <h2 class="section-heading">Comments</h2>
                
                <ul>
                <?php
                foreach ($comments as $comment) {
                    $date = new DateTime($comment->submitted_at, new DateTimeZone('UTC'));
                    echo '<li>';
                    echo '    <blockquote class="respondent">
                            ' . formatComment($comment->comment) . '
                            <time datetime="' . $date->format('Y-m-d') . '">' . $date->format('d M Y') . '</time>
                        </blockquote>';

                    if (isset($comment->reply) && !empty($comment->reply)) {
                        echo '    <blockquote class="reply">';
                        echo '        <h3>Our reply</h3>
                            ' . formatComment($comment->reply) . '
                        </blockquote>';
                    }
                    echo '</li>';
                }
                ?>
                </ul>
            </section>
            <?php } ?>

            <?php if (isset($improvements) && count($improvements)) { ?>
            <section class="improvements">
                <h2 class="section-heading">How your feedback has helped us improve</h2>

                <ul>
                    <?php
                    foreach ($improvements as $improvement) {
                        echo '<li>' . $improvement . '</li>';
                    }
                    ?>
                    <!-- <li>The surgery is now open until 6.30pm on Tuesdays and Thursdays</li>
                    <li>Our kiosk now has links to information on a wider range of conditions</li>
                    <li>We have moved our lunch hour forward by 30 mins, by popular demand</li>
                    <li>We now have a better selection of waiting room magazines</li> -->
                </ul>
            </section>
            <?php }
            } else {
                echo '<p>Please choose a ' . PHRASING_UNIT . '.</p>';
            }
            ?>

            <footer class="site-footer">
                <a class="left" href="http://www.elephantkiosks.co.uk/solutions/friends-family-test"><img src="img/fft-logo-ek-200w.png" alt=""></a>
                <a class="right" href="http://www.elephantkiosks.co.uk/"><img src="img/eklogo-w200.png" alt=""></a>
            </footer>

        </main>
    
    </div>
    
    <?php if (OVERVIEW_PAGE !== FALSE || isset($location)) { ?>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script src="//www.google.com/jsapi"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
    
    <script>
    minDate = <?php echo isset($earliestDate) ? "'" . $earliestDate->format('d/m/Y') . "'" : "false"; ?>;
    </script>
    <script src="js/scripts.js"></script>
    <?php } ?>
    
</body>

</html>
