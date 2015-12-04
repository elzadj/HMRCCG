<?php
require_once('../vendor/autoload.php');
require_once('../sc_page_generator.php');

use Symfony\Component\Yaml\Parser;

$questions = <<<EOT
q_name:
    title: What is your name?
    subtitle:
        title: Your <em>full name</em>, that is.
        type: html
    required: true
    type: text

q_gender:
    title:
        title: Are you **male** or **female**?
        type: markdown
    instruction:
        content: Choose one answer
        type: plain
    type: radio
    required: true
    options:
        - Male
        - Female
        - Male & Female
        -
            title: Neither
            value: Weirdo

q_experience:
    intro:
        content: We would like you to think about your experience in the ward where you spent the most time during this stay.
    title: What did you make of:
    type: matrix
    subquestions:
        - The food
        - Henry
        -
            id: music-sample
            content: Our little *musical number*
            type: markdown
    scale:
        - Hate
        -
            content: "*Passive*"
            type: markdown
        - Love
    options:
        - Hated
        - Disliked
        - Liked
        - Loved

EOT;


$structure = <<<EOT
cat_basic:
    pages:
        pg_name:
            questions:
                - q_name
                - q_dob
        pg_address:
            title: Address
            questions:
                - q_address
cat_primary:
    title: Primary Questions
    pages:
        pg_fft:
            title:
                content: The **FFT** Question
                type: markdown
            questions:
                -
                    content: q_fft
                -
                    type: html
                    content: And now: the <span>follow-up</span> question
                - q_fft_why
cat_secondary:
    title:
        title: Secondary questions
    pages:
        pg_last:
            questions:
                - q_last
EOT;

$yaml_parser = new Parser();
$arr_questions = $yaml_parser->parse($questions);
$arr_structure = $yaml_parser->parse($structure);

var_dump(
    SC_Survey_Constructor::standardise($arr_questions),
    SC_Page_Generator::standardise($arr_structure)
);
