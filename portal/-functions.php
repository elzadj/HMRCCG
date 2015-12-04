<?php
function html($str) {
    return htmlspecialchars($str, ENT_COMPAT, 'utf-8');
}
