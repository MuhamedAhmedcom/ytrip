<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class YTrip_Schema_SEO {

    private $options;

    public function __construct() {
        $this->options = get_option('ytrip_settings');
        add_filter( 'document_title_parts', array( $this, 'filter_front_page_title' ), 10, 1 );
        add_action( 'wp_head', array( $this, 'output_meta_description' ), 5 );
        add_action( 'wp_head', array( $this, 'output_schema' ), 99 );
    }

    /**
     * Ensure front page has a descriptive document title for Lighthouse SEO.
     *
     * @param array $title Parts (title, page, tagline).
     * @return array
     */
    public function filter_front_page_title( $title ) {
        if ( ! is_front_page() || $this->has_seo_plugin() ) {
            return $title;
        }
        $name = get_bloginfo( 'name' );
        $desc = get_bloginfo( 'description' );
        if ( ! empty( $desc ) ) {
            $title['tagline'] = wp_strip_all_tags( $desc );
        } else {
            $title['tagline'] = __( 'Tours & Travel', 'ytrip' );
        }
        return $title;
    }

    /**
     * Output meta description on front page when no SEO plugin is active (helps Lighthouse SEO).
     *
     * @return void
     */
    public function output_meta_description() {
        if ( ! is_front_page() || $this->has_seo_plugin() ) {
            return;
        }
        $desc = get_bloginfo( 'description' );
        if ( ! empty( $desc ) ) {
            echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $desc ) ) . '">' . "\n";
            return;
        }
        $name = get_bloginfo( 'name' );
        $fallback = sprintf(
            /* translators: %s: site name */
            __( 'Discover tours and travel experiences with %s.', 'ytrip' ),
            $name
        );
        echo '<meta name="description" content="' . esc_attr( $fallback ) . '">' . "\n";
    }

    /**
     * Check for SEO Plugins
     */
    private function has_seo_plugin() {
        return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION');
    }

    /**
     * Should output specific schema type?
     */
    private function should_output( $type ) {
        $enable_global = isset($this->options['schema_enable']) ? $this->options['schema_enable'] : true;
        
        if ( ! $enable_global ) return false;

        $smart_conflict = isset($this->options['schema_conflict_mode']) ? $this->options['schema_conflict_mode'] : true;
        
        // Always return true for 'product', 'faq', 'video' as these are specific to our CPT
        if ( in_array( $type, array( 'product', 'faq', 'video' ) ) ) return true;

        // For generic schemas, check conflict mode
        if ( $smart_conflict && $this->has_seo_plugin() ) {
            return false; // Disable generic schema if SEO plugin exists
        }

        // Check individual settings
        if ( $type === 'organization' ) {
            return isset($this->options['schema_organization']) ? $this->options['schema_organization'] : true;
        }
        if ( $type === 'website' ) {
            return isset($this->options['schema_website']) ? $this->options['schema_website'] : true;
        }
        if ( $type === 'breadcrumb' ) {
             // Usually handled by SEO plugins, so subject to conflict check
             return true; 
        }

        return true;
    }

    /**
     * Output JSON-LD Schema
     */
    public function output_schema() {
        $schemas = array();

        // 1. Organization (Sitewide)
        if ( $this->should_output('organization') ) {
            $org = $this->get_organization_schema();
            if ($org) $schemas[] = $org;
        }

        // 2. WebSite (Home only)
        if ( is_front_page() && $this->should_output('website') ) {
            $web = $this->get_website_schema();
            if ($web) $schemas[] = $web;
        }

        // 3. Single Tour Logic
        if ( is_singular( 'ytrip_tour' ) ) {
            
            // Product
            if ( $this->should_output('product') ) {
                $product = $this->get_product_schema();
                if ($product) $schemas[] = $product;
            }

            // FAQ
            if ( $this->should_output('faq') ) {
                $faq = $this->get_faq_schema();
                if ($faq) $schemas[] = $faq;
            }

            // Breadcrumb
            if ( $this->should_output('breadcrumb') ) {
                $crumb = $this->get_breadcrumb_schema();
                if ($crumb) $schemas[] = $crumb;
            }
        }

        if ( ! empty( $schemas ) ) {
            echo "\n<!-- YTrip JSON-LD Schema -->\n";
            echo '<script type="application/ld+json">' . "\n";
            if ( count( $schemas ) > 1 ) {
                echo json_encode( array( '@context' => 'https://schema.org', '@graph' => $schemas ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
            } else {
                 $schemas[0]['@context'] = 'https://schema.org';
                 echo json_encode( $schemas[0], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
            }
            echo "\n</script>\n";
            echo "<!-- /YTrip JSON-LD Schema -->\n";
        }
    }

    private function get_organization_schema() {
        return array(
            '@type' => 'TravelAgency',
            '@id'   => home_url( '/#organization' ),
            'name'  => get_bloginfo( 'name' ),
            'url'   => home_url(),
            'logo'  => get_site_icon_url( 512 ),
            'image' => get_site_icon_url( 512 ),
        );
    }

    private function get_website_schema() {
        return array(
            '@type' => 'WebSite',
            '@id'   => home_url( '/#website' ),
            'url'   => home_url(),
            'name'  => get_bloginfo( 'name' ),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => home_url( '/?s={search_term_string}' ),
                'query-input' => 'required name=search_term_string'
            )
        );
    }

    private function get_product_schema() {
        global $post;
        $tour_id = $post->ID;
        $meta = get_post_meta( $tour_id, 'ytrip_tour_details', true );
        
        // Video Object Check
        $video_schema = null;
        if ( ! empty( $meta['tour_video'] ) ) {
            $video_schema = array(
                '@type' => 'VideoObject',
                'name'  => get_the_title(),
                'description' => wp_strip_all_tags( get_the_excerpt() ),
                'thumbnailUrl' => get_the_post_thumbnail_url( $tour_id, 'full' ) ?: '',
                'uploadDate' => get_the_date('c'),
                'contentUrl' => $meta['tour_video'],
            );
        }

        // Basic Product Data
        $schema = array(
            '@type'       => 'Product',
            '@id'         => get_permalink( $tour_id ) . '#product',
            'name'        => get_the_title(),
            'description' => wp_strip_all_tags( get_the_excerpt() ? get_the_excerpt() : get_the_content() ),
            'url'         => get_permalink( $tour_id ),
            'sku'         => (string) $tour_id,
        );

        // Link Organization as Brand
        $schema['brand'] = array( '@id' => home_url( '/#organization' ) );
        $schema['offeredBy'] = array( '@id' => home_url( '/#organization' ) );

        // Images
        if ( has_post_thumbnail( $tour_id ) ) {
            $schema['image'] = array( get_the_post_thumbnail_url( $tour_id, 'full' ) );
        }
        
        // Add Gallery Images
        if ( ! empty( $meta['tour_gallery'] ) ) {
            $gallery_ids = explode( ',', $meta['tour_gallery'] );
            if ( ! isset( $schema['image'] ) ) {
                $schema['image'] = array();
            }
            
            foreach ( $gallery_ids as $img_id ) {
                $img_url = wp_get_attachment_url( $img_id );
                if ( $img_url ) {
                    $schema['image'][] = $img_url;
                }
            }
        }

        // Attach Video if exists
        if ( $video_schema ) {
            $schema['subjectOf'] = $video_schema;
        }

        // Offers (Price) - Integration with WooCommerce
        $product_id = get_post_meta( $tour_id, '_ytrip_wc_product_id', true );
        
        if ( $product_id && function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $product_id );
            
            if ( $product ) {
                $schema['offers'] = array(
                    '@type'         => 'Offer',
                    'price'         => $product->get_price(),
                    'priceCurrency' => get_woocommerce_currency(),
                    'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'url'           => get_permalink( $tour_id ),
                    'validFrom'     => get_the_date('c'),
                );
                
                // Aggregate Rating
                if ( $product->get_review_count() > 0 ) {
                    $schema['aggregateRating'] = array(
                        '@type'       => 'AggregateRating',
                        'ratingValue' => $product->get_average_rating(),
                        'reviewCount' => $product->get_review_count(),
                    );
                    
                    // Reviews
                     $args = array(
                        'post_id' => $product_id,
                        'status'  => 'approve',
                        'number'  => 5
                    );
                    $comments = get_comments( $args );

                    if ( $comments ) {
                        $schema['review'] = array();
                        foreach ( $comments as $comment ) {
                            $rating = get_comment_meta( $comment->comment_ID, 'rating', true );
                            $schema['review'][] = array(
                                '@type'         => 'Review',
                                'reviewRating'  => array(
                                    '@type'       => 'Rating',
                                    'ratingValue' => $rating ? $rating : 5,
                                    'bestRating'  => '5',
                                ),
                                'author'        => array(
                                    '@type' => 'Person',
                                    'name'  => $comment->comment_author,
                                ),
                                'datePublished' => get_comment_date( 'c', $comment->comment_ID ),
                                'reviewBody'    => $comment->comment_content,
                            );
                        }
                    }
                }
            }
        }

        return $schema;
    }

    /**
     * Get FAQ Schema
     */
    private function get_faq_schema() {
        global $post;
        $meta = get_post_meta( $post->ID, 'ytrip_tour_details', true );
        
        if ( empty( $meta['faq'] ) || ! is_array( $meta['faq'] ) ) {
            return null;
        }
        
        $questions = array();
        
        foreach ( $meta['faq'] as $faq_item ) {
            if ( ! empty( $faq_item['question'] ) && ! empty( $faq_item['answer'] ) ) {
                $questions[] = array(
                    '@type'          => 'Question',
                    'name'           => $faq_item['question'],
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text'  => $faq_item['answer'],
                    ),
                );
            }
        }
        
        if ( empty( $questions ) ) {
            return null;
        }

        return array(
            '@type'      => 'FAQPage',
            'mainEntity' => $questions,
        );
    }
    
    /**
     * Get Breadcrumb Schema
     */
    private function get_breadcrumb_schema() {
        global $post;
        
        $crumbs = array();
        $position = 1;
        
        // Home
        $crumbs[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => 'Home',
            'item'     => home_url(),
        );
        
        // Destinations Taxonomy
        $terms = get_the_terms( $post->ID, 'ytrip_destination' );
        if ( $terms && ! is_wp_error( $terms ) ) {
             // Just take the first one for simplicity in breadcrumb
             $term = current( $terms );
             $crumbs[] = array(
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $term->name,
                'item'     => get_term_link( $term ),
             );
        }
        
        // Current Page
        $crumbs[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => get_the_title(),
            // 'item' is optional for the last item, but good practice
             'item'     => get_permalink(),
        );
        
        return array(
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $crumbs,
        );
    }
}

new YTrip_Schema_SEO();
