<?php
/**
 * Statistics Counter Section
 *
 * @package YTrip
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'ytrip_homepage' );
$stats   = isset( $options['stats_items'] ) ? $options['stats_items'] : array();

// Default stats if none configured.
if ( empty( $stats ) ) {
	// Get real counts from database.
	$tours_count        = wp_count_posts( 'ytrip_tour' );
	$destinations_count = wp_count_terms( 'ytrip_destination' );

	$stats = array(
		array(
			'number' => $tours_count->publish ?? 50,
			'suffix' => '+',
			'label'  => esc_html__( 'Tours', 'ytrip' ),
			'icon'   => 'compass',
		),
		array(
			'number' => is_wp_error( $destinations_count ) ? 25 : $destinations_count,
			'suffix' => '+',
			'label'  => esc_html__( 'Destinations', 'ytrip' ),
			'icon'   => 'map',
		),
		array(
			'number' => 10000,
			'suffix' => '+',
			'label'  => esc_html__( 'Happy Travelers', 'ytrip' ),
			'icon'   => 'users',
		),
		array(
			'number' => 15,
			'suffix' => '',
			'label'  => esc_html__( 'Years Experience', 'ytrip' ),
			'icon'   => 'award',
		),
	);
}

// Icon SVGs.
$icons = array(
	'compass' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>',
	'map'     => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
	'users'   => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
	'award'   => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>',
	'default' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>',
);
?>

<section class="ytrip-section ytrip-stats-section">
	<div class="ytrip-container">
		<div class="ytrip-stats-grid">
			<?php foreach ( $stats as $stat ) :
				$icon_key = isset( $stat['icon'] ) ? sanitize_key( $stat['icon'] ) : 'default';
				$icon     = isset( $icons[ $icon_key ] ) ? $icons[ $icon_key ] : $icons['default'];
			?>
				<div class="ytrip-stat-item">
					<div class="ytrip-stat-item__icon">
						<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="ytrip-stat-item__number" data-count="<?php echo esc_attr( $stat['number'] ?? 0 ); ?>">
						<span class="ytrip-stat-item__value">0</span>
						<span class="ytrip-stat-item__suffix"><?php echo esc_html( $stat['suffix'] ?? '' ); ?></span>
					</div>
					<div class="ytrip-stat-item__label"><?php echo esc_html( $stat['label'] ?? '' ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<script>
(function() {
	const counters = document.querySelectorAll('.ytrip-stat-item__number');
	const speed = 200;

	const animateCounter = (counter) => {
		const target = +counter.getAttribute('data-count');
		const valueEl = counter.querySelector('.ytrip-stat-item__value');
		const count = +valueEl.innerText;
		const inc = target / speed;

		if (count < target) {
			valueEl.innerText = Math.ceil(count + inc);
			setTimeout(() => animateCounter(counter), 1);
		} else {
			valueEl.innerText = target.toLocaleString();
		}
	};

	const observer = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
				animateCounter(entry.target);
				observer.unobserve(entry.target);
			}
		});
	}, { threshold: 0.5 });

	counters.forEach(counter => observer.observe(counter));
})();
</script>
