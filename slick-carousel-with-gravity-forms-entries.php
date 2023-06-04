<?php 
/**
* Plugin Name: Slick Reviews Carousel displaying Gravity Forms Entry data
* Plugin URI: https://www.geekontheroad.com
* Description: Adds a shortcode that will output a slick carousel using entry data from gravity forms
* Version: 1.0
* Author: Johan d'Hollander
* Author URI: https://www.geekontheroad.com
**/

//define plugin constants
define( 'GOTR_SLICK_GRAVITY_CAROUSEL_VERSION', '1.0.2' );
define( 'GOTR_SLICK_GRAVITY_CAROUSEL_FILE_URL', plugin_dir_url(__FILE__) );

//load plugin
add_action( 'plugins_loaded', array( 'Gotr_Slick_Gravity_Reviews_Carousel_Bootstrap', 'load' ), 5 );

//main class
class Gotr_Slick_Gravity_Reviews_Carousel_Bootstrap {

    public static function load() {  
        self::init_hooks();
        self::init_shortcodes();
    } 

    //start hooks
    public static function init_hooks() {
        $self = new self();
        
        add_action('wp_enqueue_scripts', array($self, 'register_scripts_and_styles') );        
    }

    //start shortcode
    public static function init_shortcodes() {
        $self = new self();
        add_shortcode("gf_rating_carousel", array($self, "output_carousel_shortcode"));       
    }


    /**
     * Only register the scripts and styles here so we can load them later when the shortcode renders
     * 
     * Info: Slick is being loaded from cloudflare CDN
     */
    public static function register_scripts_and_styles() {
        $dir = plugin_dir_url(__FILE__);

        // Register Slick Carousel JS from a CDN
        wp_register_script( 'slick-carousel', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.5.8/slick.min.js', array( 'jquery' ), '1.5.8', true );
        
        //Register our local JS to init Slick
        wp_register_script( 'gotr-slick-gravity-scripts', GOTR_SLICK_GRAVITY_CAROUSEL_FILE_URL . 'assets/js/slick-carousel-with-gravity-entries.js', array( 'jquery' ), GOTR_SLICK_GRAVITY_CAROUSEL_VERSION, true );

        // Enqueue Slick Carousel CSS from CDN        
        wp_register_style( 'slick-carousel-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.5.8/slick.min.css' , array(), '1.5.8', 'all' );
        wp_register_style( 'slick-carousel-theme-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.5.8/slick-theme.min.css', array(), '1.5.8', 'all' );

        //register our local style
        wp_register_style( 'gotr-slick-gravity-styles', GOTR_SLICK_GRAVITY_CAROUSEL_FILE_URL . 'assets/css/slick-carousel-with-gravity-entries.css', array(), GOTR_SLICK_GRAVITY_CAROUSEL_VERSION, 'all' );
    }


    /**
     * Method to generate the shortcode
     */
    public static function output_carousel_shortcode($atts) {
        $self = new self();

        if(!class_exists("GFAPI")) {
            return __("Gravity Forms in required, please install and activate it.","gravityforms");
        }
    
        // normalize attribute keys, lowercase
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
    
        // override default attributes with user attributes
        $args = shortcode_atts(
            array(
                'form_id' => false,
                'name'    => false,
                'stars'   => false,
                'text'    => false,
                'company' => false,
                'function'=> false,
                "url"     => false,
                "conditional" => false,
                "conditional_field" => false,
                "conditional_value" => false,
                "page_size" => 21,
                "dark_mode" => false
            ), $atts
        );

        
        
        
        //only proceed if the required attributes are set
        if(!$args["form_id"] || !$args["name"] || !$args["stars"] || !$args["text"]) {
            return __("Please configure your shortcode and include the parameters form_id, name, stars and text.");
        }
    
        //start by getting entries
        $search_criteria = array(
            'status'        => 'active',
            'field_filters' => array(
                'mode' => 'any',                        
            )
        );        
        
        //add field filters if conditional is turned on
        if($args['conditional'] && !empty($args['conditional_field']) && !empty($args['conditional_value'])) {
            $field_filters = array('mode' => 'any', array('key' => $args['conditional_field'], 'value' => $args['conditional_value']));
            $search_criteria['field_filters'] = $field_filters;
        }
        
        $sorting = array();
        $paging = array( 'offset' => 0, 'page_size' => $args['page_size'] );
        $ratings = GFAPI::get_entries(
                            $args['form_id'],
                            $search_criteria,
                            $sorting,
                            $paging
                            );
        
        if(!is_array($ratings) || empty($ratings)) {
            return __("No reviews found.","gravityforms");
        }

        $dark_mode = "";
        if($args['dark_mode']) {
            $dark_mode = "dark-mode";
        }
    
        //start output
        ob_start();
        ?>
        <div class="gotr-rating-wrapper">  
            <div class="gotr-rating-carousel <?php echo $dark_mode; ?>">
    
            <?php 
            foreach($ratings as $key => $rating) {
                //get rating from survey field
                $surveyFieldId = $args["stars"];
                $survey_field = GFAPI::get_field($args['form_id'], $surveyFieldId);
                $survey_value = RGFormsModel::get_choice_text( $survey_field, rgar( $rating, $surveyFieldId ) );		
                
                $company_name = "";
                if($args["company"]) {
                    $company_name = rgar($rating, intval($args["company"]));
                }
                
                $company_function = "";
                if($args["function"]) {
                    $company_function = rgar($rating, intval($args["function"]));
                }	
                
                $company_link = "";
                if($args["url"]) {
                    $company_link = rgar($rating, intval($args["url"]));
                }
                
            ?>
            <div>
                <div class="rating-description">
                    <?php echo esc_html($rating[$args["text"]]); ?>
                </div>
                <hr> 
                <div class="rating-details"><?php echo $rating[$args["name"].".3"] . " " . $rating[$args["name"].".6"]; ?></div>
                <?php if($company_name && $company_function) {  ?>
                <div class="company-details">
                    <?php echo sprintf('%s at <a href="%s" target="_blank">%s</a>', $company_function, $company_link, $company_name); ?>
                </div>
                <?php } ?>			
                <div class="rating-stars"><?php echo $self::gotr_gravity_get_star_rating_html($survey_value) ?></div>
            </div> 
    
            <?php
            }
            ?>
    
            </div>
        </div>
        <?php

        //load all our scripts and styles with the shortcode
        wp_enqueue_script( 'slick-carousel' );
        wp_enqueue_script( 'gotr-slick-gravity-scripts' );

        wp_enqueue_style( 'slick-carousel-css' );
        wp_enqueue_style( 'slick-carousel-theme-css' );
        wp_enqueue_style( 'gotr-slick-gravity-styles' );

        $output = ob_get_contents();
        ob_get_clean();
        return $output;
    }



    /**
     * Method to output the correct amount of stars.
     * It returns the HTML ready for displaying
     * 
     * @param Int $star_count amount of stars to display
     * @return String HTML to output
     * @since v1.0.0
     */
    public static function gotr_gravity_get_star_rating_html($star_count) {
        $star_count = intval($star_count);
    
        // Star image URL
        $starImageUrl = GOTR_SLICK_GRAVITY_CAROUSEL_FILE_URL . '/assets/images/yellow-star.png';
    
        // Output the stars HTML
        $starsHtml = '';
        for ($i = 1; $i <= $star_count; $i++) {
            $starsHtml .= '<img src="' . $starImageUrl . '" alt="star">';
        }
        
        return $starsHtml;
    
    } 

    
}