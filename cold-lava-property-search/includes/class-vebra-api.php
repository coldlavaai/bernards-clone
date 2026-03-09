<?php
/**
 * Vebra Alto API Client
 *
 * Handles authentication, token management, fetching branches/properties,
 * parsing XML responses, and caching via WordPress transients.
 *
 * @package ColdLavaPropertySearch
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CLPS_Vebra_API {

    /**
     * Vebra API base URL.
     *
     * @var string
     */
    private $base_url;

    /**
     * Vebra username.
     *
     * @var string
     */
    private $username;

    /**
     * Vebra password.
     *
     * @var string
     */
    private $password;

    /**
     * Whether the API is configured.
     *
     * @var bool
     */
    private $configured = false;

    /**
     * Cache duration in seconds (15 minutes).
     *
     * @var int
     */
    const CACHE_DURATION = 900;

    /**
     * Token cache duration in seconds (55 minutes).
     *
     * @var int
     */
    const TOKEN_DURATION = 3300;

    /**
     * Constructor.
     */
    public function __construct() {
        $feed_id  = get_option( 'clps_vebra_feed_id', '' );
        $this->username = get_option( 'clps_vebra_username', '' );
        $this->password = get_option( 'clps_vebra_password', '' );

        if ( ! empty( $feed_id ) && ! empty( $this->username ) && ! empty( $this->password ) ) {
            $this->base_url   = 'https://webservices.vebra.com/export/' . sanitize_text_field( $feed_id ) . '/v1';
            $this->configured = true;
        }
    }

    /**
     * Check if the API is configured.
     *
     * @return bool
     */
    public function is_configured() {
        return $this->configured;
    }

    /**
     * Test the API connection.
     *
     * @return array Result with 'success' and 'message' keys.
     */
    public function test_connection() {
        if ( ! $this->configured ) {
            return array(
                'success' => false,
                'message' => 'API credentials not configured. Please fill in all fields.',
            );
        }

        $token = $this->get_token( true );
        if ( is_wp_error( $token ) ) {
            return array(
                'success' => false,
                'message' => 'Authentication failed: ' . $token->get_error_message(),
            );
        }

        // Try fetching branches
        $response = $this->api_request( '/branch', $token );
        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'message' => 'Connection succeeded but failed to fetch branches: ' . $response->get_error_message(),
            );
        }

        $branches = $this->parse_branches( $response );
        $count    = count( $branches );

        return array(
            'success' => true,
            'message' => sprintf( 'Connection successful! Found %d branch%s.', $count, $count !== 1 ? 'es' : '' ),
        );
    }

    /**
     * Get all properties (cached).
     *
     * @param bool $force_refresh Force cache refresh.
     * @return array Array of property objects.
     */
    public function get_properties( $force_refresh = false ) {
        if ( ! $this->configured ) {
            return array();
        }

        // Check cache
        if ( ! $force_refresh ) {
            $cached = get_transient( 'clps_vebra_properties' );
            if ( false !== $cached ) {
                return $cached;
            }
        }

        $token = $this->get_token();
        if ( is_wp_error( $token ) ) {
            return array();
        }

        // Fetch all branches
        $branches_xml = $this->api_request( '/branch', $token );
        if ( is_wp_error( $branches_xml ) ) {
            return array();
        }

        $branches = $this->parse_branches( $branches_xml );
        $all_properties = array();

        foreach ( $branches as $branch ) {
            // Fetch property list for this branch
            $props_xml = $this->api_request( '/branch/' . $branch['id'] . '/property', $token );
            if ( is_wp_error( $props_xml ) ) {
                continue;
            }

            $prop_list = $this->parse_property_list( $props_xml );

            // Fetch full details for each property
            foreach ( $prop_list as $prop_ref ) {
                $detail_url = ! empty( $prop_ref['url'] ) ? $prop_ref['url'] : '/branch/' . $branch['id'] . '/property/' . $prop_ref['id'];

                // If the URL is absolute, make it relative
                if ( strpos( $detail_url, 'http' ) === 0 ) {
                    $detail_url = wp_parse_url( $detail_url, PHP_URL_PATH );
                    // Extract the path after /export/{feedId}/v1
                    if ( preg_match( '#/export/[^/]+/v1(.+)#', $detail_url, $m ) ) {
                        $detail_url = $m[1];
                    }
                }

                $detail_xml = $this->api_request( $detail_url, $token );
                if ( is_wp_error( $detail_xml ) ) {
                    continue;
                }

                $property = $this->parse_property_detail( $detail_xml, $branch );
                if ( $property ) {
                    $all_properties[] = $property;
                }
            }
        }

        // Cache the results
        if ( ! empty( $all_properties ) ) {
            set_transient( 'clps_vebra_properties', $all_properties, self::CACHE_DURATION );
        }

        return $all_properties;
    }

    /**
     * Get a single property by ID.
     *
     * @param int|string $property_id Property ID.
     * @param string     $branch_id   Branch ID (optional, searches all if empty).
     * @return array|null Property data or null.
     */
    public function get_property( $property_id, $branch_id = '' ) {
        $properties = $this->get_properties();

        foreach ( $properties as $prop ) {
            if ( (string) $prop['id'] === (string) $property_id ) {
                if ( empty( $branch_id ) || (string) $prop['_branchId'] === (string) $branch_id ) {
                    return $prop;
                }
            }
        }

        return null;
    }

    /**
     * Get authentication token (cached in transient).
     *
     * @param bool $force_refresh Force new token.
     * @return string|WP_Error Token string or error.
     */
    private function get_token( $force_refresh = false ) {
        if ( ! $force_refresh ) {
            $cached_token = get_transient( 'clps_vebra_token' );
            if ( false !== $cached_token ) {
                return $cached_token;
            }
        }

        $auth_string = base64_encode( $this->username . ':' . $this->password );

        $response = wp_remote_get(
            $this->base_url . '/token',
            array(
                'headers' => array(
                    'Authorization' => 'Basic ' . $auth_string,
                    'Accept'        => 'application/xml',
                ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( 200 !== $code ) {
            return new WP_Error(
                'vebra_auth_failed',
                sprintf( 'Authentication failed (HTTP %d): %s', $code, wp_strip_all_tags( $body ) )
            );
        }

        // Extract token from XML
        if ( preg_match( '/<token[^>]*>([^<]+)<\/token>/i', $body, $matches ) ) {
            $token = trim( $matches[1] );
            set_transient( 'clps_vebra_token', $token, self::TOKEN_DURATION );
            return $token;
        }

        return new WP_Error( 'vebra_token_parse', 'Could not parse token from Vebra response.' );
    }

    /**
     * Make an authenticated API request.
     *
     * @param string $path  API path (relative to base URL).
     * @param string $token Auth token.
     * @return string|WP_Error Response body or error.
     */
    private function api_request( $path, $token ) {
        $url = $this->base_url . $path;

        $response = wp_remote_get(
            $url,
            array(
                'headers' => array(
                    'Authorization' => 'Basic ' . $token,
                    'Accept'        => 'application/xml',
                ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( 401 === $code ) {
            // Token expired, clear cache and retry once
            delete_transient( 'clps_vebra_token' );
            $new_token = $this->get_token( true );
            if ( is_wp_error( $new_token ) ) {
                return $new_token;
            }

            $response = wp_remote_get(
                $url,
                array(
                    'headers' => array(
                        'Authorization' => 'Basic ' . $new_token,
                        'Accept'        => 'application/xml',
                    ),
                    'timeout' => 30,
                )
            );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $code = wp_remote_retrieve_response_code( $response );
        }

        if ( 200 !== $code ) {
            return new WP_Error(
                'vebra_api_error',
                sprintf( 'Vebra API returned HTTP %d', $code )
            );
        }

        return wp_remote_retrieve_body( $response );
    }

    // ────────────────────────────────────────────
    // XML Parsing Methods
    // ────────────────────────────────────────────

    /**
     * Extract a single XML tag value.
     *
     * @param string $xml XML string.
     * @param string $tag Tag name.
     * @return string Tag content or empty string.
     */
    private function extract_tag( $xml, $tag ) {
        $pattern = '/<' . preg_quote( $tag, '/' ) . '[^>]*>([\s\S]*?)<\/' . preg_quote( $tag, '/' ) . '>/i';
        if ( preg_match( $pattern, $xml, $m ) ) {
            return trim( $m[1] );
        }
        return '';
    }

    /**
     * Extract all instances of a tag.
     *
     * @param string $xml XML string.
     * @param string $tag Tag name.
     * @return array Array of full tag matches.
     */
    private function extract_all_tags( $xml, $tag ) {
        $pattern = '/<' . preg_quote( $tag, '/' ) . '[^>]*>[\s\S]*?<\/' . preg_quote( $tag, '/' ) . '>/gi';
        // Use non-greedy match
        $pattern = '/<' . preg_quote( $tag, '/' ) . '[^>]*>[\s\S]*?<\/' . preg_quote( $tag, '/' ) . '>/i';
        if ( preg_match_all( $pattern, $xml, $matches ) ) {
            return $matches[0];
        }
        return array();
    }

    /**
     * Extract an XML attribute value.
     *
     * @param string $xml  XML string.
     * @param string $attr Attribute name.
     * @return string Attribute value or empty string.
     */
    private function extract_attr( $xml, $attr ) {
        if ( preg_match( '/' . preg_quote( $attr, '/' ) . '="([^"]*)"/i', $xml, $m ) ) {
            return $m[1];
        }
        return '';
    }

    /**
     * Parse branches list XML.
     *
     * @param string $xml XML response.
     * @return array Array of branch data.
     */
    private function parse_branches( $xml ) {
        $branches = $this->extract_all_tags( $xml, 'branch' );
        $result   = array();

        foreach ( $branches as $b ) {
            $result[] = array(
                'id'   => $this->extract_tag( $b, 'branchid' ) ?: $this->extract_attr( $b, 'id' ),
                'name' => $this->extract_tag( $b, 'name' ),
                'url'  => $this->extract_tag( $b, 'url' ) ?: $this->extract_attr( $b, 'url' ),
            );
        }

        return $result;
    }

    /**
     * Parse property list XML (lightweight, just IDs and URLs).
     *
     * @param string $xml XML response.
     * @return array Array of property references.
     */
    private function parse_property_list( $xml ) {
        $props  = $this->extract_all_tags( $xml, 'property' );
        $result = array();

        foreach ( $props as $p ) {
            $result[] = array(
                'id'  => $this->extract_tag( $p, 'prop_id' ) ?: $this->extract_attr( $p, 'id' ),
                'url' => $this->extract_tag( $p, 'url' ) ?: $this->extract_attr( $p, 'url' ),
            );
        }

        return $result;
    }

    /**
     * Parse a full property detail XML into normalised format.
     *
     * Maps Vebra XML schema to the same structure the frontend expects.
     *
     * @param string $xml    XML response.
     * @param array  $branch Branch data.
     * @return array|null Property data or null on failure.
     */
    private function parse_property_detail( $xml, $branch ) {
        if ( empty( $xml ) ) {
            return null;
        }

        $prop_id = $this->extract_tag( $xml, 'prop_id' ) ?: $this->extract_tag( $xml, 'propertyid' );
        if ( empty( $prop_id ) ) {
            return null;
        }

        // Address
        $street   = $this->extract_tag( $xml, 'street' ) ?: $this->extract_tag( $xml, 'display_address' );
        $town     = $this->extract_tag( $xml, 'town' );
        $county   = $this->extract_tag( $xml, 'county' );
        $postcode = $this->extract_tag( $xml, 'postcode' );
        $address  = implode( ', ', array_filter( array( $street, $town, $county, $postcode ) ) );

        // Price
        $price_raw       = $this->extract_tag( $xml, 'price' );
        $price           = absint( preg_replace( '/[^0-9]/', '', $price_raw ) );
        $price_qualifier = 'Guide Price';
        if ( preg_match( '/<price[^>]*>/', $xml, $price_tag ) ) {
            $pq = $this->extract_attr( $price_tag[0], 'qualifier' );
            if ( ! empty( $pq ) ) {
                $price_qualifier = $pq;
            }
        }

        // Beds, baths, receptions
        $beds      = absint( $this->extract_tag( $xml, 'bedrooms' ) );
        $baths     = absint( $this->extract_tag( $xml, 'bathrooms' ) );
        $reception = absint( $this->extract_tag( $xml, 'receptions' ) ?: $this->extract_tag( $xml, 'reception_rooms' ) );

        // Type and style
        $prop_type  = $this->extract_tag( $xml, 'type' );
        $prop_style = $this->extract_tag( $xml, 'style' );
        $subtype    = $prop_style ?: $prop_type ?: 'Property';

        // Map type to categories
        $type       = 'house';
        $type_lower = strtolower( $prop_type . ' ' . $prop_style );
        if ( preg_match( '/flat|apartment|penthouse|maisonette/', $type_lower ) ) {
            $type = 'flat';
        } elseif ( strpos( $type_lower, 'bungalow' ) !== false ) {
            $type = 'bungalow';
        }

        // Area / sqft
        $area_min     = $this->extract_tag( $xml, 'min' ) ?: $this->extract_tag( $xml, 'area' );
        $area_measure = $this->extract_tag( $xml, 'measure' ) ?: 'sqft';
        $sqft         = absint( $area_min );
        if ( preg_match( '/sqm|metre/i', $area_measure ) ) {
            $sqft = (int) round( $sqft * 10.764 );
        }

        // Description
        $main_desc  = $this->extract_tag( $xml, 'description' );
        $paragraphs = array();
        $para_tags  = $this->extract_all_tags( $xml, 'paragraph' );
        foreach ( $para_tags as $p ) {
            $text = $this->extract_tag( $p, 'paragraph' ) ?: preg_replace( '/<\/?paragraph>/i', '', $p );
            $text = trim( $text );
            if ( ! empty( $text ) ) {
                $paragraphs[] = $text;
            }
        }
        $description = $main_desc ?: ( ! empty( $paragraphs ) ? $paragraphs[0] : '' );

        // Key features / bullets
        $bullets    = array();
        $bullet_tags = $this->extract_all_tags( $xml, 'bullet' );
        foreach ( $bullet_tags as $b ) {
            $text = preg_replace( '/<\/?bullet>/i', '', $b );
            $text = trim( $text );
            if ( ! empty( $text ) ) {
                $bullets[] = $text;
            }
        }

        // Images
        $files            = $this->extract_all_tags( $xml, 'file' );
        $images           = array();
        $floorplan_images = array();

        foreach ( $files as $f ) {
            $file_url  = $this->extract_tag( $f, 'url' );
            $file_type = strtolower( $this->extract_tag( $f, 'type' ) ?: $this->extract_tag( $f, 'name' ) );

            if ( empty( $file_url ) ) {
                continue;
            }

            if ( strpos( $file_type, 'floorplan' ) !== false || strpos( $file_type, 'floor_plan' ) !== false ) {
                $floorplan_images[] = $file_url;
            } else {
                $images[] = $file_url;
            }
        }

        // Fallback image
        if ( empty( $images ) ) {
            $images[] = 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=800&h=500&fit=crop';
        }

        // EPC
        $epc_rating = strtoupper( $this->extract_tag( $xml, 'current_energy_rating' ) ?: $this->extract_tag( $xml, 'epc_rating' ) );

        // Location
        $lat = $this->extract_tag( $xml, 'latitude' );
        $lng = $this->extract_tag( $xml, 'longitude' );

        // Dates
        $date_added   = $this->extract_tag( $xml, 'date_added' ) ?: $this->extract_tag( $xml, 'available_date' );
        $date_updated = $this->extract_tag( $xml, 'date_updated' );

        // Format date for display
        $display_date = $date_added;
        if ( ! empty( $date_added ) && strpos( $date_added, '-' ) !== false ) {
            $timestamp = strtotime( $date_added );
            if ( $timestamp ) {
                $display_date = gmdate( 'd/m/Y', $timestamp );
            }
        }

        // Other fields
        $tenure      = $this->extract_tag( $xml, 'tenure' );
        $council_tax = $this->extract_tag( $xml, 'council_tax_band' ) ?: $this->extract_tag( $xml, 'council_tax' );
        $parking     = $this->extract_tag( $xml, 'parking' );
        $garden      = $this->extract_tag( $xml, 'garden' );
        $status_raw  = $this->extract_tag( $xml, 'status' );
        $status      = ( strpos( strtolower( $status_raw ), 'rent' ) !== false ) ? 'rent' : 'sale';

        // Build short description
        $short_desc = $description;
        if ( strlen( $short_desc ) > 300 ) {
            $short_desc = substr( $short_desc, 0, 300 ) . '...';
        }

        return array(
            'id'               => $prop_id,
            'title'            => sprintf( '%d bedroom %s for %s', $beds, strtolower( $subtype ), $status ),
            'address'          => $address,
            'area'             => $town ?: $county,
            'price'            => $price,
            'priceDisplay'     => '£' . number_format( $price ),
            'priceLabel'       => $price_qualifier,
            'status'           => $status,
            'type'             => $type,
            'subtype'          => $subtype,
            'beds'             => $beds,
            'baths'            => $baths,
            'reception'        => $reception,
            'sqft'             => $sqft,
            'featured'         => false,
            'badge'            => '',
            'dateAdded'        => $display_date,
            'dateUpdated'      => $date_updated,
            'tenure'           => $tenure,
            'councilTax'       => $council_tax,
            'parking'          => $parking ? 'Yes' : 'Ask agent',
            'garden'           => $garden ? 'Yes' : 'Ask agent',
            'accessibility'    => 'Ask agent',
            'epcRating'        => $epc_rating,
            'distance'         => '',
            'description'      => $short_desc,
            'fullDescription'  => ! empty( $paragraphs ) ? $paragraphs : array( $description ),
            'keyFeatures'      => $bullets,
            'images'           => $images,
            'floorplanImages'  => $floorplan_images,
            'lat'              => $lat ? (float) $lat : null,
            'lng'              => $lng ? (float) $lng : null,
            '_vebraRef'        => $this->extract_tag( $xml, 'ref' ) ?: $prop_id,
            '_branchId'        => isset( $branch['id'] ) ? $branch['id'] : '',
        );
    }
}
