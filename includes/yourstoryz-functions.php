<?php

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Retrieve the saved API token.
 *
 * @return string|false The API token string, or false if not set.
 */
function get_yourstoryz_api_token()
{
    return get_option(YOURSTORYZ_API_TOKEN_OPTION, false);
}

/**
 * Retrieve the selected storiable type ('Company' or 'Department').
 *
 * @return string
 */
function get_yourstoryz_storiable_type()
{
    return get_option(YOURSTORYZ_STORIABLE_TYPE_OPTION, 'Company');
}

/**
 * Retrieve the selected storiable ID.
 *
 * @return int
 */
function get_yourstoryz_storiable_id()
{
    return get_option(YOURSTORYZ_STORIABLE_ID_OPTION, 0);
}
