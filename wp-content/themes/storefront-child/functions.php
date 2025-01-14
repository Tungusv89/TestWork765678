<?php

add_action('init', 'register_post_types');

/** * Регистрирует типы записей. */
function register_post_types()
{

	// Регистрируем тип записи "Cities"
	register_post_type('cities', [
		'label'  => 'Cities',
		'labels' => [
			'name'               => 'Cities', // основное название для типа записи
			'singular_name'      => 'City', // название для одной записи этого типа
			'add_new'            => 'Добавить Cities', // для добавления новой записи
			'add_new_item'       => 'Добавление Cities', // заголовка у вновь создаваемой записи в админ-панели.
			'edit_item'          => 'Редактирование Cities', // для редактирования типа записи
			'new_item'           => 'Новое Cities', // текст новой записи
			'view_item'          => 'Смотреть Cities', // для просмотра записи этого типа.
			'search_items'       => 'Искать Cities', // для поиска по этим типам записи
			'not_found'          => 'Не найдено', // если в результате поиска ничего не было найдено
			'not_found_in_trash' => 'Не найдено в корзине', // если не было найдено в корзине
			'parent_item_colon'  => '', // для родителей (у древовидных типов)
			'menu_name'          => 'Cities', // название меню
		],
		'description'            => '',
		'public'                 => true,
		// 'publicly_queryable'  => null, // зависит от public
		// 'exclude_from_search' => null, // зависит от public
		'show_ui'             => true, // зависит от public
		'show_in_nav_menus'   => true, // зависит от public
		'show_in_menu'           => true, // показывать ли в меню админки
		'show_in_admin_bar'   => true, // зависит от show_in_menu
		'show_in_rest'        => null, // добавить в REST API. C WP 4.7
		'rest_base'           => null, // $post_type. C WP 4.7
		'menu_position'       => null,
		'menu_icon'           => null,
		//'capability_type'   => 'post',
		//'capabilities'      => 'post', // массив дополнительных прав для этого типа записи
		//'map_meta_cap'      => null, // Ставим true чтобы включить дефолтный обработчик специальных прав
		'hierarchical'        => true,
		'supports'            => ['title', 'editor'], // 'title','editor','author','thumbnail','excerpt','trackbacks','custom-fields','comments','revisions','page-attributes','post-formats'
		'taxonomies'          => ['countries'],
		'has_archive'         => true,
		'rewrite'             => true,
		'query_var'           => true,
	]);
}

// Регистрация таксономии "Countries"
add_action('init', 'register_countries_taxonomy');
function register_countries_taxonomy()
{
	register_taxonomy('Countries', ['cities'], [
		'label' => 'Countries',
		'labels' => [
			'name' => 'Countries',
			'singular_name' => 'Country',
			'search_items' => 'Искать Countries',
			'all_items' => 'Все Countries',
			'parent_item' => 'Родительская Country',
			'parent_item_colon' => 'Родительская Country:',
			'edit_item' => 'Редактировать Country',
			'update_item' => 'Обновить Country',
			'add_new_item' => 'Добавить новую Country',
			'new_item_name' => 'Новое название Country',
			'menu_name' => 'Countries',
		],
		'public' => true,
		'hierarchical' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_admin_column' => true,
		'show_in_rest' => true,
		'rest_base' => 'countries',
		'rewrite' => ['slug' => 'countries'],
	]);
}

// Добавляем метабокс в "Cities"
add_action('add_meta_boxes', 'add_lat_long_metabox');
function add_lat_long_metabox()
{
	add_meta_box(
		'lat_long_meta',      // Идентификатор
		'Координаты города',  // Заголовок
		'display_lat_long_metabox',  // Функция для отображения
		'Cities',              // Тип записи
		'normal',
		'high'
	);
}

// Функция для отображения метабокса
function display_lat_long_metabox($post)
{
	// Получаем значения произвольных полей, если они уже сохранены
	$latitude = get_post_meta($post->ID, '_latitude', true);
	$longitude = get_post_meta($post->ID, '_longitude', true);
?>
	<label for="latitude">Широта:</label>
	<input type="text" id="latitude" name="latitude" value="<?php echo esc_attr($latitude); ?>" />
	<br />
	<label for="longitude">Долгота:</label>
	<input type="text" id="longitude" name="longitude" value="<?php echo esc_attr($longitude); ?>" />
<?php
	// Добавляем nonce для безопасности
	wp_nonce_field('save_lat_long_meta', 'lat_long_nonce');
}

// Сохраняем значения метаполей при сохранении записи
add_action('save_post', 'save_lat_long_meta');
function save_lat_long_meta($post_id)
{
	// Проверяем nonce для безопасности
	if (!isset($_POST['lat_long_nonce']) || !wp_verify_nonce($_POST['lat_long_nonce'], 'save_lat_long_meta')) {
		return $post_id;
	}

	// Проверяем возможность редактирования записи
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $post_id;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return $post_id;
	}

	// Сохраняем значения метаполей
	$latitude = sanitize_text_field($_POST['latitude']);
	$longitude = sanitize_text_field($_POST['longitude']);

	update_post_meta($post_id, '_latitude', $latitude);
	update_post_meta($post_id, '_longitude', $longitude);
}

// Виджет погоды
include_once get_stylesheet_directory() . '/inc/widgets/weather.php';

//Ajax
add_action('wp_ajax_get_cities', 'get_cities_ajax_handler');
add_action('wp_ajax_nopriv_get_cities', 'get_cities_ajax_handler');

function get_cities_ajax_handler()
{
	global $wpdb;

	$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
	$query = "SELECT * FROM {$wpdb->prefix}cities";

	if (!empty($search)) {
		$query .= $wpdb->prepare(" WHERE city LIKE %s", '%' . $wpdb->esc_like($search) . '%');
	}

	$results = $wpdb->get_results($query);

	if ($results) {
		echo '<table>';
		echo '<thead><tr><th>Country</th><th>City</th><th>Temperature (°C)</th></tr></thead><tbody>';
		foreach ($results as $row) {
			echo '<tr>';
			echo '<td>' . esc_html($row->country) . '</td>';
			echo '<td>' . esc_html($row->city) . '</td>';
			echo '<td>' . esc_html($row->temperature) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p>No results found.</p>';
	}

	wp_die(); // Завершение AJAX-запроса
}

// Localizate ajax
function enqueue_custom_scripts()
{
	wp_enqueue_script('cities-ajax', get_stylesheet_directory_uri() . '/assets/js/cities-ajax.js', ['jquery'], null, true);
	wp_localize_script('cities-ajax', 'ajaxData', [
		'ajax_url' => admin_url('admin-ajax.php'),
	]);
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');


// Хуки
add_action('before_cities_table', function () {
	echo '<p>Welcome to the cities and weather table. Use the search above to find specific cities.</p>';
});

add_action('after_cities_table', function () {
	echo '<p>Thank you for using our service! Stay updated on your city\'s weather.</p>';
});