<?php
function my_user_profile_rewrite() {
    add_rewrite_rule(
    '^([a-z]{2})/user/([^/]+)/?$',
    'index.php?lang=$matches[1]&user_profile=$matches[2]',
    'top'
);

}
add_action('init', 'my_user_profile_rewrite', 10);

function my_user_profile_query_vars($vars) {
    $vars[] = 'user_profile';
    $vars[] = 'lang';
    return $vars;
}
add_filter('query_vars', 'my_user_profile_query_vars');

function my_user_profile_template($template) {
    $user_nicename = get_query_var('user_profile');
    if ($user_nicename) {
        $user = get_user_by('slug', $user_nicename);
        if ($user) {
            $new_template = locate_template(['page-template/page-user-profile.php']);
            if ($new_template) return $new_template;
        } else {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return get_404_template();
        }
    }
    return $template;
}
add_filter('template_include', 'my_user_profile_template');