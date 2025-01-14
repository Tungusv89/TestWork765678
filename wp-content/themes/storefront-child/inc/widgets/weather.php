<?php
class Custom_City_Weather_Widget extends WP_Widget
{
	// Конструктор виджета
	public function __construct()
	{
		parent::__construct(
			'custom_city_weather_widget',
			__('City Weather Widget', 'textdomain'),
			array('description' => __('Displays weather for a selected city.', 'textdomain'))
		);
	}

	// Форма настройки виджета в админке
	public function form($instance)
	{
		$selected_city = !empty($instance['city']) ? $instance['city'] : '';
		$api_key = !empty($instance['api_key']) ? $instance['api_key'] : '';

		// Получаем все записи типа "Cities"
		$cities = get_posts(array(
			'post_type'   => 'cities',
			'numberposts' => -1,
		));

?>
		<p>
			<label for="<?php echo $this->get_field_id('city'); ?>"><?php _e('Select City:', 'textdomain'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('city'); ?>" name="<?php echo $this->get_field_name('city'); ?>">
				<option value=""><?php _e('Select a City', 'textdomain'); ?></option>
				<?php foreach ($cities as $city): ?>
					<option value="<?php echo $city->ID; ?>" <?php selected($selected_city, $city->ID); ?>>
						<?php echo $city->post_title; ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('api_key'); ?>"><?php _e('OpenWeatherMap API Key:', 'textdomain'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('api_key'); ?>" name="<?php echo $this->get_field_name('api_key'); ?>" type="text" value="<?php echo esc_attr($api_key); ?>" />
		</p>
<?php
	}

	// Сохранение настроек виджета
	public function update($new_instance, $old_instance)
	{
		$instance = array();
		$instance['city'] = (!empty($new_instance['city'])) ? sanitize_text_field($new_instance['city']) : '';
		$instance['api_key'] = (!empty($new_instance['api_key'])) ? sanitize_text_field($new_instance['api_key']) : '';
		return $instance;
	}

	// Отображение виджета
	public function widget($args, $instance)
	{
		echo $args['before_widget'];

		if (!empty($instance['city']) && !empty($instance['api_key'])) {
			$city_id = $instance['city'];
			$api_key = $instance['api_key'];

			// Получаем данные о городе
			$city = get_post($city_id);
			if ($city) {
				$city_name = $city->post_title;

				// Получаем температуру через API
				$weather_data = $this->fetch_weather_data($city_name, $api_key);

				echo $args['before_title'] . esc_html($city_name) . $args['after_title'];

				if ($weather_data && isset($weather_data->main->temp)) {
					echo '<p>' . __('Temperature:', 'textdomain') . ' ' . $weather_data->main->temp . '°C</p>';
				} else {
					echo '<p>' . __('Weather data is unavailable.', 'textdomain') . '</p>';
				}
			}
		}

		echo $args['after_widget'];
	}

	// Функция получения данных погоды из OpenWeatherMap API
	private function fetch_weather_data($city_name, $api_key)
	{
		$api_url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($city_name) . '&units=metric&appid=' . $api_key;
		$response = wp_remote_get($api_url);

		if (is_wp_error($response)) {
			return false;
		}

		$data = wp_remote_retrieve_body($response);
		return json_decode($data);
	}
}

// Регистрация виджета
function register_custom_city_weather_widget()
{
	register_widget('Custom_City_Weather_Widget');
}
add_action('widgets_init', 'register_custom_city_weather_widget');
