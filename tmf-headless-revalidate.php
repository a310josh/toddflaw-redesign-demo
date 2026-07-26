<?php
/**
 * Plugin Name: TMF Headless Revalidate
 * Description: Notifies the Next.js front end (Vercel) when a post or page is published or updated, so the live site refreshes in seconds instead of waiting for the ISR hour. Install as an mu-plugin (wp-content/mu-plugins/) or a normal plugin.
 * Version: 1.0.0
 * Author: TMF Rebuild (Fable)
 *
 * Configuration: define these in wp-config.php (never commit real values):
 *   define('TMF_NEXT_REVALIDATE_URL', 'https://toddflaw-next.vercel.app/api/revalidate');
 *   define('TMF_REVALIDATE_SECRET', '<long random string, same value as the Vercel env REVALIDATE_SECRET>');
 */

if (!defined('ABSPATH')) exit;

add_action('transition_post_status', function ($new_status, $old_status, $post) {
    // Fire on publish/update of public content only.
    if ($new_status !== 'publish' && $old_status !== 'publish') return;
    if (!in_array($post->post_type, ['post', 'page'], true)) return;
    if (!defined('TMF_NEXT_REVALIDATE_URL') || !defined('TMF_REVALIDATE_SECRET')) return;

    $permalink = get_permalink($post);
    if (!$permalink) return;
    $path = wp_parse_url($permalink, PHP_URL_PATH) ?: '/';

    $paths = [$path];
    // Posts also invalidate the blog archive; pages invalidate their parent hub.
    if ($post->post_type === 'post') {
        $paths[] = '/blog/';
    } elseif ($post->post_parent) {
        $parent = get_permalink($post->post_parent);
        if ($parent) $paths[] = wp_parse_url($parent, PHP_URL_PATH);
    }

    foreach (array_unique($paths) as $p) {
        // Fire-and-forget; never block editing on front-end availability.
        wp_remote_post(TMF_NEXT_REVALIDATE_URL, [
            'timeout'  => 3,
            'blocking' => false,
            'headers'  => ['Content-Type' => 'application/json'],
            'body'     => wp_json_encode(['path' => $p, 'secret' => TMF_REVALIDATE_SECRET]),
        ]);
    }
}, 10, 3);
