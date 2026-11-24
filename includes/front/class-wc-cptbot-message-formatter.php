<?php

namespace WCAICHATBOT\Includes\Front;

defined('ABSPATH') || exit();

class Wc_Cptbot_Message_Formatter
{

	public function process_message($message)
	{
		if (empty($message)) {
			return '';
		}

		// Check for category list response
		if (strpos($message, '<!-- CATEGORY_LIST_RESPONSE -->') !== false) {
			return $this->process_category_list($message);
		}

		$clean_message = str_replace("\n", "\n", $message);
		$parts = preg_split('/═{4,}/', $clean_message);

		$final_html = '<div style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif; line-height:1.5;">';

		foreach ($parts as $part) {
			$content = trim($part);
			if (empty($content)) {
				continue;
			}

			$has_title = $this->field_has_value($content, 'TITLE');
			$has_category = $this->field_has_value($content, 'CATEGORY');
			$has_price = $this->field_has_value($content, 'PRICE');
			$has_stock = $this->field_has_value($content, 'STOCK');
			$has_link = $this->field_has_value($content, 'LINK');
			$has_type = $this->field_has_value($content, 'TYPE');
			$has_sku = $this->field_has_value($content, 'SKU');
			$has_quantity = $this->field_has_value($content, 'QUANTITY');

			$is_complete_product = $has_title && $has_category && $has_price && $has_stock && $has_link && $has_type && $has_sku && $has_quantity;

			if ($is_complete_product) {
				$final_html .= $this->format_product($content);
			} elseif ($has_title || $has_price || $has_link || $has_category || $has_stock) {
				// Incomplete product data, skipping
				continue;
			} else {
				if (preg_match('/[a-zA-Z0-9]/', $content)) {
					$final_html .= '<div style="margin-bottom:10px;color:#4a4a4a;font-size:15px;">' . nl2br($content) . '</div>';
				}
			}
		}

		$final_html .= '</div>';
		return $final_html;
	}

	private function field_has_value($content, $field)
	{
		if (preg_match('/' . $field . ':\s*([^\n\r]+)/i', $content, $match)) {
			$value = trim($match[1]);
			return strlen($value) > 0 && preg_match('/[a-zA-Z0-9]/', $value);
		}
		return false;
	}

