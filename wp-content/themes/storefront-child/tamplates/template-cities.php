<?php

/**
 * Template Name: Cities and Weather
 */

get_header();
?>

<div id="cities-search">
	<input type="text" id="search-city" placeholder="Search cities..." />
	<button id="search-button">Search</button>
</div>

<?php
// Custom action hook before the table
do_action('before_cities_table'); ?>

<div id="cities-table-container">
	<!-- Таблица будет загружаться через AJAX -->
</div>

<?php
// Custom action hook after the table
do_action('after_cities_table');

get_footer();
