<?php
/**
 * Plugin Name:       Wenprise Products By Tags
 * Description:       Example block scaffolded with Create Block tool.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:           The WordPress Contributors
 * License:          GPL-2.0-or-later
 * License URI:      https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:      wenprise-products-by-tags
 *
 * @package CreateBlock
 */

function wprs_register_products_by_tag_block() {
	// 加载翻译文件
	load_plugin_textdomain('wenprise-products-by-tags', false, dirname(plugin_basename(__FILE__)) . '/languages');

	// 注册区块脚本
	wp_register_script(
		'wenprise-products-by-tags',
		plugins_url('build/index.js', __FILE__),
		array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n')
	);

	// 为 JavaScript 添加翻译支持
	if (function_exists('wp_set_script_translations')) {
		wp_set_script_translations(
			'wenprise-products-by-tags',
			'wenprise-products-by-tags',
			plugin_dir_path(__FILE__) . 'languages'
		);
	}

	register_block_type('wenprise/products-by-tags', array(
		'editor_script' => 'wenprise-products-by-tags',
		'attributes' => array(
			'taxonomyType' => array(
				'type' => 'string',
				'default' => 'product_tag'
			),
			'tagId' => array(
				'type' => 'string',
				'default' => ''
			),
			'columns' => array(
				'type' => 'number',
				'default' => 4
			),
			'tabletColumns' => array(
				'type' => 'number',
				'default' => 3
			),
			'mobileColumns' => array(
				'type' => 'number',
				'default' => 2
			),
			'columnGap' => array(
				'type' => 'number',
				'default' => 32
			),
			'tabletColumnGap' => array(
				'type' => 'number',
				'default' => 24
			),
			'mobileColumnGap' => array(
				'type' => 'number',
				'default' => 16
			),
			'rowGap' => array(
				'type' => 'number',
				'default' => 32
			),
			'tabletRowGap' => array(
				'type' => 'number',
				'default' => 24
			),
			'mobileRowGap' => array(
				'type' => 'number',
				'default' => 16
			),
			'productsCount' => array(
				'type' => 'number',
				'default' => 12
			)
		),
		'render_callback' => 'wprs_render_products_by_tag_block'
	));
}
add_action('init', 'wprs_register_products_by_tag_block');