	private function format_product($content)
	{
		preg_match('/TITLE:\s*(.+)/i', $content, $title_match);
		$title = isset($title_match[1]) ? trim($title_match[1]) : '';

		preg_match('/CATEGORY:\s*(.+)/i', $content, $category_match);
		$category = isset($category_match[1]) ? trim($category_match[1]) : '';

		preg_match('/PRICE:\s*(.+)/i', $content, $price_match);
		$price = isset($price_match[1]) ? trim($price_match[1]) : '';

		preg_match('/STOCK:\s*(.+)/i', $content, $stock_match);
		$stock = isset($stock_match[1]) ? trim($stock_match[1]) : '';

		preg_match('/QUANTITY:\s*(.+)/i', $content, $quantity_match);
		$quantity = isset($quantity_match[1]) ? trim($quantity_match[1]) : null;

		preg_match('/TYPE:\s*(.+)/i', $content, $type_match);
		$type = isset($type_match[1]) ? trim($type_match[1]) : '';

		preg_match('/SKU:\s*(.+)/i', $content, $sku_match);
		$sku = isset($sku_match[1]) ? trim($sku_match[1]) : '';

		preg_match('/LINK:\s*(.+)/i', $content, $link_match);
		$link = isset($link_match[1]) ? trim($link_match[1]) : '#';

		preg_match('/href="([^"]+)"/', $link, $href_match);
		$href = isset($href_match[1]) ? $href_match[1] : $link;

		$variants = $this->extract_variants_from_json($content);

		$html = '
			<div style="
				border:1px solid #e8e8e8;
				border-radius:10px;
				padding:12px;
				margin:10px 0;
				background:linear-gradient(135deg,#ffffff 0%,#fafafa 100%);
				box-shadow:0 2px 8px rgba(0,0,0,0.06);
				max-width:100%;
				box-sizing:border-box;
			">
				<div style="font-weight:600;font-size:14px;color:#1a1a1a;margin-bottom:6px;">
					🛍️ ' . esc_html($title) . '
				</div>';

		if ($category) {
			$html .= '<div style="
						font-size:12px;
						color:#666;
						margin-bottom:6px;
						display:inline-block;
						background:#f5f5f5;
						padding:3px 8px;
						border-radius:4px;
					">🏷️ ' . esc_html($category) . '</div>';
		}

		$html .= '<div style="font-size:15px;color:#2e7d32;font-weight:700;margin-bottom:6px;">
					💰 Price: ' . esc_html($price) . '
				</div>';

		if ($stock) {
			$stock_display = $quantity !== null ? $quantity : $stock;
			$html .= '<span style="
				display:block;
				background:#f4f4f4;
				color:#2e7d32;
				font-size:12px;
				padding:4px 8px;
				border-radius:6px;
				margin-bottom:6px;
			">🛒 Stock: ' . esc_html($stock_display) . '</span>';
		}

		if ($type) {
			$html .= '<div style="
						font-size:12px;
						color:#1565c0;
						margin-bottom:6px;
						display:inline-block;
						background:#e3f2fd;
						padding:3px 8px;
						border-radius:4px;
						margin-left:6px;
					">📦 Type: ' . esc_html($type) . '</div>';
		}

		if ($sku) {
			$html .= '<div style="font-size:12px;color:#555;margin-top:8px;">
						<span style="font-weight:600;">🔹 SKU:</span>
						<span style="margin-left:4px;">' . esc_html($sku) . '</span>
					</div>';
		}

		if (strtolower($type) === 'variable' && ! empty($variants)) {
			$html .= '<div style="margin-top:10px;padding:8px;background:#f9f9f9;border-radius:6px;">';
			foreach ($variants as $index => $variant) {
				$emoji = $this->get_variant_emoji($variant['name']);
				$values = implode(', ', $variant['values']);
				$margin_bottom = ($index < count($variants) - 1) ? '6px' : '0';

				$html .= '<div style="margin-bottom:' . $margin_bottom . ';font-size:13px;color:#333;">
							<span style="font-weight:600;color:#1565c0;">' . $emoji . ' ' . esc_html($variant['name']) . '</span>
							<span style="color:#555;"> : ' . esc_html($values) . '</span>
						</div>';
			}
			$html .= '</div>';
		}

		$html .= '<div style="margin-top:10px;">
					<a href="' . esc_url($href) . '" target="_blank" rel="noopener noreferrer" style="
						display:inline-block;
						text-decoration:none;
						background:linear-gradient(135deg,#0066ff 0%,#0052cc 100%);
						color:#ffffff;
						padding:8px 16px;
						border-radius:6px;
						font-size:13px;
						font-weight:500;
						text-align:center;
						box-shadow:0 2px 6px rgba(0,102,255,0.25);
						transition:all .2s ease;
					">View Product →</a>
				</div>
			</div>';

		return $html;
	}

	private function extract_variants_from_json($content)
	{
		$variants = array();
		if (preg_match('/VARIANTS:\s*(\[[\s\S]*?\])\s*(?=\n[A-Z]+:|LINK:|$)/i', $content, $match)) {
			$json = $match[1];
			$variants_array = json_decode($json, true);
			if (is_array($variants_array)) {
				foreach ($variants_array as $variant_group) {
					if (isset($variant_group['name']) && isset($variant_group['values']) && is_array($variant_group['values'])) {
						$variants[] = array(
							'name' => $variant_group['name'],
							'values' => $variant_group['values'],
						);
					}
				}
			}
		}
		return $variants;
	}

	private function get_variant_emoji($variant_name)
	{
		$map = array(
			'COLOR' => '🎨',
			'COLOUR' => '🎨',
			'SIZE' => '📏',
			'DIAL SIZE' => '⌚',
			'STRAP COLOR' => '🎀',
			'LOGO' => '✨',
			'EDITION' => '📋',
			'FORMAT' => '💿',
			'STYLE' => '👔',
			'MATERIAL' => '🧵',
			'VERSION' => '🔢',
		);
		$upper = strtoupper($variant_name);
		return isset($map[$upper]) ? $map[$upper] : '🔹';
	}

	private function process_category_list($message)
	{
		$cleaned_message = str_replace('<!-- CATEGORY_LIST_RESPONSE -->', '', $message);
		$cleaned_message = trim($cleaned_message);

		$categories = array();
		if (preg_match_all('/•\s*<a href="([^"]+)">([^<]+)<\/a>\s*\((\d+)\s+(product|products)\)/', $cleaned_message, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$categories[] = array(
					'link' => $match[1],
					'name' => $match[2],
					'count' => $match[3],
					'label' => $match[4],
				);
			}
		}

		if (empty($categories)) {
			return $cleaned_message;
		}

		$total_categories = count($categories);
		$unique_id = 'cat-list-' . time();

		$html = '
            <div id="' . $unique_id . '" style="
                font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
                background:#f8f9fa;
                padding:16px;
                border-radius:12px;
                border:1px solid #e2dedeff;
            ">
                <div style="margin-bottom:16px;color:#1a1a1a;font-size:15px;font-weight:600;">
                    📂 Available Product Categories (' . $total_categories . ' total)
                </div>
                <div id="' . $unique_id . '-grid" style="
                    display:grid;
                    grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
                    gap:12px;
                    margin:12px 0;
                ">';

		// Render ALL categories (no pagination for now as it requires JS state)
		foreach ($categories as $cat) {
			$html .= '
                <a href="' . esc_url($cat['link']) . '" target="_blank" rel="noopener noreferrer" style="
                    display:block;
                    text-decoration:none;
                    border:1px solid #e2dedeff;
                    border-radius:8px;
                    padding:12px;
                    background:linear-gradient(135deg,#ffffff 0%,#fafafa 100%);
                    transition:all 0.2s ease;
                    box-shadow:0 2px 4px rgba(0,0,0,0.05);
                " onmouseover="this.style.boxShadow=\'0 4px 12px rgba(0,102,255,0.15)\';this.style.borderColor=\'#0066ff\';" onmouseout="this.style.boxShadow=\'0 2px 4px rgba(0,0,0,0.05)\';this.style.borderColor=\'#e0e0e0\';">
                    <div style="font-weight:600;color:#1a1a1a;font-size:14px;margin-bottom:6px;">
                        ' . esc_html($cat['name']) . '
                    </div>
                    <div style="font-size:12px;color:#666;">
                        ' . esc_html($cat['count']) . ' ' . esc_html($cat['label']) . '
                    </div>
                </a>';
		}

		$html .= '</div></div>';
		return $html;
	}
}
