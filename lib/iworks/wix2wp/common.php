<?php

require_once dirname( dirname( __FILE__ ) ) . '/wxr.php';

abstract class iworks_wix2wp_common {

	protected $db;
	protected $wxr;
	protected $limit  = 0;
	protected $random = false;
	protected $users  = array();
	protected $config;
	protected $root;

	private $orginal_images = array();

	public function __construct() {
		global $config;
		$this->config = $config;
		$this->db     = new iworks_default();
		$this->wxr    = new iworks_wxr();
		$this->root   = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );
	}

	abstract protected function get_items();
	abstract public function generate();

	private function get_dir_files( $dir, $subdir ) {
		$result = array();
		$cdir   = scandir( $dir . DIRECTORY_SEPARATOR . $subdir );
		foreach ( $cdir as $key => $value ) {
			if ( in_array( $value, array( '.', '..' ) ) ) {
				continue;
			}
			$f = $dir . DIRECTORY_SEPARATOR . $subdir . DIRECTORY_SEPARATOR . $value;
			if ( is_file( $f ) ) {
				$result[] = $subdir . DIRECTORY_SEPARATOR . $value;
			} elseif ( is_dir( $f ) ) {
				$result = array_merge(
					$result,
					$this->get_dir_files(
						$dir,
						$subdir . DIRECTORY_SEPARATOR . $value
					)
				);
			}
		}
		return $result;
	}

	protected function dir_to_array( $dir ) {
		$result = array();
		$cdir   = scandir( $dir );
		foreach ( $cdir as $key => $value ) {
			if ( in_array( $value, array( '.', '..' ) ) ) {
				continue;
			}
			if ( is_file( $dir . DIRECTORY_SEPARATOR . $value ) ) {
				$result[] = $value;
			} elseif ( is_dir( $dir . DIRECTORY_SEPARATOR . $value ) ) {
				$result = array_merge( $result, $this->get_dir_files( $dir, $value ) );
			}
		}
		return $result;
	}

	private function remove_attributes( $element ) {
		$attributes_to_remove = array(
			'id',
			'class',
			'data-pin-url',
			'data-pin-media',
			'data-hook',
			'role',
			'tabindex',
		);
		foreach ( $attributes_to_remove as $attribute ) {
			$element->removeAttribute( $attribute );
		}
		return $element;
	}

	protected function get_inner_html( $node, $mode = 'outer' ) {
		$innerHTML = '';
		$children  = $node->childNodes;
		foreach ( $children as $child ) {

			// echo $child->tagName, PHP_EOL;
			// echo $child->hasChildNodes(), PHP_EOL;

			if ( isset( $child->tagName ) ) {
				// echo $child->tagName, PHP_EOL;
				// echo $child->hasChildNodes(), PHP_EOL;
				switch ( $child->tagName ) {
					case 'style':
					case 'svg':
						break;
					case 'div':
						$innerHTML .= $this->get_inner_html( $child, $mode );
						break;
					case 'img':
						$innerHTML .= $this->gutenberg_tag_img( $child );
						break;
					case 'p':
						$innerHTML .= $this->gutenberg_tag_p( $child );
						break;
					case 'span':
					case 'li':
					case 'table':
					case 'svg':
						$innerHTML .= $child->ownerDocument->saveXML( $this->remove_attributes( $child ) );
						break;
					default:
						if ( $child->hasChildNodes() ) {
							$innerHTML .= $this->get_inner_html( $child, $mode );
						} else {
							$innerHTML .= $child->ownerDocument->saveXML( $this->remove_attributes( $child ) );
						}
				}
			} else {
				if ( 'inner' === $mode ) {
					$innerHTML .= $child->ownerDocument->saveXML( $this->remove_attributes( $child ) );
				} elseif ( 'DOMText' === get_class( $child ) ) {
					if ( ! empty( $child->nodeValue ) ) {
						$innerHTML .= $child->nodeValue;
					}
				} else {
					$innerHTML .= $child->saveXML( $this->remove_attributes( $child ) );
				}
			}
		}

		return $innerHTML;
	}

	protected function convert_date( $date ) {
		$tr = array(
			'sty' => 'Jan',
			'lut' => 'Feb',
			'mar' => 'Mar',
			'kwi' => 'Apr',
			'maj' => 'May',
			'cze' => 'Jun',
			'lip' => 'Jul',
			'sie' => 'Aug',
			'wrz' => 'Sep',
			'paź' => 'Oct',
			'lis' => 'Nov',
			'gru' => 'Dec',
		);
		return str_replace( array_keys( $tr ), array_values( $tr ), $date );
	}

	protected function get_user_id_from_string( $username ) {
		if ( isset( $this->users[ $username ] ) ) {
			return $this->users[ $username ]['author_id'];
		}
		$id                       = 924 + count( $this->users );
		$this->users[ $username ] = array(
			'author_login'        => slugify( $username ),
			'author_email'        => slugify( $username ) . '@sedno.org',
			'author_id'           => $id,
			'author_first_name'   => '',
			'author_last_name'    => $username,
			'author_display_name' => $username,
		);
		$this->users[ $id ]       = $this->users[ $username ];
		return $id;
	}

	protected function get_first_available_posts_id() {
		global $wpdb;
		$query = "select max(ID) + 1 from {$wpdb->posts}";
		$var   = $wpdb->gett_var( $query );
	}

	protected function gutenberg_tag_img( $child ) {
		if ( 'img' !== $child->tagName ) {
			return '';
		}
		$src = $child->getAttribute( 'src' );
		if ( empty( $src ) ) {
			return '';
		}
		$src                    = preg_replace( '@/v1/fit/.*$@', '', $src );
		$this->orginal_images[] = $src;
		$src                    = preg_replace( '@https://static.wixstatic.com@', '', $src );
		$content                = '<!-- wp:image {"sizeSlug":"large"} -->';
		$content               .= PHP_EOL;
		$content               .= sprintf(
			'<figure class="wp-block-image size-large"><img src="%s" alt="%s"/></figure>',
			$src,
			$child->getAttribute( 'alt' )
		);
		$content               .= PHP_EOL;
		$content               .= '<!-- /wp:image -->';
		$content               .= PHP_EOL;
		$content               .= PHP_EOL;
		return $content;
	}

	protected function gutenberg_tag_p( $child ) {
		if ( 'p' !== $child->tagName ) {
			return '';
		}
		$paragraph = $child->ownerDocument->saveXML( $this->remove_attributes( $child ) );
		$paragraph = preg_replace( '@<span><br role="presentation"/></span>@', '', $paragraph );
		$paragraph = preg_replace( '@<span[^>]+>@', '', $paragraph );
		$paragraph = preg_replace( '@</span>@', '', $paragraph );
		$content   = '<!-- wp:paragraph -->';
		$content  .= PHP_EOL;
		$content  .= $paragraph;
		$content  .= '<!-- /wp:paragraph -->';
		$content  .= PHP_EOL;
		$content  .= PHP_EOL;
		return $content;
	}

	protected function write_orginal_images_list() {
			$file = sprintf(
				'%s/content/wget.images.list',
				$this->root,
			);
			$fw   = fopen( $file, 'w' );
		foreach ( $this->orginal_images as $content ) {
			$content .= PHP_EOL;
			fputs( $fw, $content, strlen( $content ) );
		}
			fclose( $fw );
	}
}
