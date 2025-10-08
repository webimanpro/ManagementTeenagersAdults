<?php
// Debug function to log session state
function debug_session($step) {
    error_log("Step $step - Session state:" . print_r($_SESSION, true));
    error_log("Step $step - POST data:" . print_r($_POST, true));
    error_log("Step $step - Selected Teens: " . ($_SESSION["selected_teens"] ?? "not set"));
    error_log("Step $step - Selected Adults: " . ($_SESSION["selected_adults"] ?? "not set"));
}