function wprs_render_products_by_tag_block($attributes) {
	// 获取属性
	$taxonomy_type = $attributes['taxonomyType'] ?? 'product_tag';
	$term_id = $attributes['tagId'];
	$desktop_columns = $attributes['columns'];
	$tablet_columns = $attributes['tabletColumns'];
	$mobile_columns = $attributes['mobileColumns'];
	$desktop_column_gap = $attributes['columnGap'];
	$tablet_column_gap = $attributes['tabletColumnGap'];
	$mobile_column_gap = $attributes['mobileColumnGap'];
	$desktop_row_gap = $attributes['rowGap'];
	$tablet_row_gap = $attributes['tabletRowGap'];
	$mobile_row_gap = $attributes['mobileRowGap'];
	$products_count = $attributes['productsCount'];

	// 如果没有选择分类项，返回提示信息
	if (empty($term_id)) {
		return '<p>' . esc_html__('Please select a term in the block settings', 'wenprise-products-by-tags') . '</p>';
	}

	// 获取分类名称显示
	$taxonomy_labels = array(
		'product_tag' => esc_html__('Tag', 'wenprise-products-by-tags'),
		'product_cat' => esc_html__('Category', 'wenprise-products-by-tags'),
		'product_collection' => esc_html__('Collection', 'wenprise-products-by-tags')
	);
	$taxonomy_label = $taxonomy_labels[$taxonomy_type] ?? esc_html__('Term', 'wenprise-products-by-tags');

	// 设置查询参数
	$args = array(
		'post_type' => 'product',
		'posts_per_page' => $products_count,
		'tax_query' => array(
			array(
				'taxonomy' => $taxonomy_type,
				'field' => 'term_id',
				'terms' => $term_id
			)
		),
		'post_status' => 'publish',
	);

	// 查询产品
	$products = new WP_Query($args);

	ob_start();

	if ($products->have_posts()) {
		// 生成唯一的类名
		$unique_class = 'wprs-products-grid-' . wp_unique_id();

		// 添加响应式布局的 CSS
		$responsive_css = sprintf('
            <style>
                .%1$s.wc-block-grid.has-%2$d-columns .wc-block-grid__products {
                    grid-template-columns: repeat(%2$d, 1fr);
                    gap: %3$dpx %4$dpx;
                }
                @media (max-width: 1023px) {
                    .%1$s.wc-block-grid.has-%2$d-columns .wc-block-grid__products {
                        grid-template-columns: repeat(%5$d, 1fr);
                        gap: %6$dpx %7$dpx;
                    }
                }
                @media (max-width: 767px) {
                    .%1$s.wc-block-grid.has-%2$d-columns .wc-block-grid__products {
                        grid-template-columns: repeat(%8$d, 1fr);
                        gap: %9$dpx %10$dpx;
                    }
                }
            </style>
        ',
			esc_attr($unique_class),
			$desktop_columns,
			$desktop_row_gap,
			$desktop_column_gap,
			$tablet_columns,
			$tablet_row_gap,
			$tablet_column_gap,
			$mobile_columns,
			$mobile_row_gap,
			$mobile_column_gap
		);

		echo wp_kses($responsive_css, array(
			'style' => array()
		));

		echo '<div class="wc-block-grid has-' . esc_attr($desktop_columns) . '-columns ' . esc_attr($unique_class) . '">';
		echo '<ul class="wc-block-grid__products">';

		while ($products->have_posts()) {
			$products->the_post();
			global $product;

			// 确保 $product 是有效的 WooCommerce 产品对象
			if (!is_a($product, 'WC_Product')) {
				continue;
			}

			echo '<li class="wc-block-grid__product">';
			echo '<a href="' . esc_url(get_permalink()) . '" class="wc-block-grid__product-link">';

			// 产品图片
			if (has_post_thumbnail()) {
				echo woocommerce_get_product_thumbnail();
			} else {
				echo wc_placeholder_img();
			}

			// 产品标题
			echo '<h2 class="wc-block-grid__product-title">' . esc_html(get_the_title()) . '</h2>';
			echo '</a>';

			// 产品价格
			echo '<div class="wc-block-grid__product-price price">' . $product->get_price_html() . '</div>';

			// 添加到购物车按钮
			echo woocommerce_template_loop_add_to_cart();

			echo '</li>';
		}

		echo '</ul>';
		echo '</div>';
	} else {
		printf(
			'<p>%s</p>',
			sprintf(
			/* translators: %s: taxonomy label */
				esc_html__('No products found in this %s', 'wenprise-products-by-tags'),
				esc_html($taxonomy_label)
			)
		);
	}

	wp_reset_postdata();

	return ob_get_clean();
}

// 注册自定义分类方法
function wprs_register_product_collection_taxonomy() {
	$labels = array(
		'name'              => esc_html__('Product Collections', 'wenprise-products-by-tags'),
		'singular_name'     => esc_html__('Product Collection', 'wenprise-products-by-tags'),
		'search_items'      => esc_html__('Search Collections', 'wenprise-products-by-tags'),
		'all_items'         => esc_html__('All Collections', 'wenprise-products-by-tags'),
		'parent_item'       => esc_html__('Parent Collection', 'wenprise-products-by-tags'),
		'parent_item_colon' => esc_html__('Parent Collection:', 'wenprise-products-by-tags'),
		'edit_item'         => esc_html__('Edit Collection', 'wenprise-products-by-tags'),
		'update_item'       => esc_html__('Update Collection', 'wenprise-products-by-tags'),
		'add_new_item'      => esc_html__('Add New Collection', 'wenprise-products-by-tags'),
		'new_item_name'     => esc_html__('New Collection Name', 'wenprise-products-by-tags'),
		'menu_name'         => esc_html__('Collections', 'wenprise-products-by-tags'),
	);

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array('slug' => 'collection'),
		'show_in_rest'      => true,
	);

	register_taxonomy('product_collection', array('product'), $args);
}
add_action('init', 'wprs_register_product_collection_taxonomy', 0);