<?php
/**
 * @since             0.1.0
 * @package           WPFindSharedTerms
 *
 * @wordpress-plugin
 * Plugin Name: Find Shared Terms
 * Plugin URI: http://www.josheaton.org/
 * Description: Find shared terms in your WP install that may be split in WordPress 4.2+
 * Version: 0.1.0
 * Author: Josh Eaton
 * Author URI: http://www.josheaton.org/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-find-shared-terms
 * Domain Path: /languages
 */
/*  Copyright 2015 Josh Eaton (email : josh@josheaton.org)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'wpfst_add_admin_page' );
/**
 * Add admin page
 *
 * @since 0.1.0
 * @return void
 */
function wpfst_add_admin_page() {
	add_management_page(
		__( 'Shared Terms', 'wp-find-shared-terms' ),
		__( 'Find Shared Terms', 'wp-find-shared-terms' ),
		'manage_options',
		'wpfst-show-terms',
		'wpfst_show_terms_page'
	);
}

/**
 * Render admin page
 *
 * @since 0.1.0
 * @return void
 */
function wpfst_show_terms_page() {
	/** @var wpdb $wpdb */
	global $wpdb;

	// Get a count of any shared terms
	$sql = "
		SELECT COUNT(DISTINCT tt1.term_id)
		FROM {$wpdb->term_taxonomy} tt1
		WHERE (
			SELECT COUNT(*)
			FROM {$wpdb->term_taxonomy} tt2
			WHERE tt1.term_id = tt2.term_id
		) > 1;";
	$count_of_shared_terms = (int) $wpdb->get_var( $sql );
	?>
	<div class="wrap">
		<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
		<?php if ( 1 > $count_of_shared_terms ) : ?>
			<p><?php _e( "You have no shared terms. If you're already on WordPress 4.1, you shouldn't have any issues due to shared term splitting.", 'wp-find-shared-terms' ); ?></p>
		<?php else : ?>
			<?php wpfst_show_terms_page_table( $count_of_shared_terms ); ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render admin page table
 *
 * @since 0.1.0
 * @param int $count_of_shared_terms The number of distinct shared terms
 * @return void
 */
function wpfst_show_terms_page_table( $count_of_shared_terms ) {
	/** @var wpdb $wpdb */
	global $wpdb;

	// Get shared terms, names, taxonomies and the count of posts that have that term
	$sql = "
		SELECT tt1.term_taxonomy_id, tt1.term_id, t.name, tt1.taxonomy, tt1.count
		FROM {$wpdb->term_taxonomy} tt1
		INNER JOIN {$wpdb->terms} t ON t.term_id = tt1.term_id
		WHERE (
			SELECT COUNT(*)
			FROM {$wpdb->term_taxonomy} tt2
			WHERE tt1.term_id = tt2.term_id
		) > 1
		ORDER BY tt1.term_id;";
	?>
	<p>
		<?php printf( _n( "There is <strong>1</strong> shared term in your database.", "There are <strong>%d</strong> shared terms in your database.", $count_of_shared_terms, 'wp-find-shared-terms' ), $count_of_shared_terms ); ?>
		<?php printf( _x( "If you are running any plugins or themes that store term IDs, you may be affected by <a href=\"%s\">shared term splitting</a> in WordPress 4.2+.", '%s=URL of according post on make.wordpress.org', 'wp-find-shared-terms' ), 'https://make.wordpress.org/core/2015/02/16/taxonomy-term-splitting-in-4-2-a-developer-guide/' ); ?>
	</p>
	<table class="widefat">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Term Taxonomy ID', 'wp-find-shared-terms' ); ?></th>
			<th><?php esc_html_e( 'Term ID', 'wp-find-shared-terms' ); ?></th>
			<th><?php esc_html_e( 'Name', 'wp-find-shared-terms' ); ?></th>
			<th><?php esc_html_e( 'Taxonomy', 'wp-find-shared-terms' ); ?></th>
			<th><?php esc_html_e( '# of Posts', 'wp-find-shared-terms' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $wpdb->get_results( $sql ) as $shared_term ) {
			// Get the nice taxonomy label if it exists. It's possible you have old terms from taxonomies that are no longer active
			$taxonomy = get_taxonomy( $shared_term->taxonomy );
			if ( $taxonomy ) {
				$taxonomy_name = $taxonomy->labels->name;
			} else {
				$taxonomy_name = $shared_term->taxonomy;
			}

			// Get the term edit link
			$edit_link = get_edit_term_link( $shared_term->term_id, $shared_term->taxonomy );
			?>
			<tr>
				<td><?php echo esc_html( $shared_term->term_taxonomy_id ); ?></td>
				<td><?php echo esc_html( $shared_term->term_id ); ?></td>
				<td><a href="<?php echo esc_url( $edit_link ); ?>" title="<?php esc_html_e( 'Edit Term', 'wp-find-shared-terms' ); ?>"><?php echo esc_html( $shared_term->name ); ?></a></td>
				<td><abbr title="<?php echo esc_attr( $shared_term->taxonomy ); ?>"><?php echo esc_html( $taxonomy_name ); ?></abbr></td>
				<td><?php echo esc_html( $shared_term->count ); ?></td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
	<?php
}
